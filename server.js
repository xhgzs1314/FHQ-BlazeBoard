"use strict";
const http = require("http");
const https = require("https");
const crypto = require("crypto");
const fs = require("fs");
const path = require("path");
const { URL } = require("url");
const { Server } = require("socket.io");
const Engine = require("./assets/core/engine.js");
const Rating = require("./assets/core/rating.js");
const Replay = require("./assets/core/replay.js");   

// ---------- .env加载 ----------
(function loadEnv() {
  try {
    const p = path.join(__dirname, ".env");
    if (!fs.existsSync(p)) return;
    for (const line of fs.readFileSync(p, "utf8").split(/\r?\n/)) {
      const s = line.trim();
      if (!s || s.startsWith("#")) continue;
      const eq = s.indexOf("=");
      if (eq < 0) continue;
      const k = s.slice(0, eq).trim();
      let v = s.slice(eq + 1).trim();
      const commentIdx = v.indexOf("#");
      if (commentIdx >= 0) {
        v = v.slice(0, commentIdx).trim();
      }

      if ((v.startsWith('"') && v.endsWith('"')) || (v.startsWith("'") && v.endsWith("'"))) v = v.slice(1, -1);
      if (!(k in process.env)) process.env[k] = v;
    }
  } catch (_) {}
})();

const CORS_ORIGIN = process.env.CORS_ORIGIN
  ? process.env.CORS_ORIGIN.split(",").map(s => s.trim())
  : "*";
const API_SECRET = process.env.API_SECRET_KEY_mok || "";
const PHP_API_BASE = (process.env.PHP_API_BASE || "http://127.0.0.1").replace(/\/$/, "");

// ---------- 身份票据验签 ----------
// 票据： base64url(payloadJson) + "." + base64url(hmacRaw)，payload={uid,exp}
function b64urlDecode(s) {
  s = s.replace(/-/g, "+").replace(/_/g, "/");
  while (s.length % 4) s += "=";
  return Buffer.from(s, "base64");
}
function verifyTicket(ticket) {
  if (!API_SECRET || typeof ticket !== "string" || ticket.indexOf(".") < 0) return null;
  const [p, sig] = ticket.split(".");
  if (!p || !sig) return null;
  const expected = crypto.createHmac("sha256", API_SECRET).update(p).digest();
  let given;
  try { given = b64urlDecode(sig); } catch (_) { return null; }
  if (expected.length !== given.length || !crypto.timingSafeEqual(expected, given)) return null;
  let payload;
  try { payload = JSON.parse(b64urlDecode(p).toString("utf8")); } catch (_) { return null; }
  if (!payload || !payload.uid || !payload.exp) return null;
  if (Date.now() / 1000 > payload.exp) return null;   // 过期
  return { uid: String(payload.uid) };
}

// ---------- Calling to PHP ----------
function callPhp(apiPath, body) {
  return new Promise((resolve) => {
    if (!API_SECRET) return resolve(null);
    let u;
    try { u = new URL(PHP_API_BASE + apiPath); } catch (_) { return resolve(null); }
    const data = Buffer.from(JSON.stringify(body || {}), "utf8");
    const lib = u.protocol === "https:" ? https : http;
    const req = lib.request(u, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Content-Length": data.length,
        "X-API-KEY": API_SECRET,
      },
      timeout: 5000,
    }, (res) => {
      let buf = "";
      res.on("data", d => { buf += d; });
      res.on("end", () => {
        try { resolve(JSON.parse(buf)); } catch (_) { resolve(null); }
      });
    });
    req.on("error", () => resolve(null));
    req.on("timeout", () => { req.destroy(); resolve(null); });
    req.write(data);
    req.end();
  });
}

// ---------- 结算上报 ----------
const reportQueue = [];
const REPORT_MAX_ATTEMPTS = +process.env.REPORT_MAX_ATTEMPTS || 6;
const REPORT_BASE_DELAY_MS = +process.env.REPORT_BASE_DELAY_MS || 1500;
const REPORT_QUEUE_CAP = +process.env.REPORT_QUEUE_CAP || 500;
let reportRunning = false;
const sleep = (ms) => new Promise(r => setTimeout(r, ms));
function enqueueReport(job) {
  if (reportQueue.length >= REPORT_QUEUE_CAP) {
    console.error(`[report] 队列超上限(${REPORT_QUEUE_CAP})，丢弃 match=${job.body.matchId}`);
    return;
  }
  reportQueue.push(job);
  runReportQueue();
}
async function runReportQueue() {
  if (reportRunning) return;
  reportRunning = true;
  try {
    while (reportQueue.length) {
      const now = Date.now();
      let idx = reportQueue.findIndex(j => j.nextAt <= now);
      if (idx < 0) {                                   // 未到重试时刻 → 等最近一个
        let soonest = Infinity;
        for (const j of reportQueue) if (j.nextAt < soonest) soonest = j.nextAt;
        await sleep(Math.max(50, Math.min(soonest - now, 30000)));
        continue;
      }
      const job = reportQueue.splice(idx, 1)[0];
      const resp = await callPhp("/api/ranked/report.php", job.body);
      if (resp && resp.code === 200) {
        if (job.emitRank && resp.data && resp.data.mode === 1) {
          io.to(job.roomId).emit("rankResult", { red: resp.data.red, blue: resp.data.blue });
        }
        continue;                                      // 成功
      }
      if (++job.attempts >= REPORT_MAX_ATTEMPTS) {
        console.error(`[report] 放弃 match=${job.body.matchId} mode=${job.body.mode}（重试 ${job.attempts} 次仍失败）`);
        continue;
      }
      job.nextAt = Date.now() + REPORT_BASE_DELAY_MS * Math.pow(2, job.attempts - 1);  // 指数退避
      reportQueue.push(job);
    }
  } finally {
    reportRunning = false;
  }
}

