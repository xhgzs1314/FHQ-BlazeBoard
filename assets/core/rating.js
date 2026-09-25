(function (root) {
  "use strict";
  const MAX = { kill: 4.0, survive: 3.2, strategy: 3.2, resource: 2.4, offense: 1.6, discipline: 1.6 };
  const DIM_LABEL = { kill: "击杀贡献", survive: "生存能力", strategy: "战略视野", resource: "资源控制", offense: "进攻效率", discipline: "纪律性" };

  const clamp = (v, lo, hi) => (v < lo ? lo : v > hi ? hi : v);
  const round1 = v => Math.round(v * 10) / 10;

  /* ---------- 六维度打分 ---------- */
  function scoreKill(s) {
    const eaten = clamp(s.captures * 0.25, 0, 2.0);
    const value = clamp(s.captureValue / 10, 0, 2.0);
    const core = clamp(s.coreKills * 0.2, 0, 0.5);
    return clamp(eaten + value + core, 0, MAX.kill);
  }
  function scoreSurvive(s) {
    const base = clamp(s.aliveTotal / 20 * 1.6, 0, 1.6);
    const high = clamp(s.aliveHighValue / 8 * 1.2, 0, 1.2);
    const loss = clamp(s.deadValue / 20 * 0.4, 0, 0.4);
    return clamp(base + high - loss, 0, MAX.survive);
  }
  function scoreStrategy(s) {
    const scout = clamp(s.aliveScouts / 8 * 1.6, 0, 1.6);
    const fogCtrl = s.fogClearedForMe ? 0.6 : 0;
    const foeFog = s.foeFogStillOn ? 0.4 : 0;
    const permit = s.actable > 0 ? clamp(s.actableInPermit / s.actable * 0.6, 0, 0.6) : 0;
    return clamp(scout + fogCtrl + foeFog + permit, 0, MAX.strategy);
  }
  function scoreResource(s) {
    const genStep = clamp(s.generalBonus / 3 * 0.8, 0, 0.8);
    const shield = clamp(s.shieldDefenses * 0.2, 0, 0.8);
    const revive = clamp(s.revives * 0.2, 0, 0.4);
    // 区块均衡：差≤2 得满 0.4，≥6 得 0，线性插值
    const balance = clamp((6 - clamp(s.quadDiff, 2, 6)) / 4 * 0.4, 0, 0.4);
    return clamp(genStep + shield + revive + balance, 0, MAX.resource);
  }
  function scoreOffense(s) {
    const river = clamp(s.riverCross * 0.15, 0, 0.6);
    const coreAtk = clamp(s.coreAttacks * 0.1, 0, 0.6);
    const avgStep = s.moveCount > 0 ? clamp((s.moveStepSum / s.moveCount) / 5 * 0.4, 0, 0.4) : 0;
    return clamp(river + coreAtk + avgStep, 0, MAX.offense);
  }
  function scoreDiscipline(s) {
    let v = 1.6;
    const penalty = clamp(s.corePenalties * 0.2, 0, 0.8);
    const skip = clamp(s.skippedTurns * 0.1, 0, 0.2);
    v -= penalty + skip;
    if (s.surrendered) v -= 0.4;
    const clean = s.corePenalties === 0 && s.skippedTurns === 0 && !s.surrendered;
    if (clean) v += 0.2;
    return clamp(v, 0, MAX.discipline);
  }

  /* ---------- 评级 / ELO / 百分位---------- */
  const TIERS = [
    { min: 14.0, grade: "S+", title: "传奇统帅", icon: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L14.5 9.5L22 9.5L16 14.5L18.5 22L12 17L5.5 22L8 14.5L2 9.5L9.5 9.5L12 2Z" stroke="#f5c542" stroke-width="1.5" fill="none"/><path d="M12 4.5L13.3 8.5L17.5 8.5L14.5 11.5L15.8 15.5L12 13L8.2 15.5L9.5 11.5L6.5 8.5L10.7 8.5L12 4.5Z" stroke="#f5c542" stroke-width="1" fill="#f5c542" opacity="0.15"/></svg>', color: "#f5c542" },
    { min: 12.0, grade: "S", title: "战术大师", icon: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="9" stroke="#b57bff" stroke-width="1.5"/><polygon points="12,4 13.5,9 18.5,9 14.5,12.5 16,17.5 12,14.5 8,17.5 9.5,12.5 5.5,9 10.5,9" stroke="#b57bff" stroke-width="1.2" fill="none"/><circle cx="12" cy="12" r="2" fill="#b57bff" opacity="0.3"/></svg>', color: "#b57bff" },
    { min: 10.0, grade: "A", title: "精锐战士", icon: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="3" width="18" height="18" rx="3" stroke="#5aa0ff" stroke-width="1.5" fill="none"/><path d="M12 6L14 10.5L19 10.5L15 14L17 18.5L12 15.5L7 18.5L9 14L5 10.5L10 10.5L12 6Z" stroke="#5aa0ff" stroke-width="1.2" fill="none"/><circle cx="12" cy="12" r="1.5" fill="#5aa0ff" opacity="0.3"/></svg>', color: "#5aa0ff" },
    { min: 8.0, grade: "B", title: "铁血军士", icon: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 4L14 9L19 9L15 12.5L17 17.5L12 14.5L7 17.5L9 12.5L5 9L10 9L12 4Z" stroke="#4fd18b" stroke-width="1.5" fill="none"/><circle cx="12" cy="12" r="2.5" fill="#4fd18b" opacity="0.15"/></svg>', color: "#4fd18b" },
    { min: 6.0, grade: "C", title: "列兵", icon: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 20L12 4L20 20H15L12 14L9 20H4Z" stroke="#9aa7b8" stroke-width="1.5" fill="none"/><circle cx="12" cy="12" r="2" fill="#9aa7b8" opacity="0.15"/></svg>', color: "#9aa7b8" },
    { min: 4.0, grade: "D", title: "民兵", icon: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="8" stroke="#c08a5a" stroke-width="1.5"/><circle cx="12" cy="12" r="3" fill="#c08a5a" opacity="0.2"/><path d="M12 4V7M12 17V20M4 12H7M17 12H20" stroke="#c08a5a" stroke-width="1.2" stroke-linecap="round"/></svg>', color: "#c08a5a" },
    { min: 0.0, grade: "E", title: "炮灰", icon: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 6C10 6 6 8 6 12C6 16 10 18 12 18C14 18 18 16 18 12C18 8 14 6 12 6Z" stroke="#ff6d6d" stroke-width="1.5" fill="none"/><circle cx="10" cy="11" r="0.8" fill="#ff6d6d"/><circle cx="14" cy="11" r="0.8" fill="#ff6d6d"/><path d="M10 14.5C10.8 15.3 13.2 15.3 14 14.5" stroke="#ff6d6d" stroke-width="1.2" stroke-linecap="round"/></svg>', color: "#ff6d6d" },
  ];
  function tierFor(rating) { return TIERS.find(t => rating >= t.min) || TIERS[TIERS.length - 1]; }

  const TIER_RULES = {
    "S+": { win: 0.8, loss: 1.42, draw: 1.0 },
    "S": { win: 0.86, loss: 1.32, draw: 1.0 },
    "A": { win: 0.94, loss: 1.22, draw: 1.0 },
    "B": { win: 1.0, loss: 1.14, draw: 1.0 },
    "C": { win: 1.08, loss: 1.08, draw: 1.0 },
    "D": { win: 1.18, loss: 1.02, draw: 1.0 },
    "E": { win: 1.35, loss: 0.96, draw: 1.0 }
  };

  function eloDelta(rating, result, performance = 8.0, gap = 0) {
    if (result === "draw") return 0;
    const tier = tierFor(rating);
    const tierCfg = TIER_RULES[tier.grade] || TIER_RULES.E;
    const factor = clamp((rating - 8.0) / 8.0, -0.75, 1.25);
    const spread = clamp(Math.abs(gap) / 5.0, 0, 2.4);
    const quality = clamp((performance - 6.0) / 10.0, -0.4, 1.0);
    const base = 18 + factor * 14 + spread * 8 + quality * 10;
    const delta = result === "win" ? base * tierCfg.win : -base * tierCfg.loss;
    return Math.round(delta);
  }

  // 百分位：正态分布
  function percentileFor(rating) {
    const z = (rating - 8.0) / 2.4;
    const cdf = 1 / (1 + Math.exp(-1.702 * z));     // logistic 近似标准正态 CDF
    return clamp(Math.round(cdf * 100), 1, 99);
  }

  /* ---------- 综合评分 ---------- */
  function scoreSide(s, opts) {
    const breakdown = {
      kill: round1(scoreKill(s)),
      survive: round1(scoreSurvive(s)),
      strategy: round1(scoreStrategy(s)),
      resource: round1(scoreResource(s)),
      offense: round1(scoreOffense(s)),
      discipline: round1(scoreDiscipline(s)),
    };
    let total = breakdown.kill + breakdown.survive + breakdown.strategy +
      breakdown.resource + breakdown.offense + breakdown.discipline;
    // 短对局(<5 回合)消极游戏惩罚
    if (opts.shortGame) total *= 0.6;
    total = clamp(total, 0, 16.0);

    let rating = 2.0 + (total / 16.0) * 14.0;        // 归一化到 2.0–16.0
    if (opts.result === "win") rating += 0.5;
    if (s.surrendered) rating -= 0.3;
    rating = round1(clamp(rating, 2.0, 16.0));

    const tier = tierFor(rating);
    return {
      total: round1(total), rating, tier, breakdown,
      elo: eloDelta(rating, opts.result),
      percentile: percentileFor(rating),
      result: opts.result,
      stats: s,
    };
  }

  function computeMatch(data) {
    if (!data) return null;
    const shortGame = data.round < 5;
    const resultOf = side =>
      data.winner === null ? "draw" : (data.winner === side ? "win" : "loss");
    const match = {
      winner: data.winner,
      round: data.round,
      shortGame,
      red: scoreSide(data.red, { result: resultOf("red"), shortGame }),
      blue: scoreSide(data.blue, { result: resultOf("blue"), shortGame }),
    };
    const redGap = match.red.rating - match.blue.rating;
    const blueGap = match.blue.rating - match.red.rating;
    match.red.elo = eloDelta(match.red.rating, resultOf("red"), match.red.total, redGap);
    match.blue.elo = eloDelta(match.blue.rating, resultOf("blue"), match.blue.total, blueGap);
    match.red.result = resultOf("red");
    match.blue.result = resultOf("blue");
    return match;
  }
  /* ---------- 赛后渲染 ---------- */
  const DIM_ORDER = ["kill", "survive", "strategy", "resource", "offense", "discipline"];
  const esc = s => String(s).replace(/[&<>]/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;" }[c]));

  function ensureStyle() {
    if (typeof document === "undefined" || document.getElementById("ratingStyle")) return;
    const st = document.createElement("style");
    st.id = "ratingStyle";
    st.textContent = RATING_CSS;
    document.head.appendChild(st);
  }

  function resultBadge(sc) {
    if (sc.result === "draw") return `<span class="rt-res draw">和棋 ±0</span>`;
    const win = sc.result === "win";
    const sign = sc.elo > 0 ? "+" : "";
    return `<span class="rt-res ${win ? "win" : "loss"}">${win ? "胜" : "负"} (${sign}${sc.elo} 分)</span>`;
  }

  function dimBars(sc) {
    return DIM_ORDER.map(k => {
      const v = sc.breakdown[k], m = MAX[k];
      const pct = clamp(v / m * 100, 0, 100);
      return `<div class="rt-dim">
        <span class="rt-dl">${DIM_LABEL[k]}</span>
        <span class="rt-bar"><i style="width:${pct}%"></i></span>
        <span class="rt-dv">${v.toFixed(1)} <em>/${m.toFixed(1)}</em></span>
      </div>`;
    }).join("");
  }

  // 详细卡
  function detailCard(side, sc) {
    const t = sc.tier, st = sc.stats;
    const sign = sc.elo > 0 ? "+" : "";
    const eloTxt = sc.result === "draw" ? "±0 分" : `${sign}${sc.elo} 分`;
    return `<div class="rt-card ${side} detail">
      <div class="rt-head" style="--tc:${t.color}">
        <span class="rt-icon">${t.icon}</span>
        <span class="rt-title">${t.grade} · ${t.title}</span>
        <span class="rt-score">${sc.rating.toFixed(1)}</span>
        <span class="rt-elo">${eloTxt}</span>
      </div>
      <div class="rt-dims">${dimBars(sc)}</div>
      <div class="rt-stats">
        <span>击杀 ${st.captures}</span><span>阵亡 ${20 - st.aliveTotal}</span>
        <span>渡河 ${st.riverCross}</span><span>核攻 ${st.coreAttacks}</span>
        <span>复活 ${st.revives}</span>
      </div>
      <div class="rt-foot"><span>超越 ${sc.percentile}% 的玩家</span>${resultBadge(sc)}</div>
    </div>`;
  }

  // 紧凑卡
  function compactCard(side, sc) {
    const t = sc.tier;
    return `<div class="rt-card ${side}">
      <div class="rt-head" style="--tc:${t.color}">
        <span class="rt-icon">${t.icon}</span>
        <span class="rt-score">${sc.rating.toFixed(1)}</span>
      </div>
      <div class="rt-title-sm">${t.grade} · ${t.title}</div>
      <div class="rt-dims">${dimBars(sc)}</div>
      <div class="rt-foot">${resultBadge(sc)}</div>
    </div>`;
  }

  function render(container, match, viewSide) {
    if (typeof document === "undefined") return;
    const el = typeof container === "string" ? document.getElementById(container) : container;
    if (!el) return;
    if (!match) { el.innerHTML = ""; el.style.display = "none"; return; }
    ensureStyle();
    let html;
    if (viewSide === "red" || viewSide === "blue") {
      html = detailCard(viewSide, match[viewSide]);
    } else {
      html = `<div class="rt-pair">${compactCard("red", match.red)}${compactCard("blue", match.blue)}</div>`;
    }
    el.innerHTML = html;
    el.style.display = "";
  }

  const RATING_CSS = `
  #ratingPanel { margin-top: 18px; width: 100%; max-width: 720px;display:inline-block; }
  #ratingPanel .rt-pair { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }
  #ratingPanel .rt-card {
    background: linear-gradient(155deg, rgba(20,36,58,.94), rgba(10,20,34,.96));
    border: 1px solid rgba(140,190,255,.35); border-radius: 14px;
    padding: 14px 16px; color: #dbe9ff; box-shadow: 0 0 30px rgba(40,90,160,.3);
    flex: 1 1 300px; min-width: 280px; box-sizing: border-box;
  }
  #ratingPanel .rt-card.detail { max-width: 460px; margin: 0 auto; }
  #ratingPanel .rt-card.red { border-color: rgba(255,120,120,.45); }
  #ratingPanel .rt-card.blue { border-color: rgba(120,170,255,.5); }
  #ratingPanel .rt-head { display: flex; align-items: center; gap: 8px; }
  #ratingPanel .rt-icon { font-size: 22px; }
  #ratingPanel .rt-title { font-weight: 700; letter-spacing: .06em; color: var(--tc,#eaf2ff); }
  #ratingPanel .rt-title-sm { font-size: 12px; color: #9cc4ff; margin: 4px 0 8px; letter-spacing: .05em; }
  #ratingPanel .rt-score { margin-left: auto; font-size: 28px; font-weight: 900; color: var(--tc,#fff); text-shadow: 0 0 14px rgba(255,255,255,.25); }
  #ratingPanel .rt-elo { font-size: 13px; color: #7ee6a2; margin-left: 8px; font-weight: 700; }
  #ratingPanel .rt-dims { margin: 10px 0 8px; display: flex; flex-direction: column; gap: 5px; }
  #ratingPanel .rt-dim { display: flex; align-items: center; gap: 8px; font-size: 12px; }
  #ratingPanel .rt-dl { width: 56px; color: #b7c8e0; flex: 0 0 auto; }
  #ratingPanel .rt-bar { flex: 1; height: 8px; border-radius: 5px; background: rgba(255,255,255,.08); overflow: hidden; }
  #ratingPanel .rt-bar i { display: block; height: 100%; border-radius: 5px; background: linear-gradient(90deg,#4a9bff,#7fd0ff); }
  #ratingPanel .rt-dv { width: 58px; text-align: right; flex: 0 0 auto; color: #dbe9ff; }
  #ratingPanel .rt-dv em { color: #6f819a; font-style: normal; font-size: 10px; }
  #ratingPanel .rt-stats { display: flex; flex-wrap: wrap; gap: 4px 12px; font-size: 11.5px; color: #9fb2cc; border-top: 1px dashed rgba(255,255,255,.12); padding-top: 8px; margin-top: 4px; }
  #ratingPanel .rt-foot { display: flex; align-items: center; justify-content: space-between; margin-top: 8px; font-size: 12px; color: #8fa6c4; }
  #ratingPanel .rt-res { font-weight: 700; padding: 2px 10px; border-radius: 6px; letter-spacing: .06em; }
  #ratingPanel .rt-res.win { color: #071018; background: linear-gradient(135deg,#8effc0,#35c98b); }
  #ratingPanel .rt-res.loss { color: #fff; background: linear-gradient(135deg,#ff9b7f,#ff5d62); }
  #ratingPanel .rt-res.draw { color: #071018; background: linear-gradient(135deg,#ffe08a,#f5c542); }
  `;

  const api = { computeMatch, render, MAX, DIM_LABEL };
  if (typeof module !== "undefined" && module.exports) module.exports = api;
  else root.Rating = api;
})(typeof window !== "undefined" ? window : globalThis);
