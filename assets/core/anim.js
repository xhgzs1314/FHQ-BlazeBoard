(function (root, factory) {
  "use strict";
  if (typeof module !== "undefined" && module.exports) module.exports = factory();
  else root.FHQAnim = root.FHQAnim || factory();
})(typeof window !== "undefined" ? window : this, function () {
  "use strict";

  const VER = "1.0.0";
  const STYLE_ID = "fhqa-style-v1";
  const Z_BASE = 2147483000;   // 最高

  /* ================= 环境与画质分级 ================= */
  const hasWin = typeof window !== "undefined" && typeof document !== "undefined";

  function prefersReduced() {
    try { return !!(window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches); }
    catch (e) { return false; }
  }

  // 0 = 低（纯 CSS，无粒子）  1 = 中  2 = 高
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
    if (px > 4.2e6) s -= 1;                       // 高分屏渲染压力
    if (/Android\s+([1-7])\./.test(ua)) s -= 3;   // 老安卓
    if (/iPhone\s+OS\s+([1-9]|10|11)_/.test(ua)) s -= 2;
    if (cores === 0 && mem === 0) s += 1;         // 信息缺失（多为 Safari）
    return s >= 3 ? 2 : s >= 1 ? 1 : 0;
  }
  /* ================= 样式 ================= */
  const CSS = [
`.fhqa-root{position:fixed;inset:0;z-index:${Z_BASE};display:flex;align-items:center;
justify-content:center;overflow:hidden;pointer-events:none;
font-family:-apple-system,BlinkMacSystemFont,"Segoe UI","PingFang SC","Hiragino Sans GB",
"Microsoft YaHei",Roboto,Arial,sans-serif;-webkit-font-smoothing:antialiased;
contain:layout style paint}
.fhqa-root.fhqa-block{pointer-events:auto}
.fhqa-root *{box-sizing:border-box;margin:0;padding:0}
.fhqa-layer{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;
will-change:transform}
.fhqa-veil{position:absolute;inset:0;background:radial-gradient(ellipse at 50% 50%,
rgba(6,4,2,.62) 0%,rgba(3,2,1,.88) 62%,#000 100%);opacity:0;
animation:fhqa-veil-in .32s ease-out forwards}
.fhqa-q2 .fhqa-veil{-webkit-backdrop-filter:blur(7px) saturate(.85);backdrop-filter:blur(7px) saturate(.85)}
.fhqa-out .fhqa-veil{animation:fhqa-veil-out .42s ease-in forwards}
.fhqa-cv{position:absolute;inset:0;width:100%;height:100%;display:block}
.fhqa-fx{position:absolute;inset:0;pointer-events:none}
.fhqa-flash{position:absolute;inset:0;background:radial-gradient(circle at 50% 50%,
#fff 0%,rgba(255,238,205,.72) 34%,rgba(255,200,120,0) 70%);opacity:0;
mix-blend-mode:screen;will-change:opacity}
.fhqa-shake{animation:fhqa-shake .42s cubic-bezier(.36,.07,.19,.97) both}
.fhqa-ring{position:absolute;top:50%;left:50%;width:34vmin;height:34vmin;margin:-17vmin 0 0 -17vmin;
border-radius:50%;border:.4vmin solid rgba(255,232,180,.9);opacity:0;
will-change:transform,opacity}
.fhqa-ring.r2{border-color:rgba(255,170,90,.7);border-width:.9vmin}
.fhqa-ring.r3{border-color:rgba(255,255,255,.55)}
.fhqa-pillar{position:absolute;top:50%;left:50%;width:64vmin;height:150vh;
margin:-75vh 0 0 -32vmin;opacity:0;mix-blend-mode:screen;
background:linear-gradient(90deg,rgba(255,214,140,0) 0%,rgba(255,214,140,.20) 46%,
rgba(255,246,220,.42) 50%,rgba(255,214,140,.20) 54%,rgba(255,214,140,0) 100%);
will-change:transform,opacity}`,
`.fhqa-slab{position:absolute;left:50%;width:150vmax;height:26vmin;
transform:translate3d(-50%,-50%,0);will-change:transform,opacity;overflow:hidden;
box-shadow:0 1.4vmin 4vmin rgba(0,0,0,.55)}
.fhqa-slab.red{top:calc(50% - 13.6vmin);
background:linear-gradient(96deg,rgba(60,14,10,.05) 0%,rgba(122,26,20,.92) 30%,
rgba(196,58,42,.97) 74%,rgba(238,118,86,.99) 100%);
border-top:.22vmin solid rgba(255,176,150,.5)}
.fhqa-slab.blue{top:calc(50% + 13.6vmin);
background:linear-gradient(276deg,rgba(8,20,44,.05) 0%,rgba(20,52,110,.92) 30%,
rgba(46,104,186,.97) 74%,rgba(120,178,242,.99) 100%);
border-bottom:.22vmin solid rgba(176,214,255,.5)}
.fhqa-slab.red{animation:fhqa-slab-l .62s cubic-bezier(.16,.9,.2,1) both}
.fhqa-slab.blue{animation:fhqa-slab-r .62s cubic-bezier(.16,.9,.2,1) both}
.fhqa-out .fhqa-slab.red{animation:fhqa-slab-lo .38s cubic-bezier(.5,0,.9,.3) both}
.fhqa-out .fhqa-slab.blue{animation:fhqa-slab-ro .38s cubic-bezier(.5,0,.9,.3) both}
.fhqa-sheen{position:absolute;top:0;bottom:0;left:calc(50% - 44vmin);width:34vmin;pointer-events:none;
background:linear-gradient(104deg,rgba(255,255,255,0) 0%,rgba(255,255,255,.42) 48%,
rgba(255,255,255,0) 100%);opacity:0;will-change:transform,opacity}
.fhqa-q0 .fhqa-sheen{display:none}`,

`.fhqa-name{position:absolute;top:50%;transform:translate3d(0,-50%,0);
font-size:7.2vmin;font-weight:900;letter-spacing:.04em;line-height:1.08;
white-space:nowrap;max-width:40vw;overflow:hidden;text-overflow:ellipsis;
text-shadow:0 .3vmin 1.2vmin rgba(0,0,0,.6)}
.fhqa-slab.red .fhqa-name{color:#fff3ec;right:calc(50% + 5vmin);text-align:right}
.fhqa-slab.blue .fhqa-name{color:#eef6ff;left:calc(50% + 5vmin);text-align:left}
.fhqa-side{display:block;font-size:2.3vmin;font-weight:700;letter-spacing:.42em;
opacity:.7;margin-bottom:.9vmin}
.fhqa-slab.blue .fhqa-side{text-indent:.42em}`,

`@media (max-aspect-ratio:3/4){.fhqa-name{font-size:6vmin;max-width:46vw}
.fhqa-vs{font-size:24vmin}.fhqa-title{font-size:13vmin}.fhqa-slab{height:30vmin}
.fhqa-slab.red{top:calc(50% - 15.4vmin)}.fhqa-slab.blue{top:calc(50% + 15.4vmin)}}
.fhqa-vs{position:relative;font-size:20vmin;font-weight:900;font-style:italic;
line-height:.86;letter-spacing:-.02em;color:#ffeaba;
text-shadow:0 .5vmin 1.6vmin rgba(0,0,0,.7),0 0 3vmin rgba(255,196,96,.5);
animation:fhqa-slam .5s cubic-bezier(.12,.9,.16,1) .34s both;will-change:transform,opacity}
@supports ((-webkit-background-clip:text) or (background-clip:text)){
.fhqa-vs{text-shadow:none;
background-image:linear-gradient(178deg,#fffdf6 0%,#ffe9ae 38%,#e0a743 66%,#fff4cf 100%);
-webkit-background-clip:text;background-clip:text;color:transparent;
-webkit-text-fill-color:transparent;
filter:drop-shadow(0 .5vmin 1.6vmin rgba(0,0,0,.7)) drop-shadow(0 0 3vmin rgba(255,196,96,.5))}}
.fhqa-q0 .fhqa-vs{animation-name:fhqa-slam-flat}
.fhqa-out .fhqa-vs{animation:fhqa-pop-out .34s ease-in both}
.fhqa-vs-wrap{position:absolute;top:50%;left:50%;transform:translate3d(-50%,-50%,0);
text-align:center;will-change:transform}
.fhqa-tag{margin-top:1.6vmin;font-size:2.5vmin;font-weight:800;letter-spacing:.62em;
color:#f4dfae;text-indent:.62em;opacity:0;text-shadow:0 0 1.4vmin rgba(255,200,110,.55);
animation:fhqa-rise .5s cubic-bezier(.2,.9,.2,1) .72s both}
.fhqa-out .fhqa-tag{animation:fhqa-pop-out .28s ease-in both}
.fhqa-lines{position:absolute;inset:0;opacity:0;mix-blend-mode:screen;
background:repeating-linear-gradient(98deg,rgba(255,236,196,0) 0 1.4vmin,
rgba(255,236,196,.16) 1.4vmin 1.7vmin);animation:fhqa-lines .5s ease-out both;
will-change:transform,opacity}
.fhqa-q0 .fhqa-lines{display:none}`,

/* ---- 天雷 / 战场氛围（ready 专属） ---- */
`.fhqa-storm{position:absolute;inset:0;pointer-events:none;opacity:0;
animation:fhqa-storm-in .5s ease-out .12s both;will-change:opacity}
.fhqa-storm .cloud{position:absolute;top:-22%;left:-10%;width:120%;height:62%;
background:radial-gradient(60% 100% at 30% 0,rgba(22,16,32,.92),transparent 70%),
radial-gradient(60% 100% at 78% 8%,rgba(14,18,36,.94),transparent 70%);
filter:blur(2vmin);animation:fhqa-cloud 9s ease-in-out infinite alternate}
.fhqa-storm .cloud.b{top:-28%;right:-12%;left:auto;
background:radial-gradient(60% 100% at 60% 0,rgba(34,18,28,.9),transparent 70%);
animation-duration:11s;animation-direction:alternate-reverse}
.fhqa-storm .ground{position:absolute;left:-10%;right:-10%;bottom:-8%;height:28%;
background:linear-gradient(0deg,rgba(46,14,8,.55),rgba(20,40,92,.2) 42%,transparent);
mix-blend-mode:screen;filter:blur(1.4vmin)}
.fhqa-bolt{position:absolute;top:-12%;left:34%;width:5vmin;height:74%;opacity:0;
filter:drop-shadow(0 0 1.2vmin rgba(170,205,255,.95)) drop-shadow(0 0 3vmin rgba(120,170,255,.7));
will-change:opacity,transform}
.fhqa-bolt polyline{fill:none;stroke:#eaf3ff;stroke-width:2.4;stroke-linejoin:round;
stroke-linecap:round;vector-effect:non-scaling-stroke}
.fhqa-bolt.b2{left:62%;height:66%;transform:scaleX(-1)}
.fhqa-bolt.b3{left:82%;height:82%;width:3.4vmin}
.fhqa-nova{position:absolute;top:50%;left:50%;width:18vmin;height:18vmin;margin:-9vmin 0 0 -9vmin;
border-radius:50%;border:.5vmin solid rgba(205,228,255,.92);opacity:0;
box-shadow:0 0 6vmin rgba(150,190,255,.7);will-change:transform,opacity}
.fhqa-q0 .fhqa-storm,.fhqa-q0 .fhqa-nova{display:none}`,

/* 根节点身份钩子 */
`.fhqa-root.fhqa-ready,.fhqa-root.fhqa-result{isolation:isolate}`,

/* ---- 对局结束 ---- */
`.fhqa-rays{display:none;position:absolute;top:50%;left:50%;width:180vmax;height:180vmax;
margin:-90vmax 0 0 -90vmax;opacity:0;mix-blend-mode:screen;border-radius:50%;
background:conic-gradient(from 0deg,rgba(255,226,168,.30) 0deg,rgba(255,226,168,0) 8deg,
rgba(255,226,168,0) 22deg,rgba(255,226,168,.26) 30deg,rgba(255,226,168,0) 38deg,
rgba(255,226,168,0) 52deg,rgba(255,226,168,.30) 60deg,rgba(255,226,168,0) 68deg,
rgba(255,226,168,0) 82deg,rgba(255,226,168,.22) 90deg,rgba(255,226,168,0) 98deg,
rgba(255,226,168,0) 112deg,rgba(255,226,168,.28) 120deg,rgba(255,226,168,0) 128deg,
rgba(255,226,168,0) 142deg,rgba(255,226,168,.24) 150deg,rgba(255,226,168,0) 158deg,
rgba(255,226,168,0) 172deg,rgba(255,226,168,.30) 180deg,rgba(255,226,168,0) 188deg,
rgba(255,226,168,0) 202deg,rgba(255,226,168,.26) 210deg,rgba(255,226,168,0) 218deg,
rgba(255,226,168,0) 232deg,rgba(255,226,168,.30) 240deg,rgba(255,226,168,0) 248deg,
rgba(255,226,168,0) 262deg,rgba(255,226,168,.22) 270deg,rgba(255,226,168,0) 278deg,
rgba(255,226,168,0) 292deg,rgba(255,226,168,.28) 300deg,rgba(255,226,168,0) 308deg,
rgba(255,226,168,0) 322deg,rgba(255,226,168,.24) 330deg,rgba(255,226,168,0) 338deg,
rgba(255,226,168,0) 352deg,rgba(255,226,168,.30) 360deg);
-webkit-mask-image:radial-gradient(circle at 50% 50%,transparent 12%,#000 34%,transparent 62%);
mask-image:radial-gradient(circle at 50% 50%,transparent 12%,#000 34%,transparent 62%);
animation:fhqa-rays-in 1s ease-out .18s both,fhqa-spin 26s linear .18s infinite;
will-change:transform,opacity}
@supports ((-webkit-mask-image:radial-gradient(#000,#fff)) or (mask-image:radial-gradient(#000,#fff))){
.fhqa-rays{display:block}}
.fhqa-q0 .fhqa-rays{display:none}
.fhqa-blue .fhqa-rays{filter:hue-rotate(178deg) saturate(.85)}
.fhqa-out .fhqa-rays{animation:fhqa-fade-out .4s ease-in both}`,

`.fhqa-crest{position:absolute;top:50%;left:50%;transform:translate3d(-50%,-50%,0);
text-align:center;width:92vw;will-change:transform}
.fhqa-band{position:absolute;top:50%;left:50%;width:150vmax;height:30vmin;
margin:-15vmin 0 0 -75vmax;opacity:0;
background:linear-gradient(90deg,rgba(0,0,0,0) 0%,rgba(74,20,14,.55) 22%,
rgba(96,26,18,.72) 50%,rgba(74,20,14,.55) 78%,rgba(0,0,0,0) 100%);
animation:fhqa-band .56s cubic-bezier(.16,.9,.2,1) .1s both;will-change:transform,opacity}
.fhqa-blue .fhqa-band{background:linear-gradient(90deg,rgba(0,0,0,0) 0%,rgba(14,36,78,.55) 22%,
rgba(20,52,110,.74) 50%,rgba(14,36,78,.55) 78%,rgba(0,0,0,0) 100%)}
.fhqa-out .fhqa-band{animation:fhqa-fade-out .3s ease-in both}
.fhqa-kicker{font-size:2.6vmin;font-weight:800;letter-spacing:.6em;text-indent:.6em;
color:rgba(246,228,190,.82);opacity:0;
animation:fhqa-rise .46s cubic-bezier(.2,.9,.2,1) .58s both}
.fhqa-title{position:relative;display:inline-block;margin:1.4vmin 0 0;
font-size:16.5vmin;font-weight:900;line-height:1;letter-spacing:.06em;
animation:fhqa-slam .58s cubic-bezier(.1,.92,.14,1) .26s both;will-change:transform,opacity}
.fhqa-q0 .fhqa-title{animation-name:fhqa-slam-flat}
.fhqa-out .fhqa-title{animation:fhqa-pop-out .34s ease-in both}
.fhqa-red .fhqa-title{color:#ffd6b0;
text-shadow:0 .6vmin 1.8vmin rgba(0,0,0,.72),0 0 3.4vmin rgba(255,120,70,.55)}
.fhqa-blue .fhqa-title{color:#c4e2ff;
text-shadow:0 .6vmin 1.8vmin rgba(0,0,0,.72),0 0 3.4vmin rgba(70,140,240,.55)}
@supports ((-webkit-background-clip:text) or (background-clip:text)){
.fhqa-title{-webkit-background-clip:text;background-clip:text;color:transparent;
-webkit-text-fill-color:transparent}
.fhqa-red .fhqa-title{text-shadow:none;
background-image:linear-gradient(176deg,#fff8f2 0%,#ffd9b4 30%,#f0844f 60%,#ffe3c2 100%);
filter:drop-shadow(0 .6vmin 1.8vmin rgba(0,0,0,.72)) drop-shadow(0 0 3.4vmin rgba(255,120,70,.55))}
.fhqa-blue .fhqa-title{text-shadow:none;
background-image:linear-gradient(176deg,#f6fbff 0%,#c8e4ff 30%,#4f92e6 60%,#dcefff 100%);
filter:drop-shadow(0 .6vmin 1.8vmin rgba(0,0,0,.72)) drop-shadow(0 0 3.4vmin rgba(70,140,240,.55))}}
.fhqa-gloss{position:absolute;left:0;top:0;right:0;pointer-events:none;
-webkit-background-clip:text;background-clip:text;color:transparent;
-webkit-text-fill-color:transparent;filter:none;opacity:0;
background-image:linear-gradient(100deg,rgba(255,255,255,0) 42%,rgba(255,255,255,.92) 50%,
rgba(255,255,255,0) 58%);background-size:280% 100%;background-repeat:no-repeat;
animation:fhqa-gloss 1.1s cubic-bezier(.32,0,.38,1) .8s both}
.fhqa-q0 .fhqa-gloss,.fhqa-q1 .fhqa-gloss{display:none}
.fhqa-bar{height:.34vmin;width:0;margin:2.6vmin auto 0;border-radius:.34vmin;
background:linear-gradient(90deg,rgba(232,197,110,0),rgba(255,232,168,.95),rgba(232,197,110,0));
animation:fhqa-bar .62s cubic-bezier(.2,.9,.2,1) .82s both;will-change:width}
.fhqa-sub{margin-top:2vmin;font-size:2.9vmin;font-weight:700;letter-spacing:.3em;
text-indent:.3em;color:rgba(240,226,198,.86);opacity:0;
animation:fhqa-rise .5s cubic-bezier(.2,.9,.2,1) .96s both}
.fhqa-out .fhqa-kicker,.fhqa-out .fhqa-sub,.fhqa-out .fhqa-bar{animation:fhqa-fade-out .26s ease-in both}
.fhqa-hint{position:absolute;left:50%;bottom:6vh;transform:translateX(-50%);
font-size:1.9vmin;letter-spacing:.24em;color:rgba(236,226,205,.34);opacity:0;
animation:fhqa-rise .6s ease-out 1.5s both}
.fhqa-out .fhqa-hint{animation:fhqa-fade-out .24s ease-in both}`,
`@keyframes fhqa-veil-in{from{opacity:0}to{opacity:1}}
@keyframes fhqa-veil-out{from{opacity:1}to{opacity:0}}
@keyframes fhqa-fade-out{from{opacity:1}to{opacity:0}}
@keyframes fhqa-slab-l{0%{transform:translate3d(-152%,-50%,0) skewX(-14deg);opacity:0}
28%{opacity:1}100%{transform:translate3d(-50%,-50%,0) skewX(0deg);opacity:1}}
@keyframes fhqa-slab-r{0%{transform:translate3d(52%,-50%,0) skewX(14deg);opacity:0}
28%{opacity:1}100%{transform:translate3d(-50%,-50%,0) skewX(0deg);opacity:1}}
@keyframes fhqa-slab-lo{from{transform:translate3d(-50%,-50%,0);opacity:1}
to{transform:translate3d(-96%,-50%,0);opacity:0}}
@keyframes fhqa-slab-ro{from{transform:translate3d(-50%,-50%,0);opacity:1}
to{transform:translate3d(-4%,-50%,0);opacity:0}}
@keyframes fhqa-sheen{0%{transform:translate3d(-46vmin,0,0);opacity:0}
20%{opacity:.9}100%{transform:translate3d(76vmin,0,0);opacity:0}}
@keyframes fhqa-slam{0%{transform:scale(3.4);opacity:0;filter:blur(1.2vmin)}
54%{opacity:1}100%{transform:scale(1);opacity:1;filter:blur(0)}}
@keyframes fhqa-slam-flat{0%{transform:scale(1.9);opacity:0}100%{transform:scale(1);opacity:1}}
@keyframes fhqa-pop-out{0%{transform:scale(1);opacity:1}100%{transform:scale(1.34);opacity:0}}
@keyframes fhqa-rise{0%{transform:translate3d(0,1.8vmin,0);opacity:0}
100%{transform:translate3d(0,0,0);opacity:1}}
@keyframes fhqa-lines{0%{opacity:0;transform:scale(1.14)}22%{opacity:.85}
100%{opacity:0;transform:scale(1)}}
@keyframes fhqa-shake{0%{transform:translate3d(0,0,0)}14%{transform:translate3d(-1.1vmin,.5vmin,0)}
30%{transform:translate3d(.9vmin,-.7vmin,0)}46%{transform:translate3d(-.7vmin,-.4vmin,0)}
62%{transform:translate3d(.5vmin,.5vmin,0)}80%{transform:translate3d(-.3vmin,.2vmin,0)}
100%{transform:translate3d(0,0,0)}}
@keyframes fhqa-ring{0%{transform:scale(.14);opacity:0}12%{opacity:1}
100%{transform:scale(3.6);opacity:0}}
@keyframes fhqa-pillar{0%{transform:scale3d(.1,1,1);opacity:0}
26%{opacity:.9}100%{transform:scale3d(1,1,1);opacity:0}}
@keyframes fhqa-rays-in{0%{opacity:0;transform:scale(.55) rotate(0deg)}
100%{opacity:1;transform:scale(1) rotate(0deg)}}
@keyframes fhqa-spin{from{transform:scale(1) rotate(0deg)}to{transform:scale(1) rotate(360deg)}}
@keyframes fhqa-band{0%{transform:translate3d(0,0,0) scale3d(.2,.4,1);opacity:0}
100%{transform:translate3d(0,0,0) scale3d(1,1,1);opacity:1}}
@keyframes fhqa-bar{0%{width:0}100%{width:46vmin}}
@keyframes fhqa-gloss{0%{background-position:-140% 0;opacity:0}
14%{opacity:1}100%{background-position:240% 0;opacity:0}}
@keyframes fhqa-flash{0%{opacity:0}8%{opacity:1}100%{opacity:0}}
@keyframes fhqa-storm-in{from{opacity:0}to{opacity:1}}
@keyframes fhqa-cloud{0%{transform:translate3d(-3vmin,0,0) scale(1)}100%{transform:translate3d(4vmin,1.5vmin,0) scale(1.06)}}
@keyframes fhqa-bolt{0%{opacity:0}5%{opacity:1}10%{opacity:.2}17%{opacity:.95}26%{opacity:0}34%{opacity:.7}42%{opacity:0}100%{opacity:0}}
@keyframes fhqa-nova{0%{transform:scale(.2);opacity:0}10%{opacity:.95}100%{transform:scale(7);opacity:0}}`
  ].join("\n");

  function injectCSS() {
    if (!hasWin) return;
    if (document.getElementById(STYLE_ID)) return;
    const st = document.createElement("style");
    st.id = STYLE_ID;
    st.type = "text/css";
    st.appendChild(document.createTextNode(CSS));
    (document.head || document.documentElement).appendChild(st);
  }

  /* ================= 粒子系统================= */
  function createParticles(canvas, quality) {
    const ctx = canvas.getContext && canvas.getContext("2d", { alpha: true });
    if (!ctx) return { spawnBurst() {}, spawnRain() {}, start() {}, stop() {} };

    const MAXP = quality >= 2 ? 420 : 200;
    const ps = new Array(MAXP);
    for (let i = 0; i < MAXP; i++) ps[i] = { on: false, k: 0, x: 0, y: 0, vx: 0, vy: 0, g: 0, r: 0, rot: 0, vr: 0, life: 0, max: 1, c: "" };
    let n = 0;                 // 活跃上界（松散游标）
    let raf = 0, last = 0, dead = false;
    let W = 0, H = 0, dpr = 1;
    let trails = quality >= 2;
    let cap = MAXP;
    let acc = 0, frames = 0;   // 掉帧监测

    function resize() {
      dpr = Math.min(window.devicePixelRatio || 1, quality >= 2 ? 2 : 1.5);
      W = canvas.clientWidth || window.innerWidth;
      H = canvas.clientHeight || window.innerHeight;
      canvas.width = Math.max(1, (W * dpr) | 0);
      canvas.height = Math.max(1, (H * dpr) | 0);
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }
    resize();

    let cur = 0;               // 环形游标，避免每次从头线性扫描
    function alloc() {
      for (let k = 0; k < cap; k++) {
        if (cur >= cap) cur = 0;
        const p = ps[cur];
        const i = cur++;
        if (!p.on) { if (i >= n) n = i + 1; return p; }
      }
      return null;
    }

    // 火花：中心爆发，加色混合
    function spawnBurst(count, colors) {
      const cx = W / 2, cy = H / 2;
      for (let i = 0; i < count; i++) {
        const p = alloc(); if (!p) return;
        const a = Math.random() * Math.PI * 2;
        const sp = 5 + Math.random() * 16;
        p.on = true; p.k = 0;
        p.x = cx; p.y = cy;
        p.vx = Math.cos(a) * sp; p.vy = Math.sin(a) * sp * .72;
        p.g = 0.022; p.r = 1 + Math.random() * 2.2;
        p.life = 0; p.max = 620 + Math.random() * 620;
        p.c = colors[(Math.random() * colors.length) | 0];
      }
    }

    // 纸片 + 余烬：顶部落下 / 底部升起
    function spawnRain(count, colors) {
      for (let i = 0; i < count; i++) {
        const p = alloc(); if (!p) return;
        const ember = Math.random() < .34;
        p.on = true; p.k = ember ? 2 : 1;
        p.x = Math.random() * W;
        if (ember) {
          p.y = H + Math.random() * H * .3;
          p.vy = -(0.5 + Math.random() * 1.4); p.vx = (Math.random() - .5) * .5;
          p.g = -0.0005; p.r = .8 + Math.random() * 1.8;
          p.max = 2600 + Math.random() * 2200;
          p.c = Math.random() < .5 ? "255,214,132" : "255,168,86";
        } else {
          p.y = -Math.random() * H * .6;
          p.vy = 1.4 + Math.random() * 2.6; p.vx = (Math.random() - .5) * 1.8;
          p.g = 0.004; p.r = 2.6 + Math.random() * 4.4;
          p.max = 3400 + Math.random() * 2000;
          p.c = colors[(Math.random() * colors.length) | 0];
        }
        p.life = 0; p.rot = Math.random() * 6.28; p.vr = (Math.random() - .5) * .22;
      }
    }

    function step(t) {
      if (dead) return;
      raf = window.requestAnimationFrame(step);
      const dt = last ? Math.min(t - last, 48) : 16.7;
      last = t;

      // 掉帧自适应
      frames++; acc += dt;
      if (frames >= 24) {
        if (acc / frames > 23 && cap > 60) { cap = (cap * 0.6) | 0; trails = false; }
        frames = 0; acc = 0;
      }

      const f = dt / 16.7;
      if (trails) { ctx.globalCompositeOperation = "source-over"; ctx.clearRect(0, 0, W, H); }
      else ctx.clearRect(0, 0, W, H);

      let hi = 0;
      // 加色层：火花 / 余烬
      ctx.globalCompositeOperation = "lighter";
      for (let i = 0; i < n; i++) {
        const p = ps[i];
        if (!p.on) continue;
        p.life += dt;
        if (p.life >= p.max || p.y > H + 60 || p.y < -H) { p.on = false; continue; }
        hi = i + 1;
        p.vy += p.g * dt; p.x += p.vx * f; p.y += p.vy * f;
        if (p.k === 1) continue;                       // 纸片走下一层
        const al = 1 - p.life / p.max;
        ctx.globalAlpha = p.k === 0 ? al * al : al * .8;
        ctx.fillStyle = "rgba(" + p.c + ",1)";
        if (p.k === 0 && trails) {
          ctx.beginPath();
          ctx.lineWidth = p.r;
          ctx.strokeStyle = "rgba(" + p.c + ",1)";
          ctx.moveTo(p.x, p.y);
          ctx.lineTo(p.x - p.vx * 2.2, p.y - p.vy * 2.2);
          ctx.stroke();
        } else {
          ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, 6.2832); ctx.fill();
        }
      }
      // 常规层：纸片
      ctx.globalCompositeOperation = "source-over";
      for (let i = 0; i < n; i++) {
        const p = ps[i];
        if (!p.on || p.k !== 1) continue;
        hi = i + 1;
        p.rot += p.vr * f;
        const al = 1 - p.life / p.max;
        const sc = Math.abs(Math.cos(p.rot));          // 翻面感
        ctx.globalAlpha = al < .9 ? al : .9;
        ctx.fillStyle = "rgba(" + p.c + ",1)";
        ctx.fillRect(p.x - p.r * .5, p.y - p.r * sc, p.r, p.r * 2 * sc + .6);
      }
      ctx.globalAlpha = 1;
      n = hi;
    }

    return {
      spawnBurst, spawnRain, resize,
      start() { if (!raf && !dead) { last = 0; raf = window.requestAnimationFrame(step); } },
      stop() {
        dead = true;
        if (raf) window.cancelAnimationFrame(raf), raf = 0;
        try { ctx.clearRect(0, 0, W, H); } catch (e) {}
      }
    };
  }

  /* ================= 运行时 ================= */
  const cfg = {
    quality: "auto",     // "auto" | 0 | 1 | 2
    sound: false,        // 需要音效自行接管 onImpact 回调
    block: true,         // 播放期间拦截页面点击
    skippable: false,    // 允许点击跳过
    zIndex: Z_BASE
  };

  let live = null;       

  function esc(s) {
    return String(s == null ? "" : s).replace(/[&<>"']/g, c =>
      ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  }

  function domReady() {
    if (!hasWin) return Promise.resolve();
    if (document.readyState !== "loading") return Promise.resolve();
    return new Promise(r => document.addEventListener("DOMContentLoaded", r, { once: true }));
  }

  function resolveQuality() {
    const q = cfg.quality === "auto" ? detectQuality() : (cfg.quality | 0);
    return prefersReduced() ? 0 : Math.max(0, Math.min(2, q));
  }
  function mount(kind, quality) {
    injectCSS();
    const root = document.createElement("div");
    root.className = "fhqa-root fhqa-" + kind + " fhqa-q" + quality + (cfg.block ? " fhqa-block" : "");
    root.style.zIndex = String(cfg.zIndex);
    root.setAttribute("role", "status");
    root.setAttribute("aria-live", "polite");
    document.body.appendChild(root);
    return root;
  }

  function run(kind, quality, build, totalMs, tok) {
    if (live) live.kill("superseded");

    const root = mount(kind, quality);
    const timers = [];
    const at = (ms, fn) => timers.push(setTimeout(fn, ms));
    let settle = null, done = false;
    const parts = { current: null };

    function cleanup() {
      timers.forEach(clearTimeout);
      timers.length = 0;
      if (parts.current) parts.current.stop();
      if (onResize) window.removeEventListener("resize", onResize);
      if (onVis) document.removeEventListener("visibilitychange", onVis);
      if (root.parentNode) root.parentNode.removeChild(root);
      if (live && live.root === root) live = null;
    }

    function finish(reason) {
      if (done) return;
      done = true;
      cleanup();
      if (settle) settle(reason || "done");
    }
    function outro(delay) {
      at(delay, () => { root.classList.add("fhqa-out"); });
      at(delay + 440, () => finish("done"));
    }

    let onResize = null, onVis = null;

    build({ root, at, parts, quality });

    if (parts.current) {
      onResize = () => parts.current && parts.current.resize && parts.current.resize();
      window.addEventListener("resize", onResize, { passive: true });
    }
    onVis = () => { if (document.hidden) finish("hidden"); };
    document.addEventListener("visibilitychange", onVis);

    outro(totalMs);

    if (cfg.skippable) {
      root.classList.add("fhqa-block");
      root.addEventListener("pointerdown", () => finish("skipped"), { once: true });
      root.style.cursor = "pointer";
    }

    const p = new Promise(res => { settle = res; });
    live = { root, kind, kill: finish };
    p.cancel = () => finish("cancelled");
    if (tok) tok.kill = finish;
    return p;
  }
  function deferred(inner, tok) {
    const p = inner;
    p.cancel = () => { tok.cancelled = true; if (tok.kill) tok.kill("cancelled"); };
    return p;
  }
  function impact(root, at, parts, quality, delay, colors) {
    const fx = root.querySelector(".fhqa-fx");
    at(delay, () => {
      const flash = fx.querySelector(".fhqa-flash");
      if (flash) flash.style.animation = "fhqa-flash .5s ease-out both";
      const stage = root.querySelector(".fhqa-layer");
      if (stage && quality >= 1) {
        stage.classList.remove("fhqa-shake");
        void stage.offsetWidth;                        
        stage.classList.add("fhqa-shake");
      }
      const rings = fx.querySelectorAll(".fhqa-ring");
      for (let i = 0; i < rings.length; i++) {
        rings[i].style.animation = "fhqa-ring " + (0.62 + i * 0.16) + "s cubic-bezier(.12,.7,.3,1) " + (i * 0.07) + "s both";
      }
      const pil = fx.querySelector(".fhqa-pillar");
      if (pil) pil.style.animation = "fhqa-pillar .72s cubic-bezier(.15,.8,.25,1) both";
      if (parts.current) parts.current.spawnBurst(quality >= 2 ? 150 : 70, colors);
      const nova = fx.querySelector(".fhqa-nova");
      if (nova) nova.style.animation = "fhqa-nova .9s cubic-bezier(.1,.7,.2,1) both";
    });
  }

  function fxLayer(quality, pillar) {
    return '<div class="fhqa-fx">' +
      '<div class="fhqa-ring r1"></div>' +
      (quality >= 1 ? '<div class="fhqa-ring r2"></div><div class="fhqa-ring r3"></div>' : "") +
      (pillar && quality >= 1 ? '<div class="fhqa-pillar"></div>' : "") +
      (pillar && quality >= 1 ? '<div class="fhqa-nova"></div>' : "") +
      '<div class="fhqa-flash"></div>' +
      "</div>";
  }

  function stormLayer(quality) {
    if (quality === 0) return "";
    return '<div class="fhqa-storm">' +
      '<div class="cloud"></div><div class="cloud b"></div><div class="ground"></div>' +
      '<svg class="fhqa-bolt b1" viewBox="0 0 40 200" preserveAspectRatio="none"><polyline points="20,0 7,48 27,72 12,120 23,150 6,200"/></svg>' +
      '<svg class="fhqa-bolt b2" viewBox="0 0 40 200" preserveAspectRatio="none"><polyline points="14,0 26,44 8,78 24,118 10,156 22,200"/></svg>' +
      '<svg class="fhqa-bolt b3" viewBox="0 0 40 200" preserveAspectRatio="none"><polyline points="22,0 9,50 28,76 13,124 25,158 8,200"/></svg>' +
      '</div>';
  }

  const SPARK = ["255,236,190", "255,196,110", "255,150,80", "255,255,246"];
  const RED_C = ["232,96,72", "255,152,116", "255,214,150", "255,246,232"];
  const BLUE_C = ["86,148,232", "140,196,255", "198,228,255", "246,252,255"];
  function ready(opts) {
    opts = opts || {};
    const redName = esc(opts.red || "红方");
    const blueName = esc(opts.blue || "蓝方");
    const tag = esc(opts.tag != null ? opts.tag : "准备完毕 · 战斗开始");
    if (!hasWin) return Promise.resolve("noop");

    const q = resolveQuality();
    const total = q === 0 ? 1500 : 2280;
    const tok = {};

    return deferred(domReady().then(() => {
      if (tok.cancelled) return "cancelled";
      return run("ready", q, ({ root, at, parts }) => {
      root.innerHTML =
        '<div class="fhqa-veil"></div>' +
        '<div class="fhqa-layer">' +
          '<div class="fhqa-slab red"><div class="fhqa-name">' +
            '<span class="fhqa-side">RED</span>' + redName +
          '</div><div class="fhqa-sheen"></div></div>' +
          '<div class="fhqa-slab blue"><div class="fhqa-name">' +
            '<span style="margin-top:50px" class="fhqa-side">BLUE</span>' + blueName +
          '</div><div class="fhqa-sheen"></div></div>' +
          '<div class="fhqa-lines"></div>' +
          '<div class="fhqa-vs-wrap"><div class="fhqa-vs">VS</div>' +
            '<div class="fhqa-tag">' + tag + "</div></div>" +
        "</div>" +
        (q >= 1 ? '<canvas class="fhqa-cv"></canvas>' : "") +
        fxLayer(q, true) +
        stormLayer(q);

      const cv = root.querySelector(".fhqa-cv");
      if (cv) { parts.current = createParticles(cv, q); parts.current.start(); }
      const HIT = q === 0 ? 260 : 560;
      at(HIT - 60, () => {
        const sh = root.querySelectorAll(".fhqa-sheen");
        for (let i = 0; i < sh.length; i++) {
          sh[i].style.animation = "fhqa-sheen .85s cubic-bezier(.3,0,.4,1) " + (i * 0.09) + "s both";
        }
      });
      // 天雷：蓄势一击 + 命中主雷
      const bolts = root.querySelectorAll(".fhqa-bolt");
      const strikeBolts = (dur) => {
        for (let i = 0; i < bolts.length; i++) {
          const b = bolts[i];
          b.style.animation = "none"; void b.offsetWidth;
          b.style.animation = "fhqa-bolt " + dur + "s linear both";
        }
      };
      at(q === 0 ? 240 : 400, () => strikeBolts(0.9));
      impact(root, at, parts, q, HIT, SPARK);
      at(HIT, () => strikeBolts(0.8));
      if (parts.current) {
        const embers = ["255,120,40", "255,170,80", "180,200,255", "120,150,255"];
        at(HIT + 40, () => parts.current.spawnRain(q >= 2 ? 42 : 20, embers));
        at(HIT + 280, () => parts.current.spawnRain(q >= 2 ? 30 : 14, embers));
      }
      if (typeof opts.onImpact === "function") at(HIT, () => { try { opts.onImpact(); } catch (e) {} });
      }, total, tok);
    }), tok);
  }

  /* ---------- 动画二：对局结束 ---------- */
  function result(winner, opts) {
    opts = opts || {};
    const side = String(winner || "").toLowerCase() === "blue" ? "blue" : "red";
    const isRed = side === "red";
    const name = esc(opts[side] != null ? opts[side] : (isRed ? "红方" : "蓝方"));
    const title = esc(opts.title != null ? opts.title : (isRed ? "红方胜利" : "蓝方胜利"));
    const kicker = esc(opts.kicker != null ? opts.kicker : "对局结束");
    const sub = esc(opts.sub != null ? opts.sub : name + " 取得胜利");
    const hint = opts.hint == null ? "" : esc(opts.hint);
    if (!hasWin) return Promise.resolve("noop");

    const q = resolveQuality();
    const total = opts.hold != null ? (opts.hold | 0) : (q === 0 ? 2000 : 3400);
    const colors = isRed ? RED_C : BLUE_C;
    const tok = {};

    return deferred(domReady().then(() => {
      if (tok.cancelled) return "cancelled";
      return run("result", q, ({ root, at, parts }) => {
      root.classList.add("fhqa-" + side);
      root.innerHTML =
        '<div class="fhqa-veil"></div>' +
        '<div class="fhqa-rays"></div>' +
        '<div class="fhqa-layer">' +
          '<div class="fhqa-band"></div>' +
          '<div class="fhqa-crest">' +
            '<div class="fhqa-kicker">' + kicker + "</div>" +
            '<div class="fhqa-title">' + title +
              '<span class="fhqa-gloss" aria-hidden="true">' + title + "</span>" +
            "</div>" +
            '<div class="fhqa-bar"></div>' +
            '<div class="fhqa-sub">' + sub + "</div>" +
          "</div>" +
        "</div>" +
        (q >= 1 ? '<canvas class="fhqa-cv"></canvas>' : "") +
        (hint ? '<div class="fhqa-hint">' + hint + "</div>" : "") +
        fxLayer(q, false);

      const cv = root.querySelector(".fhqa-cv");
      if (cv) { parts.current = createParticles(cv, q); parts.current.start(); }

      const HIT = q === 0 ? 200 : 420;
      impact(root, at, parts, q, HIT, SPARK.concat(colors.slice(0, 2)));
      if (typeof opts.onImpact === "function") at(HIT, () => { try { opts.onImpact(); } catch (e) {} });

      // 庆祝：分批补撒
      if (parts.current) {
        const per = q >= 2 ? 46 : 22;
        const waves = [HIT + 120, HIT + 520, HIT + 1000, HIT + 1560];
        for (let i = 0; i < waves.length; i++) {
          const t = waves[i];
          if (t < total) at(t, () => { if (parts.current) parts.current.spawnRain(per, colors); });
        }
      }
      }, total, tok);
    }), tok);
  }

  /* ================= 接口 ================= */
  const API = {
    version: VER,

    /** 配置：{ quality:"auto"|0|1|2, block, skippable, zIndex } */
    config(o) { if (o) for (const k in o) if (k in cfg) cfg[k] = o[k]; return API; },

    /** 准备成功。ready({ red:"名字", blue:"名字", tag, onImpact }) → Promise<"done"|...> */
    ready,

    /** 对局结束。result("red"|"blue", { red, blue, title, kicker, sub, hint, hold }) */
    result,

    /** 立刻结束当前动画（若有），Promise 会以 "cancelled" 落地 */
    cancel() { if (live) live.kill("cancelled"); return API; },

    /** 是否正在播放 */
    isPlaying() { return !!live; },

    /** 当前生效画质档（0/1/2） */
    quality() { return resolveQuality(); },

    /** 提前注入样式，避免首播那一帧的样式解析开销 */
    preload() { if (hasWin) domReady().then(injectCSS); return API; }
  };

  if (hasWin) API.preload();
  return API;
});
