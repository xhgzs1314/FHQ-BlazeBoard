(function (root) {
  "use strict";
  const N = 30;
  const PIECE_VALUE = { mother: 15, prince: 12, general: 5, shield: 4, scout: 3, pawn: 1 };
  const RED_CORE = new Set([0, 1]), BLUE_CORE = new Set([28, 29]);
  const HIGH = new Set(["mother", "prince", "general"]);
  const TYPE_IDX = { mother: 0, general: 1, prince: 2, scout: 3, shield: 4, pawn: 5 };
  // 前进权重：军最高
  const ADV_W = { mother: 0, prince: 0.2, general: 1.0, shield: 0.5, scout: 0.15, pawn: 0.4 };
  const WIN = 1e6;
  const TIMEOUT = { __to: true };            // 超时哨兵
  // 评估权重
  const W_MAT = 10, ROYAL_W = 250, GUARD_W = 40, W_ADV = 2.0, W_ENG = 1.5, W_UNLOCK = 6, W_SCOUT = 6, W_FOG = 8, W_HEAT = 1.5;
  const W_HUNT = 4.0, W_WINLINE = 10;
  // W_THREAT
  const W_THREAT = 3.0;
  const QDEPTH = 4;                          // 静态搜索
  // 难度预设（0 最难 → 3 最易）
  const PRESET = {
    0: { name: "炼狱", depths: [1, 2, 3, 4, 5], beam: 16, budgetMs: 190, budgetMsComplex: 480, nodeCap: 200000, history: true,  styleEps: 4,  wild: 0.35, random: false, greedy: false },
    1: { name: "大师", depths: [1, 2, 3, 4],    beam: 11, budgetMs: 165, budgetMsComplex: 190, nodeCap: 60000,  history: true,  styleEps: 2,  wild: 0.25, random: false, greedy: false },
    2: { name: "棋手", depths: [1],             beam: 60, budgetMs: 110, budgetMsComplex: 160, nodeCap: 4000,   history: false, styleEps: 8,  wild: 0.5,  random: false, greedy: true  },
    3: { name: "新手", depths: [],              beam: 60, budgetMs: 40,  budgetMsComplex: 40,  nodeCap: 200,  history: false, styleEps: 0,  wild: 0,   random: true,  greedy: false },
  };

  function nowMs() { return (typeof performance !== "undefined" && performance.now) ? performance.now() : Date.now(); }
  function other(s) { return s === "red" ? "blue" : "red"; }
  function clampDiff(d) { d = d | 0; return d < 0 ? 0 : d > 3 ? 3 : d; }

  function create(engine, opts) {
    opts = opts || {};
    let side = (opts.side === "red" || opts.side === "blue") ? opts.side : "red";
    let difficulty = clampDiff(opts.difficulty != null ? opts.difficulty : 1);

    /* ---------- 对手历史分析模块---------- */
    const heat = { red: new Float32Array(N * N), blue: new Float32Array(N * N) };
    const activity = { red: 0, blue: 0 };
    const HEAT_CAP = 400;
    // 追踪落子：对比上次观测棋盘
    let lastSeen = null;   // [{id, r, c, alive}] 快照
    function snapshotLite() {
      const arr = engine.pieces;
      const out = new Array(arr.length);
      for (let i = 0; i < arr.length; i++) { const p = arr[i]; out[i] = { r: p.r, c: p.c, alive: p.alive }; }
      return out;
    }
    function decayIfNeeded(s) {
      if (activity[s] < HEAT_CAP) return;
      const h = heat[s];
      for (let i = 0; i < h.length; i++) h[i] *= 0.5;   // 半衰
      activity[s] = Math.floor(activity[s] / 2);
    }
    // 观测：调用方在每步之后调用
    function ingest() {
      if (!PRESET[difficulty].history) { lastSeen = snapshotLite(); return; }
      const cur = engine.pieces;
      if (lastSeen && lastSeen.length === cur.length) {
        for (let i = 0; i < cur.length; i++) {
          const a = lastSeen[i], b = cur[i];
          if (!b.alive) continue;
          if (a.r !== b.r || a.c !== b.c) {            // 该 id 棋子移动到新格
            const mv = cur[i].side;                    // 该棋子所属方
            const k = b.r * N + b.c;
            heat[mv][k] += 1; activity[mv] += 1; decayIfNeeded(mv);
          }
        }
      }
      lastSeen = snapshotLite();
    }
    function heatBias(forSide) {
      // 参考“对手历史落点偏好”
      const foe = other(forSide);
      const h = heat[foe]; const act = activity[foe];
      if (act <= 0) return null;
      return { h: h, inv: 1 / act };
    }

    /* ---------- 局面读取辅助 ---------- */
    function aliveList(s) { const o = []; const a = engine.pieces; for (let i = 0; i < a.length; i++) if (a[i].alive && a[i].side === s) o.push(a[i]); return o; }
    function royalAlive(s) { return engine.pieces.some(p => p.alive && p.side === s && (p.type === "mother" || p.type === "prince")); }
    function guardCount(s) { return engine.pieces.filter(p => p.alive && p.side === s && (p.type === "general" || p.type === "shield")).length; }
    function enemyCore(s) { return s === "red" ? BLUE_CORE : RED_CORE; }
    function totalAlive() { return engine.pieces.filter(p => p.alive).length; }
    function fwd(s) { return s === "red" ? 1 : -1; }
    // 前进度：己方核心行=0，越深入敌方越大（0~29）
    function advance(s, r) { return s === "red" ? r : (N - 1 - r); }

    /* ---------- 许可区 / 重子机动性---------- */
    function ownCoreRow(s, r) { return s === "red" ? (r <= 1) : (r >= N - 2); }
    let posTick = 0;
    const _faCache = { tick: -1, red: false, blue: false };
    const _fogCache = { tick: -1, red: true, blue: true };
    function freeAct(s) {
      if (_faCache.tick !== posTick) {
        _faCache.tick = posTick;
        try { _faCache.red = engine.freeActFor("red"); } catch (_) { _faCache.red = false; }
        try { _faCache.blue = engine.freeActFor("blue"); } catch (_) { _faCache.blue = false; }
      }
      return _faCache[s];
    }
    function fogOff(s) {
      if (_fogCache.tick !== posTick) {
        _fogCache.tick = posTick;
        try { _fogCache.red = engine.fogOffFor("red"); } catch (_) { _fogCache.red = true; }
        try { _fogCache.blue = engine.fogOffFor("blue"); } catch (_) { _fogCache.blue = true; }
      }
      return _fogCache[s];
    }
    // 该重子当前是否“可被调动”
    function heavyMobilizable(p) {
      if (freeAct(p.side)) return true;
      if (ownCoreRow(p.side, p.r)) return true;
      let inP = false; try { inP = engine.inPermit(p.side, p.r, p.c); } catch (_) { inP = false; }
      if (!inP) return false;
      if (fogOff(p.side)) return true;
      // 迷雾未散：还需探棋视野
      return engine.pieces.some(s => s.alive && s.side === p.side && s.type === "scout" &&
        (p.r === s.r || p.r === s.r - 1) && (p.c === s.c || p.c === s.c - 1));
    }
    function mobileHeavies(s) {
      // freeAct 为真
      const a = engine.pieces;
      if (freeAct(s)) {
        let n = 0;
        for (let i = 0; i < a.length; i++) { const p = a[i]; if (p.alive && p.side === s && (p.type === "general" || p.type === "shield")) n++; }
        return n;
      }
      let n = 0;
      for (let i = 0; i < a.length; i++) { const p = a[i]; if (p.alive && p.side === s && (p.type === "general" || p.type === "shield") && heavyMobilizable(p)) n++; }
      return n;
    }
    function killNear(g) {
      const over = Math.max(0, g - 4);      // 距阈值差
      return (12 - Math.min(12, over)) * W_WINLINE;
    }
    function threatValue(attackers, victims) {
      if (!attackers.length || !victims.length) return 0;
      let sum = 0;
      for (let j = 0; j < victims.length; j++) {
        const v = victims[j];
        let hit = false;
        for (let i = 0; i < attackers.length && !hit; i++) {
          const p = attackers[i];
          const dr = v.r - p.r, dc = v.c - p.c;
          const adr = dr < 0 ? -dr : dr, adc = dc < 0 ? -dc : dc;
          if (p.type === "shield") {
            if ((adr + adc) === 1) hit = true;                       // 正交相邻
          } else {                                                    // general：全向直线，切比雪夫≤3
            if ((adr <= 3 && adc <= 3) && (dr === 0 || dc === 0 || adr === adc)) hit = true;
          }
        }
        if (hit) sum += (PIECE_VALUE[v.type] || 1);
      }
      return sum;
    }
    function nearProx(atk, roy) {
      if (!atk.length || !roy.length) return 0;
      let best = -1;
      for (let i = 0; i < atk.length; i++) {
        const p = atk[i];
        for (let j = 0; j < roy.length; j++) {
          const q = roy[j];
          const d = Math.max(Math.abs(p.r - q.r), Math.abs(p.c - q.c));
          const prox = (N - 1) - d;
          if (prox > best) best = prox;
        }
      }
      return best < 0 ? 0 : best;
    }
    // 假设己方一枚探棋落到
    function scoutUnlocksAt(s, scoutId, sr, sc) {
      const a = engine.pieces; let n = 0;
      const fog = fogOff(s);
      for (let i = 0; i < a.length; i++) {
        const p = a[i];
        if (!p.alive || p.side !== s) continue;
        if (p.type !== "general" && p.type !== "shield") continue;
        if (heavyMobilizable(p)) continue;                 // 不算解锁
        // 新许可区覆盖
        const inPermitNew = (p.r === sr || p.r === sr - 1) && (p.c >= sc - 1 && p.c <= sc + 1);
        if (!inPermitNew) continue;
        if (fog) { n++; continue; }
        // 迷雾未散
        if ((p.r === sr || p.r === sr - 1) && (p.c === sc || p.c === sc - 1)) n++;
      }
      return n;
    }

    /* ---------- 着法生成 + 排序 + beam 裁剪 ---------- */
    function moveHeur(p, mv) {
      let s = 0;
      if (mv.capture != null) {
        const v = engine.pieces[mv.capture];
        if (v) s += 60 + (PIECE_VALUE[v.type] || 1) * 12;   // 吃子优先，越贵越先
      }
      s += (advance(p.side, mv.r) - advance(p.side, p.r)) * (ADV_W[p.type] || 0.4) * 4;
      if (p.type === "scout") {
        const u = scoutUnlocksAt(p.side, p.id, mv.r, mv.c);
        if (u > 0) s += 45 + u * 25;
      }
      if (enemyCore(p.side).has(mv.r)) {
        // 强攻核心有罚风险
        if (totalAlive() > 26 && engine.pieces.some(q => q.alive && q.side === other(p.side) && q.type === "shield")) s -= 40;
        else s += 30;
      }
      if (p.type === "mother" && !p.promoted) s -= 20;      // 母棋别乱跑
      return s;
    }
    function genMoves(s) {
      const out = [];
      const list = aliveList(s);
      for (let i = 0; i < list.length; i++) {
        const p = list[i];
        let act; try { act = engine.canAct(p); } catch (_) { act = false; }
        if (!act) continue;
        let mvs; try { mvs = engine.legalMoves(p); } catch (_) { mvs = []; }
        for (let j = 0; j < mvs.length; j++) {
          const m = mvs[j];
          out.push({ id: p.id, r: m.r, c: m.c, cap: m.capture != null ? m.capture : -1, h: moveHeur(p, m) });
        }
      }
      return out;
    }
    function orderAndBeam(moves, beam) {
      moves.sort((a, b) => b.h - a.h);
      return moves.length > beam ? moves.slice(0, beam) : moves;
    }

    /* ---------- 局面评估---------- */
    function evaluate(rootSide) {
      const st = engine.state;
      const foe = other(rootSide);
      // 终局：己方达成胜利/失败
      if (st.over) {
        if (st.winner === rootSide) return WIN;
        if (st.winner === foe) return -WIN;
        return 0; // 和棋
      }
      let score = 0;
      let matMe = 0, matFoe = 0, advMe = 0, advFoe = 0, scoutMe = 0, scoutFoe = 0;
      let frontMe = 0, frontFoe = 0, gMe = 0, gFoe = 0;
      // 单趟收集：材料/前进/探棋/前锋/护卫数
      const a = engine.pieces;
      const atkMe = [], atkFoe = [], royMe = [], royFoe = [], allMe = [], allFoe = [];
      for (let i = 0; i < a.length; i++) {
        const p = a[i];
        if (!p.alive) continue;
        const val = PIECE_VALUE[p.type] || 1;
        const isAtk = (p.type === "general" || p.type === "shield");
        const isRoy = (p.type === "mother" || p.type === "prince");
        if (p.side === rootSide) {
          matMe += val; allMe.push(p);
          advMe += advance(rootSide, p.r) * (ADV_W[p.type] || 0.4);
          if (p.type === "scout") scoutMe++;
          if (isAtk) { gMe++; atkMe.push(p); const d = advance(rootSide, p.r); if (d > frontMe) frontMe = d; }
          else if (p.type === "pawn") { const d = advance(rootSide, p.r); if (d > frontMe) frontMe = d; }
          if (isRoy) royMe.push(p);
        } else {
          matFoe += val; allFoe.push(p);
          advFoe += advance(foe, p.r) * (ADV_W[p.type] || 0.4);
          if (p.type === "scout") scoutFoe++;
          if (isAtk) { gFoe++; atkFoe.push(p); const d = advance(foe, p.r); if (d > frontFoe) frontFoe = d; }
          else if (p.type === "pawn") { const d = advance(foe, p.r); if (d > frontFoe) frontFoe = d; }
          if (isRoy) royFoe.push(p);
        }
      }
      score += (matMe - matFoe) * W_MAT;
      score += (advMe - advFoe) * W_ADV;
      score += (scoutMe - scoutFoe) * W_SCOUT;    // 探棋=视野+许可区
      score += (frontMe - frontFoe) * W_ENG;      // 尖刀前压
      // 重子机动性
      score += (mobileHeavies(rootSide) - mobileHeavies(foe)) * W_UNLOCK;
      // 皇族存亡（母/子）
      const royalMe = royMe.length > 0, royalFoe = royFoe.length > 0;
      if (royalMe && !royalFoe) score += ROYAL_W;
      if (!royalMe && royalFoe) score -= ROYAL_W;
      // 逼近胜利线：对方军+盾越少越好
      score += (gMe - gFoe) * GUARD_W;
      if (!royalFoe) score += (12 - Math.min(12, gFoe)) * GUARD_W * 0.5;  // 对方已无皇族→压低其护卫更值钱
      if (!royalMe) score -= (12 - Math.min(12, gMe)) * GUARD_W * 0.5;
      // 胜利线非线性梯度
      // killNear(g) 在 g→4 时快速增大
      score += killNear(gFoe) - killNear(gMe);
      // 压王梯度
      score += (nearProx(atkMe, royFoe) - nearProx(atkFoe, royMe)) * W_HUNT;
      score += (threatValue(atkMe, allFoe) - threatValue(atkFoe, allMe)) * W_THREAT;
      // 迷雾优势
      try {
        if (!engine.fogOffFor(foe)) score += W_FOG;
        if (!engine.fogOffFor(rootSide)) score -= W_FOG;
      } catch (_) {}

      // 对手历史
      const hb = heatBias(rootSide);
      if (hb) {
        let bias = 0;
        const mine = aliveList(rootSide);
        for (let i = 0; i < mine.length; i++) {
          const k = mine[i].r * N + mine[i].c;
          bias += hb.h[k] * hb.inv;
        }
        score += bias * W_HEAT;
      }
      return score;
    }

    /* ---------- 搜索：minimax + alpha-beta---------- */
    let deadline = 0, nodeBudget = 0, nodes = 0;
    function timeUp() { return nowMs() >= deadline || nodes >= nodeBudget; }

    /* ---------- 置换表(Zobrist) + 杀手着 + 历史启发 ---------- */
    const SQ = N * N;
    const MAXID = engine.pieces.length;
    // 确定性 PRNG(xorshift32) 生成 Zobrist 键
    let _rs = 0x9e3779b9 >>> 0;
    function rnd32() { _rs ^= _rs << 13; _rs >>>= 0; _rs ^= _rs >>> 17; _rs ^= _rs << 5; _rs >>>= 0; return _rs | 0; }
    const Z_A = new Int32Array(MAXID * SQ), Z_B = new Int32Array(MAXID * SQ);
    for (let i = 0; i < Z_A.length; i++) { Z_A[i] = rnd32(); Z_B[i] = rnd32(); }
    const Z_PROMO_A = new Int32Array(MAXID), Z_PROMO_B = new Int32Array(MAXID);
    for (let i = 0; i < MAXID; i++) { Z_PROMO_A[i] = rnd32(); Z_PROMO_B[i] = rnd32(); }
    const Z_TURN_A = rnd32(), Z_TURN_B = rnd32();
    const Z_FR_A = rnd32(), Z_FR_B = rnd32(), Z_FB_A = rnd32(), Z_FB_B = rnd32();
    let _hzA = 0, _hzB = 0;   // 最近一次 computeHash 的结果
    function computeHash() {
      let a = 0, b = 0; const arr = engine.pieces;
      for (let i = 0; i < arr.length; i++) {
        const p = arr[i]; if (!p.alive) continue;
        const idx = p.id * SQ + p.r * N + p.c;
        a ^= Z_A[idx]; b ^= Z_B[idx];
        if (p.promoted) { a ^= Z_PROMO_A[p.id]; b ^= Z_PROMO_B[p.id]; }
      }
      if (engine.state.turn === "blue") { a ^= Z_TURN_A; b ^= Z_TURN_B; }
      if (freeAct("red")) { a ^= Z_FR_A; b ^= Z_FR_B; }
      if (freeAct("blue")) { a ^= Z_FB_A; b ^= Z_FB_B; }
      _hzA = a >>> 0; _hzB = b | 0;
    }
    // TT：2^18 槽，直接索引 + lock 校验。flag：0 空 / 1 精确 / 2 下界(fail-high) / 3 上界(fail-low)
    const TT_SIZE = 1 << 18, TT_MASK = TT_SIZE - 1;
    const ttLock = new Int32Array(TT_SIZE), ttDepth = new Int16Array(TT_SIZE);
    const ttFlag = new Uint8Array(TT_SIZE), ttVal = new Float64Array(TT_SIZE);
    const ttId = new Int16Array(TT_SIZE), ttR = new Int8Array(TT_SIZE), ttC = new Int8Array(TT_SIZE);
    function ttClear() { ttFlag.fill(0); }
    let ttMove = null;
    function ttProbe(depth, alpha, beta) {
      const i = _hzA & TT_MASK; ttMove = null;
      if (ttFlag[i] === 0 || ttLock[i] !== _hzB) return null;
      if (ttId[i] >= 0) ttMove = { id: ttId[i], r: ttR[i], c: ttC[i] };
      if (ttDepth[i] < depth) return null;
      const v = ttVal[i], f = ttFlag[i];
      if (f === 1) return v;
      if (f === 2 && v >= beta) return v;
      if (f === 3 && v <= alpha) return v;
      return null;
    }
    function ttStore(depth, val, flag, mv) {
      const i = _hzA & TT_MASK;
      if (ttFlag[i] !== 0 && ttLock[i] === _hzB && ttDepth[i] > depth) return;  // 深度优先替换
      ttLock[i] = _hzB; ttDepth[i] = depth; ttFlag[i] = flag; ttVal[i] = val;
      if (mv) { ttId[i] = mv.id; ttR[i] = mv.r; ttC[i] = mv.c; } else { ttId[i] = -1; }
    }
    // 杀手着
    const MAXPLY = 32;
    const killA = new Array(MAXPLY), killB = new Array(MAXPLY);
    function killClear() { for (let i = 0; i < MAXPLY; i++) { killA[i] = null; killB[i] = null; } }
    const hist = new Int32Array(MAXID * SQ);
    function histClear() { hist.fill(0); }
    function histBump(m, depth) {
      const k = m.id * SQ + m.r * N + m.c;
      hist[k] += depth * depth;
      if (hist[k] > 1e7) { for (let i = 0; i < hist.length; i++) hist[i] >>= 1; }
    }
    function isKiller(m, ply) {
      if (ply >= MAXPLY) return false;
      const a = killA[ply], b = killB[ply];
      return (!!a && a.id === m.id && a.r === m.r && a.c === m.c) || (!!b && b.id === m.id && b.r === m.r && b.c === m.c);
    }
    function addKiller(m, ply) {
      if (ply >= MAXPLY) return;
      const a = killA[ply];
      if (a && a.id === m.id && a.r === m.r && a.c === m.c) return;
      killB[ply] = a; killA[ply] = { id: m.id, r: m.r, c: m.c };
    }
    // 综合排序键
    function orderScore(m, ply, ttBest) {
      let s = m.h;
      if (ttBest && m.id === ttBest.id && m.r === ttBest.r && m.c === ttBest.c) s += 1e9;
      if (isKiller(m, ply)) s += 5000;
      s += hist[m.id * SQ + m.r * N + m.c] * 0.001;
      return s;
    }

    // 静态搜索
    function quiesce(rootSide, alpha, beta, qd) {
      if (timeUp()) throw TIMEOUT;
      const st = engine.state;
      if (st.over) return evaluate(rootSide);
      const standPat = evaluate(rootSide);
      const toMove = st.turn;
      const maximizing = (toMove === rootSide);
      if (maximizing) { if (standPat >= beta) return beta; if (standPat > alpha) alpha = standPat; }
      else { if (standPat <= alpha) return alpha; if (standPat < beta) beta = standPat; }
      if (qd <= 0) return standPat;
      let caps = genMoves(toMove).filter(m => m.cap >= 0);
      if (caps.length === 0) return standPat;
      caps.sort((a, b) => b.h - a.h);
      if (caps.length > 6) caps = caps.slice(0, 6);
      const snap = engine.snapshot();
      for (let i = 0; i < caps.length; i++) {
        const m = caps[i]; nodes++;
        let res; try { res = engine.move(m.id, m.r, m.c); posTick++; } catch (_) { res = { ok: false }; }
        if (!res || !res.ok) { engine.restore(snap); posTick++; continue; }
        let val; try { val = quiesce(rootSide, alpha, beta, qd - 1); } finally { engine.restore(snap); posTick++; }
        if (maximizing) { if (val > alpha) alpha = val; if (alpha >= beta) break; }
        else { if (val < beta) beta = val; if (beta <= alpha) break; }
      }
      return maximizing ? alpha : beta;
    }

    function minimax(rootSide, depth, alpha, beta, beam, ply) {
      if (timeUp()) throw TIMEOUT;
      const st = engine.state;
      if (st.over) return evaluate(rootSide);
      if (depth <= 0) return quiesce(rootSide, alpha, beta, QDEPTH);

      computeHash();
      const alpha0 = alpha, beta0 = beta;
      const hit = ttProbe(depth, alpha, beta);   // 同时置好
      if (hit !== null) return hit;
      const ttBest = ttMove;

      const toMove = st.turn;
      const maximizing = (toMove === rootSide);
      let moves = genMoves(toMove);
      if (moves.length === 0) return evaluate(rootSide);   // 无子可动
      // 综合排序(TT最佳着/杀手/历史)
      moves.sort((a, b) => orderScore(b, ply, ttBest) - orderScore(a, ply, ttBest));
      if (moves.length > beam) moves = moves.slice(0, beam);

      const snap = engine.snapshot();
      let best = maximizing ? -Infinity : Infinity;
      let bestMove = null;
      for (let i = 0; i < moves.length; i++) {
        const m = moves[i];
        nodes++;
        let res; try { res = engine.move(m.id, m.r, m.c); posTick++; } catch (_) { res = { ok: false }; }
        if (!res || !res.ok) { engine.restore(snap); posTick++; continue; }
        let val;
        try { val = minimax(rootSide, depth - 1, alpha, beta, beam, ply + 1); }
        finally { engine.restore(snap); posTick++; }
        if (maximizing) {
          if (val > best) { best = val; bestMove = m; }
          if (best > alpha) alpha = best;
        } else {
          if (val < best) { best = val; bestMove = m; }
          if (best < beta) beta = best;
        }
        if (beta <= alpha) {                 // 剪枝 → 记杀手/历史
          if (m.cap < 0) { addKiller(m, ply); histBump(m, depth); }
          break;
        }
      }
      if (best === Infinity || best === -Infinity) return evaluate(rootSide);
      // 存 TT
      computeHash();   // 循环内 restore 已复位局面，哈希回归
      let flag = 1;
      if (best <= alpha0) flag = 3;          // fail-low → 上界
      else if (best >= beta0) flag = 2;      // fail-high → 下界
      ttStore(depth, best, flag, bestMove);
      return best;
    }

    /* ---------- 行棋风格 ---------- */
    function styleScore(p, m, cfg) {
      let s = 0;
      const dr = Math.abs(m.r - p.r), dc = Math.abs(m.c - p.c);
      if (dr > 0 && dc > 0) s += 2;                 // 走斜线/非直线
      let contact = 0;
      for (let rr = m.r - 1; rr <= m.r + 1; rr++) for (let cc = m.c - 1; cc <= m.c + 1; cc++) {
        if (rr < 0 || rr >= N || cc < 0 || cc >= N) continue;
        const q = engine.pieceAt(rr, cc);
        if (q && q.side !== p.side && q.alive) contact++;
      }
      s += contact * 1.1;
      s += Math.abs(m.c - 14.5) * 0.05;
      s *= cfg.wild;
      s += Math.random() * cfg.wild * 3;             // 随机扰动
      return s;
    }
    function pickStyled(scored, cfg) {
      if (scored.length === 0) return null;
      const top = scored[0].val;
      if (Math.abs(top) >= WIN * 0.5) return scored[0].move;
      const band = [];
      for (let i = 0; i < scored.length; i++) {
        if (top - scored[i].val <= cfg.styleEps) band.push(scored[i]);
        else break;
      }
      if (band.length <= 1 || cfg.styleEps <= 0) return scored[0].move;
      let bestMove = band[0].move, bestS = -Infinity;
      for (let i = 0; i < band.length; i++) {
        const mv = band[i].move;
        const p = engine.pieces[mv.id];
        const ss = styleScore(p, mv, cfg) + (band[i].val - top) * 0.02; // 轻微偏向更高分
        if (ss > bestS) { bestS = ss; bestMove = mv; }
      }
      return bestMove;
    }

    /* ---------- 安全回退 ---------- */
    function anyLegal(s) {
      const list = aliveList(s);
      // 轻度随机遍历
      const order = list.map((_, i) => i);
      for (let i = order.length - 1; i > 0; i--) { const j = (Math.random() * (i + 1)) | 0; const t = order[i]; order[i] = order[j]; order[j] = t; }
      for (let k = 0; k < order.length; k++) {
        const p = list[order[k]];
        let act; try { act = engine.canAct(p); } catch (_) { act = false; }
        if (!act) continue;
        let mvs; try { mvs = engine.legalMoves(p); } catch (_) { mvs = []; }
        if (mvs.length) { const m = mvs[(Math.random() * mvs.length) | 0]; return { id: p.id, r: m.r, c: m.c }; }
      }
      return null;
    }

    /* ---------- 决策：计算---------- */
    let lastInfo = { depth: 0, nodes: 0, ms: 0, moves: 0 };   // 诊断：最近一次决策达到的深度/节点/耗时
    function decide(forSide) {
      const s = (forSide === "red" || forSide === "blue") ? forSide : side;
      const _t0 = nowMs();
      try { ingest(); } catch (_) {}
      const cfg = PRESET[difficulty];

      // 3 级：纯随机合法着法
      if (cfg.random) { const r = anyLegal(s); lastInfo = { depth: 0, nodes: 0, ms: nowMs() - _t0, moves: 0 }; return r; }

      // 生成根着法
      let rootMoves = genMoves(s);
      if (rootMoves.length === 0) return anyLegal(s);

      const rootSnap = engine.snapshot();
      posTick++;   // 搜索前使局面缓存失效
      let chosen = null;
      try {
        const hasCapture = rootMoves.some(m => m.cap >= 0);
        const endgame = totalAlive() <= 20;
        const royalDown = !royalAlive("red") || !royalAlive("blue");
        const complex = hasCapture || endgame || royalDown;
        deadline = _t0 + (complex ? cfg.budgetMsComplex : cfg.budgetMs) - 12;
        nodeBudget = cfg.nodeCap;
        nodes = 0;
        // 每次决策复位搜索表
        ttClear(); killClear(); histClear();
        let ordered = orderAndBeam(rootMoves, cfg.beam);
        let completed = null;   // 最近一次评分表
        if (cfg.greedy) {
          // 2 级：一层贪心
          const scored = [];
          const snap = engine.snapshot();
          for (let i = 0; i < ordered.length; i++) {
            if (timeUp()) break;
            const m = ordered[i]; nodes++;
            let res; try { res = engine.move(m.id, m.r, m.c); posTick++; } catch (_) { res = { ok: false }; }
            if (!res || !res.ok) { engine.restore(snap); posTick++; continue; }
            const v = evaluate(s);
            engine.restore(snap); posTick++;
            scored.push({ move: m, val: v });
          }
          scored.sort((a, b) => b.val - a.val);
          completed = scored;
        } else {
          // 0/1 级：迭代加深 αβ
          for (let di = 0; di < cfg.depths.length; di++) {
            const depth = cfg.depths[di];
            const scored = [];
            const snap = engine.snapshot();
            let aborted = false;
            for (let i = 0; i < ordered.length; i++) {
              const m = ordered[i]; nodes++;
              let res; try { res = engine.move(m.id, m.r, m.c); posTick++; } catch (_) { res = { ok: false }; }
              if (!res || !res.ok) { engine.restore(snap); posTick++; continue; }
              let v;
              try { v = minimax(s, depth - 1, -Infinity, Infinity, cfg.beam, 1); }
              catch (e) { engine.restore(snap); posTick++; if (e === TIMEOUT) { aborted = true; break; } throw e; }
              engine.restore(snap); posTick++;
              scored.push({ move: m, val: v });
            }
            if (scored.length) {
              scored.sort((a, b) => b.val - a.val);
              if (!aborted) {
                completed = scored;
                lastInfo.depth = depth;                 // 记录
                const bid = scored[0].move;
                ordered.sort((x, y) => (x === bid ? -1 : y === bid ? 1 : 0));
              } else if (!completed) {
                completed = scored;   
              }
            }
            if (aborted || timeUp()) break;
          }
        }

        lastInfo.nodes = nodes; lastInfo.moves = rootMoves.length; lastInfo.ms = nowMs() - _t0;
        chosen = completed ? pickStyled(completed, cfg) : null;
      } catch (_) {
        chosen = null;
      } finally {
        engine.restore(rootSnap); posTick++;   // 复原实况
      }

      if (!chosen) chosen = anyLegal(s);   // 兜底
      return chosen;
    }

    /* ---------- 执行 ---------- */
    function act(forSide) {
      const s = (forSide === "red" || forSide === "blue") ? forSide : side;
      if (engine.state.over) return null;
      if (engine.state.turn !== s) return null;   // 不是该方回合，不动
      const mv = decide(s);
      if (!mv) return null;
      let res; try { res = engine.move(mv.id, mv.r, mv.c); } catch (_) { res = null; }
      if (!res || !res.ok) {                       //再取一个合法着法
        const fb = anyLegal(s);
        if (fb) { try { res = engine.move(fb.id, fb.r, fb.c); } catch (_) { res = null; } return res && res.ok ? fb : null; }
        return null;
      }
      try { ingest(); } catch (_) {}               // 落子后更新历史观测基线
      return mv;
    }

    /* ---------- 运行时配置 ---------- */
    function setSide(s) { if (s === "red" || s === "blue") side = s; return side; }
    function setDifficulty(d) { difficulty = clampDiff(d); return difficulty; }

    return {
      decide, act,
      observe: function () { try { ingest(); } catch (_) {} },   // 每步后调用以喂历史
      takeOver: function (s) { return setSide(s); },             // 接管任意一方后续行棋
      setSide, setDifficulty,
      getInfo: function () { return lastInfo; },                 // 诊断：最近决策的深度/节点/耗时
      getSide: function () { return side; },
      getDifficulty: function () { return difficulty; },
      difficultyName: function () { return PRESET[difficulty].name; },
      resetHistory: function () { heat.red.fill(0); heat.blue.fill(0); activity.red = 0; activity.blue = 0; lastSeen = null; },
    };
  }

  const api = { create, PRESET };
  if (typeof module !== "undefined" && module.exports) module.exports = api;
  else root.AI = api;
})(typeof window !== "undefined" ? window : globalThis);
