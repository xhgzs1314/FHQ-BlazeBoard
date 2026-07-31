(function (root) {
  "use strict";
  function fail(m) { throw new Error("Play: " + m); }

  function createRunner(o) {
    if (!o || !o.engine) fail("需要 engine");
    var engine = o.engine;
    var human = o.human === "blue" ? "blue" : "red";
    var thinkMs = o.thinkMs == null ? 240 : o.thinkMs;
    var ui = o.ui || null;                       
    var ai = null, goal = null, task = null;
    var thinking = false, settled = false, timer = null;

    function foe() { return human === "red" ? "blue" : "red"; }
    function setThinking(on) {
      thinking = on;
      if (o.onThinking) o.onThinking(on);
    }
    function setUI(u) { ui = u; }

    /* ---------- 判定---------- */
    function check() {
      if (!goal) return null;
      return goal.check(engine);
    }
    function afterChange() {
      var s = check();
      if (o.onGoal) o.onGoal(s, task);
      if (s && s.done) settle(s);
      return s;
    }
    function settle(s) {
      if (settled) return;
      settled = true;
      setThinking(false);
      clearTimer();
      if (o.onSettle) o.onSettle(s, task);
    }

    /* ---------- 玩家走子 ---------- */
    function handleMove(id, r, c) {
      if (settled || thinking) return { ok: false, reason: "暂不可行动" };
      var res = engine.move(id, r, c);
      if (res && res.ok) {
        if (ai) ai.observe();
        var s = afterChange();
        if (!(s && s.done)) scheduleAI();
      }
      return res;
    }

    /* ---------- 对手行棋 ---------- */
    function clearTimer() { if (timer) { clearTimeout(timer); timer = null; } }
    function scheduleAI() {
      clearTimer();
      if (settled || engine.state.over || engine.state.turn !== foe() || !ai) return;
      setThinking(true);
      timer = setTimeout(stepAI, thinkMs);
    }
    function stepAI() {
      timer = null;
      if (settled || engine.state.over || engine.state.turn !== foe() || !ai) { setThinking(false); return; }
      var mv = null;
      try { mv = ai.decide(foe()); } catch (e) { mv = null; }
      var res = mv ? engine.move(mv.id, mv.r, mv.c) : null;
      if (res && res.ok) {
        if (ui) { ui.flashEvents(res.events); ui.render(); }
        ai.observe();
      }
      var s = afterChange();
      if (s && s.done) { setThinking(false); if (ui) ui.render(); return; }
      // 对手连走（罚跳等）
      if (!engine.state.over && engine.state.turn === foe()) { timer = setTimeout(stepAI, thinkMs); }
      else { setThinking(false); if (ui) ui.render(); }
    }

   
    function hint() {
      if (!task || !task.solution || !task.solution.length || settled) return null;
      var s = check();
      if (!s) return null;
      var step = task.solution[s.movesUsed];
      if (!step) return null;
      if (engine.state.over || engine.state.turn !== human) return null;
      var p = engine.pieceAt(step.fr, step.fc);
      if (!p || !p.alive || p.side !== human) return null;
      if (!engine.canAct(p)) return null;                 // 许可区/自由行动
      var ok = engine.legalMoves(p).some(function (m) { return m.r === step.r && m.c === step.c; });
      return ok ? step : null;
    }

    /* ---------- 开局 ---------- */
    function run(t) {
      if (!t || !t.fen) fail("task 需要 fen");
      clearTimer();
      task = t;
      settled = false;
      setThinking(false);
      root.FEN.load(engine, t.fen);                        
      goal = root.Goal.create(t.goal).attach(engine);
      ai = root.AI.create(engine, { side: foe(), difficulty: t.ai == null ? 2 : t.ai });
      ai.observe();
      if (ui) { ui.buildBoard(); ui.render(); }
      if (o.onStart) o.onStart(task);
      afterChange();
      scheduleAI();                                        // 若局面轮到对手先行
      return task;
    }
    function retry() { return task ? run(task) : null; }

  
    function stop() {
      clearTimer();
      setThinking(false);
      settled = true;
    }

    function canInteract(p) {
      if (thinking || settled || engine.state.over) return false;
      if (engine.state.turn !== human) return false;
      return !p || p.side === human;
    }

    return {
      run: run, retry: retry, stop: stop, hint: hint, handleMove: handleMove,
      check: check, afterChange: afterChange, canInteract: canInteract,
      setUI: setUI, scheduleAI: scheduleAI,
      get human() { return human; },
      get task() { return task; },
      get settled() { return settled; },
      get thinking() { return thinking; },
      get hasSolution() { return !!(task && task.solution && task.solution.length); },
    };
  }

  var api = { createRunner: createRunner };
  if (typeof module !== "undefined" && module.exports) module.exports = api;
  else root.Play = api;
})(typeof window !== "undefined" ? window : globalThis);
