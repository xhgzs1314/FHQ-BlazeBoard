<?php
$page_title = '关于';
require($_SERVER['DOCUMENT_ROOT'] . '/use/set.php');
?>
<div id="toc-container"></div>
</body>
<h1>缘起</h1>
<p><strong>烽火棋</strong>（Beacon Chess）最初诞生于一个很简单的念头：<strong>现代策略游戏是否还有未被探索的空间？</strong></p>
<p>在棋类游戏的历史长河中，象棋、围棋、国际象棋各自构建了精妙的战术体系，而近年来涌现的自走棋、战棋等电子游戏又引入了随机性与成长元素。我们不禁思考——<strong>能否设计一款既保留传统棋类“纯信息、全策略”的博弈魅力，又融合现代战争游戏中“视野控制、区域争夺、动态成长”等要素的作品？</strong></p>
<p>于是，<strong>烽火棋</strong>在反复推敲与迭代中逐渐成形。它既不是对任何现有棋类的模仿，也不是为了堆砌规则而堆砌——<strong>每一条规则都指向一个核心目标：让对局充满张力与选择。</strong></p>
<h1>设计理念</h1>
<h2>一、视野即权力</h2>
<p>传统棋类中，双方对全局了如指掌，一切都在明面展开。而<strong>烽火棋</strong>引入了<strong>迷雾与视野</strong>机制——你看不见对方在暗处的部署，只能通过探棋的“侦察”与关键棋子的阵亡来逐步揭开战局。这一设计不仅增加了不确定性与心理博弈，更让“信息”本身成为了一种可争夺的资源。</p>
<h2>二、进退有据，攻守有别</h2>
<p>棋子的设计并非简单堆砌数值，而是<strong>各有使命、彼此制衡</strong>：</p>
<ul>
    <li><strong>母棋</strong>是王权的象征，必须固守核心，不得轻举妄动；</li>
    <li><strong>子棋</strong>是王储，既能灵活策应，又能在母棋陨落后继位翻盘；</li>
    <li><strong>军棋</strong>是主力，可渡河、可吃子、可成长，是战线推进的中坚；</li>
    <li><strong>探棋</strong>是眼睛与触手，提供视野与行动许可，但自身脆弱；</li>
    <li><strong>盾棋</strong>是壁垒，守护友军、抵御强攻，却并非坚不可摧。</li>
</ul>
<p>每一枚棋子都不是“数值怪”，而是<strong>一个有特定职责与弱点的角色</strong>。真正的胜负，取决于你是否能让它们各司其职、协同作战。</p>
<h2>三、动态平衡，拒绝死局</h2>
<p>我们深知，策略游戏最忌“开局定胜负”。因此，<strong>烽火棋</strong>设计了多层动态平衡机制：</p>
<ul>
    <li><strong>军棋步长</strong>随敌方军棋阵亡而增长，劣势方有机会反扑；</li>
    <li><strong>子棋继位</strong>让王权可以延续，避免一锤定音式的崩盘；</li>
    <li><strong>核心区惩罚</strong>限制了对关键区域的无脑压制；</li>
    <li><strong>迷雾解除条件</strong>随战局变化，劣势方可能因此获得战略转机。</li>
