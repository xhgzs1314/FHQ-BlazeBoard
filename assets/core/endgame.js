(function () {
  "use strict";
  var STORE = "fhq.endgame.done";       // 通关记录
  var DRAFT = "fhq.endgame.draft";      // 编辑器草稿
  var OFFICIAL = Levels.LEVELS.filter(function (l) { return l.chapter >= 2; });
  var engine = Engine.createGame();
  var $ = function (id) { return document.getElementById(id); };

  function loadDone() {
    try { return JSON.parse(localStorage.getItem(STORE)) || {}; } catch (e) { return {}; }
  }
  function markDone(id) {
    if (!id) return;
    var d = loadDone(); d[id] = 1;
    try { localStorage.setItem(STORE, JSON.stringify(d)); } catch (e) {}
  }
  function msg(el, text, ok) {
    el.textContent = text || "";
    el.className = "eg-msg " + (text ? (ok ? "ok" : "bad") : "");
  }

  /* ================= 对局运行 ================= */
  var human = "red", ui = null, runner = null;
  function buildRunner(side) {
    if (runner) runner.stop();                    // 旧 runner
    human = side === "blue" ? "blue" : "red";
    runner = Play.createRunner({
      engine: engine, human: human,
      onThinking: runnerOpts.onThinking, onStart: runnerOpts.onStart,
      onGoal: runnerOpts.onGoal, onSettle: runnerOpts.onSettle,
    });
    runner.setUI(ui);
  }
  var runnerOpts = {
    onThinking: function (on) { $("aiThinking").classList.toggle("on", on); },
    onStart: function () {
      $("resOverlay").classList.add("hidden");
      $("egOverlay").classList.add("hidden");
      $("egBack").style.display = "";
    },
    onGoal: function (s, t) {
      if (!s || !t) return;
      $("goalName").textContent = t.name || "自定义残局";
      $("goalLabel").textContent = s.label;
      $("goalHint").textContent = t.hint || "";
      var pct = s.max > 0 ? Math.round((s.cur / s.max) * 100) : (s.done && s.win ? 100 : 0);
      $("goalFill").style.width = pct + "%";
      $("goalProg").textContent = s.cur + "/" + s.max;
      var lf = $("goalLeft");
      lf.textContent = s.movesLeft == null ? "不限" : String(s.movesLeft);
      lf.className = (s.movesLeft != null && s.movesLeft <= 3) ? "low" : "";
    },
    onSettle: function (s, t) {
      if (s.win) markDone(t.levelId);
      $("resCard").textContent = s.win ? "过 关" : "未 过 关";
      $("resCard").className = "res-card " + (s.win ? "win" : "lose");
      $("resWhy").textContent = s.reason || "";
      $("resSub").textContent = s.label + "　·　进度 " + s.cur + "/" + s.max + "　·　用了 " + s.movesUsed + " 步";
      var nxt = nextOfficial(t.levelId);
      $("resNext").style.display = (s.win && nxt) ? "" : "none";
      $("resOverlay").classList.remove("hidden");
    },
  };

  ui = UI.createUI({
    engine: engine,
    getViewer: function () { return human; },
    // createUI 内部会先渲染一次
    canInteract: function (p) { return !!runner && runner.canInteract(p); },
    onMove: function (id, r, c) { return runner ? runner.handleMove(id, r, c) : null; },
    onRestart: function () { if (runner) runner.retry(); },
  });
  buildRunner("red");

  function nextOfficial(id) {
    if (!id) return null;
    var i = -1;
    OFFICIAL.forEach(function (l, k) { if (l.id === id) i = k; });
    return (i >= 0 && i + 1 < OFFICIAL.length) ? OFFICIAL[i + 1] : null;
  }
  // task = { fen, goal, ai, name, hint?, levelId? }
  function startTask(t) {
    if (t.goal.side !== human) buildRunner(t.goal.side);
    try { runner.run(t); }
    catch (e) { alert("这局起不来：" + e.message); openLib(); }
  }
  function openLib() {
    if (runner) runner.stop();                    // 离开对局
    $("egBack").style.display = "none";
    $("resOverlay").classList.add("hidden");
    $("egOverlay").classList.remove("hidden");
    renderOfficial();
  }

  $("btnRetry").addEventListener("click", function () { runner.retry(); });
  $("btnPick").addEventListener("click", openLib);
  $("resRetry").addEventListener("click", function () { runner.retry(); });
  $("resPick").addEventListener("click", openLib);
  $("egBack").addEventListener("click", openLib);
  $("resNext").addEventListener("click", function () {
    var n = nextOfficial(runner.task && runner.task.levelId);
    if (n) startLevel(n); else openLib();
  });

  /* ================= 分栏切换 ================= */
  Array.prototype.forEach.call(document.querySelectorAll(".eg-tabs button"), function (b) {
    b.addEventListener("click", function () {
      Array.prototype.forEach.call(document.querySelectorAll(".eg-tabs button"), function (x) {
        x.classList.toggle("on", x === b);
      });
      Array.prototype.forEach.call(document.querySelectorAll(".eg-pane"), function (p) {
        p.classList.toggle("on", p.id === "pane-" + b.dataset.pane);
      });
    });
  });

  /* ================= 官方残局 ================= */
  function startLevel(lv) {
    startTask({
      fen: lv.fen, goal: lv.goal, ai: lv.ai,
      name: lv.name, hint: lv.hint, levelId: lv.id,
    });
  }
  function renderOfficial() {
    var done = loadDone(), html = '<div class="eg-list">';
    OFFICIAL.forEach(function (lv) {
      html += '<button class="eg-card" data-id="' + lv.id + '">' +
        '<div class="n"><span>' + lv.name + "</span>" +
        '<span class="star' + (done[lv.id] ? " done" : "") + '">' + (done[lv.id] ? "★ 已过" : "☆ 未过") + "</span></div>" +
        '<div class="g">' + Goal.describe(lv.goal) + "　·　对手：" + AI.PRESET[lv.ai].name + "<br>" + (lv.hint || "") + "</div></button>";
    });
    html += "</div>";
    if (!OFFICIAL.length) html = '<div class="eg-note">暂无官方残局</div>';
    $("egOfficial").innerHTML = html;
    Array.prototype.forEach.call($("egOfficial").querySelectorAll(".eg-card"), function (b) {
      b.addEventListener("click", function () { startLevel(Levels.byId(b.getAttribute("data-id"))); });
    });
  }

  /* ================= 难度下拉 ================= */
  function fillAI(sel, def) {
    sel.innerHTML = "";
    [0, 1, 2, 3].forEach(function (d) {
      var o = document.createElement("option");
      o.value = d; o.textContent = AI.PRESET[d].name;
      sel.appendChild(o);
    });
    sel.value = String(def == null ? 2 : def);
  }
  fillAI($("edAI"), 2);
  fillAI($("pgAI"), 2);

  var TARGETS = [
    ["general", "军棋"], ["shield", "盾棋"], ["scout", "探棋"],
    ["mother", "母棋"], ["prince", "子棋"], ["royal", "母子"], ["pawn", "白板棋"],
  ];
  TARGETS.forEach(function (t) {
    var o = document.createElement("option");
    o.value = t[0]; o.textContent = t[1];
    $("edTarget").appendChild(o);
  });

  /* ================= 制作残局 ================= */
  var ed = Editor.createEditor({
    el: $("edBoard"), palEl: $("edPal"), tallyEl: $("edTally"),
    onReject: function (m) { msg($("edMsg"), "放不下：" + m, false); },
    onChange: function () { saveDraft(); },
  });
  $("edRed").addEventListener("click", function () {
    ed.setSide("red");
    $("edRed").classList.add("on"); $("edBlue").classList.remove("on");
  });
  $("edBlue").addEventListener("click", function () {
    ed.setSide("blue");
    $("edBlue").classList.add("on"); $("edRed").classList.remove("on");
  });
  $("edGoal").addEventListener("change", function () {
    $("edSlayRow").style.display = $("edGoal").value === "slay" ? "" : "none";
    $("edRoundsRow").style.display = $("edGoal").value === "survive" ? "" : "none";
  });

  function edGoalSpec() {
    var g = { type: $("edGoal").value, side: $("edSide").value };
    if (g.type === "slay") { g.target = $("edTarget").value; g.count = Math.max(1, $("edCount").value | 0); }
    if (g.type === "survive") g.rounds = Math.max(1, $("edRounds").value | 0);
    var lim = $("edLimit").value | 0;
    if (lim > 0) g.moveLimit = lim;
    return g;
  }
  function edFEN() {
    return Puzzle.fromBoard(ed.list(), { turn: $("edTurn").value, round: Math.max(1, $("edRound").value | 0) });
  }
  function edCodeNow() {
    var fen = edFEN(), goal = edGoalSpec();
    Goal.validate(goal);
    return Puzzle.format({ name: $("edName").value, goal: goal, ai: $("edAI").value | 0, fen: fen });
  }
  $("edGen").addEventListener("click", function () {
    try {
      var code = edCodeNow();
      $("edCode").value = code;
      msg($("edMsg"), "信息码已生成（" + code.length + " 字节），可复制或存成文件。", true);
    } catch (e) { $("edCode").value = ""; msg($("edMsg"), e.message.replace(/^(FEN|Goal|Puzzle): /, ""), false); }
  });
  $("edTry").addEventListener("click", function () {
    try {
      var fen = edFEN(), goal = edGoalSpec();
      Goal.validate(goal);
      startTask({ fen: fen, goal: goal, ai: $("edAI").value | 0, name: $("edName").value || "试玩：自制残局" });
    } catch (e) { msg($("edMsg"), e.message.replace(/^(FEN|Goal|Puzzle): /, ""), false); }
  });
  $("edStd").addEventListener("click", function () {
    var b = Puzzle.toBoard(FEN.START);
    ed.load(b.list); $("edTurn").value = b.turn; $("edRound").value = b.round;
    msg($("edMsg"), "已载入标准开局，可在此基础上删子改造。", true);
  });
  $("edClear").addEventListener("click", function () {
    ed.clear(); msg($("edMsg"), "已清空。", true);
  });
  $("edCopy").addEventListener("click", function () {
    var v = $("edCode").value.trim();
    if (!v) { msg($("edMsg"), "先生成信息码。", false); return; }
    copyText(v, function (ok) { msg($("edMsg"), ok ? "已复制到剪贴板。" : "复制失败，请手动选中复制。", ok); });
  });
  $("edSave").addEventListener("click", function () {
    var v = $("edCode").value.trim();
    if (!v) { msg($("edMsg"), "先生成信息码。", false); return; }
    var name = ($("edName").value || "残局").replace(/[\\/:*?"<>|]/g, "_");
    download(name + ".fhq", v);
    msg($("edMsg"), "已导出 " + name + ".fhq。", true);
  });
  $("edLoad").addEventListener("click", function () { edLoadCode($("edCode").value); });
  $("edFile").addEventListener("change", function (ev) {
    readFile(ev.target.files[0], function (text, err) {
      if (err) { msg($("edMsg"), err, false); return; }
      $("edCode").value = text.trim();
      edLoadCode(text);
    });
    ev.target.value = "";
  });

  // 把信息码读回编辑器
  function edLoadCode(str) {
    var v = Puzzle.validate(str || "");
    if (!v.ok) { msg($("edMsg"), v.reason.replace(/^Puzzle: /, ""), false); return; }
    var d = v.data, b = Puzzle.toBoard(d.fen);
    ed.load(b.list);
    $("edTurn").value = b.turn;
    $("edRound").value = b.round;
    if (d.hasSetup) {
      $("edName").value = d.name || "";
      $("edAI").value = String(d.ai);
      var g = d.goal;
      $("edSide").value = g.side;
      $("edGoal").value = (g.type === "reach") ? "standard" : g.type;   // reach 编辑器暂不支持编辑
      $("edSlayRow").style.display = g.type === "slay" ? "" : "none";
      $("edRoundsRow").style.display = g.type === "survive" ? "" : "none";
      if (g.type === "slay") { $("edTarget").value = g.target; $("edCount").value = g.count == null ? 1 : g.count; }
      if (g.type === "survive") $("edRounds").value = g.rounds;
      $("edLimit").value = g.moveLimit == null ? 0 : g.moveLimit;
    }
    msg($("edMsg"), "已载入" + (d.hasSetup ? "残局码（含设定）" : "局面（裸 FEN，设定保持当前）") +
      "，共 " + b.list.length + " 枚棋子。" + (d.goal && d.goal.type === "reach" ? "（原目标为抵达型，编辑器不支持，已改回常规取胜）" : ""), true);
  }

  /* 草稿：只存布子表与设定 */
  function saveDraft() {
    try {
      localStorage.setItem(DRAFT, JSON.stringify({
        list: ed.list(), turn: $("edTurn").value, round: $("edRound").value | 0,
      }));
    } catch (e) {}
  }
  (function restoreDraft() {
    try {
      var d = JSON.parse(localStorage.getItem(DRAFT));
      if (d && d.list && d.list.length) {
        ed.load(d.list);
        if (d.turn) $("edTurn").value = d.turn;
        if (d.round) $("edRound").value = d.round;
        msg($("edMsg"), "已恢复上次未完成的布阵。", true);
      }
    } catch (e) {}
  })();

  /* ================= 挑战残局 ================= */
  var pgData = null;
  function pgParse(showOk) {
    var v = Puzzle.validate($("pgCode").value || "");
    if (!v.ok) {
      pgData = null;
      $("pgOpts").style.display = "none";
      msg($("pgMsg"), v.reason.replace(/^Puzzle: /, ""), false);
      return null;
    }
    pgData = v.data;
    $("pgOpts").style.display = pgData.hasSetup ? "none" : "";
    if (showOk) {
      msg($("pgMsg"), pgData.hasSetup
        ? "残局码有效：" + (pgData.name || "无名") + "　·　" + Goal.describe(pgData.goal) +
          "　·　对手 " + AI.PRESET[pgData.ai].name
        : "局面有效（裸 FEN）。请在下方选执子方、对手与目标。", true);
    }
    return pgData;
  }
  $("pgCheck").addEventListener("click", function () { pgParse(true); });
  $("pgCode").addEventListener("blur", function () { if ($("pgCode").value.trim()) pgParse(true); });
  $("pgFile").addEventListener("change", function (ev) {
    readFile(ev.target.files[0], function (text, err) {
      if (err) { msg($("pgMsg"), err, false); return; }
      $("pgCode").value = text.trim();
      pgParse(true);
    });
    ev.target.value = "";
  });
  $("pgStart").addEventListener("click", function () {
    var d = pgParse(false);
    if (!d) return;
    var goal = d.goal, ai = d.ai, name = d.name;
    if (!d.hasSetup) {
      var side = $("pgSide").value, kind = $("pgGoal").value;
      goal = kind === "slay" ? { type: "slay", side: side, target: "general" }
        : kind === "survive" ? { type: "survive", side: side, rounds: 15 }
          : { type: "standard", side: side };
      ai = $("pgAI").value | 0;
      name = "挑战：自定义局面";
    }
    try {
      Goal.validate(goal);
      startTask({ fen: d.fen, goal: goal, ai: ai, name: name || "挑战残局" });
    } catch (e) { msg($("pgMsg"), e.message.replace(/^(FEN|Goal|Puzzle): /, ""), false); }
  });

  /* ================= 小工具 ================= */
  function copyText(text, cb) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(function () { cb(true); }, function () { cb(fallback()); });
      return;
    }
    cb(fallback());
    function fallback() {
      try {
        var ta = document.createElement("textarea");
        ta.value = text; ta.style.position = "fixed"; ta.style.opacity = "0";
        document.body.appendChild(ta); ta.select();
        var ok = document.execCommand("copy");
        document.body.removeChild(ta);
        return ok;
      } catch (e) { return false; }
    }
  }
  function download(name, text) {
    var blob = new Blob([text + "\n"], { type: "text/plain;charset=utf-8" });
    var a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = name;
    document.body.appendChild(a); a.click();
    setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 0);
  }
  function readFile(file, cb) {
    if (!file) { cb(null, "没有选到文件"); return; }
    if (file.size > 64 * 1024) { cb(null, "文件太大（信息码只有几百字节）"); return; }
    var fr = new FileReader();
    fr.onload = function () { cb(String(fr.result || "")); };
    fr.onerror = function () { cb(null, "文件读取失败"); };
    fr.readAsText(file, "utf-8");
  }

  openLib();
})();