const server = http.createServer((req, res) => {
  if (req.url === "/healthz") { res.writeHead(200, { "Content-Type": "text/plain" }); res.end("ok"); return; }
  res.writeHead(404); res.end();
});
const io = new Server(server, {
  cors: { origin: CORS_ORIGIN, methods: ["GET", "POST"] },
  maxHttpBufferSize: 1e5,          // 单包上限 100KB
  pingTimeout: 20000,
});

/* ==================== 连接与频率管控 ==================== */
const IP_CONN = new Map();                 // ip -> { count, resetAt }  访客并发连接计数
const CONN_WINDOW_MS = 10000;              // 连接计数窗口
const CONN_MAX_GUEST = +process.env.CONN_MAX_GUEST || 12;   // 窗口内单 IP 访客新建连接上限
const CONN_MAX_AUTH = +process.env.CONN_MAX_AUTH || 40;     // 已登录放宽

function clientIp(socket) {
  const xff = socket.handshake.headers["x-forwarded-for"];
  if (xff) return String(xff).split(",")[0].trim();
  return socket.handshake.address || "unknown";
}

// 握手中间件
io.use((socket, next) => {
  const ticket = socket.handshake.auth && socket.handshake.auth.ticket;
  const verified = verifyTicket(ticket);
  socket.data.uid = verified ? verified.uid : null;          // 权威身份确定
  socket.data.isAuth = !!verified;

  const ip = clientIp(socket);
  const now = Date.now();
  let rec = IP_CONN.get(ip);
  if (!rec || now > rec.resetAt) { rec = { count: 0, resetAt: now + CONN_WINDOW_MS }; IP_CONN.set(ip, rec); }
  rec.count++;
  const cap = socket.data.isAuth ? CONN_MAX_AUTH : CONN_MAX_GUEST;
  if (rec.count > cap) return next(new Error("连接过于频繁，请稍后再试"));
  next();
});
// 清理过期 IP 记录
const ipSweep = setInterval(() => {
  const now = Date.now();
  for (const [ip, rec] of IP_CONN) if (now > rec.resetAt) IP_CONN.delete(ip);
}, CONN_WINDOW_MS);
if (ipSweep.unref) ipSweep.unref();

// 每连接令牌桶
const RL_AUTH = { capacity: +process.env.RL_AUTH_CAP || 60, refillPerSec: +process.env.RL_AUTH_RPS || 15 };
const RL_GUEST = { capacity: +process.env.RL_GUEST_CAP || 25, refillPerSec: +process.env.RL_GUEST_RPS || 6 };
// 不计入令牌桶
const RL_EXEMPT = new Set(["sync", "ready", "lobbySubscribe", "lobbyUnsubscribe", "leaveRoom", "cancelRanked", "playerJoin", "playerSubscribe", "playerUnsubscribe"]);

function rlTake(socket) {
  const cfg = socket.data.isAuth ? RL_AUTH : RL_GUEST;
  const now = Date.now();
  let b = socket.data._bucket;
  if (!b) { b = { tokens: cfg.capacity, last: now }; socket.data._bucket = b; }
  b.tokens = Math.min(cfg.capacity, b.tokens + (now - b.last) / 1000 * cfg.refillPerSec);
  b.last = now;
  if (b.tokens < 1) return false;
  b.tokens -= 1;
  return true;
}
const rooms = new Map();
const TURN_AFK_MS = +process.env.TURN_AFK_MS || 120000;    // 双方就位对局中，轮到方超时未落子 → 判定挂机
const ROOM_IDLE_MS = +process.env.ROOM_IDLE_MS || 600000;  // 无对局进行 / 仅一人时，长时间无活动 → 销毁房间
const SWEEP_MS = +process.env.SWEEP_MS || 30000;           // 扫描间隔

