(function (root) {
  "use strict";
  const MAX_THINK_MS = 30000;             // 单步思考耗时封顶
  const FMT = "fhq-replay";               // 旧版 JSON 标识
  const MAGIC = [70, 72, 81, 82];         // "FHQR"
  const BIN_VER = 2;
  const MODE_CODE = { local: 0, casual: 1, ranked: 2 };
  const MODE_NAME = ["local", "casual", "ranked"];

  /* ---------- base64 / utf8 编解码---------- */
  function bytesToB64(bytes) {
    if (typeof Buffer !== "undefined") return Buffer.from(bytes).toString("base64");
    let bin = ""; for (let i = 0; i < bytes.length; i++) bin += String.fromCharCode(bytes[i]);
    return btoa(bin);
  }
  function b64ToBytes(str) {
    if (typeof Buffer !== "undefined") return new Uint8Array(Buffer.from(str, "base64"));
    const bin = atob(str), out = new Uint8Array(bin.length);
    for (let i = 0; i < bin.length; i++) out[i] = bin.charCodeAt(i);
    return out;
  }
  function strToUtf8(s) {
    if (typeof TextEncoder !== "undefined") return new TextEncoder().encode(s);
    return new Uint8Array(Buffer.from(String(s), "utf8"));
  }
  function utf8ToStr(bytes) {
    if (typeof TextDecoder !== "undefined") return new TextDecoder().decode(bytes);
    return Buffer.from(bytes).toString("utf8");
  }
  function ts() {
    const d = new Date(), p = n => String(n).padStart(2, "0");
    return d.getFullYear() + p(d.getMonth() + 1) + p(d.getDate()) + "-" +
      p(d.getHours()) + p(d.getMinutes()) + p(d.getSeconds());
  }

  /* ==================== 自定义二进制编解码 ====================
   * 头部：MAGIC(4) ver(1) mode(1) winner(1) savedAtSec(u32 LE,4)
   *       redLen(1) red(utf8) blueLen(1) blue(utf8) entryCount(u16 LE,2)
   * 指令(逐条)：op(1) think(u16 LE,2)
   *   op=0 走子：+ move(u16 LE,2)= id(6bit) | r(5)<<6 | c(5)<<11
   *   op=1 认输：+ side(1) 0=red 1=blue
   *   op=2 和棋：无附加
   */
  const OP_MOVE = 0, OP_SURRENDER = 1, OP_DRAW = 2;
  const WIN_CODE = { red: 0, blue: 1, draw: 2 };
  const WIN_NAME = ["red", "blue", "draw"];

  function encodeReplay(meta, entries) {
    const out = [];
    const pushU16 = v => { out.push(v & 255, (v >> 8) & 255); };
    const pushU32 = v => { out.push(v & 255, (v >> 8) & 255, (v >> 16) & 255, (v >> 24) & 255); };
    const pushName = s => {
      let b = strToUtf8(s == null ? "" : String(s));
      if (b.length > 255) b = b.subarray(0, 255);
      out.push(b.length); for (let i = 0; i < b.length; i++) out.push(b[i]);
    };
    for (const m of MAGIC) out.push(m);
    out.push(BIN_VER);
    out.push(MODE_CODE[meta.mode] != null ? MODE_CODE[meta.mode] : 0);
    out.push(meta.winner in WIN_CODE ? WIN_CODE[meta.winner] : 2);
    pushU32(meta.savedAtSec >>> 0);
    pushName(meta.red); pushName(meta.blue);
    pushU16(entries.length);
    for (const e of entries) {
      out.push(e.op);
      pushU16(Math.min(Math.max(0, e.think | 0), 65535));
      if (e.op === OP_MOVE) pushU16((e.id & 63) | ((e.r & 31) << 6) | ((e.c & 31) << 11));
      else if (e.op === OP_SURRENDER) out.push(e.side === "blue" ? 1 : 0);
    }
    return new Uint8Array(out);
  }

  function isBinary(bytes) {
    return bytes && bytes.length >= 5 && bytes[0] === MAGIC[0] && bytes[1] === MAGIC[1] &&
      bytes[2] === MAGIC[2] && bytes[3] === MAGIC[3];
  }

  // 仅解析头部与指令序列
  function decodeReplay(bytes) {
    if (!isBinary(bytes)) throw new Error("非烽火棋二进制回放");
    let o = 4;
    const ver = bytes[o++];
    if (ver !== BIN_VER) throw new Error("回放版本不兼容(v" + ver + ")");
    const mode = MODE_NAME[bytes[o++]] || "local";
    const winner = WIN_NAME[bytes[o++]] || null;
    const savedAtSec = bytes[o] | (bytes[o + 1] << 8) | (bytes[o + 2] << 16) | (bytes[o + 3] << 24); o += 4;
    const readName = () => { const n = bytes[o++]; const s = utf8ToStr(bytes.subarray(o, o + n)); o += n; return s; };
    const red = readName(), blue = readName();
    const cnt = bytes[o] | (bytes[o + 1] << 8); o += 2;
    const entries = [];
    for (let i = 0; i < cnt; i++) {
      const op = bytes[o++];
      const think = bytes[o] | (bytes[o + 1] << 8); o += 2;
      if (op === OP_MOVE) {
        const v = bytes[o] | (bytes[o + 1] << 8); o += 2;
        entries.push({ op, think, id: v & 63, r: (v >> 6) & 31, c: (v >> 11) & 31 });
      } else if (op === OP_SURRENDER) {
        entries.push({ op, think, side: bytes[o++] === 1 ? "blue" : "red" });
      } else {
        entries.push({ op, think });
      }
    }
    return { fmt: FMT, ver, mode, winner: winner === "draw" ? "draw" : winner, red, blue, savedAtSec, entries };
  }

  /* ==================== 录制器 ==================== */
  function createRecorder(cfg) {
    cfg = cfg || {};
    const mode = cfg.mode || "local";
    const getNames = typeof cfg.getNames === "function" ? cfg.getNames : null;
    const onSave = typeof cfg.onSave === "function" ? cfg.onSave : null;   // onSave(bytes, meta)
    let entries = [], recording = false, lastB = null, lastTime = 0, prevPieces = null, winner = null;

    function snap(engine) { return engine.pieces.map(p => ({ alive: p.alive, r: p.r, c: p.c, hidden: !!p.hidden })); }

    // 帧差推导指令
    function deriveEntry(engine, think) {
      if (prevPieces) {
        const cur = engine.pieces;
        for (let i = 0; i < cur.length && i < prevPieces.length; i++) {
          const a = prevPieces[i], b = cur[i];
          if (a && a.hidden) continue;
          if (b.hidden) continue;
          if (a && a.alive && (b.r !== a.r || b.c !== a.c)) return { op: OP_MOVE, think, id: i, r: b.r, c: b.c };
        }
      }
      const st = engine.state;
      if (st.over) {
        if (!st.winner) return { op: OP_DRAW, think };
        // 无子位移却已分胜负 → 认输：输方 = 胜方之对方
        return { op: OP_SURRENDER, think, side: st.winner === "red" ? "blue" : "red" };
      }
      return null;
    }

    function isFreshStart(engine) {
      const st = engine.state;
      return st.round === 1 && !st.over && st.log.length === 1 && st.log[0] === "游戏开始";
    }

    function observe(engine) {
      let b;
      try { b = engine.netEncode().b; } catch (_) { return; }   // 出错静默跳过，不打断对局
      if (isFreshStart(engine)) {                                // 新开局 → 重置采集
        entries = []; recording = true; winner = null;
        lastB = b; lastTime = Date.now(); prevPieces = snap(engine);
        return;
      }
      if (!recording) return;
      if (b === lastB) return;                                   // 棋盘未变（仅选中/重渲染）
      const now = Date.now();
      const think = Math.min(Math.max(0, now - lastTime), MAX_THINK_MS);
      const e = deriveEntry(engine, think);
      if (e) entries.push(e);                                    
      lastB = b; lastTime = now; prevPieces = snap(engine);
      if (engine.state.over) { winner = engine.state.winner || "draw"; recording = false; finish(); }
    }

    function buildMeta() {
      const names = getNames ? (getNames() || {}) : {};
      return {
        mode, winner: winner || "draw",
        red: names.red || "红方", blue: names.blue || "蓝方",
        savedAtSec: Math.floor(Date.now() / 1000),
      };
    }

    function finish() {
      const moves = entries.filter(e => e.op === OP_MOVE).length;
      const snapEntries = entries.slice();
      entries = [];                                              // 释放采集
      if (moves < 1) return;                                     // 无实际走子不值得存
      const meta = buildMeta();
      const bytes = encodeReplay(meta, snapEntries);             // 二进制
      if (onSave) { try { onSave(bytes, meta); } catch (_) { } } // 云端上报
      prompt(bytes, meta);                                       // 本地下载提示
    }

    function download(bytes, meta) {
      try {
        const blob = new Blob([bytesToB64(bytes)], { type: "text/plain" });
        const url = URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url; a.download = `烽火棋回放-${meta.mode}-${ts()}.fhq`; 
        document.body.appendChild(a); a.click(); a.remove();
        setTimeout(() => URL.revokeObjectURL(url), 4000);
      } catch (_) { }
    }

    function prompt(bytes, meta) {
      if (typeof document === "undefined") return;
      const old = document.getElementById("replaySavebar");
      if (old) old.remove();
      const bar = document.createElement("div");
      bar.id = "replaySavebar";
      bar.setAttribute("style",
        "position:fixed;bottom:20px;left:50%;transform:translateX(-50%);z-index:120;" +
        "display:flex;align-items:center;gap:12px;padding:11px 18px;border-radius:12px;" +
        "background:linear-gradient(155deg,rgba(20,40,64,.96),rgba(10,22,38,.98));" +
        "border:1px solid rgba(140,190,255,.5);box-shadow:0 8px 30px rgba(0,0,0,.5);" +
        "color:#dbe9ff;font-family:inherit;font-size:13px;letter-spacing:.04em;");
      const kb = (bytes.length / 1024).toFixed(2);
      bar.innerHTML =
        '<span>本局已结束，是否保存回放？<b style="color:#8effc0">(' + bytes.length + ' 字节)</b></span>' +
        '<button id="rsSave" style="padding:7px 16px;border:none;border-radius:8px;font-weight:700;cursor:pointer;color:#071018;font-family:inherit;background:linear-gradient(135deg,#8effc0,#35c98b);">保存回放</button>' +
        '<button id="rsSkip" style="padding:7px 14px;border:1px solid rgba(140,190,255,.4);border-radius:8px;cursor:pointer;color:#9cc4ff;font-family:inherit;background:rgba(8,18,30,.7);">不保存</button>';
      document.body.appendChild(bar);
      const done = () => bar.remove();                            
      bar.querySelector("#rsSave").onclick = () => { download(bytes, meta); done(); };
      bar.querySelector("#rsSkip").onclick = done;
      setTimeout(() => { if (document.getElementById("replaySavebar") === bar) done(); }, 60000);
    }

    return {
      observe,
      reset() { entries = []; recording = false; lastB = null; prevPieces = null; winner = null; },
    };
  }

  /* ==================== 播放器 ==================== */
  function rebuildFrames(Engine, decoded) {
    const eng = Engine.createGame();       
    const frames = [{ b: eng.netEncode().b, log: eng.state.log.slice(), t: 0, cmd: null }];
    for (const e of decoded.entries) {
      if (e.op === OP_MOVE) eng.move(e.id, e.r, e.c);
      else if (e.op === OP_SURRENDER) eng.surrender(e.side);
      else eng.drawGame();
      const cmd = e.op === OP_MOVE ? { k: "move", id: e.id, r: e.r, c: e.c }
        : e.op === OP_SURRENDER ? { k: "end", winner: e.side === "red" ? "blue" : "red" }
          : { k: "draw" };
      frames.push({ b: eng.netEncode().b, log: eng.state.log.slice(), t: e.think || 0, cmd });
    }
    return {
      fmt: FMT, mode: decoded.mode, red: decoded.red, blue: decoded.blue,
      winner: decoded.winner,
      savedAt: new Date((decoded.savedAtSec || 0) * 1000).toISOString(),
      moves: decoded.entries.filter(e => e.op === OP_MOVE).length,
      frames,
    };
  }

  // 归一化任意载入源
  function normalize(Engine, input) {
    // 原始字节
    if (input instanceof Uint8Array) return rebuildFrames(Engine, decodeReplay(input));
    if (typeof ArrayBuffer !== "undefined" && input instanceof ArrayBuffer)
      return rebuildFrames(Engine, decodeReplay(new Uint8Array(input)));
    if (typeof input === "string") {
      const s = input.trim();
      if (s.charAt(0) === "{") {               // 2) 旧版 JSON
        const d = JSON.parse(s);
        if (d && d.fmt === FMT && Array.isArray(d.frames)) return d;
        throw new Error("回放文件格式不正确");
      }
      return rebuildFrames(Engine, decodeReplay(b64ToBytes(s)));   // 3) base64
    }
    if (input && Array.isArray(input.frames)) return input;        // 4) 数据对象
    throw new Error("无法识别的回放数据");
  }

  // deps: { Engine, UI }  cfg: { onFrame(idx,total,data,f), onEnd() }
  function createPlayer(deps, cfg) {
    deps = deps || {}; cfg = cfg || {};
    const Engine = deps.Engine || root.Engine;
    const UI = deps.UI || root.UI;
    const engine = Engine.createGame();
    let data = null, idx = 0, playing = false, speed = 1, timer = null;
    let perspective = "red";                    // "red" | "blue" | "follow" | 'null'

    const ui = UI.createUI({
      engine,
      getViewer: () => perspective === "follow" ? engine.state.turn : perspective,
      canInteract: () => false,
      onMove: () => null,
      onRestart: () => { },
    });

    function load(input) {
      const d = normalize(Engine, input);
      if (!d || !Array.isArray(d.frames) || !d.frames.length) throw new Error("回放为空");
      data = d; idx = 0; pause();
      applyFrame(0);
      return d;
    }
    function applyFrame(i) {
      if (!data) return;
      i = Math.max(0, Math.min(i, data.frames.length - 1));
      idx = i;
      const f = data.frames[i];
      engine.netApply({ b: f.b, log: f.log || [] });
      ui.clearSelection();
      ui.render();                              // ui 内部按棋子位移差量
      if (cfg.onFrame) cfg.onFrame(idx, data.frames.length, data, f);
    }
    function next() {
      if (!data || idx >= data.frames.length - 1) return false;
      applyFrame(idx + 1); return true;
    }
    function scheduleNext() {
      clearTimeout(timer);
      if (!playing || !data) return;
      if (idx >= data.frames.length - 1) { playing = false; if (cfg.onEnd) cfg.onEnd(); return; }
      const nf = data.frames[idx + 1];
      const wait = Math.max(120, Math.min(nf.t || 0, MAX_THINK_MS)) / (speed || 1);
      timer = setTimeout(() => { if (playing) { next(); scheduleNext(); } }, wait);
    }
    function play() { if (!data || playing) return; if (idx >= data.frames.length - 1) applyFrame(0); playing = true; scheduleNext(); }
    function pause() { playing = false; clearTimeout(timer); }
    function toggle() { playing ? pause() : play(); }
    function seek(i) { pause(); applyFrame(i); }
    function step(d) { pause(); applyFrame(idx + (d || 1)); }
    function setSpeed(s) { speed = +s || 1; if (playing) scheduleNext(); }
    function setPerspective(p) { perspective = p; ui.render(); }

    return {
      load, play, pause, toggle, seek, step, next, setSpeed, setPerspective,
      get index() { return idx; }, get total() { return data ? data.frames.length : 0; },
      get playing() { return playing; }, get meta() { return data; },
    };
  }


  const api = {
    createRecorder, attachRecorder, createPlayer,
    encode: encodeReplay, decode: decodeReplay, bytesToB64, b64ToBytes,
    rec: null,
  };
  function attachRecorder(cfg) { api.rec = createRecorder(cfg); return api.rec; }

  if (typeof module !== "undefined" && module.exports) module.exports = api;
  else root.Replay = api;
})(typeof window !== "undefined" ? window : globalThis);
