/* 关卡生成与校验
 *   node tools/build-levels.js          校验并打印报告
 *   node tools/build-levels.js --write  校验通过后写
 *
 */
"use strict";
const path = require("path");
const fs = require("fs");
const CORE = path.join(__dirname, "..", "assets", "core");
const Engine = require(path.join(CORE, "engine.js"));
const FEN = require(path.join(CORE, "fen.js"));
const Goal = require(path.join(CORE, "goal.js"));
const AI = require(path.join(CORE, "ai.js"));
function board(list) {
  const grid = new Array(900).fill(null);
  for (const [c, r, ch] of list) {
    if (c < 0 || c > 29 || r < 0 || r > 29) throw new Error("越界 (" + c + "," + r + ")");
    if (grid[r * 30 + c]) throw new Error("同格重复 (" + c + "," + r + ")");
    grid[r * 30 + c] = ch;
  }
  const out = [];
  for (let r = 0; r < 30; r++) {
    let s = "", gap = 0;
    for (let c = 0; c < 30; c++) {
      const g = grid[r * 30 + c];
      if (g) { if (gap) { s += gap; gap = 0; } s += g; } else gap++;
    }
    if (gap) s += gap;
    out.push(s);
  }
  return out.join("/");
}
const fen = (list, tail) => "FHQ1 " + board(list) + " " + tail;
const row = (c0, r, letters) => letters.split("").map((ch, i) => [c0 + i, r, ch]);
/* ---------- 穷举证明---------- */
function movesOf(e) {
  const out = [];
  for (const p of e.pieces) {
    if (!p.alive || p.side !== e.state.turn || !e.canAct(p)) continue;
    for (const t of e.legalMoves(p)) out.push({ id: p.id, r: t.r, c: t.c });
  }
  return out;
}
function prove(level, nodeCap) {
  const e = Engine.createGame();
  FEN.load(e, level.fen);
  const g = Goal.create(level.goal).attach(e);
  const side = level.goal.side;
  const plies = (level.goal.moveLimit || 4) * 2;
  let nodes = 0, best = null;
  // 返回 true
  function rec(d, line) {
    const s = g.check(e);
    if (s.done) return s.win;
    if (d <= 0) return false;
    if (++nodes > nodeCap) throw new Error("节点超限");
    const mine = e.state.turn === side;
    const mv = movesOf(e);
    if (!mv.length) return false;                  // 无子可动
    const snap = e.snapshot();
    if (mine) {
      for (const m of mv) {
        const p = e.pieces[m.id];
        // 记录起点
        const step = { fc: p.c, fr: p.r, c: m.c, r: m.r };
        if (!e.move(m.id, m.r, m.c).ok) { e.restore(snap); continue; }
        const ok = rec(d - 1, line.concat([step]));
        e.restore(snap);
        if (ok) { if (!best) best = line.concat([step]); return true; }
      }
      return false;
    }
    for (const m of mv) {                          // 全枚举，必须全赢
      if (!e.move(m.id, m.r, m.c).ok) { e.restore(snap); continue; }
      const ok = rec(d - 1, line);
      e.restore(snap);
      if (!ok) return false;
    }
    return true;
  }
  const at0 = g.check(e);
  if (at0.done) return { ok: false, why: "开局即判定（" + at0.reason + "）" };
  let ok;
  try { ok = rec(plies, []); } catch (err) { return { ok: false, why: err.message + "（已搜 " + nodes + " 节点）" }; }
  if (!ok) return { ok: false, why: plies + " 半步内无必胜走法（搜 " + nodes + " 节点）" };
  return { ok: true, nodes, line: best || [] };
}

/* ---------- 确定性随机 */
const REAL_RANDOM = Math.random;
function seedRandom(seed) {
  let s = seed >>> 0;
  Math.random = function () {                       // mulberry32
    s = (s + 0x6D2B79F5) >>> 0;
    let t = s;
    t = Math.imul(t ^ (t >>> 15), t | 1);
    t ^= t + Math.imul(t ^ (t >>> 7), t | 61);
    return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
  };
}

/* ---------- 实测 ---------- */
function playtest(level, trials, playerDiff) {
  let wins = 0, decided = 0, best = null;
  const notes = [];
  const plyCap = ((level.goal.moveLimit || 30) * 2) + 4;
  for (let t = 0; t < trials; t++) {
    seedRandom(0x5EED + t * 7919);                 // 固定种子
    const e = Engine.createGame();
    FEN.load(e, level.fen);
    const g = Goal.create(level.goal).attach(e);
    const me = level.goal.side, foe = me === "red" ? "blue" : "red";
    const aiMe = AI.create(e, { side: me, difficulty: playerDiff });
    const aiFoe = AI.create(e, { side: foe, difficulty: level.ai });
    let s = g.check(e), plies = 0;
    while (!s.done && plies < plyCap) {
      const turn = e.state.turn;
      const mv = (turn === me ? aiMe : aiFoe).act(turn);
      if (!mv) break;                              // 该方无着法可走
      plies++;
      s = g.check(e);
    }
    if (s.done) decided++;
    if (s.done && s.win) { wins++; if (!best) best = s; }
    notes.push(s.done ? (s.win ? "胜" : "负:" + s.reason) : "未判定(" + plies + "半步 " + s.cur + "/" + s.max + ")");
  }
  Math.random = REAL_RANDOM;
  if (!wins) return { ok: false, why: "AI 代打 " + trials + " 局均未达成目标 → " + notes.join(" | ") };
  return { ok: true, wins, trials, notes };
}