</ul>
<p>这些机制共同构成了一张<strong>弹性之网</strong>——领先者不能松懈，落后者仍有翻盘可能。</p>
<h1>为什么</h1>
<p>将烽火棋搬上网页，并非仅仅是“把棋盘画在屏幕上”这么简单。我们有三层考量：</p>
<h2>1. <strong>让对局更纯粹，让规则更透明</strong></h2>
<p>棋盘、棋子、视野、许可区——这些在实体桌游中需要大量辅助标记与裁判监督的要素，在网页版中由引擎自动管理。玩家只需要专注于<strong>策略本身</strong>，而不会被繁琐的规则执行所困扰。</p>
<h2>2. <strong>打破地域的限制</strong></h2>
<p>棋类游戏最美好的部分，是与人对弈的乐趣。通过网页服务，无论你身处何地，只要打开浏览器，就能与好友切磋、与陌生人竞技、与 AI 试炼。<strong>我们想让烽火棋成为一座桥梁，而不是一张桌子。</strong></p>
<h2>3. <strong>为策略社群提供一个共同家园</strong></h2>
<p>我们相信，任何一款值得反复推敲的游戏，都会生长出属于自己的社群。<strong>排位赛、回放系统、大神观战、排行榜</strong>——这些功能不是为了“做功能”而做，而是为了<strong>让玩家的每一局对弈都有记录、有反馈、有成长，让高水平对局被看见、被学习、被传承。</strong></p>
<h1>最后</h1>
<p><strong>烽火棋</strong>不是一款“速成”的游戏。它的规则需要耐心阅读，它的策略需要反复琢磨，它的胜负往往藏在几回合之前的某一个抉择里。</p>
<p>但正是这种<strong>深度与复杂度</strong>，让我们觉得它值得被认真对待、被持续打磨。</p>
<p>如果你读到了这里，我们由衷感谢你的兴趣与耐心。希望你在烽火棋的棋盘上，找到属于你自己的烽火与谋略。</p>
<hr>
<p><strong>—— 烽火棋开发组</strong></p>
<script>
    function generateTOC() {
        const container = document.getElementById('toc-container');
        if (!container) return;
        const headings = document.querySelectorAll('h1, h2, h3');
        if (!headings.length) return;

        let html = '<nav class="side-nav"><div class="nav-header">目录</div><ul class="nav-tree">';
        let lastLevel = 0;
        let stack = [];

        headings.forEach((el, index) => {

            if (!el.id) {
                el.id = 'sec-' + index + '-' + (el.textContent || '').replace(/\s+/g, '-').slice(0, 20);
            }

            const level = parseInt(el.tagName.substring(1));
            const text = el.textContent.trim();

            if (level > lastLevel) {

                html += '<ul>';
            } else if (level < lastLevel) {

                html += '</ul></li>';
            } else if (index > 0) {

                html += '</li>';
            }

            html += `<li><a href="#${el.id}">${text}</a>`;
            lastLevel = level;
        });


        for (let i = 0; i < lastLevel; i++) {
            html += '</li></ul>';
        }

        html += '</ul></nav>';
        container.innerHTML = html;
    }


    document.addEventListener('click', function(e) {
        const link = e.target.closest('.side-nav a[href^="#"]');
        if (link) {
            e.preventDefault();
            const target = document.querySelector(link.getAttribute('href'));
            if (target) target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });


    document.addEventListener('DOMContentLoaded', generateTOC);
</script>

</html>
<style>
    h1,
    h2,
    h3,
    h4 {
        color: #f0d080;
        font-weight: 700;
        margin: 1.2em 0 0.6em;
        letter-spacing: 0.5px;
    }

    h1 {
        font-size: 28px;
        text-align: center;
        margin-top: 0.2em;
        background: linear-gradient(180deg, #fbe6b4 0%, #d9ab54 100%);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    h2 {
        font-size: 20px;
        border-bottom: 1px solid rgba(232, 197, 110, 0.15);
        padding-bottom: 6px;
    }

    h3 {
        font-size: 17px;
        color: #e0c880;
    }

    hr {
        border: none;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(232, 197, 110, 0.25), transparent);
        margin: 20px 0;
    }

    p {
        margin: 0.6em 0;
    }

    ul,
    ol {
        padding-left: 22px;
        margin: 0.5em 0;
    }

    li {
        margin: 0.3em 0;
    }

    li strong {
        color: #ecd8b0;
    }

    code {
        background: rgba(240, 208, 128, 0.10);
        color: #e8d090;
        padding: 1px 8px;
        border-radius: 4px;
        font-size: 0.9em;
        font-family: "SF Mono", "JetBrains Mono", "Fira Code", monospace;
        border: 1px solid rgba(232, 197, 110, 0.10);
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin: 14px 0;
        font-size: 14px;
        border-radius: 10px;
        overflow: hidden;
    }

    th,
    td {
        border: 1px solid rgba(232, 197, 110, 0.13);
        padding: 8px 12px;
        text-align: left;
    }

    th {
        background: rgba(240, 208, 128, 0.08);
        color: #f0d080;
        font-weight: 600;
    }

    td {
        color: #cdc0a8;
    }

    tr:nth-child(even) td {
        background: rgba(240, 208, 128, 0.02);
    }

    a {
        color: #e8c56e;
        text-decoration: none;
        border-bottom: 1px dotted rgba(232, 197, 110, 0.2);
        transition: border-color 0.2s;
    }

    a:hover {
        border-bottom-color: #e8c56e;
    }

    blockquote {
        margin: 12px 0;
        padding: 10px 16px;
        border-left: 3px solid rgba(232, 197, 110, 0.3);
        background: rgba(240, 208, 128, 0.04);
        border-radius: 0 8px 8px 0;
        color: #c0b090;
    }

    blockquote p {
        margin: 0;
    }


    .highlight {
        color: #f0d080;
        font-weight: 600;
    }

    .dim {
        color: #8a7a62;
        font-size: 0.9em;
    }


    .tag {
        display: inline-block;
        background: rgba(232, 197, 110, 0.10);
        border: 1px solid rgba(232, 197, 110, 0.15);
        padding: 0 10px;
        border-radius: 12px;
        font-size: 12px;
        color: #d0b878;
        font-weight: 600;
    }

    #toc-container {
        position: fixed;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        opacity: 0.15;
        z-index: 999;
        max-height: 85vh;
        overflow-y: auto;
    }

    #toc-container:hover {
        opacity: 1;
    }

    .side-nav {
        background: rgba(26, 23, 18, 0.92);
        border: 1px solid rgba(232, 197, 110, 0.15);
        border-radius: 10px;
        padding: 12px 8px;
        backdrop-filter: blur(8px);
        min-width: 160px;
        max-width: 200px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
    }

    .nav-header {
        color: #f0d080;
        font-size: 13px;
        font-weight: 700;
        text-align: center;
        padding-bottom: 8px;
        border-bottom: 1px solid rgba(232, 197, 110, 0.1);
        margin-bottom: 6px;
    }

    .nav-tree {
        list-style: none;
        padding: 0;
        margin: 0;
        font-size: 12px;
    }

    .nav-tree li {
        margin: 2px 0;
        padding: 0;
    }

    .nav-tree>li>a {
        color: #e8c56e;
        font-weight: 600;
        font-size: 13px;
        display: block;
        padding: 2px 0;
    }

    .nav-tree ul {
        list-style: none;
        padding-left: 12px;
        margin: 2px 0;
    }

    .nav-tree ul li a {
        color: #b0a088;
        display: block;
        padding: 1px 0 1px 4px;
        text-decoration: none;
        border-left: 2px solid transparent;
        font-size: 12px;
    }

    .nav-tree ul li a:hover {
        color: #f0d080;
        border-left-color: #e8c56e;
    }

    .nav-tree a {
        text-decoration: none;
    }

    @media (max-width: 768px) {
        #toc-container {
            display: none;
        }
    }
</style>