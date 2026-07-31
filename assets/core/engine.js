(function (root) {
  "use strict";
  const N = 30;

  const TYPES = {
    mother: { glyph: "母", label: "母棋", en: "K" },
    general: { glyph: "军", label: "军棋", en: "G" },
    prince: { glyph: "子", label: "子棋", en: "P" },
    scout: { glyph: "探", label: "探棋", en: "S" },
    shield: { glyph: "盾", label: "盾棋", en: "D" },
    pawn: { glyph: "兵", label: "白板", en: "·" },
  };

  // 棋子战力价值;白板/复活兵按 pawn 计
  const PIECE_VALUE = { mother: 15, prince: 12, general: 5, shield: 4, scout: 3, pawn: 1 };
  function pieceSVG(type, side) {
    const grad = side === "red" ? "url(#gRed)" : "url(#gBlue)";
    const ring = side === "red" ? "#ffd0cd" : "#c4dcff";
    const t = TYPES[type];
    const body = type === "shield"
      ? `<path d="M50 5 L92 20 V54 C92 78 72 92 50 99 C28 92 8 78 8 54 V20 Z" fill="${grad}" stroke="${ring}" stroke-width="4"/>`
      : `<circle cx="50" cy="50" r="46" fill="${grad}" stroke="${ring}" stroke-width="4"/>`;
    const gloss = `<ellipse cx="38" cy="30" rx="22" ry="12" fill="rgba(255,255,255,.4)"/>`;
    return `<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">${body}${gloss}` +
      `<text x="50" y="54" text-anchor="middle" dominant-baseline="central" ` +
      `font-family="'Microsoft YaHei','PingFang SC',sans-serif" font-size="52" font-weight="900" ` +
      `fill="#fff" style="paint-order:stroke;stroke:rgba(0,0,0,.5);stroke-width:3px">${t.glyph}</text></svg>`;
  }

  function riverCols() {
    const cols = new Set();
    for (let c = 0; c + 6 <= N; c += 8) for (let i = c; i < c + 6; i++) cols.add(i);
    return cols;
  }
  const RIVER_COLS = riverCols();
  function isRiver(r, c) { return (r === 14 || r === 15) && RIVER_COLS.has(c); }
  function riverSeg(c) { return Math.floor(c / 8); }
  function isGapChannel(r, c) { return (r === 14 || r === 15) && !RIVER_COLS.has(c); }

  function classesFor(r, c) {
    const cl = ["cell"];
    const mid = r >= 13 && r <= 16;
    if (mid) {
      cl.push("zone-mid");
      if (isRiver(r, c)) { cl.push("river"); if (r === 14 || r === 15) cl.push("river-edge"); }
    } else if (r < 13) {
      cl.push("zone-red");
      if (r <= 1) cl.push("core-red");
      if (r === 1) cl.push("core-edge-red");
    } else {
      cl.push("zone-blue");
      if (r >= 28) cl.push("core-blue");
      if (r === 28) cl.push("core-edge-blue");
    }
    if (c === 15 && !mid) cl.push("b-left");
    return cl;
  }

  /* ---------- 移动规格 ---------- */
  const DIAG = [[-1, -1], [-1, 1], [1, -1], [1, 1]];
  const ORTHO = [[-1, 0], [1, 0], [0, -1], [0, 1]];
  const ALL = [...ORTHO, ...DIAG];
  const MOVE = {
    mother: { dirs: DIAG, range: 1, capture: false },
    prince: { dirs: ALL, range: 2, capture: false },
    shield: { dirs: ORTHO, range: 1, capture: true },
    scout: { dirs: ORTHO, range: 4, capture: false },
    general: { dirs: ALL, range: 3, capture: true },
    pawn: { dirs: ORTHO, range: 1, capture: false },
  };
  const RED_CORE = new Set([0, 1]);
  const BLUE_CORE = new Set([28, 29]);
  const TYPE_ORDER = ["mother", "general", "prince", "scout", "shield", "pawn"];
  const TYPE_IDX = {}; TYPE_ORDER.forEach((t, i) => (TYPE_IDX[t] = i));
  const HIDDEN = 31;                 // 越界哨兵坐标
  const COORD_RE = /\((\d+),\s*(\d+)\)/g;
  function maskLogLine(line, viewer) {
    const iR = line.indexOf("红方"), iB = line.indexOf("蓝方");
    let owner = null;
    if (iR >= 0 && (iB < 0 || iR < iB)) owner = "red";
    else if (iB >= 0) owner = "blue";
    if (owner === null) return line;
    return owner === viewer ? line : line.replace(COORD_RE, "(***)");
  }
  const b64enc = (bytes) => (typeof Buffer !== "undefined")
    ? Buffer.from(bytes).toString("base64")
    : btoa(String.fromCharCode.apply(null, bytes));
  function b64dec(str) {
    if (typeof Buffer !== "undefined") return new Uint8Array(Buffer.from(str, "base64"));
    const bin = atob(str), out = new Uint8Array(bin.length);
    for (let i = 0; i < bin.length; i++) out[i] = bin.charCodeAt(i);
    return out;
  }
  function createGame() {
    let pieces, occ, state, events;

    function redLayout() {
      const P = [];
      P.push({ r: 0, c: 15, type: "mother" });
      P.push({ r: 0, c: 14, type: "prince" });
      [7, 11, 18, 22].forEach(c => P.push({ r: 3, c, type: "shield" }));
      [4, 8, 12, 17, 21, 25].forEach(c => P.push({ r: 5, c, type: "general" }));
      [2, 6, 10, 13].forEach(c => P.push({ r: 7, c, type: "scout" }));
      [16, 19, 23, 27].forEach(c => P.push({ r: 8, c, type: "scout" }));
      return P;
    }
    function mirror(P) { return P.map(p => ({ r: (N - 1) - p.r, c: p.c, type: p.type })); }
    function initialPieces() {
      const red = redLayout().map(p => ({ ...p, side: "red" }));
      const blue = mirror(redLayout()).map(p => ({ ...p, side: "blue" }));
      return [...red, ...blue].map((p, i) => {
        const pc = { id: i, alive: true, ...p };
        if (pc.type === "scout") pc.path = [{ r: pc.r, c: pc.c }];
        return pc;
      });
    }
    function freshStats() {
      const s = () => ({
        captures: 0,        // 吃子数
        captureValue: 0,    // 被吃棋子战力价值总和
        coreKills: 0,       // 在对方核心区内的击杀数
        riverCross: 0,      // 渡河次数
        coreAttacks: 0,     // 进攻对方核心区次数
        corePenalties: 0,   // 因强攻核心被罚次数
        skippedTurns: 0,    // 被罚跳过回合数
        revives: 0,         // 复活白板棋次数
        shieldDefenses: 0,  // 盾棋成功防御次数
        moveCount: 0,       // 移动次数
        moveStepSum: 0,     // 移动步长总和
        surrendered: false, // 是否投降
      });
      return { red: s(), blue: s() };
    }

    function reset() {
      pieces = initialPieces();
      state = {
        turn: "red", round: 1, selected: null,
        skip: { red: false, blue: false },
        lostRoyal: { red: false, blue: false },
        fogCleared: { red: false, blue: false },
        freeAct: { red: false, blue: false },
        motherDown: false,
        permit: { red: null, blue: null },
        revealed: { red: new Set(), blue: new Set() },
        freeAnnounced: false,
        over: false, winner: null, log: [],
        stats: freshStats(),
      };
      pushLog("游戏开始");
      rebuildOcc();
    }

    function rebuildOcc() {
      occ = {};
      pieces.forEach(p => { if (p.alive && !p.hidden) occ[p.r * N + p.c] = p; });
    }
    function pushLog(msg) { state.log.unshift(msg); if (state.log.length > 8) state.log.pop(); }
    function sideCN(s) { return s === "red" ? "红方" : "蓝方"; }
    function otherSide(s) { return s === "red" ? "blue" : "red"; }

    /* ---------- 序列化---------- */
    function snapshot() {
      return JSON.stringify({
        pieces,
        state: { ...state, revealed: { red: [...state.revealed.red], blue: [...state.revealed.blue] } },
      });
    }
    function restore(json) {
      const o = typeof json === "string" ? JSON.parse(json) : json;
      pieces = o.pieces;
      state = o.state;
      state.revealed = { red: new Set(o.state.revealed.red), blue: new Set(o.state.revealed.blue) };
      rebuildOcc();
    }
    // 视角投影：viewer="red"/"blue"
    function netEncode(viewer) {
      const proj = viewer === "red" || viewer === "blue" || viewer === "none";
      const vis = proj && viewer !== "none" ? buildVisible(viewer) : null;
      const canSee = (p) => {
        if (!proj) return true;
        if (viewer === "none") return false;
        if (p.side === viewer) return true;
        return vis === null || vis.has(p.r * N + p.c);
      };
      const rev = proj
        ? { red: viewer === "red" ? [...state.revealed.red] : [], blue: viewer === "blue" ? [...state.revealed.blue] : [] }
        : { red: [...state.revealed.red], blue: [...state.revealed.blue] };
      // 头部固定字节：flagsA, flagsB, round(2B), permit red(2B), permit blue(2B)
      const head = [];
      const permR = proj && viewer !== "red" ? null : state.permit.red;
      const permB = proj && viewer !== "blue" ? null : state.permit.blue;
      const winnerBit = state.winner === "blue" ? 1 : 0;  // winner: 0=red/null
      const flagsA =
        (state.turn === "blue" ? 1 : 0) |
        (state.motherDown ? 2 : 0) |
        (state.over ? 4 : 0) |
        (state.winner ? 8 : 0) |
        (winnerBit ? 16 : 0) |
        (state.skip.red ? 32 : 0) |
        (state.skip.blue ? 64 : 0) |
        (state.freeAnnounced ? 128 : 0);
      const flagsB =
        (state.lostRoyal.red ? 1 : 0) |
        (state.lostRoyal.blue ? 2 : 0) |
        (state.fogCleared.red ? 4 : 0) |
        (state.fogCleared.blue ? 8 : 0) |
        (state.freeAct.red ? 16 : 0) |
        (state.freeAct.blue ? 32 : 0) |
        (permR ? 64 : 0) |
        (permB ? 128 : 0);
      head.push(flagsA, flagsB, state.round & 255, (state.round >> 8) & 255);
      // 许可区
      const pr = permR, pb = permB;
      head.push(pr ? pr.r1 : 255, pr ? pr.c0 + 1 : 255, pb ? pb.r1 : 255, pb ? pb.c0 + 1 : 255);
      // 40 枚棋子 × 2 字节：type(3) | alive(1) | promoted(1) | revived(1) | r(5) | c(5)
      const pcs = [];
      for (let i = 0; i < 40; i++) {
        const p = pieces[i];
        // 不可见者坐标写越界哨兵 31,31
        const seen = canSee(p);
        const pr2 = seen ? p.r : HIDDEN, pc2 = seen ? p.c : HIDDEN;
        const v = (TYPE_IDX[p.type]) | (p.alive ? 8 : 0) | (p.promoted ? 16 : 0) |
          (p.revived ? 32 : 0) | ((pr2 & 31) << 6) | ((pc2 & 31) << 11);
        pcs.push(v & 255, (v >> 8) & 255);
      }
      // revealed：每侧 count(2B) + cells(2B each)
      const revBytes = [];
      ["red", "blue"].forEach(s => {
        revBytes.push(rev[s].length & 255, (rev[s].length >> 8) & 255);
        rev[s].forEach(k => revBytes.push(k & 255, (k >> 8) & 255));
      });
      const bytes = new Uint8Array(head.length + pcs.length + revBytes.length);
      bytes.set(head, 0); bytes.set(pcs, head.length); bytes.set(revBytes, head.length + pcs.length);
      const log = proj ? state.log.map(m => maskLogLine(m, viewer)) : state.log.slice();
      return { b: b64enc(bytes), log };
    }
    function netApply(payload) {
      const bytes = b64dec(payload.b);
      let o = 0;
      const flagsA = bytes[o++], flagsB = bytes[o++];
      const round = bytes[o++] | (bytes[o++] << 8);
      const prR = bytes[o++], prC = bytes[o++], pbR = bytes[o++], pbC = bytes[o++];
      const mkPermit = (r, c) => (r === 255 ? null : { r0: r - 1, r1: r, c0: c - 1, c1: c + 1 });
      const np = [];
      for (let i = 0; i < 40; i++) {
        const v = bytes[o++] | (bytes[o++] << 8);
        const r = (v >> 6) & 31, c = (v >> 11) & 31;
        np.push({
          id: i, side: i < 20 ? "red" : "blue",
          type: TYPE_ORDER[v & 7],
          alive: !!(v & 8), promoted: !!(v & 16), revived: !!(v & 32),
          r, c, hidden: r === HIDDEN && c === HIDDEN,
        });
      }
      const rev = { red: [], blue: [] };
      ["red", "blue"].forEach(s => {
        const n = bytes[o++] | (bytes[o++] << 8);
        for (let i = 0; i < n; i++) rev[s].push(bytes[o++] | (bytes[o++] << 8));
      });
      pieces = np;
      state = {
        turn: (flagsA & 1) ? "blue" : "red", round, selected: null,
        skip: { red: !!(flagsA & 32), blue: !!(flagsA & 64) },
        lostRoyal: { red: !!(flagsB & 1), blue: !!(flagsB & 2) },
        fogCleared: { red: !!(flagsB & 4), blue: !!(flagsB & 8) },
        freeAct: { red: !!(flagsB & 16), blue: !!(flagsB & 32) },
        motherDown: !!(flagsA & 2),
        permit: { red: (flagsB & 64) ? mkPermit(prR, prC) : null, blue: (flagsB & 128) ? mkPermit(pbR, pbC) : null },
        revealed: { red: new Set(rev.red), blue: new Set(rev.blue) },
        freeAnnounced: !!(flagsA & 128),
        over: !!(flagsA & 4),
        winner: (flagsA & 8) ? ((flagsA & 16) ? "blue" : "red") : null,
        log: payload.log || [],
      };
      rebuildOcc();
    }

    /* ---------- 计数 与 状态 ---------- */
    function aliveCount(side) { return pieces.filter(p => p.alive && p.side === side).length; }
    function totalAlive() { return pieces.filter(p => p.alive).length; }
    function hasShield(side) { return pieces.some(p => p.alive && p.side === side && p.type === "shield"); }
    function hasPrince(side) { return pieces.some(p => p.alive && p.side === side && p.type === "prince"); }
    function shieldDefenseActive(side) { return hasPrince(side); }
    function forwardDir(side) { return side === "red" ? 1 : -1; }
    function coreRestrictionActive() { return totalAlive() > 26; }
    function deadCount(side, type) { return pieces.filter(p => !p.alive && p.side === side && p.type === type).length; }
    function generalBonus(side) {
      if (state.lostRoyal[side]) return 0;
      return Math.floor(deadCount(otherSide(side), "general") / 2);
    }
    function pieceSpec(p) {
      if (p.revived) return { dirs: ORTHO, range: 1, capture: false };
      if (p.type === "mother" && p.promoted) return { dirs: ORTHO, range: 2, capture: true };
      const base = MOVE[p.type];
      if (p.type === "general") return { ...base, range: base.range + generalBonus(p.side) };
      if (p.type === "shield") return { ...base, capture: shieldDefenseActive(p.side) };
      return base;
    }
    function motherCoreLocked(p) { return p.type === "mother" && !p.promoted; }
    function inOwnCore(side, r) { return side === "red" ? RED_CORE.has(r) : BLUE_CORE.has(r); }
    function enemyCoreRows(side) { return side === "red" ? BLUE_CORE : RED_CORE; }

    function kill(p) {
      if (!p.alive) return;
      p.alive = false;
      if (p.type === "mother" || p.type === "prince") state.lostRoyal[p.side] = true;
      if (p.type === "mother") {
        if (!state.motherDown) pushLog(`🌫 ${sideCN(p.side)}母棋阵亡 → 双方迷雾解除`);
        state.motherDown = true;
      }
      if (p.type === "prince") onPrinceDeath(p.side);
    }
    function onPrinceDeath(side) {
      const foe = otherSide(side);
      state.fogCleared[foe] = true;
      state.freeAct[foe] = true;
      pushLog(`💀 ${sideCN(side)}子棋阵亡 → ${sideCN(foe)}迷雾解除·全体自由行动；${sideCN(side)}盾棋防御失效`);
    }
    function discloseScoutPath(capturer, scout) {
      const path = scout.path || [{ r: scout.r, c: scout.c }];
      path.forEach(pt => state.revealed[capturer].add(pt.r * N + pt.c));
      const coords = path.map(pt => `(${pt.c},${pt.r})`).join(" → ");
      pushLog(`🔎 ${sideCN(capturer)}消灭${sideCN(scout.side)}探棋，获知其落点：${coords}`);
    }
    function royalAlive(side) {
      return pieces.some(q => q.alive && q.side === side && (q.type === "mother" || q.type === "prince"));
    }
    function guardCount(side) {
      return pieces.filter(q => q.alive && q.side === side && (q.type === "general" || q.type === "shield")).length;
    }
    function defeated(side) { return !royalAlive(side) && guardCount(side) <= 4; }

    /* ---------- 区块 9 子上限 ---------- */
    function homeQuadrant(side, r, c) {
      const inHome = side === "red" ? (r <= 12) : (r >= 17);
      if (!inHome) return null;
      return (c <= 14 ? "L" : "R");
    }
    function countsForCap(p) { return p.type !== "mother" && p.type !== "prince"; }
    function quadCount(side, quad) {
      return pieces.filter(p => p.alive && p.side === side && countsForCap(p) &&
        homeQuadrant(side, p.r, p.c) === quad).length;
    }
    function capAllowsLanding(p, r, c) {
      if (!countsForCap(p)) return true;
      const destQuad = homeQuadrant(p.side, r, c);
      if (destQuad === null) return true;
      const srcQuad = homeQuadrant(p.side, p.r, p.c);
      if (srcQuad === destQuad) return true;
      return quadCount(p.side, destQuad) < 9;
    }

    /* ---------- 视野 / 迷雾 / 许可区 ---------- */
    function fogGloballyOff() { return state.motherDown || totalAlive() <= 20; }
    function fogOffFor(side) { return fogGloballyOff() || state.fogCleared[side]; }
    function inScoutVision(side, r, c) {
      return pieces.some(s => s.alive && s.side === side && s.type === "scout" &&
        (r === s.r || r === s.r - 1) && (c === s.c || c === s.c - 1));
    }
    function inHomeZone(side, r) { return side === "red" ? (r <= 12) : (r >= 17); }
    function inOwnCoreRow(side, r) { return side === "red" ? RED_CORE.has(r) : BLUE_CORE.has(r); }
    function visibleTo(side, r, c) {
      if (fogOffFor(side)) return true;
      if (inHomeZone(side, r)) return true;
      return inScoutVision(side, r, c);
    }
    // 预计算 viewer 可见格集合
    function buildVisible(side) {
      if (fogOffFor(side)) return null;
      const vis = new Set();
      const lo = side === "red" ? 0 : 17, hi = side === "red" ? 12 : N - 1;
      for (let r = lo; r <= hi; r++) for (let c = 0; c < N; c++) vis.add(r * N + c);
      for (const s of pieces) {
        if (!s.alive || s.side !== side || s.type !== "scout") continue;
        for (let dr = -1; dr <= 0; dr++) for (let dc = -1; dc <= 0; dc++) {
          const rr = s.r + dr, cc = s.c + dc;
          if (rr >= 0 && rr < N && cc >= 0 && cc < N) vis.add(rr * N + cc);
        }
      }
      return vis;
    }
    function makePermit(r, c) { return { r0: r - 1, r1: r, c0: c - 1, c1: c + 1 }; }
    function inPermit(side, r, c) {
      const z = state.permit[side];
      if (!z) return false;
      return r >= z.r0 && r <= z.r1 && c >= z.c0 && c <= z.c1;
    }
    function freeActFor(side) { return state.freeAct[side] || totalAlive() <= 20; }
    function canAct(p) {
      if (p.side !== state.turn) return false;
      if (freeActFor(p.side)) return true;
      if (p.type === "scout") return true;
      if (inOwnCoreRow(p.side, p.r)) return true;
      if (!inPermit(p.side, p.r, p.c)) return false;
      if (!fogOffFor(p.side) && !inScoutVision(p.side, p.r, p.c)) return false;
      return true;
    }

    /* ---------- 渡河 / 盾防御 / 合法着法 ---------- */
    function segOccupiedByOther(p, c) {
      const seg = riverSeg(c);
      return pieces.some(q => q.alive && q.id !== p.id && isRiver(q.r, q.c) && riverSeg(q.c) === seg);
    }
    function gap2x2Blocked(p) {
      for (let rr = p.r - 1; rr <= p.r; rr++)
        for (let cc = p.c - 1; cc <= p.c; cc++) {
          if (rr < 0 || rr >= N || cc < 0 || cc >= N) continue;
          const q = occ[rr * N + cc];
          if (q && q.id !== p.id) return true;
        }
      return false;
    }
    function canCrossRiver(p, dr, dc, c) {
      if (p.type !== "general") return false;
      if (dr !== 0 && dc !== 0) return false;
      const vertical = dc === 0;
      const step = pieceSpec(p).range;
      if (vertical && step < 3) return false;
      if (!vertical && step < 4) return false;
      if (segOccupiedByOther(p, c)) return false;
      return true;
    }
    function inShieldProtectedZone(target) {
      const side = target.side;
      if (!shieldDefenseActive(side)) return false;
      const fd = forwardDir(side);
      return pieces.some(s => s.alive && s.side === side && s.type === "shield" &&
        s.c === target.c && (target.r - s.r) * fd >= 1 && (target.r - s.r) * fd <= 3);
    }
    function canCapture(attacker, victim, dr, dc) {
      if (!pieceSpec(attacker).capture) return false;
      if (victim.type === "shield" && shieldDefenseActive(victim.side)) return dr === 0;
      if (inShieldProtectedZone(victim)) return false;
      return true;
    }
    function legalMoves(p) {
      const spec = pieceSpec(p);
      const gapBlocked = gap2x2Blocked(p);
      const out = [];
      for (const [dr, dc] of spec.dirs) {
        for (let step = 1; step <= spec.range; step++) {
          const r = p.r + dr * step, c = p.c + dc * step;
          if (r < 0 || r >= N || c < 0 || c >= N) break;
          if (motherCoreLocked(p) && !inOwnCore(p.side, r)) break;
          if (isRiver(r, c) && !canCrossRiver(p, dr, dc, c)) break;
          if (isGapChannel(r, c) && gapBlocked) break;
          const occupant = occ[r * N + c];
          if (occupant) {
            if (occupant.side !== p.side && canCapture(p, occupant, dr, dc) && capAllowsLanding(p, r, c))
              out.push({ r, c, capture: occupant.id });
            break;
          }
          if (!capAllowsLanding(p, r, c)) continue;
          out.push({ r, c });
        }
      }
      return out;
    }
    function move(pieceId, r, c) {
      if (state.over) return { ok: false, reason: "游戏已结束" };
      const p = pieces[pieceId];
      if (!p || !p.alive) return { ok: false, reason: "棋子不存在" };
      if (p.side !== state.turn) return { ok: false, reason: "非本方回合" };
      if (!canAct(p)) return { ok: false, reason: "该棋子无行动权" };
      if (!legalMoves(p).some(t => t.r === r && t.c === c)) return { ok: false, reason: "非法落点" };
      events = [];
      doMove(p, r, c);
      return { ok: true, events };
    }

    function doMove(p, r, c) {
      const defender = otherSide(p.side);
      const fromR = p.r, fromC = p.c;
      const st = state.stats && state.stats[p.side];   // 客户端 netApply
      const victim = occ[r * N + c];
      let ate = null;
      if (victim && victim.side !== p.side && pieceSpec(p).capture) {
        kill(victim); ate = victim;
        if (victim.type === "scout") discloseScoutPath(p.side, victim);
        if (st) {
          st.captures++;
          st.captureValue += PIECE_VALUE[ate.type] || 1;
          if (enemyCoreRows(p.side).has(r)) st.coreKills++;
        }
      }
      if (st) {
        st.moveCount++;
        st.moveStepSum += Math.max(Math.abs(r - fromR), Math.abs(c - fromC));
        // 渡河：跨越河道所在的中央两行(14/15)分界
        if ((fromR <= 14 && r >= 15) || (fromR >= 15 && r <= 14)) st.riverCross++;
        if (enemyCoreRows(p.side).has(r)) st.coreAttacks++;
      }
      const fromLabel = `(${fromC},${fromR})`;
      p.r = r; p.c = c;
      state.selected = null;
      pushLog(`${sideCN(p.side)}·${TYPES[p.type].label}${p.promoted ? "(继)" : ""} ${fromLabel}→(${c},${r})` +
        (ate ? ` 吃${sideCN(ate.side)}${TYPES[ate.type].label}` : ""));
      if (p.type === "scout") {
        state.permit[p.side] = makePermit(r, c);
        if (!p.path) p.path = [];
        const last = p.path[p.path.length - 1];
        if (!last || last.r !== r || last.c !== c) p.path.push({ r, c });
        pushLog(`　${sideCN(p.side)}探棋许可区更新 → 中心(${c},${r})`);
      }
      if (ate && p.type === "mother" && p.promoted) reviveOne(p.side, fromR, fromC);
      if (enemyCoreRows(p.side).has(r) && hasShield(defender) && coreRestrictionActive()) {
        applyPenalty(p);
        pushLog(`　（${sideCN(p.side)}进攻核心）`);
      }
      promoteIfNeeded();
      if (totalAlive() <= 20 && !state.freeAnnounced) {
        state.freeAnnounced = true;
        pushLog(`⚔ 全场棋子≤20 → 双方迷雾解除·全体自由行动`);
      }
      rebuildOcc();
      if (checkWin()) return;
      passTurn();
      rebuildOcc();
    }

    function promoteIfNeeded() {
      ["red", "blue"].forEach(side => {
        if (pieces.some(q => q.alive && q.side === side && q.type === "mother")) return;
        const prince = pieces.find(q => q.alive && q.side === side && q.type === "prince");
        if (prince) {
          prince.type = "mother"; prince.promoted = true;
          pushLog(`👑 ${sideCN(side)}子棋继位为母棋（横纵2·可吃·吃子复活）`);
        }
      });
    }
    function reviveOne(side, r, c) {
      if (pieces.some(q => q.alive && q.r === r && q.c === c)) return;
      const dead = pieces.find(q => !q.alive && q.side === side);
      if (!dead) return;
      const was = TYPES[dead.type].label;
      dead.alive = true; dead.revived = true; dead.promoted = false;
      dead.type = "pawn"; dead.r = r; dead.c = c;
      if (state.stats && state.stats[side]) state.stats[side].revives++;
      pushLog(`✚ ${sideCN(side)}复活${was}为白板棋→(${c},${r})（横纵1·不可吃）`);
    }
    function checkWin() {
      for (const side of ["red", "blue"]) {
        if (defeated(side)) {
          state.over = true; state.winner = otherSide(side); state.selected = null;
          pushLog(`🏆 ${sideCN(otherSide(side))}获胜！（对方 母+子 全灭 且 军+盾 ≤4）`);
          return true;
        }
      }
      return false;
    }
    function applyPenalty(attacker) {
      const side = attacker.side;
      if (state.stats && state.stats[side]) state.stats[side].corePenalties++;
      let pick = null;
      if (attacker.alive && (attacker.type === "scout" || attacker.type === "general")) pick = attacker;
      else pick = pieces.find(p => p.alive && p.side === side && p.type === "scout") ||
        pieces.find(p => p.alive && p.side === side && p.type === "general");
      if (pick) {
        const pr = pick.r, pc = pick.c;
        kill(pick);
        const self = pick === attacker ? "（进攻棋子）" : "";
        pushLog(`⚠ ${sideCN(side)}强攻核心 → 罚下${TYPES[pick.type].label}${self} (${pc},${pr})`);
        events.push({ type: "penalty", r: pr, c: pc });
        return true;
      }
      state.skip[side] = true;
      if (state.stats && state.stats[side]) state.stats[side].skippedTurns++;
      pushLog(`⚠ ${sideCN(side)}强攻核心且无子可罚 → 罚下一回合`);
      return false;
    }
    // 认输 / 和棋直接结束对局
    function surrender(side) {
      if (state.over) return { ok: false, reason: "游戏已结束" };
      if (side !== "red" && side !== "blue") return { ok: false, reason: "无效阵营" };
      state.over = true; state.winner = otherSide(side); state.selected = null;
      if (state.stats && state.stats[side]) state.stats[side].surrendered = true;
      pushLog(`🏳 ${sideCN(side)}投降 → ${sideCN(otherSide(side))}获胜`);
      return { ok: true };
    }
    function drawGame() {
      if (state.over) return { ok: false, reason: "游戏已结束" };
      state.over = true; state.winner = null; state.selected = null;
      pushLog(`🤝 双方达成和棋`);
      return { ok: true };
    }

    /* ---------- 评分数据收集 ---------- */
    const HIGH_VALUE = new Set(["mother", "prince", "general"]);   // 母+子+6军棋 = 8
    function pieceActable(p) {   
      if (freeActFor(p.side)) return true;
      if (p.type === "scout") return true;
      if (inOwnCoreRow(p.side, p.r)) return true;
      if (!inPermit(p.side, p.r, p.c)) return false;
      if (!fogOffFor(p.side) && !inScoutVision(p.side, p.r, p.c)) return false;
      return true;
    }
    function collectSide(side) {
      const st = state.stats[side];
      let aliveTotal = 0, aliveHighValue = 0, aliveScouts = 0, deadValue = 0;
      let actable = 0, actableInPermit = 0, quadL = 0, quadR = 0;
      for (const p of pieces) {
        if (p.side !== side) continue;
        if (p.alive) {
          aliveTotal++;
          if (HIGH_VALUE.has(p.type)) aliveHighValue++;
          if (p.type === "scout") aliveScouts++;
          if (pieceActable(p)) {
            actable++;
            if (inPermit(side, p.r, p.c)) actableInPermit++;
          }
          if (countsForCap(p)) {
            const q = homeQuadrant(side, p.r, p.c);
            if (q === "L") quadL++; else if (q === "R") quadR++;
          }
        } else {
          deadValue += PIECE_VALUE[p.type] || 1;
        }
      }
      return {
        ...st,
        aliveTotal, aliveHighValue, aliveScouts, deadValue,
        actable, actableInPermit, quadDiff: Math.abs(quadL - quadR),
        generalBonus: generalBonus(side),
        fogClearedForMe: state.fogCleared[side],       // 我方迷雾被解除
        foeFogStillOn: !fogOffFor(otherSide(side)),    // 对方仍处迷雾
      };
    }
    function getRatingData() {
      if (!state.stats) return null;
      return {
        winner: state.winner,
        round: state.round,
        red: collectSide("red"),
        blue: collectSide("blue"),
      };
    }

    function passTurn() {
      let next = state.turn === "red" ? "blue" : "red";
      if (next === "red") state.round++;
      if (state.skip[next]) {
        state.skip[next] = false;
        pushLog(`${sideCN(next)}被罚，跳过本回合`);
        const after = next === "red" ? "blue" : "red";
        if (after === "red") state.round++;
        state.turn = after;
      } else state.turn = next;
    }

    reset();
    return {
      // r
      get pieces() { return pieces; },
      get state() { return state; },
      pieceAt: (r, c) => occ[r * N + c],
      legalMoves, canAct, visibleTo, inPermit, freeActFor, fogOffFor, buildVisible,
      getRatingData,
      // w
      move, reset, snapshot, restore, netEncode, netApply,
      surrender, drawGame,
    };
  }

  const api = { N, TYPES, RIVER_COLS, pieceSVG, isRiver, classesFor, riverSeg, isGapChannel, createGame };
  if (typeof module !== "undefined" && module.exports) module.exports = api;
  else root.Engine = api;
})(typeof window !== "undefined" ? window : globalThis);
