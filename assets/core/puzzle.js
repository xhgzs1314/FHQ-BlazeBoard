(function (root) {
  "use strict";
  var TAG = "FHQP1", SEP = "|", MAXN = 24;

  function fail(m) { throw new Error("Puzzle: " + m); }
  function F() { return root.FEN || fail("缺少 fen.js"); }
  function G() { return root.Goal || fail("缺少 goal.js"); }

  /* ---------------- 编码 ---------------- */
  function format(p) {
    if (!p || !p.fen) fail("需要 fen");
    var v = F().validate(p.fen);
    if (!v.ok) fail("局面不合法：" + v.reason);
    if (!p.goal && p.ai == null) return p.fen;          // 无设定
    if (!p.goal) fail("带设定的残局码必须给出目标");
    G().validate(p.goal);
    var ai = p.ai == null ? 2 : (p.ai | 0);
    if (ai < 0 || ai > 3) fail("对手难度应为 0..3");
    var name = String(p.name == null ? "" : p.name).replace(/[|\r\n]/g, "").slice(0, MAXN);
    return [TAG, name, JSON.stringify(p.goal), ai, p.fen].join(SEP);
  }

  /* ---------------- 解码 ---------------- */
  function parse(str) {
    if (typeof str !== "string") fail("信息码必须是字符串");
    var s = str.replace(/^﻿/, "").trim();
    if (!s) fail("信息码为空");
    if (s.indexOf(TAG + SEP) === 0) {
      var f = s.split(SEP);
      if (f.length < 5) fail("残局码字段不足（应为 5 段，得到 " + f.length + "）");
      var fen = f[f.length - 1], ai = +f[f.length - 2], gj = f[f.length - 3];
      var name = f.slice(1, f.length - 3).join(SEP).slice(0, MAXN);
      var v = F().validate(fen);
      if (!v.ok) fail("局面不合法：" + v.reason);
      var goal;
      try { goal = JSON.parse(gj); } catch (e) { fail("目标字段不是合法 JSON"); }
      G().validate(goal);
      if (!(ai >= 0 && ai <= 3)) fail("对手难度应为 0..3");
      return { name: name, goal: goal, ai: ai | 0, fen: fen, hasSetup: true };
    }
    if (s.indexOf(F().TAG) !== 0) fail("无法识别的信息码（应以 " + F().TAG + " 或 " + TAG + " 开头）");
    var fen2 = s.replace(/\s+/g, " ");
    var v2 = F().validate(fen2);
    if (!v2.ok) fail("局面不合法：" + v2.reason);
    return { name: "", goal: null, ai: null, fen: fen2, hasSetup: false };
  }

  function validate(str) {
    try { return { ok: true, data: parse(str) }; }
    catch (e) { return { ok: false, reason: e.message }; }
  }
  var N = 30, LETTERS = "KQPGSDW";

  function boardRows(list) {
    var grid = new Array(N * N).fill(null);
    for (var i = 0; i < list.length; i++) {
      var b = list[i], up = String(b.letter || "").toUpperCase();
      if (LETTERS.indexOf(up) < 0) fail("未知棋子代号 " + JSON.stringify(b.letter));
      if (!(b.r >= 0 && b.r < N && b.c >= 0 && b.c < N)) fail("坐标越界 (" + b.c + "," + b.r + ")");
      var k = b.r * N + b.c;
      if (grid[k]) fail("同格重复棋子 (" + b.c + "," + b.r + ")");
      grid[k] = b.side === "blue" ? up.toLowerCase() : up;
    }
    var rows = [];
    for (var r = 0; r < N; r++) {
      var s = "", gap = 0;
      for (var c = 0; c < N; c++) {
        var g = grid[r * N + c];
        if (g) { if (gap) { s += gap; gap = 0; } s += g; }
        else gap++;
      }
      if (gap) s += gap;
      rows.push(s);
    }
    return rows.join("/");
  }

  function fromBoard(list, opt) {
    opt = opt || {};
    var rows = boardRows(list || []);
    var turn = opt.turn === "blue" ? "b" : "r";
    var round = Math.max(1, Math.min(opt.round | 0 || 1, 65535));
    var has = { red: {}, blue: {} };
    for (var i = 0; i < list.length; i++) {
      var up = String(list[i].letter).toUpperCase();
      has[list[i].side === "blue" ? "blue" : "red"][up] = true;
    }
    var fogR = !(has.blue.P || has.blue.Q);            // 红方解雾
    var fogB = !(has.red.P || has.red.Q);
    var fog = (fogR ? "r" : "") + (fogB ? "b" : "") || "-";
    var fen = [F().TAG, rows, turn, round, "-", "-", "-", fog, "-", "-"].join(" ");
    var v = F().validate(fen);
    if (!v.ok) fail(v.reason.replace(/^FEN: /, ""));
    return fen;
  }

  /* FEN → 布子表 */
  function toBoard(fen) {
    var d = F().parse(fen);
    return {
      list: d.board.map(function (b) { return { side: b.side, letter: b.letter, r: b.r, c: b.c }; }),
      turn: d.turn, round: d.round,
    };
  }

  var api = {
    TAG: TAG, LETTERS: LETTERS,
    format: format, parse: parse, validate: validate,
    fromBoard: fromBoard, toBoard: toBoard, boardRows: boardRows,
  };
  if (typeof module !== "undefined" && module.exports) module.exports = api;
  else root.Puzzle = api;
})(typeof window !== "undefined" ? window : globalThis);
