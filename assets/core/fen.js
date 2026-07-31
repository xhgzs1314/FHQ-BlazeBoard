(function (root) {
  "use strict";
  const N = 30, TAG = "FHQ1";
  const MAX_ROUND = 65535;                

  // 字母
  const LETTER = {
    K: { type: "mother" },
    Q: { type: "mother", promoted: true },
    P: { type: "prince" },
    G: { type: "general" },
    S: { type: "scout" },
    D: { type: "shield" },
    W: { type: "pawn", revived: true },
  };
  // 单方各类槽位数
  const CAP = { K: 1, Q: 1, P: 1, D: 4, G: 6, S: 8, W: 20 };

  const START =
    TAG + " 14PK14/30/30/7D3D6D3D7/30/4G3G3G4G3G3G4/30/2S3S3S2S16/16S2S3S3S2/" +
    "30/30/30/30/30/30/30/30/30/30/30/30/" +
    "16s2s3s3s2/2s3s3s2s16/30/4g3g3g4g3g3g4/30/7d3d6d3d7/30/30/14pk14" +
    " r 1 - - - - - -";

  function fail(msg) { throw new Error("FEN: " + msg); }
  function letterOf(p) {
    if (p.revived) return "W";                       // pieceSpec
    if (p.type === "mother") return p.promoted ? "Q" : "K";
    if (p.type === "prince") return "P";
    if (p.type === "general") return "G";
    if (p.type === "scout") return "S";
    if (p.type === "shield") return "D";
    if (p.type === "pawn") return "W";
    fail("未知棋子类型 " + p.type);
  }
  function coordStr(c, r) { return c + "." + r; }
  function parseCoord(s, what) {
    const m = /^(\d{1,2})\.(\d{1,2})$/.exec(s);
    if (!m) fail(what + "坐标格式应为 c.r，得到 " + JSON.stringify(s));
    const c = +m[1], r = +m[2];
    if (c >= N || r >= N) fail(what + "坐标越界 " + s);
    return { c, r };
  }
  function sidesStr(o) { return (o.red ? "r" : "") + (o.blue ? "b" : "") || "-"; }
  function parseSides(s, what) {
    if (s === "-") return { red: false, blue: false };
    if (s === "r") return { red: true, blue: false };
    if (s === "b") return { red: false, blue: true };
    if (s === "rb") return { red: true, blue: true };
    fail(what + "应为 - / r / b / rb，得到 " + JSON.stringify(s));
  }

  /* ---------------- 导出：引擎---------------- */
  function encode(engine) {
    if (!engine || !engine.pieces || !engine.state) fail("需要一个引擎实例");
    const pieces = engine.pieces, st = engine.state;
    if (!st.stats) fail("视角投影态，不可导出局面");
    const grid = new Array(N * N).fill(null);
    for (const p of pieces) {
      if (!p.alive || p.hidden) continue;               //  迷雾投影态
      const k = p.r * N + p.c;
      if (grid[k]) fail("同格重复棋子 " + coordStr(p.c, p.r));
      grid[k] = p.side === "red" ? letterOf(p) : letterOf(p).toLowerCase();
    }
    const rows = [];
    for (let r = 0; r < N; r++) {
      let s = "", gap = 0;
      for (let c = 0; c < N; c++) {
        const g = grid[r * N + c];
        if (g) { if (gap) { s += gap; gap = 0; } s += g; }
        else gap++;
      }
      if (gap) s += gap;
      rows.push(s);
    }
    const permit = side => {
      const z = st.permit && st.permit[side];
      return z ? coordStr(z.c0 + 1, z.r1) : "-";       // 探棋落点
    };
    // 情报点按格序排序
    const intelSide = side => {
      const set = st.revealed && st.revealed[side];
      if (!set || !set.size) return "-";
      const ks = [];
      set.forEach(k => ks.push(k));
      ks.sort((a, b) => a - b);
      return ks.map(k => coordStr(k % N, (k - k % N) / N)).join(",");
    };
    const iR = intelSide("red"), iB = intelSide("blue");
    const intel = (iR === "-" && iB === "-") ? "-" : iR + ";" + iB;
    const paths = [];
    for (const p of pieces) {
      if (!p.alive || p.hidden || p.revived || p.type !== "scout") continue;
      if (!p.path || p.path.length < 2) continue;
      paths.push(p.path.map(pt => coordStr(pt.c, pt.r)).join(","));
    }
    paths.sort();
    return [
      TAG, rows.join("/"),
      st.turn === "blue" ? "b" : "r",
      Math.max(1, Math.min(st.round | 0 || 1, MAX_ROUND)),
      permit("red"), permit("blue"),
      sidesStr(st.skip || {}), sidesStr(st.fogCleared || {}),
      intel, paths.length ? paths.join("_") : "-",
    ].join(" ");
  }

  /* ---------------- 解析：FEN---------------- */
  function parse(str) {
    if (typeof str !== "string") fail("局面码必须是字符串");
    const f = str.trim().split(/\s+/);
    if (f[0] !== TAG) fail("标识应为 " + TAG + "，得到 " + JSON.stringify(f[0] || ""));
    if (f.length < 8) fail("字段不足（至少 8 段，得到 " + f.length + "）");
    if (f.length > 10) fail("字段过多（最多 10 段，得到 " + f.length + "）");

    /* 棋盘 */
    const rows = f[1].split("/");
    if (rows.length !== N) fail("棋盘应有 " + N + " 行，得到 " + rows.length);
    const board = [], seen = new Set(), tally = { red: {}, blue: {} };
    for (let r = 0; r < N; r++) {
      const line = rows[r];
      let c = 0, i = 0;
      while (i < line.length) {
        const ch = line[i];
        if (ch >= "0" && ch <= "9") {
          let d = "";
          while (i < line.length && line[i] >= "0" && line[i] <= "9") d += line[i++];
          const n = +d;
          if (n < 1 || n > N) fail("第 " + r + " 行空格数 " + n + " 越界");
          c += n;
          continue;
        }
        const up = ch.toUpperCase();
        if (!LETTER[up]) fail("第 " + r + " 行含非法字符 " + JSON.stringify(ch));
        if (c >= N) fail("第 " + r + " 行超出 " + N + " 列");
        const side = ch === up ? "red" : "blue";
        const k = r * N + c;
        if (seen.has(k)) fail("同格重复棋子 " + coordStr(c, r));
        seen.add(k);
        tally[side][up] = (tally[side][up] || 0) + 1;
        board.push({ side, letter: up, r, c });
        c++; i++;
      }
      if (c !== N) fail("第 " + r + " 行宽度为 " + c + "，应为 " + N);
    }
    for (const side of ["red", "blue"]) {
      const t = tally[side];
      for (const L in t) if (t[L] > CAP[L]) fail(side + " 方 " + L + " 有 " + t[L] + " 枚，超出上限 " + CAP[L]);
      if ((t.K || 0) + (t.Q || 0) > 1) fail(side + " 方同时存在母棋与继位母棋");
      if (t.Q && t.P) fail(side + " 方子棋已继位却仍有子棋");
      if (t.P && !t.K) fail(side + " 方母棋已亡，子棋应已继位为 Q");
      const total = (t.K || 0) + (t.Q || 0) + (t.P || 0) + (t.D || 0) + (t.G || 0) + (t.S || 0) + (t.W || 0);
      if (total > 20) fail(side + " 方共 " + total + " 枚棋子，超出单方 20 枚上限");
    }

    /* 其余字段 */
    if (f[2] !== "r" && f[2] !== "b") fail("行动方应为 r 或 b，得到 " + JSON.stringify(f[2]));
    if (!/^\d{1,5}$/.test(f[3])) fail("回合数格式非法 " + JSON.stringify(f[3]));
    const round = +f[3];
    if (round < 1 || round > MAX_ROUND) fail("回合数 " + round + " 越界(1.." + MAX_ROUND + ")");

    const permit = {};
    ["red", "blue"].forEach((side, i) => {
      const s = f[4 + i];
      permit[side] = s === "-" ? null : parseCoord(s, (side === "red" ? "红" : "蓝") + "方许可区");
    });
    const skip = parseSides(f[6], "罚跳字段");
    const fogCleared = parseSides(f[7], "解雾字段");
    ["red", "blue"].forEach(side => {
      const foe = side === "red" ? "blue" : "red", t = tally[foe];
      const fcn = side === "red" ? "红" : "蓝", ocn = side === "red" ? "蓝" : "红";
      const princeAlive = !!(t.P || t.Q);          
      if (princeAlive && fogCleared[side])
        fail(ocn + "方子棋仍在场，" + fcn + "方解雾位不应置位");
      if (!princeAlive && t.K && !fogCleared[side])
        fail(ocn + "方母棋在场而子棋已亡 → " + fcn + "方解雾位必须置位");
    });

    const revealed = { red: [], blue: [] };
    const intel = f.length > 8 ? f[8] : "-";
    if (intel !== "-") {
      const halves = intel.split(";");
      if (halves.length !== 2) fail("情报字段应为 <红>;<蓝>");
      ["red", "blue"].forEach((side, i) => {
        if (halves[i] === "-" || halves[i] === "") return;
        halves[i].split(",").forEach(s => {
          const p = parseCoord(s, "情报点");
          revealed[side].push(p.r * N + p.c);
        });
      });
    }

    const paths = [];
    const pf = f.length > 9 ? f[9] : "-";
    if (pf !== "-") {
      pf.split("_").forEach(entry => {
        const pts = entry.split(",").map(s => parseCoord(s, "探棋轨迹"));
        if (pts.length < 2) fail("轨迹条目至少 2 点，单点无需记录");
        paths.push(pts.map(p => ({ r: p.r, c: p.c })));
      });
    }
    return { board, turn: f[2] === "b" ? "blue" : "red", round, permit, skip, fogCleared, revealed, paths };
  }

  function validate(str) {
    try { parse(str); return { ok: true }; }
    catch (e) { return { ok: false, reason: e.message }; }
  }

  
  const LET2SLOT = { K: "mother", Q: "prince", P: "prince", G: "general", S: "scout", D: "shield" };

  function load(engine, str) {
    if (!engine || typeof engine.reset !== "function" || typeof engine.snapshot !== "function"
      || typeof engine.restore !== "function") fail("需要一个引擎实例");
    const d = parse(str);
    engine.reset();
    const snap = JSON.parse(engine.snapshot());
    const pieces = snap.pieces;
    const canon = pieces.map(p => p.type);           
    const pool = { red: {}, blue: {} };              
    pieces.forEach(p => { (pool[p.side][p.type] = pool[p.side][p.type] || []).push(p.id); });
    const used = new Set(), bySide = { red: [], blue: [] };
    d.board.forEach(b => bySide[b.side].push(b));

    function place(p, b) {
      const f = LETTER[b.letter];
      p.alive = true; p.r = b.r; p.c = b.c;
      p.type = f.type; p.promoted = !!f.promoted; p.revived = !!f.revived;
      if (p.type === "scout" && !p.revived) p.path = [{ r: b.r, c: b.c }];
      else delete p.path;
      delete p.hidden;
    }
    for (const side of ["red", "blue"]) {
      const cn = side === "red" ? "红" : "蓝", wlist = [];
      for (const b of bySide[side]) {
        if (b.letter === "W") { wlist.push(b); continue; }
        const arr = pool[side][LET2SLOT[b.letter]] || [];
        let id = -1;
        while (arr.length) { const x = arr.shift(); if (!used.has(x)) { id = x; break; } }
        if (id < 0) fail(cn + "方 " + b.letter + " 无可用槽位 " + coordStr(b.c, b.r));
        used.add(id); place(pieces[id], b);
      }
      // 白板棋占用剩余最小 id 槽位
      for (const b of wlist) {
        const p = pieces.find(q => q.side === side && !used.has(q.id));
        if (!p) fail(cn + "方白板棋无可用槽位 " + coordStr(b.c, b.r));
        used.add(p.id); place(p, b);
      }
    }
    for (const p of pieces) if (!used.has(p.id)) { p.alive = false; p.promoted = false; p.revived = false; }

    for (const pts of d.paths) {
      const last = pts[pts.length - 1];
      const p = pieces.find(q => q.alive && q.type === "scout" && !q.revived
        && q.r === last.r && q.c === last.c);
      if (!p) fail("轨迹末点 " + coordStr(last.c, last.r) + " 处无存活探棋");
      p.path = pts;
    }

    // 派生：母/子存亡
    const goneAt = p => !p.alive || p.revived;
    const royalLost = side => pieces.some(p => p.side === side && goneAt(p) &&
      (canon[p.id] === "mother" || canon[p.id] === "prince"));
    const aliveTotal = pieces.filter(p => p.alive).length;

    const st = snap.state;
    st.turn = d.turn; st.round = d.round; st.selected = null;
    st.skip = d.skip;
    st.lostRoyal = { red: royalLost("red"), blue: royalLost("blue") };
    st.fogCleared = { red: d.fogCleared.red, blue: d.fogCleared.blue };
    st.freeAct = { red: d.fogCleared.red, blue: d.fogCleared.blue };  //同时置位
    st.motherDown = pieces.some(p => canon[p.id] === "mother" && goneAt(p));
    st.permit = {
      red: d.permit.red ? mkPermit(d.permit.red) : null,
      blue: d.permit.blue ? mkPermit(d.permit.blue) : null,
    };
    st.revealed = { red: d.revealed.red, blue: d.revealed.blue };     // restore 内转 Set
    st.freeAnnounced = aliveTotal <= 20;
    st.over = false; st.winner = null;
    st.log = ["数据已载入"];                                          
    engine.restore(snap);
    return engine;
  }
  function mkPermit(p) { return { r0: p.r - 1, r1: p.r, c0: p.c - 1, c1: p.c + 1 }; }

  const api = { TAG, START, encode, decode: parse, parse, validate, load };
  if (typeof module !== "undefined" && module.exports) module.exports = api;
  else root.FEN = api;
})(typeof window !== "undefined" ? window : globalThis);