function makeRoomId() {
  let id;
  do { id = Math.random().toString(36).slice(2, 8).toUpperCase(); } while (rooms.has(id));
  return id;
}
// 每一局唯一的对局标识：房间号-时间戳-随机
function makeMatchId(roomId) {
  return (roomId + "-" + Date.now().toString(36) + Math.random().toString(36).slice(2, 6)).slice(0, 32);
}
// 开一局
function startNewMatch(room) {
  room.engine.reset();
  room._reported = false;
  room._rating = null;
  room.matchId = makeMatchId(room.id);
  if (room.replay) room.replay = [];   // 续局：清空上一局指令序列
}
/* ==================== 排位回放录制 ==================== */
const RP_OP_MOVE = 0, RP_OP_SURRENDER = 1, RP_OP_DRAW = 2;
const RP_MAX_THINK_MS = 30000;                      
// 取思考耗时
function rpThink(room) {
  const base = room && room.turnStartedAt ? room.turnStartedAt : Date.now();
  return Math.min(Math.max(0, Date.now() - base), RP_MAX_THINK_MS);
}
// 追加一条指令
function rpPush(room, entry) {
  if (!room || room.mode !== "ranked" || !room.replay) return;
  room.replay.push(entry);
}
function bothSeated(room) { return !!room.seats.red && !!room.seats.blue; }
function bothReady(room) { return bothSeated(room) && room.ready.red && room.ready.blue; }
// 评分
function ratingFor(room) {
  const st = room.engine.state;
  if (!st.over) { room._rating = null; return null; }
  if (!room._rating) {
    try { room._rating = Rating.computeMatch(room.engine.getRatingData()); }
    catch (e) { room._rating = null; }
  }
  return room._rating;
}
// viewer: "red"/"blue" 按视角投影
function publicState(room, viewer) {
  const enc = room.engine.netEncode(viewer);    // 紧凑二进制(base64) + 明文 log
  return {
    b: enc.b, log: enc.log,                      // 客户端用 engine.netApply({b,log}) 还原
    mode: room.mode,                             // "casual" | "ranked"，客户端据此切换 UI
    seats: { red: !!room.seats.red, blue: !!room.seats.blue },
    ready: { red: bothReady(room), blue: bothReady(room) },   // 双方均就位才允许落子
    rating: ratingFor(room),                     // 结束态附带评分；进行中为 null
  };
}
// 对局结束结算
function reportMatch(room) {
  if (room._reported) return;
  const st = room.engine.state;
  if (!st.over) return;
  markLobbyDirty();                 // 对局结束 → 列表状态回到"等待中"
  room._reported = true;
  let match;
  try { match = Rating.computeMatch(room.engine.getRatingData()); }
  catch (e) { match = null; }
  if (!match) return;
  const uids = room.uids || {};
  const payloadSide = (side) => ({
    uid: uids[side] || "",
    eloDelta: match[side].elo,
    rating: match[side].rating,
    grade: match[side].tier ? match[side].tier.grade : "",
    breakdown: match[side].breakdown,
  });
  const body = {
    matchId: room.matchId || makeMatchId(room.id),   // 每局唯一，非房间号
    mode: room.mode === "ranked" ? 1 : 0,
    winner: st.winner,           // "red" | "blue" | null
    round: st.round,
    red: payloadSide("red"),
    blue: payloadSide("blue"),
  };

  if (room.mode === "ranked") {
    if (!uids.red || !uids.blue) return;
    try {
      const bytes = Replay.encode(
        { mode: "ranked", winner: st.winner || "draw", red: uids.red, blue: uids.blue, savedAtSec: Math.floor(Date.now() / 1000) },
        room.replay || []
      );
      body.replay = Replay.bytesToB64(bytes);      
      const sb = room.scoreBefore || {};
      body.redScore = sb.red | 0;
      body.blueScore = sb.blue | 0;
    } catch (e) { }
    enqueueReport({ body, roomId: room.id, emitRank: true, attempts: 0, nextAt: Date.now() });
  } else {
    // 娱乐：双方均已登录才留档；否则不记录
    if (uids.red && uids.blue) enqueueReport({ body, roomId: room.id, emitRank: false, attempts: 0, nextAt: Date.now() });
  }
}
function viewerFor(room, socketId) {
  if (room.engine.state.over) return null;
  const side = sideOf(room, socketId);
  if (side !== "spectator") return side;
  return room.mode === "ranked" ? "none" : null;
}
function visibleEvents(room, events, viewer) {
  if (!events || !events.length || viewer === null) return events || [];
  if (viewer === "none") return [];
  const vis = room.engine.buildVisible(viewer);
  if (vis === null) return events;
  return events.filter(ev => typeof ev.r !== "number" || vis.has(ev.r * 30 + ev.c));
}
function broadcast(room, events) {
  const cache = new Map();                            // 同视角只编码一次
  for (const sid of [...room.sockets]) {
    const s = io.sockets.sockets.get(sid);
    if (!s) { room.sockets.delete(sid); continue; }
    const v = viewerFor(room, sid);
    const key = v === null ? "*" : v;
    let payload = cache.get(key);
    if (!payload) {
      payload = { ...publicState(room, v), events: visibleEvents(room, events, v) };
      cache.set(key, payload);
    }
    s.emit("state", payload);
  }
  if (room.engine.state.over) reportMatch(room);   // over 态单点结算入口
}
function sideOf(room, socketId) {
  if (room.seats.red === socketId) return "red";
  if (room.seats.blue === socketId) return "blue";
  return "spectator";
}
function seatedCount(room) { return (room.seats.red ? 1 : 0) + (room.seats.blue ? 1 : 0); }
// 彻底销毁房间：通知所有在场者回大厅
function destroyRoom(room, reason) {
  io.to(room.id).emit("roomClosed", { reason: reason || "房间已关闭" });
  for (const sid of room.sockets) {
    const s = io.sockets.sockets.get(sid);
    if (s) s.leave(room.id);
  }
  rooms.delete(room.id);
  markLobbyDirty();
}

