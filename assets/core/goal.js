(function (root) {
  "use strict";
  const CN = {
    mother: "母棋", prince: "子棋", general: "军棋",
    scout: "探棋", shield: "盾棋", pawn: "白板棋", royal: "母子",
  };
  const TARGETS = ["mother", "prince", "general", "scout", "shield", "pawn", "royal"];
  const GOALS = ["standard", "slay", "survive", "reach"];
  function fail(m) { throw new Error("Goal: " + m); }
  function foeOf(s) { return s === "red" ? "blue" : "red"; }
  function sideCN(s) { return s === "red" ? "红方" : "蓝方"; }
  function isKind(p, kind) {
    if (kind === "royal") return isKind(p, "mother") || isKind(p, "prince");
    if (p.revived) return kind === "pawn";                       
    if (kind === "mother") return p.type === "mother" && !p.promoted;
    if (kind === "prince") return p.type === "prince" || (p.type === "mother" && !!p.promoted);
    return p.type === kind;
  }
  function countKind(e, side, kind) {
    let n = 0;
    for (const p of e.pieces) if (p.alive && p.side === side && isKind(p, kind)) n++;
    return n;
  }
  function inZone(z, r, c) { return r >= z.r0 && r <= z.r1 && c >= z.c0 && c <= z.c1; }

  /* ---------------- 规格校验 ---------------- */
  function validate(spec) {
    if (!spec || typeof spec !== "object") fail("目标规格必须是对象");
    if (GOALS.indexOf(spec.type) < 0) fail("未知目标类型 " + JSON.stringify(spec.type) + "，应为 " + GOALS.join("/"));
    if (spec.side !== "red" && spec.side !== "blue") fail("side 应为 red 或 blue");
    if (spec.moveLimit != null) {
      if (!Number.isInteger(spec.moveLimit) || spec.moveLimit < 1) fail("moveLimit 应为 ≥1 的整数");
    }
    if (spec.type === "slay") {
      if (TARGETS.indexOf(spec.target) < 0) fail("slay 的 target 应为 " + TARGETS.join("/"));
      if (spec.count != null && (!Number.isInteger(spec.count) || spec.count < 1)) fail("count 应为 ≥1 的整数");
    }
    if (spec.type === "survive") {
      if (!Number.isInteger(spec.rounds) || spec.rounds < 1) fail("survive 需要 rounds ≥1");
    }
    if (spec.type === "reach") {
      if (spec.pieceType != null && TARGETS.indexOf(spec.pieceType) < 0) fail("reach 的 pieceType 非法");
      const hasZone = spec.zone && typeof spec.zone === "object";
      const hasCells = Array.isArray(spec.cells) && spec.cells.length;
      if (!hasZone && !hasCells) fail("reach 需要 zone 或 cells");
      if (hasZone) {
        const z = spec.zone, ks = ["r0", "r1", "c0", "c1"];
        for (const k of ks) if (!Number.isInteger(z[k]) || z[k] < 0 || z[k] > 29) fail("zone." + k + " 应为 0..29 的整数");
        if (z.r0 > z.r1 || z.c0 > z.c1) fail("zone 上下界颠倒");
      }
      if (hasCells) for (const cell of spec.cells) {
        if (!Number.isInteger(cell.r) || !Number.isInteger(cell.c) || cell.r < 0 || cell.r > 29 || cell.c < 0 || cell.c > 29)
          fail("cells 含非法坐标 " + JSON.stringify(cell));
      }
    }
    return true;
  }

  /* ---------------- 文案 ---------------- */
  function describe(spec) {
    const foe = sideCN(foeOf(spec.side));
    let s;
    if (spec.type === "standard") s = "按常规棋规取胜";
    else if (spec.type === "slay") {
      s = spec.count == null ? "全歼" + foe + CN[spec.target]
        : "歼灭" + foe + CN[spec.target] + " " + spec.count + " 枚";
    } else if (spec.type === "survive") s = "撑过 " + spec.rounds + " 个回合";
    else {
      const who = spec.pieceType ? CN[spec.pieceType] : "任意棋子";
      s = "令" + who + "抵达指定区域";
    }
    if (spec.moveLimit != null) s += "（限 " + spec.moveLimit + " 步）";
    return s;
  }

  /* ---------------- 判定 ---------------- */
  function create(spec) {
    validate(spec);
    const g = JSON.parse(JSON.stringify(spec));            
    let base = null;                                        // attach

    function attach(e) {
      if (!e || !e.pieces || !e.state) fail("attach 需要一个引擎实例");
      base = {
        round: e.state.round,
        moves: moveCount(e),
        foeKind: g.type === "slay" ? countKind(e, foeOf(g.side), g.target) : 0,
      };
      return api;
    }
    function moveCount(e) {
      const st = e.state.stats;
      return st && st[g.side] ? st[g.side].moveCount : 0;    // 投影态无 stats → 0
    }
    function reached(e) {
      for (const p of e.pieces) {
        if (!p.alive || p.side !== g.side) continue;
        if (g.pieceType && !isKind(p, g.pieceType)) continue;
        if (g.zone && inZone(g.zone, p.r, p.c)) return true;
        if (g.cells) for (const cell of g.cells) if (cell.r === p.r && cell.c === p.c) return true;
      }
      return false;
    }

    
    function check(e) {
      if (!base) fail("请先 attach(engine) 记录基线");
      const st = e.state;
      const used = Math.max(0, moveCount(e) - base.moves);
      const left = g.moveLimit == null ? null : Math.max(0, g.moveLimit - used);
      const out = {
        done: false, win: false, reason: "", label: describe(g),
        cur: 0, max: 0, movesUsed: used, movesLeft: left,
      };
      // 达成判定
      if (g.type === "standard") {
        out.max = 1;
        if (st.over && st.winner === g.side) { out.done = true; out.win = true; out.cur = 1; out.reason = "取胜"; return out; }
      } else if (g.type === "slay") {
        const leftN = countKind(e, foeOf(g.side), g.target);
        const need = Math.min(g.count == null ? base.foeKind : g.count, base.foeKind);
        out.max = need; out.cur = Math.min(need, base.foeKind - leftN);
        if (need > 0 && out.cur >= need) { out.done = true; out.win = true; out.reason = "目标达成"; return out; }
        if (need === 0) { out.done = true; out.win = true; out.max = 0; out.reason = "无需歼灭"; return out; }
      } else if (g.type === "survive") {
        const passed = Math.max(0, st.round - base.round);
        out.max = g.rounds; out.cur = Math.min(g.rounds, passed);
        if (st.over && st.winner === g.side) { out.done = true; out.win = true; out.cur = g.rounds; out.reason = "取胜"; return out; }
        if (passed >= g.rounds) { out.done = true; out.win = true; out.reason = "成功坚守"; return out; }
      } else {
        out.max = 1;
        if (reached(e)) { out.done = true; out.win = true; out.cur = 1; out.reason = "已抵达"; return out; }
      }
      if (st.over && st.winner === g.side) { out.done = true; out.win = true; out.reason = "取胜"; return out; }
      if (st.over && st.winner && st.winner !== g.side) { out.done = true; out.reason = "被判负"; return out; }
      if (st.over && !st.winner) { out.done = true; out.reason = "和棋，目标未达成"; return out; }
      if (left === 0) { out.done = true; out.reason = "步数用尽"; return out; }
      return out;
    }

    const api = { spec: g, attach, check, describe: () => describe(g) };
    return api;
  }

  const apiMod = { create, validate, describe, TARGETS, GOALS, CN };
  if (typeof module !== "undefined" && module.exports) module.exports = apiMod;
  else root.Goal = apiMod;
})(typeof window !== "undefined" ? window : globalThis);
