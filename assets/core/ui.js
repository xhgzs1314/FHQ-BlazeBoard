(function (root) {
  "use strict";
  const E = (typeof module !== "undefined" && module.exports) ? require("./engine.js") : root.Engine;
  const { N, TYPES, isRiver, classesFor, pieceSVG } = E;
  const MusicManager = (function () {
    let audio = null;
    let enabled = true;
    const MUSIC_URL = '/assets/music/music.mp3';

    function loadSetting() {
      try {
        const saved = localStorage.getItem('fhq_musicToggle');
        if (saved !== null) {
          enabled = saved === 'true';
        } else {
          enabled = true;
        }
      } catch (_) {
        enabled = true;
      }
      return enabled;
    }

    function createAudio() {
      if (!audio) {
        audio = new Audio(MUSIC_URL);
        audio.loop = true;
        audio.volume = 0.4;
      }
      return audio;
    }

    return {
      isEnabled: function () {
        loadSetting();
        return enabled;
      },
      play: function () {
        if (!this.isEnabled()) return;
        try {
          const a = createAudio();
          if (!a.paused) return;
          a.play().catch(function (e) {
          });
        } catch (e) {
          console.warn('[音乐] 播放失败:', e);
        }
      },
      pause: function () {
        try {
          if (audio && !audio.paused) {
            audio.pause();
          }
        } catch (_) { }
      },
      stop: function () {
        try {
          if (audio) {
            audio.pause();
            audio.currentTime = 0;
          }
        } catch (_) { }
      },
      setEnabled: function (val) {
        enabled = val;
        if (!enabled) {
          this.pause();
        } else {
          if (audio && audio.paused) {
            audio.play().catch(function (e) {
              console.log('[音乐] 需要用户交互');
            });
          }
        }
      },
      unlock: function () {
        if (!this.isEnabled()) return;
        try {
          const a = createAudio();
          if (a.paused) {
            a.play().catch(function () { });
          }
        } catch (_) { }
      },
      isPlaying: function () {
        return audio ? !audio.paused : false;
      }
    };
  })();
  function createUI(opts) {
    const engine = opts.engine;
    const board = document.getElementById("board");
    let cells = [];
    let selected = null;           // UI 本地选中的 pieceId
    const BASE_CLS = new Array(N * N);
    let pieceEls = [], intelEls = [], sig = [];
    const svgCache = {};
    function svgFor(type, side) {
      const k = type + "|" + side;
      return svgCache[k] || (svgCache[k] = pieceSVG(type, side));
    }

    /* ---------- 音效：WebAudio 合成 ---------- */
    const Sound = (function () {
      let ctx = null, enabled = true;
      function ac() {
        if (typeof window === "undefined") return null;
        if (!ctx) { const AC = window.AudioContext || window.webkitAudioContext; if (AC) ctx = new AC(); }
        if (ctx && ctx.state === "suspended") ctx.resume();
        return ctx;
      }
      // 单音：频率滑行 + 短促包络，避免爆音
      function tone(freq, dur, type, gain, whenOff, slideTo) {
        const c = ac(); if (!c) return;
        const t0 = c.currentTime + (whenOff || 0);
        const osc = c.createOscillator(), g = c.createGain();
        osc.type = type || "sine";
        osc.frequency.setValueAtTime(freq, t0);
        if (slideTo) osc.frequency.exponentialRampToValueAtTime(slideTo, t0 + dur);
        const peak = gain == null ? 0.14 : gain;
        g.gain.setValueAtTime(0.0001, t0);
        g.gain.exponentialRampToValueAtTime(peak, t0 + 0.008);
        g.gain.exponentialRampToValueAtTime(0.0001, t0 + dur);
        osc.connect(g); g.connect(c.destination);
        osc.start(t0); osc.stop(t0 + dur + 0.02);
      }
      return {
        unlock() { if (enabled) ac(); },
        move() { tone(320, 0.09, "triangle", 0.35, 0, 500); },                         // 轻脆"哒"
        capture() { tone(200, 0.12, "sawtooth", 0.18, 0, 90); tone(520, 0.10, "square", 0.10, 0.02); }, // 厚重"咚"+叮
        win() { [523, 659, 784, 1047].forEach((f, i) => tone(f, 0.22, "triangle", 0.14, i * 0.11)); },  // 上行大三和弦
        lose() { [392, 330, 262, 196].forEach((f, i) => tone(f, 0.26, "sine", 0.14, i * 0.12)); },      // 下行叹息
        musicUnlock: function () { MusicManager.unlock(); }
      };
    })();
    function startMusic() {
      setTimeout(function () {
        Sound.musicUnlock();
        if (MusicManager.isEnabled() && !MusicManager.isPlaying()) {
          MusicManager.play();
        }
      }, 300);
    }
    /* ---------- 动画样式 ---------- */
    (function ensureAnimStyle() {
      if (typeof document === "undefined" || document.getElementById("uiAnimStyle")) return;
      const st = document.createElement("style");
      st.id = "uiAnimStyle";
      st.textContent = `
        #board .move-ghost { position:absolute; z-index:8; pointer-events:none;
          transition: transform .18s cubic-bezier(.4,.05,.2,1); filter: drop-shadow(0 2px 4px rgba(0,0,0,.6)); }
        #board .move-ghost svg { width:100%; height:100%; display:block; }
        .eat-burst { position:fixed; left:50%; top:50%; z-index:9999; pointer-events:none;
          transform:translate(-50%,-50%) scale(.4); opacity:0;
          font-weight:900; color:#ffd24a; font-family:"Microsoft YaHei","PingFang SC",sans-serif;
          text-shadow:0 0 18px rgba(255,120,60,.9), 0 4px 10px rgba(0,0,0,.7);
          animation: eatBurst .7s cubic-bezier(.2,.7,.3,1) forwards; }
        @keyframes eatBurst {
          0% { transform:translate(-50%,-50%) scale(.3) rotate(-12deg); opacity:0; }
          25% { transform:translate(-50%,-50%) scale(1.25) rotate(3deg); opacity:1; }
          60% { transform:translate(-50%,-50%) scale(1) rotate(0); opacity:1; }
          100% { transform:translate(-50%,-50%) scale(1.15); opacity:0; } }
        #board .fog-veil { position:absolute; inset:0; z-index:6; pointer-events:none;
          background: radial-gradient(circle at 50% 50%, rgba(6,16,30,.10), rgba(4,10,20,.34));
          opacity:0; transition: opacity .5s ease; }
        #board .fog-veil.on { opacity:1; }
      `;
      document.head.appendChild(st);
    })();

    function buildBoard() {
      board.innerHTML = "";
      cells = []; pieceEls = []; intelEls = []; sig = new Array(N * N).fill("");
      const frag = document.createDocumentFragment();
      for (let r = 0; r < N; r++) {
        cells[r] = []; pieceEls[r] = []; intelEls[r] = [];
        for (let c = 0; c < N; c++) {
          const cell = document.createElement("div");
          const base = classesFor(r, c).join(" ");
          BASE_CLS[r * N + c] = base;
          cell.className = base;
          cell.dataset.r = r; cell.dataset.c = c;
          if (isRiver(r, c) && r === 14 && (c % 8 === 0)) {
            const lb = document.createElement("span");
            lb.className = "river-label"; lb.textContent = "≈河≈";
            cell.appendChild(lb);
          }
          // 持久化的棋子层 + 情报点
          const pe = document.createElement("div");
          pe.className = "piece"; pe.style.display = "none";
          const ie = document.createElement("span");
          ie.className = "intel"; ie.style.display = "none";
          ie.title = `已知敌方探棋落点 (${c},${r})`;
          cell.appendChild(pe); cell.appendChild(ie);
          pieceEls[r][c] = pe; intelEls[r][c] = ie;
          cells[r][c] = cell;
          frag.appendChild(cell);
        }
      }
      board.appendChild(frag);
      startMusic();
    }

    // 预计算「当前视角可见格集合」
    function buildVisset(viewer, state) {
      if (viewer === "both") return null;
      if (state.over || engine.fogOffFor(viewer)) return null;   // null = 全可见
      const vis = new Set();
      const homeLo = viewer === "red" ? 0 : 17, homeHi = viewer === "red" ? 12 : 29;
      for (let r = homeLo; r <= homeHi; r++) for (let c = 0; c < N; c++) vis.add(r * N + c);
      for (const p of engine.pieces) {
        if (!p.alive || p.side !== viewer || p.type !== "scout") continue;
        for (let dr = -1; dr <= 0; dr++) for (let dc = -1; dc <= 0; dc++) {
          const rr = p.r + dr, cc = p.c + dc;
          if (rr >= 0 && rr < N && cc >= 0 && cc < N) vis.add(rr * N + cc);
        }
      }
      return vis;
    }

    /* ---------- 动画驱动 ---------- */
    let prevPieces = null;   
    let prevOver = false;
    let prevFogged = null;     // 上一帧本视角是否处于迷雾

    function snapshotPieces() {
      return engine.pieces.map(p => ({ alive: p.alive, r: p.r, c: p.c, type: p.type, side: p.side, hidden: !!p.hidden }));
    }
    // 绘制“吃”字
    function eatBurst() {
      if (typeof document === "undefined") return;
      const el = document.createElement("div");
      el.className = "eat-burst";
      el.textContent = "吃";
      el.style.fontSize = "min(22vmin,180px)";
      document.body.appendChild(el);
      setTimeout(() => el.remove(), 750);
    }
    // 平滑滑行
    function slidePiece(p, fromR, fromC) {
      const destCell = cells[p.r] && cells[p.r][p.c];
      const srcCell = cells[fromR] && cells[fromR][fromC];
      if (!destCell || !srcCell) return;
      const ghost = document.createElement("div");
      ghost.className = "move-ghost";
      ghost.style.left = destCell.offsetLeft + "px";
      ghost.style.top = destCell.offsetTop + "px";
      ghost.style.width = destCell.offsetWidth + "px";
      ghost.style.height = destCell.offsetHeight + "px";
      ghost.innerHTML = svgFor(p.type, p.side);
      const dx = srcCell.offsetLeft - destCell.offsetLeft;
      const dy = srcCell.offsetTop - destCell.offsetTop;
      ghost.style.transform = `translate(${dx}px,${dy}px)`;
      board.appendChild(ghost);
      const destPe = pieceEls[p.r][p.c];
      const prevVis = destPe.style.visibility;
      destPe.style.visibility = "hidden";                 // 滑行期间隐藏落点真身，避免双影
      requestAnimationFrame(() => { ghost.style.transform = "translate(0,0)"; });
      setTimeout(() => { ghost.remove(); destPe.style.visibility = prevVis || ""; }, 210);
    }

    // 滑行/吃子/迷雾/胜负音画
    function runAnimations(visset) {
      const cur = engine.pieces;
      const over = engine.state.over;
      if (prevPieces) {
        let moves = [], captured = 0;
        for (let i = 0; i < cur.length && i < prevPieces.length; i++) {
          const a = prevPieces[i], b = cur[i];
          if (a.hidden || b.hidden) continue;          
          if (a.alive && !b.alive) captured++;
          else if (a.alive && b.alive && (a.r !== b.r || a.c !== b.c)) moves.push({ i, fromR: a.r, fromC: a.c });
        }
        if (moves.length + captured <= 3) {
          const seen = (r, c) => visset === null || visset.has(r * N + c);
          if (moves.length === 1) {
            const m = moves[0], p = cur[m.i];
            if (seen(m.fromR, m.fromC) && seen(p.r, p.c)) slidePiece(p, m.fromR, m.fromC);
          }
          if (captured > 0) { eatBurst(); Sound.capture(); }
          else if (moves.length >= 1) Sound.move();
        }
      }
      // 胜负音效
      if (over && !prevOver) {
        if (engine.state.winner) {
          const me = opts.getViewer();
          if (engine.state.winner === me) Sound.win(); else Sound.lose();
        }
      }
      // 迷雾开合
      const foggedNow = visset !== null;
      if (prevFogged !== null && prevFogged !== foggedNow) {
        let veil = board.querySelector(".fog-veil");
        if (!veil) { veil = document.createElement("div"); veil.className = "fog-veil"; board.appendChild(veil); }
        veil.classList.toggle("on", foggedNow);
      }
      prevFogged = foggedNow;
      prevOver = over;
      prevPieces = snapshotPieces();
    }

    function render() {
      const state = engine.state;
      const sel = selected != null ? engine.pieces[selected] : null;
      const targets = sel && sel.alive ? engine.legalMoves(sel) : [];
      const moveSet = new Set(targets.filter(t => !t.capture).map(t => t.r * N + t.c));
      const capSet = new Set(targets.filter(t => t.capture).map(t => t.r * N + t.c));
      const viewer = opts.getViewer();
      const permit = state.permit[viewer] || null;
      const revealed = state.revealed[viewer] || new Set();
      const visset = buildVisset(viewer, state);   // null=全可见
      for (let r = 0; r < N; r++) {
        for (let c = 0; c < N; c++) {
          const key = r * N + c;
          const seen = visset === null || visset.has(key);
          const inPermit = permit && r >= permit.r0 && r <= permit.r1 && c >= permit.c0 && c <= permit.c1;
          const hasIntel = revealed.has(key);
          const p = engine.pieceAt(r, c);
          const hide = p && p.side !== viewer && !seen;
          const showP = p && !hide;
          const actable = showP && !state.over && p.side === state.turn && engine.canAct(p) && opts.canInteract(p);
          const isSel = sel && sel.alive && sel.r === r && sel.c === c;
          const isMove = moveSet.has(key);
          const isCap = capSet.has(key);
          const pkey = showP ? (p.type + p.side + (p.promoted ? "P" : "") + (p.revived ? "R" : "")) : "";
          const s = `${pkey}|${seen ? 0 : 1}${inPermit ? 1 : 0}${hasIntel ? 1 : 0}${isSel ? 1 : 0}${isMove ? 1 : 0}${isCap ? 1 : 0}${actable ? 1 : 0}`;
          if (s === sig[key]) continue;
          sig[key] = s;

          let cls = BASE_CLS[key];
          if (!seen) cls += " fogged";
          if (inPermit) cls += " permit";
          if (showP) cls += actable ? " movable" : " inactive";
          if (isSel) cls += " selected";
          if (isMove) cls += " target";
          if (isCap) cls += " capture";
          cells[r][c].className = cls;

          const pe = pieceEls[r][c];
          if (showP) {
            const cn = "piece" + (p.promoted ? " promoted" : "") + (p.revived ? " revived" : "");
            if (pe._t !== pkey) { pe.innerHTML = svgFor(p.type, p.side); pe._t = pkey; }
            if (pe.className !== cn) pe.className = cn;
            pe.title = `${p.side === "red" ? "红" : "蓝"}·${TYPES[p.type].label}` +
              (p.promoted ? "(继位)" : "") + (p.revived ? "(复活·降级)" : "") + ` (${c},${r})`;
            pe.style.display = "";
          } else if (pe.style.display !== "none") pe.style.display = "none";

          intelEls[r][c].style.display = hasIntel ? "" : "none";
        }
      }
      renderHUD();
      runAnimations(visset);   // 驱动音画
    }

    function sideCN(s) { return s === "red" ? "红方" : "蓝方"; }
    let lastLog = "";

    function renderHUD() {
      const state = engine.state;
      const t = state.turn, red = t === "red";
      const badge = document.getElementById("turnBadge");
      if (badge) {
        badge.innerHTML = `<span class="turn-dot"></span>${sideCN(t)}回合 · 第 ${state.round} 回合`;
        badge.style.background = red ? "rgba(230,70,75,.16)" : "rgba(70,130,235,.18)";
        badge.style.color = red ? "#ff9b9e" : "#9cc4ff";
        badge.style.borderColor = red ? "rgba(255,110,110,.5)" : "rgba(120,180,255,.55)";
        badge.style.boxShadow = red ? "0 0 16px rgba(255,80,85,.3)" : "0 0 16px rgba(80,150,255,.35)";
        const dot = badge.querySelector(".turn-dot");
        if (dot) { dot.style.background = red ? "#ff5d62" : "#5aa0ff"; dot.style.boxShadow = `0 0 10px ${red ? "#ff5d62" : "#5aa0ff"}`; }
      }
      const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
      set("redCount", engine.pieces.filter(p => p.alive && p.side === "red").length);
      set("blueCount", engine.pieces.filter(p => p.alive && p.side === "blue").length);
      const gsR = document.getElementById("genStepR"), gsB = document.getElementById("genStepB");
      // 军棋步长 = 基础3 + 加成
      const bonus = s => state.lostRoyal[s] ? 0 : Math.floor(engine.pieces.filter(p => !p.alive && p.side === (s === "red" ? "blue" : "red") && p.type === "general").length / 2);
      if (gsR) gsR.textContent = 3 + bonus("red");
      if (gsB) gsB.textContent = 3 + bonus("blue");

      const total = engine.pieces.filter(p => p.alive).length;
      const core = document.getElementById("coreState");
      if (core) { const on = total > 26; core.textContent = on ? "生效中" : "已解除"; core.style.color = on ? "#ff9b9e" : "#7ee6a2"; }
      const fog = document.getElementById("fogThreshState");
      const threshOff = total <= 20;
      if (fog) { fog.textContent = threshOff ? "已触发" : "待触发"; fog.style.color = threshOff ? "#7ee6a2" : "#c7a1ff"; }
      const cleared = s => threshOff || state.fogCleared[s];
      const sides = document.getElementById("fogSides");
      if (sides) sides.innerHTML = `<span class="fog-lamp"></span>红方${cleared("red") ? "解除" : "迷雾"}　蓝方${cleared("blue") ? "解除" : "迷雾"}`;
      const bothClear = cleared("red") && cleared("blue");
      const fs = document.getElementById("fogState");
      if (fs) { fs.textContent = bothClear ? "迷雾解除" : "迷雾开启"; fs.style.color = bothClear ? "#7ee6a2" : "#c7a1ff"; }
      const sw = document.getElementById("fogSwitch"); if (sw) sw.classList.toggle("off", bothClear);
      const ul = document.getElementById("logList");
      const mask = opts.maskLog;
      if (ul) ul.innerHTML = state.log.map(m => `<li>${mask ? mask(m) : m}</li>`).join("");
      // 浮层
      const ov = document.getElementById("winOverlay");
      if (ov) {
        if (state.over) {
          const card = document.getElementById("winCard");
          if (card) {
            if (state.winner) { card.textContent = `${sideCN(state.winner)} 获胜`; card.className = "win-card " + state.winner; }
            else { card.textContent = "和 棋"; card.className = "win-card draw"; }   // 联机和棋：over 且无 winner
          }
          ov.classList.add("show");
        } else ov.classList.remove("show");
      }
    }

    function flashEvents(events) {
      (events || []).forEach(ev => {
        if (ev.type === "penalty" && cells[ev.r]) {
          const cell = cells[ev.r][ev.c];
          cell.classList.add("penalty");
          setTimeout(() => cell.classList.remove("penalty"), 900);
        }
      });
    }

    function clearSelection() { selected = null; }

    board.addEventListener("click", (e) => {
      Sound.unlock();          // 解锁 WebAudio
      const state = engine.state;
      if (state.over) return;
      const cell = e.target.closest(".cell");
      if (!cell) return;
      const r = +cell.dataset.r, c = +cell.dataset.c;
      const clicked = engine.pieceAt(r, c);
      // 已选中 → 若点到合法落点则落子
      if (selected != null) {
        const sel = engine.pieces[selected];
        if (sel && sel.alive && engine.legalMoves(sel).some(t => t.r === r && t.c === c)) {
          const pid = selected;
          selected = null;
          const res = opts.onMove(pid, r, c);   // 单机即时返回，联网返回 null
          if (res && res.ok) flashEvents(res.events);
          render();
          return;
        }
      }
      // 否则（重新）选中
      if (clicked && clicked.side === state.turn && engine.canAct(clicked) && opts.canInteract(clicked)) {
        selected = clicked.id;
      } else {
        selected = null;
      }
      render();
    });
    const rb = document.getElementById("restart");
    if (rb) rb.addEventListener("click", () => { selected = null; opts.onRestart(); });
    const wrb = document.getElementById("winRestart");
    if (wrb) wrb.addEventListener("click", () => { selected = null; opts.onRestart(); });
    (function buildLegend() {
      const lg = document.getElementById("legendGrid");
      if (!lg) return;
      Object.keys(TYPES).forEach((k, i) => {
        const side = i % 2 === 0 ? "red" : "blue";
        const d = document.createElement("div");
        d.className = "legend-item";
        d.innerHTML = `<span class="ic">${pieceSVG(k, side)}</span><span>${TYPES[k].label} · ${TYPES[k].en}</span>`;
        lg.appendChild(d);
      });
    })();

    buildBoard();
    render();
    return { render, flashEvents, clearSelection, buildBoard };
  }
  const settleLatch = new WeakMap();
  const settleGen = new WeakMap();     
  function panelEl(panel) {
    if (!panel) return null;
    return typeof panel === "string" ? document.getElementById(panel) : panel;
  }

  function settle(o) {
    o = o || {};
    const R = root.Rating;
    const el = panelEl(o.panel);
    const done = Promise.resolve();
    if (!R || !el) return done;
    if (!o.match) {
      if (settleLatch.has(el)) settleGen.set(el, (settleGen.get(el) || 0) + 1);
      settleLatch.delete(el);
      R.render(el, null);
      return done;
    }

    const cardSide = o.cardSide !== undefined ? o.cardSide : (o.side || null);
    const drawCard = () => R.render(el, o.match, cardSide);

    const phase = settleLatch.get(el);
    if (phase === 2) { drawCard(); return done; }            // 播完
    if (phase === 1) return done;                           // 播放中

    const FX = root.FHQScore;
    if (!FX) { settleLatch.set(el, 2); drawCard(); return done; }   // 退化

    settleLatch.set(el, 1);
    R.render(el, null);                                     // 藏卡片
    const gen = settleGen.get(el) || 0;
    const stale = () => (settleGen.get(el) || 0) !== gen;    // 丢弃
    const wait = typeof o.wait === "function" ? o.wait() : o.wait;
    const fin = () => {
      if (stale() || settleLatch.get(el) === 2) return;
      settleLatch.set(el, 2);
      drawCard();
    };
    return Promise.resolve(wait).catch(() => {}).then(() => {
      if (stale()) return;
      const fxSide = o.side || o.match.winner || "red";
      const extra = typeof o.extra === "function" ? o.extra() : o.extra;
      const userReveal = extra && extra.onReveal;
      const cfg = Object.assign({}, extra, {
        onReveal() {
          fin();
          if (typeof userReveal === "function") { try { userReveal(); } catch (e) {} }
        },
      });
      return FX.fromRating(o.match, fxSide, cfg).catch(() => {});
    }).catch(() => {}).then(fin, fin);
  }

  const api = { createUI, settle };
  if (typeof module !== "undefined" && module.exports) module.exports = api;
  else root.UI = api;
})(typeof window !== "undefined" ? window : globalThis);
