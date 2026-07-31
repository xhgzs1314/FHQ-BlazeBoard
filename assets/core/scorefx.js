(function (root, factory) {
  "use strict";
  if (typeof module !== "undefined" && module.exports) module.exports = factory();
  else root.FHQScore = root.FHQScore || factory();
})(typeof window !== "undefined" ? window : this, function () {
  "use strict";

  const VER = "1.0.0";
  const STYLE_ID = "fhqs-style-v1";
  const Z_BASE = 2147483600;
  const hasWin = typeof window !== "undefined" && typeof document !== "undefined";
  function prefersReduced() {
    try { return !!(window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches); }
    catch (e) { return false; }
  }
  function detectQuality() {
    if (!hasWin) return 0;
    const nav = window.navigator || {};
    const ua = nav.userAgent || "";
    const cores = nav.hardwareConcurrency || 0;
    const mem = nav.deviceMemory || 0;
    const dpr = window.devicePixelRatio || 1;
    const px = (window.innerWidth || 320) * (window.innerHeight || 480) * dpr;
    let s = 0;
    if (cores >= 8) s += 2; else if (cores >= 4) s += 1; else if (cores > 0) s -= 1;
    if (mem >= 8) s += 2; else if (mem >= 4) s += 1; else if (mem > 0) s -= 1;
    if (px > 4.2e6) s -= 1;
    if (/Android\s+([1-7])\./.test(ua)) s -= 3;
    if (/iPhone\s+OS\s+([1-9]|10|11)_/.test(ua)) s -= 2;
    if (cores === 0 && mem === 0) s += 1;
    return s >= 3 ? 2 : s >= 1 ? 1 : 0;
  }

  /* ================= 工具 ================= */
  const clamp = (v, a, b) => (v < a ? a : v > b ? b : v);
  const rand = (a, b) => a + Math.random() * (b - a);
  const easeOutQuart = t => 1 - Math.pow(1 - t, 4);
  const easeOutCubic = t => 1 - Math.pow(1 - t, 3);
  const esc = s => String(s).replace(/[&<>"]/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c]));
  const now = () => (window.performance && performance.now ? performance.now() : Date.now());

  function fmtNum(v, d, group) {
    const neg = v < 0, av = Math.abs(v);
    let s = av.toFixed(d);
    if (group !== false && av >= 10000) {
      const p = s.split(".");
      p[0] = p[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
      s = p.join(".");
    }
    return (neg ? "-" : "") + s;
  }
  function autoMax(v) {
    if (!(v > 0)) return 1;
    const p = Math.pow(10, Math.floor(Math.log10(v)));
    return Math.max(p, Math.ceil(v / p) * p);
  }
  /* ================= 配色 ================= */
  function hex2rgb(h) {
    let s = String(h || "").trim().replace(/^#/, "");
    if (s.length === 3) s = s[0] + s[0] + s[1] + s[1] + s[2] + s[2];
    const n = parseInt(s, 16);
    if (s.length !== 6 || isNaN(n)) return [255, 200, 80];
    return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
  }
  const mix = (c, t, k) => c.map((v, i) => Math.round(v + (t[i] - v) * k));
  const rgbStr = c => c[0] + "," + c[1] + "," + c[2];
  const TONES = {
    gold: "#ffc94a",
    red: "#ff5a52",
    blue: "#54b8ff",
    green: "#4fe3a0",
    purple: "#b57bff",
  };

  function buildTone(seed) {
    const base = hex2rgb(TONES[seed] || seed || TONES.gold);
    return {
      base: rgbStr(base),
      lite: rgbStr(mix(base, [255, 255, 255], 0.55)),
      deep: rgbStr(mix(base, [24, 12, 4], 0.45)),
      hex: "#" + base.map(v => v.toString(16).padStart(2, "0")).join(""),
      arr: base,
    };
  }
  /* ================= 样式 ================= */
  const CSS = [
    `.fhqs-root{position:fixed;inset:0;z-index:${Z_BASE};display:flex;align-items:center;
justify-content:center;overflow:hidden;pointer-events:auto;
font-family:"DIN Alternate","Bahnschrift",-apple-system,BlinkMacSystemFont,"Segoe UI",
"PingFang SC","Microsoft YaHei",Roboto,sans-serif;-webkit-font-smoothing:antialiased;
contain:layout style paint;--t:255,201,74;--tl:255,228,165;--td:120,74,20}
.fhqs-root *{box-sizing:border-box;margin:0;padding:0}
.fhqs-veil{position:absolute;inset:0;opacity:0;background:radial-gradient(ellipse at 50% 46%,
rgba(10,8,16,.58) 0%,rgba(5,4,9,.86) 58%,rgba(0,0,0,.95) 100%);
animation:fhqs-veil-in .34s ease-out forwards}
.fhqs-q2 .fhqs-veil{-webkit-backdrop-filter:blur(9px) saturate(.82);backdrop-filter:blur(9px) saturate(.82)}
.fhqs-out .fhqs-veil{animation:fhqs-veil-out .34s ease-in forwards}
.fhqs-cv{position:absolute;inset:0;width:100%;height:100%;display:block;pointer-events:none}
.fhqs-flash{position:absolute;inset:0;opacity:0;pointer-events:none;mix-blend-mode:screen;
background:radial-gradient(circle at 50% 50%,#fff 0%,rgba(var(--tl),.72) 30%,rgba(var(--t),0) 66%)}
@keyframes fhqs-veil-in{to{opacity:1}}
@keyframes fhqs-veil-out{from{opacity:1}to{opacity:0}}`,

    `.fhqs-stage{position:relative;display:flex;flex-direction:column;align-items:center;
padding:0 18px;max-width:min(560px,92vw);will-change:transform,opacity;opacity:0;
transform:scale(.86) translateY(14px);animation:fhqs-stage-in .62s cubic-bezier(.16,1.1,.3,1) .06s forwards}
.fhqs-out .fhqs-stage{animation:fhqs-stage-out .3s ease-in forwards}
@keyframes fhqs-stage-in{60%{opacity:1}to{opacity:1;transform:scale(1) translateY(0)}}
@keyframes fhqs-stage-out{to{opacity:0;transform:scale(.94) translateY(8px)}}
.fhqs-shake{animation:fhqs-shake .44s cubic-bezier(.36,.07,.19,.97) both}
@keyframes fhqs-shake{10%,90%{transform:translate3d(-2px,1px,0)}20%,80%{transform:translate3d(4px,-2px,0)}
30%,50%,70%{transform:translate3d(-7px,2px,0)}40%,60%{transform:translate3d(6px,-1px,0)}}
.fhqs-kicker{font-size:clamp(10px,2.6vw,12px);letter-spacing:.44em;text-indent:.44em;
color:rgba(var(--tl),.62);text-transform:uppercase;font-weight:700;margin-bottom:14px;
opacity:0;animation:fhqs-fade-down .5s ease-out .2s forwards;white-space:nowrap}
@keyframes fhqs-fade-down{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:none}}`,
    `.fhqs-ring{position:relative;width:clamp(224px,62vmin,318px);aspect-ratio:1;
display:flex;align-items:center;justify-content:center}
.fhqs-svg{position:absolute;inset:0;width:100%;height:100%;transform:rotate(-90deg);overflow:visible}
.fhqs-track{fill:none;stroke:rgba(255,255,255,.055);stroke-width:9}
.fhqs-ticks{fill:none;stroke:rgba(var(--tl),.16);stroke-width:6;
stroke-dasharray:1.5 13.4;opacity:0;transition:opacity .5s ease-out .18s}
.fhqs-ready .fhqs-ticks{opacity:1}
.fhqs-arc{fill:none;stroke:url(#fhqsGrad);stroke-width:9;stroke-linecap:round}
.fhqs-arc-glow{fill:none;stroke:rgba(var(--t),.85);stroke-width:15;stroke-linecap:round;
filter:blur(11px);opacity:.62}
.fhqs-halo{position:absolute;width:78%;aspect-ratio:1;border-radius:50%;
background:radial-gradient(circle,rgba(var(--t),.3) 0%,rgba(var(--t),.09) 46%,transparent 72%);
filter:blur(24px);opacity:0;transition:opacity .8s ease-out}
.fhqs-lit .fhqs-halo{opacity:1;animation:fhqs-breathe 3.4s ease-in-out 1s infinite}
@keyframes fhqs-breathe{50%{opacity:.68;transform:scale(1.05)}}
.fhqs-dial{position:absolute;inset:-7%;border-radius:50%;opacity:0;
border:1px solid rgba(var(--tl),.1);
border-top-color:rgba(var(--t),.42);border-bottom-color:rgba(var(--t),.2);
transition:opacity .6s ease-out .1s;animation:fhqs-spin 14s linear infinite}
.fhqs-ready .fhqs-dial{opacity:1}
@keyframes fhqs-spin{to{transform:rotate(360deg)}}`,

    `.fhqs-core{position:relative;z-index:2;display:flex;flex-direction:column;align-items:center;
line-height:1;pointer-events:none}
.fhqs-num{font-size:clamp(46px,13vmin,78px);font-weight:900;letter-spacing:-.01em;
font-variant-numeric:tabular-nums;font-feature-settings:"tnum" 1;color:#fff;
text-shadow:0 0 26px rgba(var(--t),.72),0 0 62px rgba(var(--t),.4);display:flex;align-items:baseline}
.fhqs-num .fhqs-suffix{font-size:.36em;font-weight:800;margin-left:.14em;
color:rgba(var(--tl),.72);letter-spacing:.04em;text-shadow:none;align-self:flex-end;padding-bottom:.42em}
.fhqs-label{margin-top:11px;font-size:clamp(9px,2.4vw,11px);letter-spacing:.4em;text-indent:.4em;
color:rgba(255,255,255,.34);text-transform:uppercase;font-weight:700;white-space:nowrap}
.fhqs-delta{margin-top:12px;font-size:clamp(12px,3.2vw,15px);font-weight:800;letter-spacing:.05em;
padding:3px 12px;border-radius:99px;color:rgb(var(--tl));background:rgba(var(--t),.11);
border:1px solid rgba(var(--t),.3);opacity:0;transform:scale(.5);
font-variant-numeric:tabular-nums;white-space:nowrap}
.fhqs-done .fhqs-delta{animation:fhqs-pop .5s cubic-bezier(.2,1.5,.4,1) forwards}
@keyframes fhqs-pop{to{opacity:1;transform:scale(1)}}
.fhqs-fly{position:absolute;left:50%;top:38%;font-size:clamp(24px,7vmin,40px);font-weight:900;
letter-spacing:.02em;color:rgb(var(--tl));pointer-events:none;z-index:6;white-space:nowrap;
text-shadow:0 0 22px rgba(var(--t),.9),0 0 52px rgba(var(--t),.5);
animation:fhqs-fly 1.15s cubic-bezier(0,.72,.28,1) forwards}
@keyframes fhqs-fly{0%{opacity:0;transform:translate(-50%,0) scale(.4)}
18%{opacity:1;transform:translate(-50%,-26px) scale(1.28)}
62%{opacity:.92;transform:translate(-50%,-62px) scale(1.04)}
100%{opacity:0;transform:translate(-50%,-116px) scale(.86)}}`,
    `.fhqs-badge{position:relative;margin-top:22px;display:flex;align-items:center;gap:10px;
padding:9px 20px 9px 15px;border-radius:99px;overflow:hidden;
background:linear-gradient(140deg,rgba(var(--td),.5),rgba(8,7,12,.86));
border:1px solid rgba(var(--t),.34);box-shadow:0 8px 30px rgba(0,0,0,.5),
inset 0 1px 0 rgba(var(--tl),.16);opacity:0;transform:scale(.72);filter:blur(6px)}
.fhqs-done .fhqs-badge{animation:fhqs-stamp .52s cubic-bezier(.18,1.3,.32,1) forwards}
@keyframes fhqs-stamp{to{opacity:1;transform:scale(1);filter:blur(0)}}
.fhqs-badge::after{content:"";position:absolute;inset:0;pointer-events:none;
background:linear-gradient(105deg,transparent 34%,rgba(255,255,255,.26) 50%,transparent 66%);
transform:translateX(-130%)}
.fhqs-done .fhqs-badge::after{animation:fhqs-sheen 1.05s ease-out .42s}
@keyframes fhqs-sheen{to{transform:translateX(130%)}}
.fhqs-bicon{font-size:20px;line-height:1;filter:drop-shadow(0 0 10px rgba(var(--t),.7))}
.fhqs-bgrade{font-size:19px;font-weight:900;letter-spacing:.04em;color:rgb(var(--tl));
text-shadow:0 0 16px rgba(var(--t),.6)}
.fhqs-bsep{width:1px;height:15px;background:rgba(var(--tl),.26)}
.fhqs-btitle{font-size:13.5px;font-weight:700;letter-spacing:.14em;color:rgba(255,255,255,.9)}
.fhqs-sub{margin-top:14px;font-size:12.5px;letter-spacing:.1em;color:rgba(255,255,255,.42);
text-align:center;opacity:0}
.fhqs-done .fhqs-sub{animation:fhqs-fade-up .55s ease-out .3s forwards}
@keyframes fhqs-fade-up{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}`,

    `.fhqs-rows{margin-top:20px;width:100%;display:grid;gap:7px 22px;
grid-template-columns:repeat(auto-fit,minmax(184px,1fr))}
.fhqs-row{display:flex;align-items:center;gap:9px;font-size:11.5px;opacity:0;
transform:translateY(6px);transition:opacity .4s ease-out,transform .4s ease-out}
.fhqs-row.on{opacity:1;transform:none}
.fhqs-rl{flex:0 0 auto;min-width:52px;color:rgba(255,255,255,.5);letter-spacing:.06em}
.fhqs-rb{flex:1;height:3px;border-radius:3px;background:rgba(255,255,255,.08);overflow:hidden}
.fhqs-rb i{display:block;height:100%;width:0;border-radius:3px;
background:linear-gradient(90deg,rgba(var(--t),.75),rgb(var(--tl)));
box-shadow:0 0 8px rgba(var(--t),.55);transition:width .62s cubic-bezier(.2,.9,.25,1)}
.fhqs-rv{flex:0 0 auto;min-width:30px;text-align:right;color:rgba(255,255,255,.82);
font-weight:700;font-variant-numeric:tabular-nums}`,

    `.fhqs-close{position:absolute;top:max(16px,env(safe-area-inset-top));
right:max(16px,env(safe-area-inset-right));width:34px;height:34px;border-radius:50%;
display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:8;
border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.03);color:rgba(255,255,255,.34);
font-size:17px;line-height:1;padding:0;font-family:inherit;
opacity:0;pointer-events:none;transition:opacity .6s ease-out,color .2s,background .2s,border-color .2s}
.fhqs-done .fhqs-close{opacity:.4;pointer-events:auto;transition-delay:.5s}
.fhqs-close:hover{opacity:1;color:#fff;background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.3)}
.fhqs-close:focus-visible{opacity:1;outline:1px solid rgba(var(--t),.6);outline-offset:2px}
.fhqs-hint{position:absolute;left:0;right:0;bottom:max(22px,env(safe-area-inset-bottom));
text-align:center;font-size:10.5px;letter-spacing:.24em;text-indent:.24em;
color:rgba(255,255,255,.2);opacity:0;pointer-events:none}
.fhqs-done .fhqs-hint{animation:fhqs-fade-up .6s ease-out .7s forwards}
@media (prefers-reduced-motion:reduce){.fhqs-root *{animation-duration:.01ms!important;
animation-delay:0s!important;transition-duration:.01ms!important}
.fhqs-dial{animation:none!important}.fhqs-lit .fhqs-halo{animation:none!important}}`,
  ].join("\n");

  function injectCSS() {
    if (!hasWin || document.getElementById(STYLE_ID)) return;
    const st = document.createElement("style");
    st.id = STYLE_ID;
    st.textContent = CSS;
    (document.head || document.documentElement).appendChild(st);
  }
  /* ================= 粒子引擎 ================= */
  const CAP = 620;

  function createFX(canvas, quality, tone) {
    const ctx = canvas.getContext("2d", { alpha: true });
    if (!ctx) return null;
    const k = quality >= 2 ? 1 : 0.5;
    const lite = "rgb(" + tone.lite + ")", base = "rgb(" + tone.base + ")";
    let dust = [], sparks = [], motes = [], waves = [], rays = [];
    let W = 0, H = 0, dpr = 1, cx = 0, cy = 0, raf = 0, last = 0, dead = false;

    function resize() {
      dpr = Math.min(window.devicePixelRatio || 1, quality >= 2 ? 2 : 1.5);
      W = canvas.clientWidth || window.innerWidth;
      H = canvas.clientHeight || window.innerHeight;
      canvas.width = Math.round(W * dpr);
      canvas.height = Math.round(H * dpr);
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }
    function center(x, y) { cx = x; cy = y; }
    function trim(a) { if (a.length > CAP) a.splice(0, a.length - CAP); }
    function converge(r) {
      const n = Math.round(84 * k);
      for (let i = 0; i < n; i++) {
        const a = rand(0, Math.PI * 2), d = r * rand(1.35, 2.9);
        dust.push({
          a, d, d0: d, r: rand(1.1, 2.6), t: 0,
          life: rand(0.5, 0.92), tgt: r * rand(.92, 1.06), hot: Math.random() < .3
        });
      }
      trim(dust);
    }
    function burst(r) {
      const n = Math.round(118 * k);
      for (let i = 0; i < n; i++) {
        const a = rand(0, Math.PI * 2), sp = rand(170, 640) * (Math.random() < .18 ? 1.7 : 1);
        sparks.push({
          x: cx + Math.cos(a) * r * .9, y: cy + Math.sin(a) * r * .9,
          vx: Math.cos(a) * sp, vy: Math.sin(a) * sp, t: 0, life: rand(.5, 1.25),
          r: rand(1, 2.5), hot: Math.random() < .34
        });
      }
      trim(sparks);
      wave(r * .8, r * 2.4, .78);
      wave(r * .5, r * 1.5, .52);
    }
    function comet(r, ang, dt) {
      const n = 26 * k * dt;
      for (let i = 0; i < n + (Math.random() < n % 1 ? 1 : 0); i++) {
        const a = ang + rand(-.05, .05), rr = r + rand(-5, 5);
        const tan = a + Math.PI / 2;
        sparks.push({
          x: cx + Math.cos(a) * rr, y: cy + Math.sin(a) * rr,
          vx: Math.cos(tan) * rand(-40, 130) + Math.cos(a) * rand(20, 130),
          vy: Math.sin(tan) * rand(-40, 130) + Math.sin(a) * rand(20, 130),
          t: 0, life: rand(.22, .55), r: rand(.8, 1.9), hot: Math.random() < .5
        });
      }
      trim(sparks);
    }
    function ambient(r) {
      const n = Math.round(26 * k);
      for (let i = 0; i < n; i++)
        motes.push({
          a: rand(0, Math.PI * 2), d: r * rand(.98, 1.2),
          w: rand(-.16, .16), r: rand(.7, 1.7), ph: rand(0, 6.28)
        });
      trim(motes);
    }
    function wave(r0, r1, a0) { waves.push({ r0, r1, a0, t: 0, life: .72 }); }
    function flare(r) {
      const n = quality >= 2 ? 9 : 6;
      for (let i = 0; i < n; i++)
        rays.push({
          a: rand(0, Math.PI * 2), len: r * rand(1.5, 3.1),
          w: rand(.02, .075), t: 0, life: rand(.55, .95)
        });
    }
    /* --- 渲染 --- */
    function step(ts) {
      if (dead) return;
      if (!last) last = ts;
      const dt = Math.min((ts - last) / 1000, 0.05);
      last = ts;
      ctx.clearRect(0, 0, W, H);
      ctx.globalCompositeOperation = "lighter";
      for (let i = rays.length - 1; i >= 0; i--) {
        const p = rays[i]; p.t += dt;
        const u = p.t / p.life;
        if (u >= 1) { rays.splice(i, 1); continue; }
        const al = Math.sin(u * Math.PI) * .17, ln = p.len * (.5 + u * .7);
        const g = ctx.createLinearGradient(cx, cy, cx + Math.cos(p.a) * ln, cy + Math.sin(p.a) * ln);
        g.addColorStop(0, "rgba(" + tone.lite + "," + al + ")");
        g.addColorStop(1, "rgba(" + tone.base + ",0)");
        ctx.fillStyle = g;
        ctx.beginPath(); ctx.moveTo(cx, cy);
        ctx.arc(cx, cy, ln, p.a - p.w, p.a + p.w); ctx.closePath(); ctx.fill();
      }
      for (let i = waves.length - 1; i >= 0; i--) {
        const p = waves[i]; p.t += dt;
        const u = p.t / p.life;
        if (u >= 1) { waves.splice(i, 1); continue; }
        const e = easeOutCubic(u);
        ctx.strokeStyle = "rgba(" + tone.lite + "," + (p.a0 * (1 - e)).toFixed(3) + ")";
        ctx.lineWidth = 2.4 * (1 - e) + .4;
        ctx.beginPath(); ctx.arc(cx, cy, p.r0 + (p.r1 - p.r0) * e, 0, 6.2832); ctx.stroke();
      }
      for (let i = dust.length - 1; i >= 0; i--) {
        const p = dust[i]; p.t += dt;
        const u = p.t / p.life;
        if (u >= 1) { dust.splice(i, 1); continue; }
        const e = easeOutCubic(u);
        p.d = p.d0 + (p.tgt - p.d0) * e;
        p.a += dt * .5 * (1 - e);
        ctx.globalAlpha = Math.sin(u * Math.PI) * .85;
        ctx.fillStyle = p.hot ? "#fff" : lite;
        ctx.beginPath();
        ctx.arc(cx + Math.cos(p.a) * p.d, cy + Math.sin(p.a) * p.d, p.r * (1 - u * .4), 0, 6.2832);
        ctx.fill();
      }
      for (let i = sparks.length - 1; i >= 0; i--) {
        const p = sparks[i]; p.t += dt;
        const u = p.t / p.life;
        if (u >= 1) { sparks.splice(i, 1); continue; }
        const drag = Math.pow(.055, dt);
        p.x += p.vx * dt; p.y += p.vy * dt;
        p.vx *= drag; p.vy = p.vy * drag + 230 * dt;
        ctx.globalAlpha = (1 - u) * (1 - u);
        ctx.strokeStyle = p.hot ? "#fff" : base;
        ctx.lineWidth = p.r * (1 - u * .5) * 2;
        ctx.lineCap = "round";
        ctx.beginPath(); ctx.moveTo(p.x, p.y);
        ctx.lineTo(p.x - p.vx * dt * 2.4, p.y - p.vy * dt * 2.4); ctx.stroke();
      }
      for (let i = 0; i < motes.length; i++) {
        const p = motes[i];
        p.a += p.w * dt; p.ph += dt * 2.1; p.d += dt * 1.4;
        ctx.globalAlpha = .22 + Math.sin(p.ph) * .2;
        ctx.fillStyle = lite;
        ctx.beginPath();
        ctx.arc(cx + Math.cos(p.a) * p.d, cy + Math.sin(p.a) * p.d, p.r, 0, 6.2832);
        ctx.fill();
      }
      ctx.globalAlpha = 1;
      ctx.globalCompositeOperation = "source-over";
      raf = requestAnimationFrame(step);
    }

    resize();
    raf = requestAnimationFrame(step);
    return {
      resize, center, converge, burst, comet, ambient, wave, flare,
      stop() {
        dead = true; if (raf) cancelAnimationFrame(raf), raf = 0;
        dust = sparks = motes = waves = rays = [];
      }
    };
  }
  /* ================= 配置参数 ================= */
  const DEF = {
    score: 0,            // 目标分数
    from: 0,             // 起始分数
    max: null,           // 满环对应分值；null=自动
    ratio: null,         // 指定环填充比 0~1
    decimals: null,      // 小数位
    suffix: "",          // 后缀"%" / "分"
    label: "SCORE",      // 小字
    delta: null,         // 变化量
    deltaSuffix: "",     // 变化量后缀
    kicker: "",          // 顶部标签
    grade: "",           // 徽章等级
    title: "",           // 徽章称号
    icon: "",            // 徽章图标
    sub: "",             // 底部副文案
    rows: null,          // 明细,最多 8 条
    tone: null,          // 配色
    result: null,        // win|loss|draw
    countDuration: 1500, // 滚动时长(ms)
    hold: 2600,          // 高潮后自动关闭的停留时长(ms)
    autoClose: false,    // 是否自动关闭
    closable: true,      // 是否显示关闭按钮
    hint: "",            // 底部提示文案
    skippable: true,     // 跳过动画
    quality: "auto",     // auto|0|1|2
    onReveal: null,      // 高潮时回调
    onClose: null,       // 关闭时回调
  };

  function pickTone(o) {
    if (o.tone) return buildTone(o.tone);
    if (o.result === "win") return buildTone(TONES.gold);
    if (o.result === "loss") return buildTone(TONES.red);
    if (o.result === "draw") return buildTone(TONES.blue);
    if (typeof o.delta === "number" && o.delta !== 0)
      return buildTone(o.delta > 0 ? TONES.gold : TONES.red);
    return buildTone(TONES.gold);
  }
  const R = 152, VB = 340, CIRC = 2 * Math.PI * R;
  function buildDOM(o, tone, quality) {
    const root = document.createElement("div");
    root.className = "fhqs-root fhqs-q" + quality;
    root.style.setProperty("--t", tone.base);
    root.style.setProperty("--tl", tone.lite);
    root.style.setProperty("--td", tone.deep);
    root.setAttribute("role", "dialog");
    root.setAttribute("aria-modal", "true");
    root.setAttribute("aria-label", (o.kicker || "结算") + " " + o.score + (o.suffix || ""));

    const badge = (o.grade || o.title || o.icon) ? `<div class="fhqs-badge">
  ${o.icon ? `<span class="fhqs-bicon">${o.icon}</span>` : ""}
  ${o.grade ? `<span class="fhqs-bgrade">${esc(o.grade)}</span>` : ""}
  ${o.grade && o.title ? `<span class="fhqs-bsep"></span>` : ""}
  ${o.title ? `<span class="fhqs-btitle">${esc(o.title)}</span>` : ""}
</div>` : "";

    const rows = (o.rows && o.rows.length) ? `<div class="fhqs-rows">${o.rows.slice(0, 8).map(r => {
      const mx = +r.max > 0 ? +r.max : 1;
      const pct = clamp((+r.value || 0) / mx * 100, 0, 100);
      return `<div class="fhqs-row" data-pct="${pct.toFixed(1)}">
          <span class="fhqs-rl">${esc(r.label)}</span>
          <span class="fhqs-rb"><i></i></span>
          <span class="fhqs-rv">${esc(r.value)}</span>
        </div>`;
    }).join("")}</div>` : "";

    root.innerHTML =
      `<div class="fhqs-veil"></div>` +
      `<canvas class="fhqs-cv"></canvas>` +
      `<div class="fhqs-flash"></div>` +
      `<div class="fhqs-stage">` +
      (o.kicker ? `<div class="fhqs-kicker">${esc(o.kicker)}</div>` : "") +
      `<div class="fhqs-ring">
          <div class="fhqs-halo"></div><div class="fhqs-dial"></div>
          <svg class="fhqs-svg" viewBox="0 0 ${VB} ${VB}" aria-hidden="true">
            <defs><linearGradient id="fhqsGrad" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0%" stop-color="rgb(${tone.base})"/>
              <stop offset="55%" stop-color="rgb(${tone.lite})"/>
              <stop offset="100%" stop-color="#fff"/>
            </linearGradient></defs>
            <circle class="fhqs-track" cx="${VB / 2}" cy="${VB / 2}" r="${R}"/>
            <circle class="fhqs-ticks" cx="${VB / 2}" cy="${VB / 2}" r="${R + 15}"/>
            <circle class="fhqs-arc-glow" cx="${VB / 2}" cy="${VB / 2}" r="${R}"/>
            <circle class="fhqs-arc" cx="${VB / 2}" cy="${VB / 2}" r="${R}"/>
          </svg>
          <div class="fhqs-core">
            <div class="fhqs-num"><span class="fhqs-val">0</span>${o.suffix ? `<span class="fhqs-suffix">${esc(o.suffix)}</span>` : ""}</div>
            ${o.label ? `<div class="fhqs-label">${esc(o.label)}</div>` : ""}
            ${o.delta !== null && o.delta !== undefined ? `<div class="fhqs-delta"></div>` : ""}
          </div>
        </div>` +
      badge +
      (o.sub ? `<div class="fhqs-sub">${esc(o.sub)}</div>` : "") +
      rows +
      `</div>` +
      (o.closable ? `<button class="fhqs-close" type="button" aria-label="关闭">✕</button>` : "") +
      (o.hint ? `<div class="fhqs-hint">${esc(o.hint)}</div>` : "");
    return root;
  }
  let live = null;
  function show(opts) {
    if (!hasWin) return Promise.resolve("noop");
    const o = Object.assign({}, DEF, opts || {});
    if (live) live.kill("replaced");
    injectCSS();

    const reduced = prefersReduced();
    let quality = o.quality === "auto" ? detectQuality() : clamp(o.quality | 0, 0, 2);
    if (reduced) quality = 0;

    const tone = pickTone(o);
    const dec = o.decimals !== null && o.decimals !== undefined
      ? o.decimals : (Number.isInteger(+o.score) ? 0 : 1);
    const score = +o.score || 0, from = +o.from || 0;
    const max = o.ratio !== null && o.ratio !== undefined
      ? null : (+o.max > 0 ? +o.max : autoMax(Math.max(score, from)));
    const pFrom = o.ratio !== null && o.ratio !== undefined ? 0 : clamp(from / max, 0, 1);
    const pTo = o.ratio !== null && o.ratio !== undefined
      ? clamp(+o.ratio, 0, 1) : clamp(score / max, 0, 1);
    const dur = reduced ? 1 : Math.max(120, +o.countDuration || 0);

    const root = buildDOM(o, tone, quality);
    document.body.appendChild(root);

    const $ = s => root.querySelector(s);
    const stage = $(".fhqs-stage"), cv = $(".fhqs-cv"), flash = $(".fhqs-flash");
    const arc = $(".fhqs-arc"), glow = $(".fhqs-arc-glow"), ring = $(".fhqs-ring");
    const valEl = $(".fhqs-val"), deltaEl = $(".fhqs-delta"), closeBtn = $(".fhqs-close");
    const prevFocus = document.activeElement;

    let fx = quality > 0 ? createFX(cv, quality, tone) : null;
    let timers = [], raf = 0, done = false, settled = false, resolveFn = null;
    const at = (ms, fn) => timers.push(setTimeout(fn, reduced ? Math.min(ms, 16) : ms));

    [arc, glow].forEach(c => {
      c.style.strokeDasharray = CIRC;
      c.style.strokeDashoffset = CIRC * (1 - pFrom);
    });
    function metrics() {
      const b = ring.getBoundingClientRect();
      return { x: b.left + b.width / 2, y: b.top + b.height / 2, r: b.width / 2 * (R / (VB / 2)) };
    }
    function setArc(p) {
      const off = CIRC * (1 - p);
      arc.style.strokeDashoffset = off;
      glow.style.strokeDashoffset = off;
    }
    function pulse(peak) {
      if (!flash || !flash.animate) return;
      flash.animate([{ opacity: 0 }, { opacity: peak }, { opacity: 0 }],
        { duration: 520, easing: "ease-out" });
    }
    at(120, () => {
      root.classList.add("fhqs-ready");
      if (fx) { const m = metrics(); fx.center(m.x, m.y); fx.converge(m.r); }
    });

    at(reduced ? 20 : 430, () => {
      const m = metrics();
      if (fx) { fx.center(m.x, m.y); fx.wave(m.r * .3, m.r * 1.9, .55); }
      pulse(.5);
      if (!reduced) {
        stage.classList.add("fhqs-shake");
        at(460, () => stage.classList.remove("fhqs-shake"));
      }
      root.classList.add("fhqs-lit");
      startCount();
    });

    function startCount() {
      const t0 = now();
      let lastTs = t0;
      const tick = () => {
        if (settled) return;
        const ts = now();
        const u = clamp((ts - t0) / dur, 0, 1);
        const e = easeOutQuart(u);
        const p = pFrom + (pTo - pFrom) * e;
        valEl.textContent = fmtNum(from + (score - from) * e, dec);
        setArc(p);
        if (fx) {
          const m = metrics(); fx.center(m.x, m.y);
          if (u < 1) fx.comet(m.r, -Math.PI / 2 + p * Math.PI * 2, Math.min((ts - lastTs) / 1000, .05));
        }
        lastTs = ts;
        if (u < 1) raf = requestAnimationFrame(tick);
        else { raf = 0; climax(); }
      };
      raf = requestAnimationFrame(tick);
    }

    function climax() {
      if (done || settled) return;
      done = true;
      valEl.textContent = fmtNum(score, dec);
      setArc(pTo);
      root.classList.add("fhqs-done");

      if (deltaEl) {
        const d = +o.delta || 0;
        const sign = d > 0 ? "+" : d < 0 ? "−" : "±";
        const txt = sign + fmtNum(Math.abs(d), Number.isInteger(d) ? 0 : 1) + (o.deltaSuffix || "");
        deltaEl.textContent = txt;
        if (!reduced && d !== 0) {
          const fly = document.createElement("span");
          fly.className = "fhqs-fly";
          fly.textContent = txt;
          ring.appendChild(fly);
          fly.addEventListener("animationend", () => fly.remove(), { once: true });
        }
      }
      if (fx) { const m = metrics(); fx.center(m.x, m.y); fx.burst(m.r); fx.flare(m.r); fx.ambient(m.r); }
      pulse(.72);

      const rows = root.querySelectorAll(".fhqs-row");
      rows.forEach((r, i) => at(120 + i * 70, () => {
        r.classList.add("on");
        const bar = r.querySelector("i");
        if (bar) bar.style.width = r.getAttribute("data-pct") + "%";
      }));

      if (closeBtn) at(reduced ? 30 : 1000, () => { try { closeBtn.focus({ preventScroll: true }); } catch (e) { } });
      if (typeof o.onReveal === "function") { try { o.onReveal(); } catch (e) { } }
      if (o.autoClose) at(Math.max(0, +o.hold || 0), () => close("auto"));
    }
    function skip() {
      if (done || settled) return;
      timers.forEach(clearTimeout); timers = [];
      if (raf) cancelAnimationFrame(raf), raf = 0;
      root.classList.add("fhqs-ready", "fhqs-lit");
      climax();
    }
    function close(reason) {
      if (settled) return;
      settled = true;
      timers.forEach(clearTimeout); timers = [];
      if (raf) cancelAnimationFrame(raf), raf = 0;
      root.classList.add("fhqs-out");
      window.removeEventListener("resize", onResize);
      document.removeEventListener("keydown", onKey, true);
      setTimeout(() => {
        if (fx) fx.stop(), fx = null;
        if (root.parentNode) root.parentNode.removeChild(root);
        if (prevFocus && prevFocus.focus) { try { prevFocus.focus({ preventScroll: true }); } catch (e) { } }
      }, reduced ? 20 : 340);
      if (live && live.root === root) live = null;
      if (typeof o.onClose === "function") { try { o.onClose(reason); } catch (e) { } }
      if (resolveFn) resolveFn(reason);
    }

    function onResize() { if (fx) { fx.resize(); const m = metrics(); fx.center(m.x, m.y); } }
    function onKey(e) {
      if (e.key === "Escape" || e.key === "Esc") {
        e.stopPropagation();
        if (!done && o.skippable) skip();
        else if (o.closable) close("esc");
      }
    }
    window.addEventListener("resize", onResize, { passive: true });
    document.addEventListener("keydown", onKey, true);

    root.addEventListener("click", e => {
      if (closeBtn && closeBtn.contains(e.target)) { close("button"); return; }
      if (!done) { if (o.skippable) skip(); return; }
      const onBackdrop = e.target === root || e.target === $(".fhqs-veil");
      if (o.closable && onBackdrop) close("backdrop");
    });
    root.addEventListener("wheel", e => e.preventDefault(), { passive: false });
    root.addEventListener("touchmove", e => { if (e.touches.length === 1) e.preventDefault(); }, { passive: false });

    const p = new Promise(res => { resolveFn = res; });
    p.close = close; p.skip = skip; p.root = root;
    live = { root, kill: close, skip };
    return p;
  }
  const RESULT_PRESET = {
    win: { kicker: "对局胜利", result: "win", label: "SCORE" },
    loss: { kicker: "对局失利", result: "loss", label: "SCORE" },
    draw: { kicker: "和棋", result: "draw", label: "SCORE" },
  };

  const API = {
    version: VER,

    /** 主入口*/
    show,
    result(kind, opts) {
      return show(Object.assign({}, RESULT_PRESET[kind] || RESULT_PRESET.draw, opts || {}));
    },
    fromRating(match, side, extra) {
      if (!match || !match[side]) return Promise.resolve("noop");
      const sc = match[side], t = sc.tier || {};
      const L = { kill: "击杀", survive: "生存", strategy: "战略", resource: "资源", offense: "进攻", discipline: "纪律" };
      const MAXD = { kill: 4, survive: 3.2, strategy: 3.2, resource: 2.4, offense: 1.6, discipline: 1.6 };
      return show(Object.assign({
        score: sc.rating, max: 16, decimals: 1,
        from: 0, delta: sc.elo, deltaSuffix: " 分",
        kicker: sc.result === "win" ? "对局胜利" : sc.result === "loss" ? "对局失利" : "和棋",
        result: sc.result, tone: t.color || null,
        grade: t.grade || "", title: t.title || "", icon: t.icon || "",
        label: "RATING", sub: sc.percentile ? "超越 " + sc.percentile + "% 的玩家" : "",
        rows: Object.keys(sc.breakdown || {}).map(k =>
          ({ label: L[k] || k, value: sc.breakdown[k], max: MAXD[k] || 4 })),
      }, extra || {}));
    },

    /** 关掉悬浮层 */
    close(reason) { if (live) live.kill(reason || "manual"); return API; },

    /** 跳转结果态 */
    skip() { if (live) live.skip(); return API; },

    isPlaying() { return !!live; },
    quality() { return prefersReduced() ? 0 : detectQuality(); },

    /** 注入样式 */
    preload() {
      if (!hasWin) return API;
      if (document.readyState === "loading")
        document.addEventListener("DOMContentLoaded", injectCSS, { once: true });
      else injectCSS();
      return API;
    },
  };

  if (hasWin) API.preload();
  return API;
});
