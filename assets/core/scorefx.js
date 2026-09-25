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
.fhqs-cv{position:absolute;inset:0;width:100%;height:100%;display:block;pointer-events:none;z-index:1}
.fhqs-flash{position:absolute;inset:0;opacity:0;pointer-events:none;mix-blend-mode:screen;z-index:6;
background:radial-gradient(circle at 50% 50%,#fff 0%,rgba(var(--tl),.72) 30%,rgba(var(--t),0) 66%)}
.fhqs-rush,.fhqs-cracks{position:absolute;inset:0;pointer-events:none;overflow:hidden;opacity:0}
.fhqs-rush::before,.fhqs-rush::after{content:"";position:absolute;left:50%;top:50%;width:150vmax;height:2px;
transform-origin:center;background:linear-gradient(90deg,transparent,rgba(255,255,255,.95),rgba(var(--t),.9),transparent);
filter:blur(.2px);box-shadow:0 0 14px rgba(var(--tl),.8)}
.fhqs-rush::before{transform:translate(-50%,-50%) rotate(18deg)}
.fhqs-rush::after{transform:translate(-50%,-50%) rotate(-18deg);opacity:.7}
.fhqs-rank-rise .fhqs-rush{animation:fhqs-rush-in .9s cubic-bezier(.16,1,.3,1) .2s both}
@keyframes fhqs-rush-in{0%{opacity:0;transform:scale(.25)}35%{opacity:1}100%{opacity:0;transform:scale(1.25)}}
.fhqs-cracks::before,.fhqs-cracks::after{content:"";position:absolute;left:50%;top:50%;width:min(68vw,560px);height:min(68vw,560px);
transform:translate(-50%,-50%) rotate(14deg);border:1px solid rgba(255,92,82,.7);
clip-path:polygon(49% 0,52% 38%,76% 14%,58% 46%,100% 45%,60% 54%,82% 82%,54% 59%,48% 100%,45% 61%,14% 82%,40% 55%,0 58%,39% 46%,17% 18%,45% 39%);
box-shadow:0 0 28px rgba(255,61,52,.45)}
.fhqs-cracks::after{transform:translate(-50%,-50%) rotate(-30deg) scale(.72);opacity:.55}
.fhqs-rank-drop .fhqs-cracks{animation:fhqs-crack-in .8s cubic-bezier(.2,.8,.3,1) .18s both}
@keyframes fhqs-crack-in{0%{opacity:0;transform:scale(1.6)}20%{opacity:1}100%{opacity:.72;transform:scale(1)}}
/* ===== 段位奇观：龙盘绕冲天 / 恶魔捏碎心脏 ===== */
.fhqs-spectacle{position:absolute;inset:0;z-index:2;display:flex;align-items:center;justify-content:center;
pointer-events:none;perspective:1100px;overflow:hidden}
.fhqs-spec-cv{position:absolute;inset:0;width:100%;height:100%;display:block;pointer-events:none;z-index:1;opacity:0;
transition:opacity .4s ease-out}
.fhqs-lit .fhqs-spec-cv{opacity:1}
.fhqs-spectacle svg{width:min(90vw,660px);height:min(90vw,660px);overflow:visible;opacity:0;
filter:drop-shadow(0 0 34px rgba(var(--t),.62));transition:opacity .6s ease-out}
.fhqs-lit .fhqs-spectacle svg{opacity:1}
.fhqs-dragon,.fhqs-demon{transform-style:preserve-3d}
.fhqs-dragon{transform-origin:50% 58%}
.fhqs-demon{transform-origin:50% 56%}
.fhqs-spec-rays{position:absolute;top:50%;left:50%;width:170vmax;height:170vmax;margin:-85vmax 0 0 -85vmax;
opacity:0;mix-blend-mode:screen;border-radius:50%;pointer-events:none;
background:conic-gradient(from 0deg,rgba(255,226,168,.32) 0deg,rgba(255,226,168,0) 8deg,
rgba(255,226,168,0) 22deg,rgba(255,226,168,.28) 30deg,rgba(255,226,168,0) 38deg,
rgba(255,226,168,0) 52deg,rgba(255,226,168,.32) 60deg,rgba(255,226,168,0) 68deg,
rgba(255,226,168,0) 82deg,rgba(255,226,168,.24) 90deg,rgba(255,226,168,0) 98deg,
rgba(255,226,168,0) 112deg,rgba(255,226,168,.3) 120deg,rgba(255,226,168,0) 128deg,
rgba(255,226,168,0) 142deg,rgba(255,226,168,.26) 150deg,rgba(255,226,168,0) 158deg,
rgba(255,226,168,0) 172deg,rgba(255,226,168,.32) 180deg,rgba(255,226,168,0) 188deg,
rgba(255,226,168,0) 202deg,rgba(255,226,168,.28) 210deg,rgba(255,226,168,0) 218deg,
rgba(255,226,168,0) 232deg,rgba(255,226,168,.32) 240deg,rgba(255,226,168,0) 248deg,
rgba(255,226,168,0) 262deg,rgba(255,226,168,.24) 270deg,rgba(255,226,168,0) 278deg,
rgba(255,226,168,0) 292deg,rgba(255,226,168,.3) 300deg,rgba(255,226,168,0) 308deg,
rgba(255,226,168,0) 322deg,rgba(255,226,168,.26) 330deg,rgba(255,226,168,0) 338deg,
rgba(255,226,168,0) 352deg,rgba(255,226,168,.32) 360deg);
-webkit-mask-image:radial-gradient(circle at 50% 50%,transparent 12%,#000 36%,transparent 64%);
mask-image:radial-gradient(circle at 50% 50%,transparent 12%,#000 36%,transparent 64%)}
.fhqs-demon .fhqs-spec-rays{filter:hue-rotate(-18deg) saturate(1.25)}
.fhqs-root.fhqs-lit.fhqs-soar .fhqs-spec-rays,.fhqs-root.fhqs-lit.fhqs-crush .fhqs-spec-rays{
animation:fhqs-spec-rays-in 1.1s ease-out both,fhqs-spin 20s linear infinite}
@keyframes fhqs-spec-rays-in{0%{opacity:0;transform:scale(.5)}100%{opacity:.95;transform:scale(1)}}
/* 龙：入场盘绕蓄力 */
.fhqs-lit .fhqs-dragon{animation:fhqs-dragon-enter 1.2s cubic-bezier(.16,.8,.24,1) both}
.fhqs-dragon-body{transform-origin:50% 58%;animation:fhqs-dragon-coil 2.6s ease-in-out .2s infinite}
.fhqs-dragon-wing{transform-origin:50% 50%;animation:fhqs-wing-beat .46s ease-in-out .4s infinite alternate}
.fhqs-dragon-wing.back{opacity:.5;transform:scaleX(-1)}
.fhqs-dragon-tail{transform-origin:50% 70%;animation:fhqs-tail-sway 1.8s ease-in-out infinite}
.fhqs-dragon-eye{animation:fhqs-eye-glow 1.4s ease-in-out infinite}
/* 龙：冲天 */
.fhqs-root.fhqs-lit.fhqs-soar .fhqs-dragon{animation:fhqs-dragon-soar 1.55s cubic-bezier(.16,.7,.2,1) both}
.fhqs-soar .fhqs-dragon-flame{animation:fhqs-flame-burst .6s ease-out .05s both}
.fhqs-soar .fhqs-dragon-wing{animation:fhqs-wing-beat .26s ease-in-out infinite alternate}
/* 恶魔：入场降临 */
.fhqs-lit .fhqs-demon{animation:fhqs-demon-enter 1s cubic-bezier(.15,.8,.25,1) both}
.fhqs-demon-shadow{transform-origin:50% 60%;animation:fhqs-shadow-breathe 2.4s ease-in-out infinite}
.fhqs-demon-eye{animation:fhqs-eye-glow 1.2s ease-in-out infinite}
.fhqs-demon-hand{transform-origin:50% 52%;animation:fhqs-hand-grab 2.2s ease-in-out .3s infinite}
.fhqs-demon-heart{transform-origin:50% 56%;animation:fhqs-heart-beat 1s ease-in-out infinite}
/* 恶魔：捏碎 */
.fhqs-root.fhqs-lit.fhqs-crush .fhqs-demon-hand{animation:fhqs-hand-clench 1.1s cubic-bezier(.2,.9,.2,1) both}
.fhqs-root.fhqs-lit.fhqs-crush .fhqs-demon-heart{animation:fhqs-heart-crush 1.1s cubic-bezier(.2,.9,.2,1) both}
.fhqs-demon-shards{opacity:0;transform-origin:50% 56%}
.fhqs-root.fhqs-lit.fhqs-crush .fhqs-demon-shards{animation:fhqs-shards-explode .8s ease-out .55s both}
@keyframes fhqs-dragon-enter{0%{opacity:0;transform:translateY(28vh) scale(.32) rotateX(20deg) rotateY(-30deg)}55%{opacity:1}100%{opacity:1;transform:translateY(0) scale(1) rotateX(0) rotateY(0)}}
@keyframes fhqs-dragon-coil{0%{transform:rotateZ(-7deg) rotateY(-16deg)}50%{transform:rotateZ(7deg) rotateY(16deg)}100%{transform:rotateZ(-7deg) rotateY(-16deg)}}
@keyframes fhqs-wing-beat{from{transform:rotateX(0) rotateZ(0) scaleY(1)}to{transform:rotateX(34deg) rotateZ(6deg) scaleY(.62)}}
@keyframes fhqs-tail-sway{0%,100%{transform:rotateZ(-6deg)}50%{transform:rotateZ(8deg)}}
@keyframes fhqs-eye-glow{0%,100%{opacity:.8}50%{opacity:1;filter:drop-shadow(0 0 12px #ffd34d)}}
@keyframes fhqs-dragon-soar{0%{transform:translateY(0) scale(1) rotateX(0) rotateY(0)}25%{transform:translateY(-6vh) scale(1.08) rotateX(-6deg) rotateY(20deg)}60%{opacity:1;transform:translateY(-22vh) scale(1.22) rotateX(-10deg) rotateY(40deg)}100%{opacity:0;transform:translateY(-64vh) scale(1.9) rotateX(-18deg) rotateY(70deg)}}
@keyframes fhqs-flame-burst{0%{opacity:0;transform:scale(.2)}35%{opacity:1;transform:scale(1.6)}100%{opacity:0;transform:scale(2.8)}}
@keyframes fhqs-demon-enter{0%{opacity:0;transform:translateY(24vh) scale(.5) rotate(-6deg)}55%{opacity:1}100%{opacity:1;transform:translateY(0) scale(1) rotate(0)}}
@keyframes fhqs-shadow-breathe{0%,100%{transform:scale(1);opacity:.8}50%{transform:scale(1.08);opacity:1}}
@keyframes fhqs-hand-grab{0%{transform:translateY(8%) scale(1.05)}50%{transform:translateY(0) scale(.96)}100%{transform:translateY(8%) scale(1.05)}}
@keyframes fhqs-heart-beat{0%,100%{transform:scale(1)}18%{transform:scale(1.16)}30%{transform:scale(1)}46%{transform:scale(1.1)}60%{transform:scale(1)}}
@keyframes fhqs-hand-clench{0%{transform:translateY(0) scale(1)}45%{transform:translateY(-2%) scale(.9)}72%{transform:scale(.74) rotate(0)}100%{transform:scale(.8)}}
@keyframes fhqs-heart-crush{0%{transform:scale(1);filter:drop-shadow(0 0 14px #ff4c4c)}40%{transform:scale(.84) rotate(7deg);filter:drop-shadow(0 0 30px #ff2525)}70%{transform:scale(.3) rotate(-12deg)}100%{transform:scale(0);opacity:0}}
@keyframes fhqs-shards-explode{0%{opacity:0;transform:scale(.2)}18%{opacity:1}100%{opacity:0;transform:scale(2.6) rotate(40deg)}}
.fhqs-root.fhqs-shake-root{animation:fhqs-shake .5s cubic-bezier(.36,.07,.19,.97) both}
@media (prefers-reduced-motion:reduce){.fhqs-spectacle{display:none}.fhqs-spec-cv{display:none}}
@keyframes fhqs-veil-in{to{opacity:1}}
@keyframes fhqs-veil-out{from{opacity:1}to{opacity:0}}`,

    `.fhqs-stage{position:relative;z-index:5;display:flex;flex-direction:column;align-items:center;
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
.fhqs-rank-mode .fhqs-done .fhqs-badge{animation:fhqs-rank-badge .86s cubic-bezier(.16,1.25,.3,1) .08s forwards}
@keyframes fhqs-rank-badge{0%{opacity:0;transform:translateY(92px) scale(.38) rotate(-9deg);filter:blur(10px)}58%{opacity:1;transform:translateY(-10px) scale(1.1) rotate(2deg);filter:blur(0)}100%{opacity:1;transform:translateY(0) scale(1) rotate(0);filter:blur(0)}}
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
  /* ================= 段位奇观粒子引擎 ================= */
  function createSpectacleFX(canvas, quality, tone, kind) {
    const ctx = canvas.getContext("2d", { alpha: true });
    if (!ctx) return null;
    const k = quality >= 2 ? 1 : 0.55;
    const C = kind === "demon"
      ? ["255,90,72", "255,150,120", "255,40,56", "255,200,190", "120,20,30"]
      : ["255,240,190", "255,205,120", "255,150,70", "255,255,250", "255,180,70"];
    const pick = () => C[(Math.random() * C.length) | 0];
    const rnd = (a, b) => a + Math.random() * (b - a);
    let embers = [], coil = [], beam = [], rings = [], shards = [], ash = [];
    let W = 0, H = 0, dpr = 1, cx = 0, cy = 0, raf = 0, last = 0, dead = false, ambient = false;
    const trails = quality >= 2;
    const CAP = quality >= 2 ? 760 : 380;

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

    function spawnAmbient(dt) {
      const nc = (kind === "demon" ? 3 : 4) * k * (dt * 60);
      for (let i = 0; i < nc; i++) {
        const a = rnd(0, 6.2832), r = rnd(40, Math.min(W, H) * 0.34);
        coil.push({ a, r, vr: (kind === "demon" ? -1 : 1) * rnd(0.6, 1.4), t: 0, life: rnd(0.7, 1.6), size: rnd(1, 2.6), c: pick() });
      }
      trim(coil);
      const ne = (kind === "demon" ? 2 : 3) * k * (dt * 60);
      for (let i = 0; i < ne; i++) {
        embers.push({
          x: cx + rnd(-W * 0.18, W * 0.18), y: cy + rnd(-H * 0.12, H * 0.2),
          vx: rnd(-12, 12), vy: (kind === "demon" ? rnd(-30, -8) : rnd(-46, -14)),
          t: 0, life: rnd(1.1, 2.4), size: rnd(1, 2.8), c: pick(), flick: rnd(2, 6)
        });
      }
      trim(embers);
      if (kind === "demon") {
        const na = 1.4 * k * (dt * 60);
        for (let i = 0; i < na; i++) {
          ash.push({ x: cx + rnd(-W * 0.2, W * 0.2), y: cy + rnd(0, H * 0.25),
            vx: rnd(-20, 20), vy: rnd(-10, 18), t: 0, life: rnd(1.4, 3), size: rnd(8, 22), c: "40,12,16" });
        }
        trim(ash);
      }
    }
    function ring(r0, r1, a0, col) { rings.push({ r0, r1, a0, t: 0, life: kind === "demon" ? 0.85 : 1.0, col: col || C[2] }); }

    function launch() {
      ring(20, Math.max(W, H) * 0.55, 0.5, C[1]);
      ring(10, Math.max(W, H) * 0.42, 0.42, C[0]);
      const nb = Math.round(170 * k);
      for (let i = 0; i < nb; i++) {
        beam.push({
          x: cx + rnd(-60, 60), y: cy + rnd(-30, 30),
          vx: rnd(-26, 26), vy: -rnd(320, 920),
          t: 0, life: rnd(0.5, 1.1), size: rnd(1.2, 3), c: pick()
        });
      }
      trim(beam);
      const ne = Math.round(130 * k);
      for (let i = 0; i < ne; i++) {
        const a = rnd(-Math.PI * 0.9, -Math.PI * 0.1), sp = rnd(120, 640);
        embers.push({ x: cx, y: cy, vx: Math.cos(a) * sp, vy: Math.sin(a) * sp, t: 0, life: rnd(0.7, 1.6), size: rnd(1.4, 3.4), c: pick(), flick: rnd(2, 6) });
      }
      trim(embers);
    }
    function crush() {
      ring(20, Math.max(W, H) * 0.5, 0.6, C[2]);
      ring(10, Math.max(W, H) * 0.38, 0.5, C[0]);
      const ns = Math.round(90 * k);
      for (let i = 0; i < ns; i++) {
        const a = rnd(0, 6.2832), sp = rnd(160, 580);
        shards.push({ x: cx, y: cy, vx: Math.cos(a) * sp, vy: Math.sin(a) * sp - 60, rot: rnd(0, 6.28), vr: rnd(-8, 8), t: 0, life: rnd(0.6, 1.2), size: rnd(4, 12), c: pick() });
      }
      trim(shards);
      const ne = Math.round(130 * k);
      for (let i = 0; i < ne; i++) {
        const a = rnd(0, 6.2832), sp = rnd(120, 560);
        embers.push({ x: cx, y: cy, vx: Math.cos(a) * sp, vy: Math.sin(a) * sp, t: 0, life: rnd(0.6, 1.4), size: rnd(1.2, 3), c: pick(), flick: rnd(2, 6) });
      }
      trim(embers);
    }

    function step(ts) {
      if (dead) return;
      raf = requestAnimationFrame(step);
      const dt = last ? Math.min((ts - last) / 1000, 0.05) : 0.016;
      last = ts;
      ctx.globalCompositeOperation = "destination-out";
      ctx.fillStyle = "rgba(0,0,0," + (trails ? 0.16 : 0.3) + ")";
      ctx.fillRect(0, 0, W, H);
      ctx.globalCompositeOperation = "lighter";

      if (ambient) spawnAmbient(dt);

      for (let i = rings.length - 1; i >= 0; i--) {
        const p = rings[i]; p.t += dt;
        const u = p.t / p.life; if (u >= 1) { rings.splice(i, 1); continue; }
        const e = easeOutCubic(u);
        ctx.strokeStyle = "rgba(" + p.col + "," + (1 - u) * 0.85 + ")";
        ctx.lineWidth = (1 - u) * 6 + 1;
        ctx.beginPath(); ctx.arc(cx, cy, p.r0 + (p.r1 - p.r0) * e, 0, 6.2832); ctx.stroke();
      }
      for (let i = coil.length - 1; i >= 0; i--) {
        const p = coil[i]; p.t += dt; if (p.t >= p.life) { coil.splice(i, 1); continue; }
        p.a += p.vr * dt;
        const x = cx + Math.cos(p.a) * p.r, y = cy + Math.sin(p.a) * p.r;
        ctx.globalAlpha = Math.sin(p.t / p.life * Math.PI) * 0.9;
        ctx.fillStyle = "rgba(" + p.c + ",1)";
        ctx.beginPath(); ctx.arc(x, y, p.size * (1 - p.t / p.life * 0.3), 0, 6.2832); ctx.fill();
      }
      for (let i = beam.length - 1; i >= 0; i--) {
        const p = beam[i]; p.t += dt; if (p.t >= p.life) { beam.splice(i, 1); continue; }
        p.x += p.vx * dt; p.y += p.vy * dt; p.vy += 60 * dt;
        ctx.globalAlpha = 1 - p.t / p.life;
        ctx.strokeStyle = "rgba(" + p.c + ",1)";
        ctx.lineWidth = p.size * (1 - p.t / p.life * 0.5);
        ctx.lineCap = "round";
        ctx.beginPath(); ctx.moveTo(p.x, p.y);
        ctx.lineTo(p.x - p.vx * 0.03, p.y - p.vy * 0.03); ctx.stroke();
      }
      for (let i = embers.length - 1; i >= 0; i--) {
        const p = embers[i]; p.t += dt; if (p.t >= p.life) { embers.splice(i, 1); continue; }
        p.x += p.vx * dt; p.y += p.vy * dt; p.vy += (kind === "demon" ? -4 : 30) * dt;
        ctx.globalAlpha = Math.max(0, (1 - p.t / p.life) * (0.7 + 0.3 * Math.sin(p.t * p.flick)));
        ctx.fillStyle = "rgba(" + p.c + ",1)";
        ctx.beginPath(); ctx.arc(p.x, p.y, p.size * (1 - p.t / p.life * 0.4), 0, 6.2832); ctx.fill();
      }
      for (let i = shards.length - 1; i >= 0; i--) {
        const p = shards[i]; p.t += dt; if (p.t >= p.life) { shards.splice(i, 1); continue; }
        p.x += p.vx * dt; p.y += p.vy * dt; p.vy += 120 * dt; p.rot += p.vr * dt;
        ctx.globalAlpha = 1 - p.t / p.life;
        ctx.fillStyle = "rgba(" + p.c + ",1)";
        ctx.save(); ctx.translate(p.x, p.y); ctx.rotate(p.rot);
        ctx.beginPath(); ctx.moveTo(0, -p.size); ctx.lineTo(p.size * 0.7, p.size); ctx.lineTo(-p.size * 0.7, p.size); ctx.closePath(); ctx.fill();
        ctx.restore();
      }
      ctx.globalCompositeOperation = "source-over";
      for (let i = ash.length - 1; i >= 0; i--) {
        const p = ash[i]; p.t += dt; if (p.t >= p.life) { ash.splice(i, 1); continue; }
        p.x += p.vx * dt; p.y += p.vy * dt;
        ctx.globalAlpha = (1 - p.t / p.life) * 0.25;
        ctx.fillStyle = "rgba(" + p.c + ",1)";
        ctx.beginPath(); ctx.arc(p.x, p.y, p.size * (0.6 + p.t / p.life), 0, 6.2832); ctx.fill();
      }
      ctx.globalAlpha = 1;
    }

    resize();
    raf = requestAnimationFrame(step);
    return {
      resize, center, beginAmbient() { ambient = true; }, launch, crush,
      stop() { dead = true; if (raf) cancelAnimationFrame(raf), raf = 0; embers = coil = beam = rings = shards = ash = []; }
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
    const delta = Number(o.delta || 0);
    const rankState = o.rankMode ? (delta > 0 ? " fhqs-rank-rise" : (delta < 0 ? " fhqs-rank-drop" : " fhqs-rank-stable")) : "";
    root.className = "fhqs-root fhqs-q" + quality + (o.rankMode ? " fhqs-rank-mode" : "") + rankState;
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
      (o.rankEvent ? `<canvas class="fhqs-spec-cv"></canvas>` : "") +
      (o.rankEvent === "promotion" ? `<div class="fhqs-spectacle fhqs-rank-promotion" aria-hidden="true">
        <div class="fhqs-spec-rays"></div>
        <svg class="fhqs-dragon" viewBox="0 0 600 600">
          <defs>
            <linearGradient id="fhqDragonGold" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#fff3a0"/><stop offset=".4" stop-color="#f6b53a"/><stop offset="1" stop-color="#7c2a0e"/></linearGradient>
            <linearGradient id="fhqDragonBelly" x1="0" y1="0" x2="0" y2="1"><stop stop-color="#fff0c0"/><stop offset="1" stop-color="#e9a23a"/></linearGradient>
            <linearGradient id="fhqDragonWing" x1="0" y1="0" x2="0" y2="1"><stop stop-color="#ffe58a" stop-opacity=".92"/><stop offset="1" stop-color="#b83b18" stop-opacity=".12"/></linearGradient>
            <radialGradient id="fhqDragonAura"><stop stop-color="#ffd86b" stop-opacity=".5"/><stop offset=".6" stop-color="#ff9b2e" stop-opacity=".15"/><stop offset="1" stop-color="#ff7a18" stop-opacity="0"/></radialGradient>
            <radialGradient id="fhqDragonEye"><stop stop-color="#fff"/><stop offset=".3" stop-color="#ffe75c"/><stop offset="1" stop-color="#ff4b18"/></radialGradient>
          </defs>
          <circle cx="300" cy="330" r="250" fill="url(#fhqDragonAura)"/>
          <g class="fhqs-dragon-flame"><path d="M300 470 C262 524 312 524 288 566 C360 520 336 498 350 462Z" fill="#ff7a18" opacity=".9"/><path d="M308 474 C292 516 322 510 314 544 C352 508 332 492 336 472Z" fill="#fff1a0"/></g>
          <g class="fhqs-dragon-body">
            <path class="fhqs-dragon-wing back" d="M312 300 C200 190 96 196 40 120 C150 130 244 150 340 244 C262 120 268 64 300 30 C330 116 372 168 372 268Z" fill="url(#fhqDragonWing)" stroke="#ffbd48" stroke-width="5"/>
            <path class="fhqs-dragon-wing" d="M288 300 C400 190 504 196 560 120 C450 130 356 150 260 244 C338 120 332 64 300 30 C270 116 228 168 228 268Z" fill="url(#fhqDragonWing)" stroke="#ffbd48" stroke-width="5"/>
            <g class="fhqs-dragon-tail">
              <path d="M300 196 C250 232 232 320 262 392 C282 432 278 470 300 506 C322 470 318 432 338 392 C368 320 350 232 300 196Z" fill="url(#fhqDragonGold)" stroke="#ffdd70" stroke-width="6"/>
              <path d="M262 240 L224 184 L276 206 L300 162 L324 206 L376 184 L338 240 L324 296 L276 296Z" fill="#b84319" stroke="#ffdc71" stroke-width="5"/>
            </g>
            <path d="M300 150 C246 188 226 270 258 350 C277 398 273 446 300 492 C327 446 323 398 342 350 C374 270 354 188 300 150Z" fill="url(#fhqDragonGold)" stroke="#ffdd70" stroke-width="7"/>
            <path d="M300 180 C272 210 262 280 282 348 C295 386 295 430 300 470 C305 430 305 386 318 348 C338 280 328 210 300 180Z" fill="url(#fhqDragonBelly)" opacity=".6"/>
            <path d="M300 168 C268 196 256 256 276 318 M300 200 C330 230 342 290 322 350 M300 240 C270 268 260 320 282 376" fill="none" stroke="#9c3411" stroke-width="5" stroke-linecap="round" opacity=".6"/>
            <path d="M250 300 C206 326 172 320 140 348 C190 356 232 378 270 360 M350 300 C394 326 428 320 460 348 C410 356 368 378 330 360" fill="none" stroke="#ffb63e" stroke-width="13" stroke-linecap="round"/>
            <g>
              <path d="M300 96 C256 120 246 176 274 214 C292 238 308 238 326 214 C354 176 344 120 300 96Z" fill="url(#fhqDragonGold)" stroke="#ffdd70" stroke-width="7"/>
              <path d="M276 104 C262 64 244 52 226 40 C256 64 268 86 286 110Z" fill="#b84319" stroke="#ffdc71" stroke-width="4"/>
              <path d="M324 104 C338 64 356 52 374 40 C344 64 332 86 314 110Z" fill="#b84319" stroke="#ffdc71" stroke-width="4"/>
              <path d="M276 176 C268 196 286 206 300 206 C314 206 332 196 324 176Z" fill="#c9541d" stroke="#ffdc71" stroke-width="4"/>
              <path d="M286 186 C250 196 224 214 206 242" fill="none" stroke="#ffdd70" stroke-width="4" stroke-linecap="round"/>
              <path d="M314 186 C350 196 376 214 394 242" fill="none" stroke="#ffdd70" stroke-width="4" stroke-linecap="round"/>
              <circle class="fhqs-dragon-eye" cx="282" cy="150" r="11" fill="url(#fhqDragonEye)"/>
              <circle class="fhqs-dragon-eye" cx="318" cy="150" r="11" fill="url(#fhqDragonEye)"/>
              <path d="M288 198 l6 14 6 -14 M306 198 l6 14 6 -14" fill="#fff" stroke="#ffdc71" stroke-width="2"/>
            </g>
          </g>
        </svg>
      </div>` : "") +
      (o.rankEvent === "demotion" ? `<div class="fhqs-spectacle fhqs-rank-demotion" aria-hidden="true">
        <div class="fhqs-spec-rays"></div>
        <svg class="fhqs-demon" viewBox="0 0 600 600">
          <defs>
            <radialGradient id="fhqDemonHeart"><stop stop-color="#ffb0a1"/><stop offset=".35" stop-color="#e52f45"/><stop offset="1" stop-color="#5d0719"/></radialGradient>
            <linearGradient id="fhqDemonHand" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#5b1728"/><stop offset=".5" stop-color="#d34848"/><stop offset="1" stop-color="#260711"/></linearGradient>
            <radialGradient id="fhqDemonAura"><stop stop-color="#7a1020" stop-opacity=".55"/><stop offset=".6" stop-color="#3a0a14" stop-opacity=".2"/><stop offset="1" stop-color="#000" stop-opacity="0"/></radialGradient>
            <radialGradient id="fhqDemonEye"><stop stop-color="#fff"/><stop offset=".3" stop-color="#ffd24a"/><stop offset="1" stop-color="#ff2a2a"/></radialGradient>
          </defs>
          <circle class="fhqs-demon-shadow" cx="300" cy="340" r="260" fill="url(#fhqDemonAura)"/>
          <g class="fhqs-demon-shards" fill="#ff4d55">
            <path d="M300 300 l-90 -110 116 74Z"/><path d="M300 320 l110 -100 -64 124Z"/><path d="M280 330 l-128 44 116 -70Z"/><path d="M320 330 l128 54 -134 -26Z"/><path d="M300 250 l-40 -120 70 96Z"/>
          </g>
          <g>
            <path d="M120 600 C140 470 210 430 300 430 C390 430 460 470 480 600Z" fill="url(#fhqDemonHand)" stroke="#ff6b62" stroke-width="6"/>
            <path d="M210 300 C210 230 250 196 300 196 C350 196 390 230 390 300 C390 360 350 392 300 392 C250 392 210 360 210 300Z" fill="#3a0c16" stroke="#ff6b62" stroke-width="5"/>
            <path d="M236 232 C212 180 196 168 176 150 C214 178 232 210 248 250Z" fill="#5b1728" stroke="#ff7a6a" stroke-width="4"/>
            <path d="M364 232 C388 180 404 168 424 150 C386 178 368 210 352 250Z" fill="#5b1728" stroke="#ff7a6a" stroke-width="4"/>
            <ellipse class="fhqs-demon-eye" cx="262" cy="290" rx="22" ry="14" fill="url(#fhqDemonEye)"/>
            <ellipse class="fhqs-demon-eye" cx="338" cy="290" rx="22" ry="14" fill="url(#fhqDemonEye)"/>
            <path d="M238 262 C256 250 282 252 296 264 M364 262 C346 250 320 252 304 264" fill="none" stroke="#ff6b62" stroke-width="6" stroke-linecap="round"/>
            <path d="M262 346 C282 360 318 360 338 346" fill="none" stroke="#ff6b62" stroke-width="5" stroke-linecap="round"/>
            <path d="M276 348 l8 18 8 -18 M308 348 l8 18 8 -18" fill="#fff" stroke="#ff8a7a" stroke-width="2"/>
          </g>
          <path class="fhqs-demon-heart" d="M300 300 C246 250 194 298 224 348 C244 381 280 400 300 428 C320 400 356 381 376 348 C406 298 354 250 300 300Z" fill="url(#fhqDemonHeart)" stroke="#ff7474" stroke-width="7"/>
          <path class="fhqs-demon-heart" d="M300 312 C292 340 300 360 300 392 M270 330 C284 344 290 360 286 380 M330 330 C316 344 310 360 314 380" fill="none" stroke="#ff8a8a" stroke-width="3" opacity=".7"/>
          <g class="fhqs-demon-hand" fill="url(#fhqDemonHand)" stroke="#ff6b62" stroke-width="5" stroke-linejoin="round">
            <path d="M150 250 C176 220 206 236 214 272 L238 360 L262 286 C272 250 304 258 296 296 L274 388 L308 312 C320 280 350 296 336 330 L300 396 C280 432 236 444 200 420 L150 378 C116 352 100 308 110 276Z"/>
            <path d="M450 250 C424 220 394 236 386 272 L362 360 L338 286 C328 250 296 258 304 296 L326 388 L292 312 C280 280 250 296 264 330 L300 396 C320 432 364 444 400 420 L450 378 C484 352 500 308 490 276Z"/>
          </g>
        </svg>
      </div>` : "") +
      (o.rankMode && delta > 0 ? `<div class="fhqs-rush" aria-hidden="true"></div>` : "") +
      (o.rankMode && delta < 0 ? `<div class="fhqs-cracks" aria-hidden="true"></div>` : "") +
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

    const specCv = $(".fhqs-spec-cv");
    let specFX = null;
    if (specCv && quality > 0 && (o.rankEvent === "promotion" || o.rankEvent === "demotion")) {
      specFX = createSpectacleFX(specCv, quality, tone, o.rankEvent === "promotion" ? "dragon" : "demon");
    }

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
      if (specFX) { specFX.center(window.innerWidth / 2, window.innerHeight * 0.55); specFX.beginAmbient(); }
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
      if (specFX) {
        if (o.rankEvent === "promotion") { root.classList.add("fhqs-soar"); specFX.launch(); }
        else if (o.rankEvent === "demotion") { root.classList.add("fhqs-crush"); specFX.crush(); }
        root.classList.add("fhqs-shake-root");
        at(reduced ? 30 : 520, () => root.classList.remove("fhqs-shake-root"));
      }
      pulse(.9);

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
        if (specFX) specFX.stop(), specFX = null;
        if (root.parentNode) root.parentNode.removeChild(root);
        if (prevFocus && prevFocus.focus) { try { prevFocus.focus({ preventScroll: true }); } catch (e) { } }
      }, reduced ? 20 : 340);
      if (live && live.root === root) live = null;
      if (typeof o.onClose === "function") { try { o.onClose(reason); } catch (e) { } }
      if (resolveFn) resolveFn(reason);
    }

    function onResize() { if (fx) { fx.resize(); const m = metrics(); fx.center(m.x, m.y); } if (specFX) { specFX.resize(); specFX.center(window.innerWidth / 2, window.innerHeight * 0.55); } }
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
      const tierText = score => {
        if (score >= 1600) return "S+";
        if (score >= 1300) return "S";
        if (score >= 1000) return "A";
        if (score >= 700) return "B";
        if (score >= 450) return "C";
        if (score >= 200) return "D";
        return "E";
      };
      const delta = Number(extra && extra.delta != null ? extra.delta : (sc.elo != null ? sc.elo : 0));
      const before = Number(extra && extra.scoreBefore != null ? extra.scoreBefore : (sc.rating != null ? sc.rating : 0));
      const after = Number(extra && extra.scoreAfter != null ? extra.scoreAfter : (sc.rating != null ? sc.rating : 0));
      const beforeTier = extra && (extra.tierBefore || extra.tierBeforeText) ? (extra.tierBefore || extra.tierBeforeText) : tierText(before);
      const afterTier = extra && (extra.tierAfter || extra.tierAfterText) ? (extra.tierAfter || extra.tierAfterText) : tierText(after);
      const trend = delta > 0 ? "晋升" : (delta < 0 ? "掉段" : "稳定");
      const kicker = extra && extra.resultLabel ? extra.resultLabel : (delta > 0 ? "段位提升" : (delta < 0 ? "段位下降" : "段位稳定"));
      const toneKey = delta > 0 ? "gold" : (delta < 0 ? "red" : "blue");
      return show(Object.assign({
        score: after, max: 2200, decimals: 0,
        from: before, delta: delta, deltaSuffix: " 分",
        kicker: kicker,
        result: delta > 0 ? "win" : (delta < 0 ? "loss" : "draw"), tone: toneKey,
        grade: afterTier || (t.grade || ""), title: (afterTier ? afterTier + " 段位" : (t.title || "")),
        icon: extra && extra.icon ? extra.icon : (delta > 0 ? "▲" : (delta < 0 ? "▼" : "◆")),
        label: "积分变化",
        rankMode: true,
        sub: (beforeTier && afterTier) ? (
          beforeTier + " " + before + " → " + afterTier + " " + after
        ) : (sc.percentile ? "超越 " + sc.percentile + "% 的玩家" : ""),
        rows: Object.keys(sc.breakdown || {}).map(k =>
          ({ label: L[k] || k, value: sc.breakdown[k], max: MAXD[k] || 4 })),
        hint: extra && extra.hint ? extra.hint : (trend + " · 这把对局将直接影响你的段位曲线"),
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
