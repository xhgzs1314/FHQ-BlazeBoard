(function (root) {
  "use strict";
  var N = 30;
  // 单方各类上限
  var CAP = { K: 1, Q: 1, P: 1, D: 4, G: 6, S: 8, W: 20 };
  var PAL = [
    { letter: "K", name: "母棋", type: "mother" },
    { letter: "P", name: "子棋", type: "prince" },
    { letter: "Q", name: "继位母", type: "mother" },
    { letter: "G", name: "军棋", type: "general" },
    { letter: "S", name: "探棋", type: "scout" },
    { letter: "D", name: "盾棋", type: "shield" },
    { letter: "W", name: "白板", type: "pawn" },
    { letter: "", name: "橡皮", type: null },
  ];

  function createEditor(o) {
    var el = o.el, palEl = o.palEl, tallyEl = o.tallyEl;
    var grid = new Array(N * N).fill(null);      
    var side = "red", letter = "K";
    var cells = [], pieceEls = [];

    /* ---------- 建格 ---------- */
    (function build() {
      el.innerHTML = "";
      var frag = document.createDocumentFragment();
      for (var r = 0; r < N; r++) {
        cells[r] = []; pieceEls[r] = [];
        for (var c = 0; c < N; c++) {
          var cell = document.createElement("div");
          cell.className = root.Engine.classesFor(r, c).join(" ");
          cell.dataset.r = r; cell.dataset.c = c;
          var pe = document.createElement("div");
          pe.className = "piece"; pe.style.display = "none";
          cell.appendChild(pe);
          cells[r][c] = cell; pieceEls[r][c] = pe;
          frag.appendChild(cell);
        }
      }
      el.appendChild(frag);
    })();

    function coordOf(ev) {
      var t = ev.target;
      while (t && t !== el && !(t.dataset && t.dataset.r !== undefined)) t = t.parentNode;
      if (!t || t === el) return null;
      return { r: +t.dataset.r, c: +t.dataset.c };
    }
    el.addEventListener("click", function (ev) {
      var p = coordOf(ev); if (!p) return;
      if (!letter) remove(p.r, p.c); else place(p.r, p.c);
      sync();
    });
    el.addEventListener("contextmenu", function (ev) {
      var p = coordOf(ev); if (!p) return;
      ev.preventDefault();
      remove(p.r, p.c); sync();
    });

    /* ---------- 摆子 ---------- */
    function tally(s) {
      var t = { total: 0 };
      for (var i = 0; i < grid.length; i++) {
        var g = grid[i];
        if (!g || (s && g.side !== s)) continue;
        t[g.letter] = (t[g.letter] || 0) + 1; t.total++;
      }
      return t;
    }
    // 放不下就说清为什么
    function why(s, L, k) {
      var t = tally(s), old = grid[k];
      if (old && old.side === s && old.letter === L) return null;      // 原地同子 = 无变化
      var minus = (old && old.side === s) ? old.letter : null;
      var n = (t[L] || 0) - (minus === L ? 1 : 0);
      if (n >= CAP[L]) return L + " 最多 " + CAP[L] + " 枚";
      var total = t.total - (minus ? 1 : 0);
      if (total >= 20) return "单方最多 20 枚棋子";
      var K = (t.K || 0) - (minus === "K" ? 1 : 0);
      var Q = (t.Q || 0) - (minus === "Q" ? 1 : 0);
      var P = (t.P || 0) - (minus === "P" ? 1 : 0);
      if ((L === "K" || L === "Q") && K + Q >= 1) return "母棋与继位母棋只能有一枚";
      if (L === "Q" && P >= 1) return "已有子棋，不能再放继位母棋（继位意味着子棋已上位）";
      if (L === "P" && Q >= 1) return "已继位，不能再放子棋";
      return null;
    }
    function place(r, c) {
      var k = r * N + c, msg = why(side, letter, k);
      if (msg) { if (o.onReject) o.onReject(msg); return false; }
      grid[k] = { side: side, letter: letter };
      paint(r, c);
      return true;
    }
    function remove(r, c) {
      var k = r * N + c;
      if (!grid[k]) return false;
      grid[k] = null; paint(r, c);
      return true;
    }
    function clear() {
      for (var i = 0; i < grid.length; i++) if (grid[i]) { grid[i] = null; paint((i - i % N) / N, i % N); }
      sync();
    }

    /* ---------- 画 ---------- */
    function paint(r, c) {
      var g = grid[r * N + c], pe = pieceEls[r][c];
      if (!g) { pe.style.display = "none"; pe.innerHTML = ""; return; }
      var spec = PAL.filter(function (x) { return x.letter === g.letter; })[0];
      pe.style.display = "";
      pe.innerHTML = root.Engine.pieceSVG(spec.type, g.side);
    }
    function repaintAll() {
      for (var r = 0; r < N; r++) for (var c = 0; c < N; c++) paint(r, c);
    }

    
    var palBtns = [];
    function buildPal() {
      palEl.innerHTML = "";
      palBtns = [];
      PAL.forEach(function (x) {
        var b = document.createElement("button");
        b.type = "button";
        b.dataset.letter = x.letter;
        var icon = document.createElement("span");
        icon.className = "ico";
        var name = document.createElement("span");
        name.textContent = x.name;
        var cnt = document.createElement("span");
        cnt.className = "cnt";
        b.appendChild(icon); b.appendChild(name); b.appendChild(cnt);
        b.addEventListener("click", function () { setLetter(x.letter); });
        palEl.appendChild(b);
        palBtns.push({ btn: b, icon: icon, cnt: cnt, letter: x.letter, type: x.type });
      });
    }
    function syncPal() {
      var t = tally(side);
      palBtns.forEach(function (x) {
        x.btn.classList.toggle("on", x.letter === letter);
        // 图标随执子方换色
        var wantIco = x.letter ? (x.type + ":" + side) : "eraser";
        if (x.icon.dataset.ico !== wantIco) {
          x.icon.dataset.ico = wantIco;
          x.icon.innerHTML = x.letter ? root.Engine.pieceSVG(x.type, side)
            : "<span style='font-size:22px'>⌫</span>";
        }
        if (!x.letter) { x.cnt.textContent = "右键亦可"; x.btn.classList.remove("full"); return; }
        var n = t[x.letter] || 0;
        x.cnt.textContent = n + " / " + CAP[x.letter];
        x.btn.classList.toggle("full", n >= CAP[x.letter]);
      });
    }
    function syncTally() {
      if (!tallyEl) return;
      var R = tally("red"), B = tally("blue");
      tallyEl.innerHTML = "红方 <b>" + R.total + "</b> / 20　蓝方 <b>" + B.total + "</b> / 20　" +
        "全场 <b>" + (R.total + B.total) + "</b>" +
        "<br>" + (R.total + B.total <= 20 ? "全场 ≤20：<b>迷雾解除 + 全体自由行动</b>" : "全场 >20：迷雾与许可区照常生效");
    }
    function sync() {
      syncPal(); syncTally();
      if (o.onChange) o.onChange();
    }

    function setSide(s) { side = s === "blue" ? "blue" : "red"; sync(); }
    function setLetter(L) { letter = L || ""; sync(); }

    /* ---------- 载入局面 ---------- */
    function load(list, opt) {
      for (var i = 0; i < grid.length; i++) grid[i] = null;
      (list || []).forEach(function (b) {
        grid[b.r * N + b.c] = { side: b.side, letter: String(b.letter).toUpperCase() };
      });
      repaintAll(); sync();
    }
    function list() {
      var out = [];
      for (var i = 0; i < grid.length; i++) {
        var g = grid[i];
        if (g) out.push({ side: g.side, letter: g.letter, r: (i - i % N) / N, c: i % N });
      }
      return out;
    }

    buildPal(); sync();
    return {
      setSide: setSide, setLetter: setLetter, clear: clear, load: load, list: list,
      tally: tally, place: place, remove: remove,
      get side() { return side; },
      get letter() { return letter; },
    };
  }

  var api = { createEditor: createEditor, CAP: CAP, PAL: PAL };
  if (typeof module !== "undefined" && module.exports) module.exports = api;
  else root.Editor = api;
})(typeof window !== "undefined" ? window : globalThis);