/* ==================== 娱乐大厅（房间列表）==================== */
const lobbySubs = new Set();
const LOBBY_PUSH_MS = +process.env.LOBBY_PUSH_MS || 1000;   // 节流：最快每秒一推
let lobbyDirty = false;

function roomStatus(room) {
  return (bothReady(room) && !room.engine.state.over) ? "playing" : "waiting";
}
function lobbyList() {
  const out = [];
  for (const room of rooms.values()) {
    if (room.mode !== "casual") continue;
    // 邀请私房未坐满前不公开在大厅
    if (room.invite && seatedCount(room) < 2) continue;
    out.push({
      id: room.id,
      name: room.name || ("房间 " + room.id),
      host: room.host || "游客",
      seated: seatedCount(room),
      status: roomStatus(room),
      password: !!room.pwd,
      spectate: room.allowSpectate !== false,
    });
  }
  out.sort((a, b) => (a.status === b.status ? 0 : a.status === "waiting" ? -1 : 1));
  return out;
}
function pushLobby(target) {
  const payload = { rooms: lobbyList(), t: Date.now() };
  if (target) target.emit("lobbyRooms", payload);
  else for (const sid of lobbySubs) {
    const s = io.sockets.sockets.get(sid);
    if (s) s.emit("lobbyRooms", payload); else lobbySubs.delete(sid);
  }
}
function markLobbyDirty() { lobbyDirty = true; }
const lobbyTimer = setInterval(() => {
  if (!lobbyDirty || lobbySubs.size === 0) return;
  lobbyDirty = false;
  pushLobby();
}, LOBBY_PUSH_MS);
if (lobbyTimer.unref) lobbyTimer.unref();

const sanitizeName = (s, max, fallback) => {
  const t = String(s == null ? "" : s).replace(/[<>\u0000-\u001f]/g, "").trim().slice(0, max);
  return t || fallback;
};

/* ==================== 玩家表==================== */
const players = new Map();            // socketId -> { id, uid, name, status }
const playerSubs = new Set();         // 订阅玩家表推送的 socketId
const PLAYER_PUSH_MS = +process.env.LOBBY_PUSH_MS || 1000;
let playersDirty = false;
function playerList() {
  const out = [];
  for (const p of players.values()) out.push({ id: p.id, name: p.name, status: p.status });
  return out;
}
function pushPlayers(target) {
  const payload = { players: playerList(), t: Date.now() };
  if (target) target.emit("playerList", payload);
  else for (const sid of playerSubs) {
    const s = io.sockets.sockets.get(sid);
    if (s) s.emit("playerList", payload); else playerSubs.delete(sid);
  }
}
function markPlayersDirty() { playersDirty = true; }
const playerTimer = setInterval(() => {
  if (!playersDirty || playerSubs.size === 0) return;
  playersDirty = false;
  pushPlayers();
}, PLAYER_PUSH_MS);
if (playerTimer.unref) playerTimer.unref();
const INVITE_GRACE_MS = +process.env.INVITE_GRACE_MS || 60000;   // 邀请房空座宽限，双方导航入座期间不被清理

/* 挂机清理扫描 */
const RANKED_SEAT_GRACE_MS = 45000;   // 匹配成功后双方入座宽限，超时未坐满则解散
function sweepRooms() {
  const now = Date.now();
  for (const room of [...rooms.values()]) {
    // 排位房初始双座为空，等待 enterRanked 入座；宽限期内不按“无人”销毁
    if (room.mode === "ranked" && !bothSeated(room) && !room.engine.state.over) {
      if (now - room.createdAt > RANKED_SEAT_GRACE_MS) {
        io.to(room.id).emit("kicked", { reason: "对手未进入对局，请重新匹配" });
        destroyRoom(room, "排位对局未成局");
      }
      continue;   // 未坐满前不进入后续常规清理
    }
    // 邀请房：双方尚未入座时，宽限期内保留（等待双方从大厅导航进入）
    if (room.invite && seatedCount(room) === 0 && now < room.inviteGraceUntil) continue;
    if (seatedCount(room) === 0) { destroyRoom(room, "房间已无对局玩家"); continue; }

    const st = room.engine.state;
    if (bothSeated(room) && bothReady(room) && !st.over) {
      if (now - room.turnStartedAt > TURN_AFK_MS) {
        // 排位：挂机方直接判负结算，销毁房间
        if (room.mode === "ranked") {
          const afkSide = st.turn;
          const res = room.engine.surrender(afkSide);
          if (res.ok) rpPush(room, { op: RP_OP_SURRENDER, think: rpThink(room), side: afkSide });   // 录制：挂机判负
          if (res.ok) broadcast(room);                 // 触发结算上报
          io.to(room.id).emit("kicked", { reason: "长时间未操作，本局判负" });
          destroyRoom(room, "排位对局因挂机结束");
          continue;
        }
        const afkSide = st.turn;                       // 轮到谁、谁挂机
        const afkId = room.seats[afkSide];
        const keepId = room.seats[afkSide === "red" ? "blue" : "red"];
        const afkSock = io.sockets.sockets.get(afkId);
        if (afkSock) { afkSock.leave(room.id); afkSock.emit("kicked", { reason: "长时间未操作，已被移出房间" }); room.sockets.delete(afkId); }
        // 另一名对局玩家转正为红方房主，重开一局等待新对手
        room.seats = { red: keepId, blue: null };
        room.ready = { red: true, blue: false };
        room.engine.reset();
        room.lastActivityAt = now; room.turnStartedAt = now;
        const keepSock = io.sockets.sockets.get(keepId);
        if (keepSock) keepSock.emit("becameHost", { side: "red", reason: "对手挂机，你已成为房主，等待新对手" });
        broadcast(room);
      }
      continue;
    }

    // 未开局 / 已结束：仅凭活动时间清理，防误杀
    if (now - room.lastActivityAt > ROOM_IDLE_MS) destroyRoom(room, "房间长时间无活动，已关闭");
  }
}
const sweepTimer = setInterval(sweepRooms, SWEEP_MS);
if (sweepTimer.unref) sweepTimer.unref();

