<?php
$page_title = '更新公告';
require($_SERVER['DOCUMENT_ROOT'] . '/use/set.php');
?>
<style>
    h1 {
        font-size: 3.2rem;
        font-weight: 700;
        letter-spacing: -0.02em;
        margin: 0 0 0.4rem 0;
        padding: 0.4rem 0 0.2rem 0;
        color: #f0e6d3;
        border-bottom: 3px solid #c0392b;
        display: inline-block;
        padding-right: 2rem;
        background: linear-gradient(135deg, #f5e6d3 20%, #d4a373 70%, #b87333 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        text-shadow: 0 0 40px rgba(192, 57, 43, 0.2);
        border-image: linear-gradient(to right, #c0392b, #e67e22, #c0392b) 1;
        border-bottom-style: solid;
        border-bottom-width: 3px;
    }

    .meta {
        display: inline-block;
        font-size: 1.05rem;
        font-weight: 500;
        color: #c9d4e6;
        background: rgba(255, 215, 180, 0.06);
        padding: 0.4rem 1.6rem;
        border-radius: 100px;
        letter-spacing: 0.6px;
        margin-top: 0.2rem;
        margin-bottom: 2.2rem;
        border: 1px solid rgba(230, 126, 34, 0.25);
        backdrop-filter: blur(8px);
        box-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
    }

    .meta strong {
        color: #f5cba0;
        font-weight: 600;
    }


    h2 {
        font-size: 2rem;
        font-weight: 600;
        margin: 2.8rem 0 1rem 0;
        padding: 0.4rem 0 0.4rem 1.4rem;
        color: #f0e6d3;
        border-left: 6px solid #e67e22;
        background: linear-gradient(to right, rgba(230, 126, 34, 0.08), transparent 80%);
        letter-spacing: -0.01em;
        border-radius: 0 4px 4px 0;
    }


    h3 {
        font-size: 1.45rem;
        font-weight: 600;
        margin: 1.8rem 0 0.6rem 0;
        color: #f0dcc8;
        display: inline-block;
        padding-right: 1.5rem;
        border-bottom: 2px solid rgba(230, 126, 34, 0.3);
        padding-bottom: 0.2rem;
        letter-spacing: 0.3px;
    }


    p {
        margin: 0.8rem 0 1rem 0;
        padding: 0.1rem 0;
        font-size: 1.08rem;
        max-width: 78ch;
        color: #d0d9e8;
        line-height: 1.8;
    }


    ul {
        margin: 0.4rem 0 1.2rem 0;
        padding-left: 0.2rem;
        list-style-type: none;
    }

    li {
        margin: 0.5rem 0;
        padding: 0.3rem 0 0.3rem 2.4rem;
        font-size: 1.08rem;
        position: relative;
        color: #d5deec;
        border-bottom: 1px solid rgba(255, 215, 180, 0.04);
        transition: all 0.1s;
    }

    li::before {
        content: "◆";
        position: absolute;
        left: 0.2rem;
        color: #e67e22;
        font-weight: 400;
        font-size: 1rem;
        opacity: 0.9;
        top: 0.4rem;
    }


    ul ul {
        margin: 0.2rem 0 0.3rem 0;
        padding-left: 1.2rem;
    }

    ul ul li::before {
        content: "◇";
        color: #c9a87c;
        font-size: 0.9rem;
        left: 0.4rem;
        top: 0.35rem;
    }


    strong,
    b {
        color: #f5cba0;
        font-weight: 650;
        background: rgba(230, 126, 34, 0.08);
        padding: 0 0.5rem;
        border-radius: 6px;
        font-weight: 650;
    }

    em {
        font-style: italic;
        color: #e6b17e;
        background: rgba(255, 215, 180, 0.05);
        padding: 0.1rem 0.4rem;
        border-radius: 6px;
    }


    hr {
        margin: 2.8rem 0 2rem 0;
        border: 0;
        height: 1px;
        background: linear-gradient(to right, rgba(230, 126, 34, 0.3), rgba(192, 57, 43, 0.5), rgba(230, 126, 34, 0.1));
        width: 100%;
        max-width: 80ch;
        box-shadow: 0 0 20px rgba(192, 57, 43, 0.1);
    }


    blockquote {
        margin: 1.6rem 0 1.6rem 0.5rem;
        padding: 1.2rem 2rem;
        background: rgba(192, 57, 43, 0.04);
        border-left: 6px solid #e67e22;
        border-radius: 0 16px 16px 0;
        color: #d5deec;
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
        max-width: 74ch;
        backdrop-filter: blur(4px);
    }

    blockquote p {
        margin: 0.3rem 0;
        color: #d5deec;
    }


    small {
        display: inline-block;
        margin-top: 1rem;
        font-size: 0.95rem;
        color: #8a9bb5;
        border-top: 1px solid rgba(255, 215, 180, 0.08);
        padding-top: 1rem;
        letter-spacing: 0.4px;
    }


    a {
        color: #e6b17e;
        text-decoration: none;
        border-bottom: 1px solid rgba(230, 126, 34, 0.2);
        transition: all 0.2s;
        font-weight: 500;
    }

    a:hover {
        border-bottom: 2px solid #e67e22;
        color: #f5cba0;
        text-shadow: 0 0 12px rgba(230, 126, 34, 0.15);
    }


    .tag {
        background: rgba(200, 160, 120, 0.08);
        color: #d5c8b8;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 0.2rem 1rem;
        border-radius: 30px;
        letter-spacing: 0.5px;
        display: inline-block;
        margin: 0 0.2rem;
        vertical-align: middle;
        border: 1px solid rgba(230, 126, 34, 0.15);
    }

    .tag-fire {
        background: rgba(192, 57, 43, 0.15);
        color: #f5cba0;
        border-color: rgba(192, 57, 43, 0.3);
        box-shadow: 0 0 20px rgba(192, 57, 43, 0.05);
    }


    .feature-grid li::before {
        content: "⚔";
        color: #d4a373;
        font-size: 1rem;
        left: 0;
        top: 0.4rem;
    }


    .signature {
        font-size: 1rem;
        color: #8a9bb5;
        background: rgba(255, 215, 180, 0.02);
        padding: 0.4rem 1.4rem;
        border-radius: 40px;
        display: inline-block;
        border: 1px solid rgba(255, 215, 180, 0.06);
    }


    .glow-text {
        background: linear-gradient(135deg, #f5e6d3, #d4a373);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }


    @media (max-width: 600px) {
        body {
            padding: 1.5rem 1.2rem 3rem 1.2rem;
        }

        h1 {
            font-size: 2.4rem;
        }

        h2 {
            font-size: 1.6rem;
            padding-left: 1rem;
        }

        li {
            padding-left: 2rem;
        }
    }
</style>
</head>

<body>


    <h1>⚔ 烽火棋 · V1.06</h1>
    <div class="meta">
        <strong>✦ 赛季竞技重构</strong> &nbsp;·&nbsp; 2026.09.25 &nbsp;·&nbsp; 天雷擂鼓，分秒必争
    </div>

    <h2>✦ 排位竞技 · 赛季分体系重做</h2>
    <p>
        V1.06 对排位进行了<strong>底层重构</strong>：告别单一积分，引入更细腻、更公平的赛季竞技体系。
    </p>
    <ul>
        <li><strong>赛季真实分 rating + 段位 tier</strong> —— 段位划分为 <em>S+ / S / A / B / C / D / E</em>（S+≥1600、S≥1300、A≥1000、B≥700、C≥450、D≥200），段位越高越显荣耀。</li>
        <li><strong>动态积分结算</strong> —— 综合<em>预期胜率、分差、新手保护、连胜加成、表现评分、以弱胜强</em>多因子计算；段位越高赢分越稳、落败扣分越重，低段位保护更厚。</li>
        <li><strong>数据沉淀</strong> —— 数据库新增 rating / tier / 波动值 / 近况记录等字段，结算接口同步返回段位与真实分，战绩更有迹可循。</li>
    </ul>

    <h2>✦ 对局体验 · 引擎与 HUD 升级</h2>
    <ul>
        <li><strong>引擎实时统计</strong> —— 新增按阵营 / 兵种的存活计数与母棋阵亡计数，状态读取零重复计算，落子更跟手。</li>
        <li><strong>HUD 增量刷新</strong> —— 迷雾、核心区、战报改为仅在状态真正变化时更新 DOM，告别卡顿与闪烁。</li>
    </ul>

    <h2>✦ 视觉焕新 · 大厅与匹配</h2>
    <ul>
        <li><strong>竞技场首页重做</strong> —— 棋盘实景 + 飘浮棋子 + 光晕噪点氛围；顶部用户条、右侧悬浮 Dock、迷你工具入口焕新。</li>
        <li><strong>匹配模态框颠覆性换血</strong> —— 更沉浸的搜寻 / 匹配界面，操作更顺手。</li>
        <li><strong>评分结算面板重构</strong> —— 六维评分与结算交互视觉整体升级。</li>
    </ul>

    <h2>✦ 匹配成功 · 天雷战场动画</h2>
    <p>
        匹配成功瞬间，<strong>天雷劈落、雷云压顶、湮灭冲击波与余烬飞溅</strong>交织，
        营造两军对垒、肃杀湮灭的战场景象，竞技感拉满。
    </p>

    <h2>✦ 昵称与公平</h2>
    <ul>
        <li>匹配成功与对局结算<strong>显示双方真实昵称</strong>。</li>
        <li>当双方平均分超过阈值（2000）时，自动匿名为 <em>“巅峰棋手一 / 巅峰棋手二”</em>，保障高分段的公平对抗。</li>
    </ul>

    <hr>


    <h1>⚔ 烽火棋 · V1.00</h1>
    <div class="meta">
        <strong>✦ 正式版</strong> &nbsp;·&nbsp; 2026.07.31 &nbsp;·&nbsp; 棋盘烽火，即日点燃
    </div>

    <h2>✦ 开篇 · 从零到一</h2>
    <p>
        <strong>烽火棋 · GMOK</strong> 是 30×30 大棋盘上的原创回合制战棋，融合迷雾、许可区、渡河与核心区攻防。
        历经数月开发与内测，我们迎来了首个稳定版本 <strong>V1.00</strong>。
        这不仅是一次版本更新，更是完整游戏体验的正式启航。
    </p>
    <p>
        本次发布包含 <em>完整人机对战</em>、<em>本地双人</em>、<em>联机娱乐</em>、<em>排位竞技</em> 以及 <em>教学关卡</em> 和 <em>残局编辑器</em>
    </p>

    <h2>✦ 核心玩法 · 烽火机制</h2>
    <p>
        红蓝对坐，各 20 枚棋子。母棋、子棋、军棋、探棋、盾棋、白板 —— 六类棋子各司其职。
    </p>
    <ul>
        <li><strong>母棋 · 斜向 1 格</strong> —— 核心，未继位前只能待在本方核心区。</li>
        <li><strong>子棋 · 八向 2 格</strong> —— 母棋阵亡后继位，成为新的母棋。</li>
        <li><strong>军棋 · 八向 3 格</strong> —— 唯一能渡河的棋子；每消灭 2 枚敌方军棋，步长 +1。</li>
        <li><strong>探棋 · 横纵 4 格</strong> —— 提供视野与许可区，是全盘发动机。</li>
        <li><strong>盾棋 · 横纵 1 格</strong> —— 本方子棋在场时只能被横向吃掉，保护同列前方 1–3 格。</li>
        <li><strong>白板 · 横纵 1 格</strong> —— 复活产生的降级棋子。</li>
    </ul>

    <h3>🔥 迷雾 · 许可区 · 渡河</h3>
    <ul>
        <li><strong>迷雾</strong>：开局仅见本方半场。母棋阵亡或全场棋子 ≤20 时全局解除；子棋阵亡则对方单方解除。</li>
        <li><strong>许可区</strong>：军棋、盾棋开局锁死。探棋落点周围形成 2×3 许可区，被覆盖且可见的重子才能行动。</li>
        <li><strong>渡河</strong>：中央 14/15 行为河道。军棋直线渡河（纵向步长 ≥3、横向 ≥4），缺口通道要求周围 2×2 无子。</li>
    </ul>

    <h3>⚔ 核心区惩罚 · 区块上限 · 继位复活</h3>
    <ul>
        <li><strong>核心区惩罚</strong>：全场棋子 >26 且对方有盾棋时强攻核心区，罚下探棋或军棋；无子可罚则罚停一回合。</li>
        <li><strong>区块上限</strong>：本方半场左右两区各最多 9 枚非王棋子。</li>
        <li><strong>继位与复活</strong>：母棋阵亡后子棋继位（横纵 2 格、可吃子）；继位母棋每次吃子可复活一枚阵亡棋子为白板。</li>
    </ul>

    <h2>✦ 功能全览 · V1.00</h2>

    <h3> 对局模式</h3>
    <ul class="feature-grid">
        <li><strong>人机对战</strong> — 4 档难度（新手 / 棋手 / 大师 / 炼狱），αβ 迭代加深 + 历史启发热力图，硬实时预算 ≤200ms。</li>
        <li><strong>本地双人</strong> — 同屏对弈，适合好友切磋。</li>
        <li><strong>联机娱乐</strong> — 房间制，支持密码、观战、实时大厅列表。</li>
        <li><strong>排位竞技</strong> — 积分匹配池，按分差撮合，随等待时长放宽窗口。</li>
    </ul>

    <h3> 赛后系统</h3>
    <ul class="feature-grid">
        <li><strong>六维度评分</strong> — 杀伤 / 攻势 / 生存 / 资源 / 谋略 / 纪律 + 评级动画。</li>
        <li><strong>对局录像</strong> — 二进制 FHQR 格式存档与回放。</li>
        <li><strong>排行榜</strong> — 综合 / 积分 / 胜率 / 连胜 / S+ 五榜。</li>
    </ul>

    <h3> 教学与创作</h3>
    <ul class="feature-grid">
        <li><strong>教学关卡</strong> — 5 关新手教程 + 10 关官方残局。</li>
        <li><strong>残局编辑器</strong> — 自由摆子、导出 FEN、本地草稿。</li>
    </ul>





    <h2> 版本历史 · 烽火之路</h2>
    <ul>
        <li><strong>V1.06</strong> · 2026.09.25 — 赛季分/段位体系重做、引擎与 HUD 升级、大厅与匹配视觉焕新、天雷匹配动画、真实昵称与巅峰匿名。</li>
        <li><strong>V1.00</strong> · 2026.07.31 — 首个正式版：完整玩法、联机、排位、后台、教学与编辑器。</li>
        <li><strong>v0.9.5</strong> · 2026.07.15 — 内测收官：修复排位匹配 bug，优化 AI 搜索效率。</li>
        <li><strong>v0.9.0</strong> · 2026.06.28 — 新增评分系统、录像回放、残局编辑器。</li>
        <li><strong>v0.8.0</strong> · 2026.06.01 — 核心机制定型：迷雾、许可区、渡河、继位。</li>
    </ul>





    <small>
        后续规划：V1.10 将继续打磨 <em>赛季系统</em>（段位特效、赛季排行榜），并开放更多残局挑战。敬请期待。
    </small>



</body>

</html>