/* ---------- 主流程 ---------- */
const DEF = require("./levels.def.js");
const WRITE = process.argv.indexOf("--write") >= 0;
const ONLY = (process.argv.find(a => a.indexOf("--only=") === 0) || "").slice(7);
const NODE_CAP = 400000, TRIALS = 5, PLAYER_DIFF = 2;

const results = [];
let bad = 0;
for (const d of DEF) {
  if (ONLY && ONLY.split(",").indexOf(d.id) < 0) continue;
  const line = ["[" + d.id + "] " + d.name];
  let f;
  try { f = fen(d.place, d.tail); } catch (err) { console.log(line[0] + " → 落子表错误：" + err.message); bad++; continue; }
  const v = FEN.validate(f);
  if (!v.ok) { console.log(line[0] + " → FEN 非法：" + v.reason); bad++; continue; }
  try { Goal.validate(d.goal); } catch (err) { console.log(line[0] + " → 目标非法：" + err.message); bad++; continue; }
  const lv = { fen: f, goal: d.goal, ai: d.ai };
  const t0 = Date.now();
  const proxy = d.proxy != null ? d.proxy : PLAYER_DIFF;
  const r = d.verify === "proof" ? prove(lv, NODE_CAP) : playtest(lv, TRIALS, proxy);
  const ms = Date.now() - t0;
  if (!r.ok) { console.log(line[0] + " → 不可用：" + r.why + "  (" + ms + "ms)"); bad++; continue; }
  if (r.line) lv.solution = r.line;                // 教学关
  const detail = d.verify === "proof"
    ? "必胜已证（" + r.nodes + " 节点）解法 " + r.line.map(m => "(" + m.fc + "," + m.fr + ")→(" + m.c + "," + m.r + ")").join(" ")
    : "AI 实测 " + r.wins + "/" + r.trials + " 局达成 → " + r.notes.join(" | ");
  console.log(line[0] + " → " + detail + "  (" + ms + "ms)");
  results.push({ def: d, fen: f, solution: lv.solution || null });
}

console.log("\n通过 " + results.length + " / 校验 " + (results.length + bad) + (bad ? "，失败 " + bad : ""));
if (bad || !WRITE) { if (WRITE) console.log("存在失败，未写出 levels.js"); process.exit(bad ? 1 : 0); }

/* ---------- 数据模块 ---------- */
const esc = s => String(s).replace(/\\/g, "\\\\").replace(/"/g, '\\"');
const body = results.map(({ def: d, fen: f, solution }) => {
  const g = JSON.stringify(d.goal).replace(/"([A-Za-z0-9_]+)":/g, "$1: ").replace(/,/g, ", ");
  const sol = solution && solution.length
    ? "      solution: [" + solution.map(s => "{ fc: " + s.fc + ", fr: " + s.fr + ", c: " + s.c + ", r: " + s.r + " }").join(", ") + "],\n"
    : "";
  return "    {\n" +
    '      id: "' + d.id + '", chapter: ' + d.chapter + ', name: "' + esc(d.name) + '",\n' +
    '      hint: "' + esc(d.hint) + '",\n' +
    "      ai: " + d.ai + ",\n" +
    "      goal: " + g + ",\n" +
    '      fen: "' + f + '",\n' + sol +
    "    },";
}).join("\n");

const out = "(function (root) {\n" +
  '  "use strict";\n' +
  "  const LEVELS = [\n" + body + "\n  ];\n" +
  "  const CHAPTERS = [\n" +
  '    { id: 1, name: "教学 · 识阵", desc: "五节小课，认识走子、盾防、许可区、渡河与继位" },\n' +
  '    { id: 2, name: "官方残局", desc: "对手会真的思考" },\n' +
  "  ];\n" +
  "  const byId = id => LEVELS.filter(function (l) { return l.id === id; })[0] || null;\n" +
  "  const ofChapter = ch => LEVELS.filter(function (l) { return l.chapter === ch; });\n" +
  "  const api = { LEVELS, CHAPTERS, byId, ofChapter };\n" +
  '  if (typeof module !== "undefined" && module.exports) module.exports = api;\n' +
  "  else root.Levels = api;\n" +
  '})(typeof window !== "undefined" ? window : globalThis);\n';

fs.writeFileSync(path.join(CORE, "levels.js"), out, "utf8");
console.log("已写出 assets/core/levels.js（" + results.length + " 关）");