/* ==================== 排位匹配池 ==================== */
const rankQueue = new Map();   // socketId -> entry
const MATCH_MS = +process.env.MATCH_MS || 3000;   // 撮合扫描间隔
// 等待时长 → 允许的积分差窗口
function scoreWindow(waitedMs) {
  if (waitedMs >= 120000) return Infinity;   // ≥120s 完全放宽
  if (waitedMs >= 60000) return 400;         // ≥60s
  if (waitedMs >= 30000) return 300;         // ≥30s
  return 200;                                // 默认 ±200
}

function createRankedRoom(a, b) {
  const id = makeRoomId();
  const now = Date.now();
  // 座位随机分配红/蓝，保持公平
  const [red, blue] = Math.random() < 0.5 ? [a, b] : [b, a];
  const room = {
    id, pwd: "", engine: Engine.createGame(),
    mode: "ranked",
    uids: { red: red.uid, blue: blue.uid },
    scoreBefore: { red: red.score, blue: blue.score },
    reserved: { red: red.uid, blue: blue.uid },   // 座位仅限指定 uid 入座
    _reported: false, matchId: makeMatchId(id),
    replay: [],                                     // 排位指令序列（内存，仅本局）
    seats: { red: null, blue: null }, ready: { red: false, blue: false },
    sockets: new Set(), createdAt: now, lastActivityAt: now, turnStartedAt: now,
  };
  rooms.set(id, room);
  const notify = (entry, side, foe) => {
    const s = io.sockets.sockets.get(entry.socketId);
    if (s) s.emit("matched", { room: id, side, myScore: entry.score, foeScore: foe.score, myName: entry.name, foeName: foe.name });
  };
  notify(red, "red", blue);
  notify(blue, "blue", red);
  return room;
}

function sweepMatch() {
  if (rankQueue.size < 2) return;
  const now = Date.now();
  // 按积分排序，相邻优先撮合
  const list = [...rankQueue.values()].sort((x, y) => x.score - y.score);
  const paired = new Set();
  for (let i = 0; i < list.length; i++) {
    const a = list[i];
    if (paired.has(a.socketId)) continue;
    for (let j = i + 1; j < list.length; j++) {
      const b = list[j];
      if (paired.has(b.socketId)) continue;
      const diff = Math.abs(a.score - b.score);
      const win = Math.min(scoreWindow(now - a.enqueuedAt), scoreWindow(now - b.enqueuedAt));
      if (diff <= win) {
        paired.add(a.socketId); paired.add(b.socketId);
        rankQueue.delete(a.socketId); rankQueue.delete(b.socketId);
        createRankedRoom(a, b);
        break;
      }
      if (b.score - a.score > win) break;
    }
  }
}
const matchTimer = setInterval(sweepMatch, MATCH_MS);
if (matchTimer.unref) matchTimer.unref();

io.on("connection", (socket) => {
  let joined = null;   // 当前 socket 所在房间 id
  socket.use((packet, next) => {
    const evt = packet && packet[0];
    if (!evt || RL_EXEMPT.has(evt)) return next();
    if (rlTake(socket)) return next();
    const last = packet[packet.length - 1];
    if (typeof last === "function") { try { last({ ok: false, reason: "操作过于频繁，请稍候" }); } catch (_) { } }
  });

  function leaveCurrent() {
    if (!joined) return;
    const room = rooms.get(joined);
    if (room) {
      // 判定“离席前对局是否进行中”
      const wasPlaying = bothReady(room) && !room.engine.state.over;
      room.sockets.delete(socket.id);
      let seatFreed = null;
      if (room.seats.red === socket.id) { room.seats.red = null; room.ready.red = false; seatFreed = "red"; }
      if (room.seats.blue === socket.id) { room.seats.blue = null; room.ready.blue = false; seatFreed = "blue"; }

      // 排位局：座位玩家中途离席即判负
      if (seatFreed && room.mode === "ranked") {
        if (wasPlaying) {
          const res = room.engine.surrender(seatFreed);   // 离席方投降 → 对方胜
          if (res.ok) rpPush(room, { op: RP_OP_SURRENDER, think: rpThink(room), side: seatFreed });   // 录制：离席判负
          if (res.ok) { broadcast(room); }                // broadcast 内触发结算
        }
        socket.to(room.id).emit("opponentLeft", { side: seatFreed, ranked: true });
        destroyRoom(room, "对手已离开，排位结束");
        socket.leave(joined);
        joined = null;
        return;
      }

      if (seatFreed) socket.to(room.id).emit("opponentLeft", { side: seatFreed });
      // 观战者不计入存续；一旦无对局玩家占座即销毁
      if (seatedCount(room) === 0) destroyRoom(room, "房间已无对局玩家");
      else { broadcast(room); if (seatFreed) markLobbyDirty(); }
    }
    socket.leave(joined);
    joined = null;
  }

  // 进入排位匹配池
  socket.on("queueRanked", (data, cb) => {
    if (!socket.data.uid) return cb && cb({ ok: false, reason: "未登录，无法进行排位" });
    const reqName = (data && typeof data.name === "string") ? data.name.slice(0, 30) : "";
    if (joined) return cb && cb({ ok: false, reason: "请先离开当前对局" });
    if (rankQueue.has(socket.id)) return cb && cb({ ok: true, queued: true });
    callPhp("/api/ranked/profile.php", { uid: socket.data.uid }).then((resp) => {
      if (!resp || resp.code !== 200 || !resp.data) return cb && cb({ ok: false, reason: "无法获取排位资料" });
      if (!socket.connected) return;                 // 期间已断开
      rankQueue.set(socket.id, {
        socketId: socket.id, uid: socket.data.uid,
        score: resp.data.score, enqueuedAt: Date.now(),
        name: reqName,
      });
      cb && cb({ ok: true, queued: true, profile: resp.data, poolSize: rankQueue.size });
    });
  });

  socket.on("cancelRanked", (_, cb) => {
    rankQueue.delete(socket.id);
    cb && cb({ ok: true });
  });

  // 匹配成功后客户端回执入座
  socket.on("enterRanked", ({ room: rid } = {}, cb) => {
    const room = rooms.get((rid || "").toUpperCase());
    if (!room || room.mode !== "ranked") return cb && cb({ ok: false, reason: "对局不存在" });
    let side = null;
    if (room.reserved.red === socket.data.uid && !room.seats.red) side = "red";
    else if (room.reserved.blue === socket.data.uid && !room.seats.blue) side = "blue";
    if (!side) return cb && cb({ ok: false, reason: "无此对局席位" });
    leaveCurrent();
    joined = room.id; socket.join(room.id); room.sockets.add(socket.id);
    room.seats[side] = socket.id;
    room.lastActivityAt = Date.now();
    cb && cb({ ok: true, room: room.id, side });
    broadcast(room);
  });

  socket.on("createRoom", ({ pwd, name, spectate, host } = {}, cb) => {
    try {
      const id = makeRoomId();
      const now = Date.now();
      const room = {
        id,
        pwd: String(pwd || "").slice(0, 32),
        name: sanitizeName(name, 24, "房间 " + id),
        host: sanitizeName(host, 16, "游客"),
        allowSpectate: spectate !== false,          // 默认允许观战
        engine: Engine.createGame(),
        mode: "casual", uids: { red: socket.data.uid || null, blue: null },
        _reported: false, matchId: makeMatchId(id),
        seats: { red: socket.id, blue: null }, ready: { red: false, blue: false },
        sockets: new Set([socket.id]), createdAt: now, lastActivityAt: now, turnStartedAt: now,
      };
      rooms.set(id, room);
      joined = id; socket.join(id);
      cb && cb({ ok: true, room: id, side: "red" });
      broadcast(room);
      markLobbyDirty();
    } catch (e) { cb && cb({ ok: false, reason: "创建失败" }); }
  });

  socket.on("joinRoom", ({ room: rid, pwd } = {}, cb) => {
    const room = rooms.get((rid || "").toUpperCase());
    if (!room) return cb && cb({ ok: false, reason: "房间不存在" });
    if (room.mode !== "casual") return cb && cb({ ok: false, reason: "该房间不可加入" });
    if (room.pwd && room.pwd !== (pwd || "")) return cb && cb({ ok: false, reason: "密码错误" });
    const full = bothSeated(room);
    // 邀请私房：仅受邀双方 uid 可入座，不接纳观战/第三者
    if (room.invite && room.reserved) {
      const uid = socket.data.uid;
      const canSit = uid && (room.reserved.red === uid || room.reserved.blue === uid);
      if (!canSit) return cb && cb({ ok: false, reason: "该房间为邀请对局，无法加入" });
    }
    // 满座且房主禁止观战 → 拒绝进入
    if (full && room.allowSpectate === false) return cb && cb({ ok: false, reason: "房间已满且不允许观战" });
    leaveCurrent();
    joined = room.id; socket.join(room.id); room.sockets.add(socket.id);
    // 分配座位：邀请房按预留 uid 就座；普通房优先补空位，满则观战
    let side = "spectator";
    if (room.invite && room.reserved) {
      const uid = socket.data.uid;
      if (room.reserved.red === uid && !room.seats.red) { room.seats.red = socket.id; side = "red"; }
      else if (room.reserved.blue === uid && !room.seats.blue) { room.seats.blue = socket.id; side = "blue"; }
    } else {
      if (!room.seats.red) { room.seats.red = socket.id; side = "red"; }
      else if (!room.seats.blue) { room.seats.blue = socket.id; side = "blue"; }
    }
    if (side !== "spectator") {
      room.lastActivityAt = Date.now();
      if (room.uids) room.uids[side] = socket.data.uid || null;   // 记录该座位登录者
    }
    cb && cb({ ok: true, room: room.id, side });
    broadcast(room);
    markLobbyDirty();
  });

  // 大厅订阅
  socket.on("lobbySubscribe", (_, cb) => {
    lobbySubs.add(socket.id);
    pushLobby(socket);
    cb && cb({ ok: true });
  });
  socket.on("lobbyUnsubscribe", () => { lobbySubs.delete(socket.id); });

  /* ---------- 玩家表：登记 / 订阅 / 状态 / 邀请 ---------- */
  // 登记进入在线玩家表
  socket.on("playerJoin", ({ name } = {}, cb) => {
    if (!socket.data.uid) return cb && cb({ ok: false, reason: "未登录" });
    const nm = sanitizeName(name, 24, "玩家");
    const exist = players.get(socket.id);
    if (exist) { exist.name = nm; }
    else players.set(socket.id, { id: socket.id, uid: socket.data.uid, name: nm, status: "idle" });
    markPlayersDirty();
    cb && cb({ ok: true, id: socket.id });
  });

  socket.on("playerSubscribe", (_, cb) => {
    playerSubs.add(socket.id);
    pushPlayers(socket);
    cb && cb({ ok: true });
  });
  socket.on("playerUnsubscribe", () => { playerSubs.delete(socket.id); });

  // 玩家自选状态
  socket.on("setStatus", ({ status } = {}, cb) => {
    const p = players.get(socket.id);
    if (!p) return cb && cb({ ok: false, reason: "未在玩家表中" });
    const st = status === "busy" ? "busy" : "idle";
    if (p.status !== st) { p.status = st; markPlayersDirty(); }
    cb && cb({ ok: true, status: st });
  });

  // 邀请对战：为双方创建预留私房
  socket.on("invite", ({ target } = {}, cb) => {
    if (!socket.data.uid) return cb && cb({ ok: false, reason: "未登录，无法邀请" });
    const me = players.get(socket.id);
    if (!me) return cb && cb({ ok: false, reason: "请先进入玩家列表" });
    if (!target || target === socket.id) return cb && cb({ ok: false, reason: "无效的邀请对象" });
    const foe = players.get(target);
    if (!foe) return cb && cb({ ok: false, reason: "该玩家已离线" });
    if (foe.status === "busy") return cb && cb({ ok: false, reason: "该玩家忙碌中，暂不可邀请" });
    if (foe.uid === socket.data.uid) return cb && cb({ ok: false, reason: "不能邀请自己" });
    const foeSock = io.sockets.sockets.get(target);
    if (!foeSock || !foeSock.connected) return cb && cb({ ok: false, reason: "该玩家已离线" });

    const id = makeRoomId();
    const now = Date.now();
    const token = crypto.randomBytes(16).toString("hex");   // 私房口令，仅双方持有
    const room = {
      id, pwd: token, name: me.name + " 的邀请对局", host: me.name,
      allowSpectate: false,
      engine: Engine.createGame(),
      mode: "casual", invite: true,
      uids: { red: socket.data.uid, blue: foe.uid },
      reserved: { red: socket.data.uid, blue: foe.uid },   // 座位仅限受邀双方
      inviteGraceUntil: now + INVITE_GRACE_MS,
      _reported: false, matchId: makeMatchId(id),
      seats: { red: null, blue: null }, ready: { red: false, blue: false },
      sockets: new Set(), createdAt: now, lastActivityAt: now, turnStartedAt: now,
    };
    rooms.set(id, room);
    // 通知受邀方：待其确认后导航进入
    foeSock.emit("invited", { room: id, token, from: me.name });
    cb && cb({ ok: true, room: id, token });
  });

  // 客户端加载完毕后上报
  socket.on("ready", () => {
    const room = rooms.get(joined);
    if (!room) return;
    const side = sideOf(room, socket.id);
    if (side === "spectator") return;
    if (room.ready[side]) return;
    room.ready[side] = true;
    if (bothReady(room)) { room.turnStartedAt = Date.now(); markLobbyDirty(); }   // 就位 → 开局，列表转对局中
    broadcast(room);
  });

  socket.on("move", ({ id, r, c } = {}, cb) => {
    const room = rooms.get(joined);
    if (!room) return cb && cb({ ok: false, reason: "未在房间中" });
    const side = sideOf(room, socket.id);
    if (side === "spectator") return cb && cb({ ok: false, reason: "观战者不能落子" });
    if (!bothReady(room)) return cb && cb({ ok: false, reason: "对手尚未就位" });
    if (side !== room.engine.state.turn) return cb && cb({ ok: false, reason: "非本方回合" });
    const p = room.engine.pieces[id];
    if (!p || p.side !== side) return cb && cb({ ok: false, reason: "只能移动己方棋子" });
    const res = room.engine.move(id, r, c);
    if (!res.ok) return cb && cb({ ok: false, reason: res.reason });
    rpPush(room, { op: RP_OP_MOVE, think: rpThink(room), id, r, c });   // 录制：落子（思考耗时取自本回合）
    const now = Date.now();
    room.lastActivityAt = now; room.turnStartedAt = now;   // 落子成功
    cb && cb({ ok: true });
    broadcast(room, res.events);
  });

  // 认输：立即结束对局
  socket.on("surrender", (_, cb) => {
    const room = rooms.get(joined);
    if (!room) return cb && cb({ ok: false, reason: "未在房间中" });
    const side = sideOf(room, socket.id);
    if (side === "spectator") return cb && cb({ ok: false, reason: "观战者无此操作" });
    const res = room.engine.surrender(side);
    if (!res.ok) return cb && cb({ ok: false, reason: res.reason });
    rpPush(room, { op: RP_OP_SURRENDER, think: rpThink(room), side });   // 录制：主动认输
    room.lastActivityAt = Date.now();
    cb && cb({ ok: true });
    broadcast(room);
  });

  // 求和：转发给对手
  socket.on("drawOffer", (_, cb) => {
    const room = rooms.get(joined);
    if (!room) return cb && cb({ ok: false, reason: "未在房间中" });
    const side = sideOf(room, socket.id);
    if (side === "spectator") return cb && cb({ ok: false, reason: "观战者无此操作" });
    if (room.engine.state.over) return cb && cb({ ok: false, reason: "游戏已结束" });
    if (!bothSeated(room)) return cb && cb({ ok: false, reason: "对手尚未就位" });
    const foeId = room.seats[side === "red" ? "blue" : "red"];
    const foe = io.sockets.sockets.get(foeId);
    if (foe) foe.emit("drawOffered", { from: side });
    cb && cb({ ok: true });
  });
  socket.on("drawRespond", ({ accept } = {}, cb) => {
    const room = rooms.get(joined);
    if (!room) return cb && cb({ ok: false, reason: "未在房间中" });
    const side = sideOf(room, socket.id);
    if (side === "spectator") return;
    const foeId = room.seats[side === "red" ? "blue" : "red"];
    const foe = io.sockets.sockets.get(foeId);
    if (accept) {
      const res = room.engine.drawGame();
      if (res.ok) { rpPush(room, { op: RP_OP_DRAW, think: rpThink(room) }); room.lastActivityAt = Date.now(); broadcast(room); }   // 录制：和棋
    } else if (foe) foe.emit("drawDeclined");
    cb && cb({ ok: true });
  });

  // 聊天：只在同房间广播
  socket.on("chat", ({ text } = {}, cb) => {
    const room = rooms.get(joined);
    if (!room) return cb && cb({ ok: false, reason: "未在房间中" });
    const msg = String(text || "").slice(0, 200).trim();
    if (!msg) return cb && cb({ ok: false, reason: "空消息" });
    const side = sideOf(room, socket.id);
    room.lastActivityAt = Date.now();
    io.to(room.id).emit("chat", { side, text: msg, t: Date.now() });
    cb && cb({ ok: true });
  });

  // 对局结束后「继续游戏」
  socket.on("restart", (_, cb) => {
    const room = rooms.get(joined);
    if (!room) return cb && cb({ ok: false, reason: "未在房间中" });
    if (room.mode === "ranked") return cb && cb({ ok: false, reason: "排位不支持再来一局，请返回重新匹配" });
    startNewMatch(room);                 // 重开
    const now = Date.now();
    room.lastActivityAt = now; room.turnStartedAt = now;
    cb && cb({ ok: true });
    broadcast(room);
    markLobbyDirty();
  });

  // 断联恢复
  socket.on("sync", (_, cb) => {
    const room = rooms.get(joined);
    if (!room) return cb && cb({ ok: false, reason: "未在房间中" });
    cb && cb({ ok: true, ...publicState(room, viewerFor(room, socket.id)), side: sideOf(room, socket.id) });
  });

  socket.on("leaveRoom", () => { rankQueue.delete(socket.id); lobbySubs.delete(socket.id); leaveCurrent(); });
  socket.on("disconnect", () => {
    rankQueue.delete(socket.id);
    lobbySubs.delete(socket.id);
    playerSubs.delete(socket.id);
    if (players.delete(socket.id)) markPlayersDirty();   // 移出玩家表
    leaveCurrent();
  });
});

const PORT = +process.env.port || 8080;
server.listen(PORT, () => {
  console.log(`烽火棋 WebSocket 服务端已启动: 端口 ${PORT}`);
});