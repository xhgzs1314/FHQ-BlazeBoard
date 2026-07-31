<?php
$page_title = '玩法&竞技规则';
require($_SERVER['DOCUMENT_ROOT'] . '/use/set.php');
?>
<div id="toc-container"></div>
<div id="fhq_rule">
    <h1>烽火棋 · 玩法规则</h1>
    <hr>
    <h2>一、坐标与棋盘</h2>
    <ul>
        <li>棋盘 <strong>30 × 30</strong>，格子坐标记作 <code>(c, r)</code>：<code>c</code> 为列(0–29，自左向右)，<code>r</code> 为行(0–29，自上向下)。</li>
        <li><strong>红方</strong>位于上方(行号小)，<strong>蓝方</strong>位于下方(行号大)。红方先手。</li>
        <li>五个区域：</li>
        <li><strong>红方本土</strong>：行 <code>0–12</code>；<strong>蓝方本土</strong>：行 <code>17–29</code>。</li>
        <li>每方本土再以 <strong>列 15</strong> 为界分为左(列 0–14)、右(列 15–29)两个 <strong>15×13 区块</strong>，共四块(区域 1–4)。</li>
        <li><strong>中央区域(区域 5)</strong>：行 <code>13–16</code>，共 30×4。</li>
        <li><strong>核心区</strong>：红方 = 行 <code>0–1</code>；蓝方 = 行 <code>28–29</code>（各 30×2）。</li>
    </ul>
    <h3>河与空隙通道</h3>
    <ul>
        <li><strong>河</strong>位于中央区的 <strong>行 14–15</strong>，按列分为 4 段，每段 6 列宽 × 2 行高：</li>
        <li>河段列：<code>0–5</code>、<code>8–13</code>、<code>16–21</code>、<code>24–29</code>。</li>
        <li><strong>空隙通道</strong>：河段之间的 2 列缺口，位于 <strong>行 14–15</strong> 的列 <code>6–7</code>、<code>14–15</code>、<code>22–23</code>。它是非军棋棋子穿越中央区的唯一通路。</li>
    </ul>
    <hr>
    <h2>二、棋子配置（每方 20 枚）</h2>
    <table>
        <thead>
            <tr>
                <th>棋子</th>
                <th>数量</th>
                <th>记号</th>
                <th>步长</th>
                <th>方向</th>
                <th>可吃子</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>母棋</td>
                <td>1</td>
                <td>母/K</td>
                <td>1</td>
                <td>斜向</td>
                <td>否</td>
            </tr>
            <tr>
                <td>子棋</td>
                <td>1</td>
                <td>子/P</td>
                <td>2</td>
                <td>任意</td>
                <td>否</td>
            </tr>
            <tr>
                <td>军棋</td>
                <td>6</td>
                <td>军/G</td>
                <td>3(可加成)</td>
                <td>任意</td>
                <td>是</td>
            </tr>
            <tr>
                <td>探棋</td>
                <td>8</td>
                <td>探/S</td>
                <td>4</td>
                <td>横/纵</td>
                <td>否</td>
            </tr>
            <tr>
                <td>盾棋</td>
                <td>4</td>
                <td>盾/D</td>
                <td>1</td>
                <td>横/纵</td>
                <td>是(有条件)</td>
            </tr>
            <tr>
                <td>白板棋</td>
                <td>复活生成</td>
                <td>兵/·</td>
                <td>1</td>
                <td>横/纵</td>
                <td>否</td>
            </tr>
        </tbody>
    </table>
    <ul>
        <li><strong>区块 9 子上限</strong>：每个 15×13 区块内，<strong>己方</strong>棋子最多 9 枚，<strong>母棋与子棋不计入</strong>，对方棋子不计入。白板棋计入。</li>
        <li>〔g〕判定发生在<strong>落子终点</strong>：若某己方棋子停留后会使其所在本土区块的计入棋子数 &gt; 9 则该落点非法；<strong>区块内移动</strong>(数量不变)与<strong>移动到中央/敌方</strong>始终允许；棋子可<strong>穿过</strong>已满区块继续滑行，只是不能停在其中。</li>
    </ul>
    <h3>移动通则</h3>
    <ul>
        <li>所有棋子按“<strong>滑行</strong>”方式移动：沿允许方向逐格前进，<strong>路径必须为空</strong>，不可跳子。</li>
        <li>落点为空 → 普通移动；落点为敌方棋子且本棋可吃子 → 吃子(见下)；射线遇到任何棋子即停止(不可穿越棋子)。</li>
    </ul>
    <hr>
    <h2>三、吃子</h2>
    <ul>
        <li>仅 <strong>军棋</strong> 与 <strong>盾棋(需满足条件)</strong> 可吃子；母/子/探/白板 <strong>不可吃子</strong>。</li>
        <li>吃子 = 移动到敌方棋子所在格并将其移除，本棋落于该格。一次移动最多吃 1 子。</li>
        <li>受 <strong>盾棋防御</strong>、<strong>区块 9 子上限</strong> 限制的落点不构成合法吃子(见对应章节)。</li>
    </ul>
    <hr>
    <h2>四、渡河（仅军棋）</h2>
    <ul>
        <li><strong>只有军棋</strong>可进入/穿越河面(行 14–15 的河段格)；其他棋子视河面为<strong>不可通行的墙</strong>。</li>
        <li>军棋渡河<strong>不可斜向</strong>，只能横/纵：</li>
        <li><strong>纵向渡河</strong>需有效步长 <strong>≥ 3</strong>；<strong>横向渡河</strong>需有效步长 <strong>≥ 4</strong>。</li>
        <li>因军棋基础步长为 3，<strong>横向渡河</strong>需先通过“军棋步长加成”达到 ≥4。</li>
        <li><strong>单段单枚</strong>：同一条横向河段同一时刻只允许一枚棋子处于其中；若目标河段已有其他棋子，则不可进入。</li>
    </ul>
    <h2>五、空隙通道的 2×2 阻挡（适用于任何棋子）</h2>
    <ul>
        <li><strong>任何棋子</strong>经由空隙通道(行 14–15 的非河列)穿越中央区时，需通过 2×2 阻挡检查。</li>
        <li><strong>2×2 范围</strong>〔g〕：以该棋子<strong>起点格</strong>为右下角，即列 <code>{c-1, c}</code> × 行 <code>{r-1, r}</code>。</li>
        <li>例：棋子 <code>(6,13)</code> → 检查 <code>(5,12)(5,13)(6,12)(6,13)</code>。</li>
        <li>若该 2×2 内存在<strong>任何其他棋子</strong>，则<strong>不能穿越</strong>空隙通道(可停在通道之前的格子，但射线到通道即止)。</li>
    </ul>
    <hr>
    <h2>六、核心区进攻限制与惩罚</h2>
    <ul>
        <li><strong>限制生效条件</strong>：当<strong>全场棋子总数 &gt; 26</strong> 时生效；总数 ≤ 26 时自动解除。</li>
        <li><strong>触发惩罚</strong>：当一方棋子<strong>本次移动的落点位于对方核心区</strong>，且<strong>防守方仍有存活盾棋</strong>，且限制生效时，判定为“强攻核心”并施加惩罚。</li>
        <li>〔g〕以<strong>落点是否在对方核心行</strong>为准，<strong>停留或在核心内移动均会再次触发</strong>（不限于首次进入）。</li>
        <li><strong>罚子优先级</strong>（先罚子，无子可罚才罚回合）：</li>
    </ul>
    <ol>
        <li>若进攻棋子本身是 <strong>军棋/探棋</strong> → 直接罚下<strong>它本身</strong>；</li>
        <li>否则罚下己方一枚 <strong>探棋(优先)/军棋</strong>；</li>
        <li>若无军棋/探棋可罚 → 罚掉该方<strong>下一次回合权</strong>(跳过一回合)。</li>
    </ol>
    <hr>
    <h2>七、视野与迷雾</h2>
    <ul>
        <li><strong>可见性</strong>（对某一方而言）：满足任一条件即可见——</li>
    </ul>
    <ol>
        <li>该方<strong>迷雾已解除</strong>(见下)；</li>
        <li>格子在该方<strong>本土行范围</strong>内(己方本土常亮)；</li>
        <li>格子被该方某枚<strong>探棋的 2×2 视野</strong>覆盖。</li>
    </ol>
    <ul>
        <li><strong>探棋视野</strong>〔g〕：以探棋格为右下角的 2×2，即行 <code>{r-1, r}</code> × 列 <code>{c-1, c}</code>。</li>
        <li>渲染上，<strong>看不见的格子里的敌方棋子被隐藏</strong>；己方棋子始终可见。</li>
        <li><strong>迷雾解除条件</strong>：</li>
        <li><strong>任一方母棋阵亡</strong> → <strong>双方</strong>迷雾全局解除；</li>
        <li><strong>全场棋子总数 ≤ 20</strong> → <strong>双方</strong>迷雾全局解除；</li>
        <li><strong>对方子棋阵亡</strong> → <strong>该方</strong>(即对方的敌人)迷雾解除。</li>
    </ul>
    <hr>
    <h2>八、行动许可（探棋许可区）</h2>
    <ul>
        <li>除<strong>探棋</strong>与<strong>己方核心区内</strong>的棋子外，其余棋子(“从属棋子”)需满足行动许可才能移动。</li>
        <li><strong>许可区生成</strong>：探棋每次移动后，以其<strong>落点</strong>为中心生成一个 <strong>2×3 许可区</strong>并覆盖本方旧许可区。</li>
        <li>〔g〕许可区 = 行 <code>{r-1, r}</code> × 列 <code>{c-1, c, c+1}</code>（横向居中，纵向以落点为下缘）。</li>
        <li>〔g〕许可区<strong>持续有效直到本方探棋再次移动</strong>（而非“仅本回合”）。因单机/联机均为“每回合一步”，从属棋子只要当前不在许可区内即无法被指令。</li>
        <li><strong>可行动判定 <code>canAct</code></strong>（自上而下）：</li>
    </ul>
    <ol>
        <li>非本方回合 → 不可动；</li>
        <li>该方处于<strong>自由行动</strong>状态 → 可动（无视一切限制，见下）；</li>
        <li><strong>探棋</strong> → 始终可动；</li>
        <li>位于<strong>己方核心行</strong> → 可动（核心豁免）；</li>
        <li>否则：必须<strong>处于本方许可区内</strong>，且（若本方迷雾未解除）还需<strong>处于探棋视野内</strong>。</li>
    </ol>
    <ul>
        <li><strong>自由行动</strong>：满足任一即该方全体自由行动(绕过许可区/视野)——</li>
        <li><strong>对方子棋阵亡</strong>（该方获得自由行动）；或</li>
        <li><strong>全场棋子总数 ≤ 20</strong>（双方自由行动）。</li>
    </ul>
    <hr>
    <h2>九、探棋阵亡的坐标披露</h2>
    <ul>
        <li>当探棋被<strong>对方吃掉</strong>时（注意：因己方罚子而移除<strong>不触发</strong>）：</li>
        <li>对方<strong>不获得</strong>该探棋任何时刻的许可区/视野范围信息；</li>
        <li>对方<strong>永久获知</strong>该探棋<strong>每一步的落点坐标</strong>（含初始位置、各步落点、直至阵亡格），以<strong>单格点位</strong>形式披露；连续停留同一格只记一次。</li>
        <li>披露坐标仅供获知方查看（棋盘上以情报点标记），不附带任何范围或路径周边视野。</li>
    </ul>
    <hr>
    <h2>十、各棋子特殊规则</h2>
    <h3>母棋</h3>
    <ul>
        <li>步长 1，仅斜向；<strong>不得离开己方核心区</strong>(行 0–1 / 28–29)；不可吃子。</li>
        <li>母棋阵亡后，若子棋尚存 → <strong>子棋继位为新母棋</strong>（见子棋）。</li>
    </ul>
    <h3>子棋 → 继位母棋</h3>
    <ul>
        <li>原始子棋：步长 2，任意方向；不可吃子。</li>
        <li><strong>继位后</strong>〔g〕：类型变为“母棋(继位)”，<strong>步长 2、仅横/纵、可吃子</strong>，且<strong>不再受核心区约束</strong></li>
        <li><strong>继位母棋每吃一子</strong> → 复活一枚己方已阵亡棋子为 <strong>白板棋</strong>，落于<strong>继位母棋移动前的原格</strong>（该格被占则放弃复活）。</li>
        <li>白板棋：横/纵步长 1、不可吃子、无任何特殊能力，且计入区块 9 子上限。</li>
        <li><strong>子棋阵亡（真正阵亡，非继位）</strong> → 对方迷雾解除且对方全体自由行动；同时<strong>己方盾棋防御与吃子能力失效</strong>。</li>
    </ul>
    <h3>盾棋</h3>
    <ul>
        <li>步长 1，仅横/纵。<strong>吃子与防御能力均需己方子棋(原始子棋)存活</strong>。</li>
        <li><strong>防御(子棋存活时)</strong>：</li>
        <li>盾棋<strong>正方向 3×1</strong> 区域(红朝下、蓝朝上，同列前方 1–3 格)内的<strong>己方棋子不可被吃</strong>；</li>
        <li>盾棋<strong>自身仅可被侧向(横向 <code>dr==0</code>)吃下</strong>，正面/斜向进攻无效。〔g：侧向 = 严格横向。〕</li>
        <li>己方子棋阵亡后：防御失效，且盾棋<strong>不再具备吃子能力</strong>。</li>
    </ul>
    <h3>探棋</h3>
    <ul>
        <li>步长 4，仅横/纵；不可吃子。</li>
        <li>提供 2×2 视野与 2×3 许可区（见第七、八章）。</li>
        <li>阵亡坐标披露见第九章</li>
    </ul>
    <h3>军棋</h3>
    <ul>
        <li>步长 3(可加成)，任意方向；可吃子；可渡河(第四章)。</li>
        <li><strong>步长加成</strong>：对方每损失 2 枚军棋，本方军棋步长 <strong>+1</strong>（<code>floor(对方阵亡军棋数 / 2)</code>）。</li>
        <li><strong>加成清零</strong>：一旦本方<strong>母棋或子棋曾阵亡</strong>(<code>lostRoyal</code>) → 本方军棋步长加成<strong>永久归零</strong>。</li>
    </ul>
    <hr>
    <h2>十一、回合流程与胜负</h2>
    <ul>
        <li>红方先手，红/蓝交替。<strong>蓝方走完 → 回合数 +1</strong>。</li>
        <li>若某方被罚“跳过回合”，轮到它时自动跳过并交还对方。</li>
        <li><strong>胜利条件</strong>：当对方<strong>母棋与子棋(含继位母棋)全部阵亡</strong>，<strong>且</strong>对方<strong>军棋 + 盾棋存活总数 ≤ 4</strong> 时，即获胜。判定在每步移动后进行。</li>
    </ul>
</div>
<hr>
<div id="ranking_rule">
    <h1>烽火棋 · 竞技规则</h1>

    <h2>一、生态总览</h2>
    <p>烽火棋提供六条并行的对局路线，从零压力练习一路通到高强度竞技。它们共用同一套棋规与同一套评分，区别只在于「算不算分」和「谁来裁决」。</p>
    <table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; text-align: center;">
        <thead>
            <tr>
                <th>模式</th>
                <th>对手</th>
                <th>计分</th>
                <th>六维评分</th>
                <th>可观战</th>
                <th>适合场景</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>娱乐房间</strong></td>
                <td>真人（自建/加入）</td>
                <td>不计分</td>
                <td>有</td>
                <td>是</td>
                <td>约战好友、试新战术</td>
            </tr>
            <tr>
                <td><strong>排位赛</strong></td>
                <td>真人（系统匹配）</td>
                <td><span class="highlight">计入排位分</span></td>
                <td>有</td>
                <td>否</td>
                <td>冲段位、上排行榜</td>
            </tr>
            <tr>
                <td><strong>人机试炼</strong></td>
                <td>AI（四档难度）</td>
                <td>不计分</td>
                <td>有</td>
                <td>—</td>
                <td>练基本功、摸清棋理</td>
            </tr>
            <tr>
                <td><strong>本地对战</strong></td>
                <td>同设备真人</td>
                <td>不计分</td>
                <td>有</td>
                <td>—</td>
                <td>面对面切磋、无网络</td>
            </tr>
            <tr>
                <td><strong>新手教程</strong></td>
                <td>预设小局面</td>
                <td>不计分</td>
                <td>—</td>
                <td>—</td>
                <td>第一次上手、学会走子</td>
            </tr>
            <tr>
                <td><strong>残局挑战</strong></td>
                <td>AI（每局指定难度）</td>
                <td>不计分</td>
                <td>—</td>
                <td>—</td>
                <td>练定式、出题给别人</td>
            </tr>
        </tbody>
    </table>
    <ul>
        <li><strong>整局对战一套战绩语言：</strong>娱乐房间、排位赛、人机试炼、本地对战这四条路线收场后，你都会看到同样的六维雷达与 S 级评价，练习里的进步可以直接换算成排位赛的底气。</li>
        <li><strong>教程与残局只判「过没过」：</strong>这两条是单题训练，不是整局对战，因此按关卡目标判定通关与否，不出六维评分。</li>
        <li><strong>唯一会影响段位的是排位赛：</strong>其余模式随便试错，不必担心掉分。</li>
        <li><strong>每一局都能留档：</strong>对局可导出回放文件，高质量排位局还会被系统自动挑进「排位精选」供所有人观摩。</li>
    </ul>

    <h2>二、娱乐房间</h2>
    <p>不计分的真人对战场。开一间房，把房号发给朋友，就能开打；也可以在房间列表里随手找一局。</p>
    <h3>建房与入座</h3>
    <div>
        <ul>
            <li><strong>创建房间：</strong>点击「创建房间」即成为房主，可自定房间名称、密码（留空即公开房）、以及是否允许观战。</li>
            <li><strong>两种加入方式：</strong>直接输入 <span class="highlight">6 位房间号</span>，或从房间列表里点选。</li>
            <li><strong>房间列表：</strong>实时刷新所有等待中的公开房间，显示房主昵称、当前人数与状态（等待中 / 对局中），一眼看清哪间还缺人。</li>
            <li><strong>密码房：</strong>设了密码的房间不会被陌生人乱入，适合约好的私局与队内训练。</li>
            <li><strong>先到先坐：</strong>前两名进入者分别落座红方（先手）与蓝方（后手），其余进入者按观战处理。</li>
        </ul>
    </div>
    <h3>观战与续局</h3>
    <div>
        <ul>
            <li><strong>观战：</strong>房间满员后，若房主开启了观战许可，其他玩家可进房旁观，实时同步棋局与战报。</li>
            <li><strong>继续游戏：</strong>一局收场后，任意一方点「继续游戏」即可重置棋盘再来一局，房间、座位、观众都不用重建。</li>
            <li><strong>房间清理：</strong>长时间无人活动的房间会自动销毁，不会在列表里留下空壳。</li>
            <li><strong>心态提示：</strong>娱乐房间不计分也不判逃跑，请放心尝试激进开局；但对手的时间同样宝贵，突然离席前打个招呼更好。</li>
        </ul>
    </div>
    <h2>三、排位赛</h2>
    <p>排位赛是烽火棋的正式竞技场：不设房间、不选对手，点一下「开始匹配」，系统会把水平相近的两人凑到一起。这里的每一分都算数，因此规则也最严。</p>

    <h3>匹配机制</h3>
    <ul>
        <li><strong>同水平优先：</strong>入池后优先寻找 <span class="highlight">段位差 ≤ 1 大段且积分差 ≤ 200</span> 的对手，尽量避免让你被越级碾压或去欺负新人。</li>
        <li><strong>阶梯式放宽：</strong>等待 30 秒放宽到 2 大段，60 秒放宽到 400 分，120 秒完全放宽。冷门时段也不会让你无限期干等。</li>
        <li><strong>先手随机：</strong>红蓝阵营由系统分配，不存在靠抢先手取胜的空间。</li>
        <li><strong>取消匹配：</strong>成功配对前可随时退出匹配池，不产生任何记录与扣分。</li>
    </ul>

    <h3>对局秩序</h3>
    <ul>
        <li><strong>挂机判定：</strong>连续 2 回合无操作会收到警告，累计 3 回合仍无操作直接判负。排位赛不接受用拖时间耗对手耐心。</li>
        <li><strong>掉线保护：</strong>断线后 <span class="highlight">60 秒内重连</span>可无损续战，棋局原样等你回来；超时则判负。手机切后台、网络抖动都在这个保护窗口内。</li>
        <li><strong>逃跑处理：</strong>中途弃局（关页面、长时间不归）视为失败，<strong>倒扣 25~40 分</strong>，对手正常拿到胜方加分——逃跑不能让对手白打。</li>
        <li><strong>投降限制：</strong>开局 <span class="highlight">5 分钟内投降双倍扣分</span>。认输是权利，但秒投送分不是。</li>
        <li><strong>防刷分：</strong>与同一对手连续对局达 3 场起，积分变化<strong>减半</strong>，堵住互喂分的路子。</li>
        <li><strong>新号快速定级：</strong>前 10 局积分变化 <span class="highlight">×1.5</span>，让真实水平尽快浮出水面，不必在低段位磨几十局。</li>
    </ul>

    <h3>公平性保障</h3>
    <ul>
        <li><strong>战场由服务器裁决：</strong>排位赛中，你的每一步是否合法、吃子结果、胜负判定，全部由服务器计算后下发，客户端只负责把结果画出来。改本地数据改不动棋局。</li>
        <li><strong>迷雾是真的看不见：</strong>服务器只会把「你这一方本该看到的战场」发给你——敌方藏在迷雾里的棋子，位置数据并不载入客户端</li>
        <li><strong>评分与积分服务端结算：</strong>六维评分、S 级评价、积分与段位变化都在服务器算完并入库，前端只是展示窗口，无法伪造战绩上榜。</li>
        <li><strong>身份校验：</strong>进入排位需通过账号票据校验，冒用他人身份打排位不被允许。</li>
        <li><strong>终局全开：</strong>对局判定结束后迷雾解除，双方都能看到完整棋盘复盘，事后核对不留死角。</li>
    </ul>

    <div>
        <h3>段位体系（9大段 × 4小段）</h3>
        <table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; text-align: center;">
            <thead>
                <tr>
                    <th>图标</th>
                    <th>段位</th>
                    <th>分数范围</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="16" cy="16" r="14" stroke="#6B6B6B" stroke-width="2" />
                            <circle cx="16" cy="16" r="8" stroke="#6B6B6B" stroke-width="1.5" />
                            <circle cx="16" cy="16" r="3" fill="#6B6B6B" />
                            <path d="M16 2 L16 6 M16 26 L16 30 M2 16 L6 16 M26 16 L30 16" stroke="#6B6B6B" stroke-width="1.5" stroke-linecap="round" />
                        </svg>
                    </td>
                    <td>黑铁 IV~I</td>
                    <td>0–599</td>
                </tr>
                <tr>
                    <td>
                        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="16" cy="16" r="14" stroke="#CD7F32" stroke-width="2" />
                            <circle cx="16" cy="16" r="9" stroke="#CD7F32" stroke-width="1.5" />
                            <text x="16" y="20" font-size="11" text-anchor="middle" fill="#CD7F32" font-weight="700">Ⅲ</text>
                        </svg>
                    </td>
                    <td>青铜 IV~I</td>
                    <td>600–999</td>
                </tr>
                <tr>
                    <td>
                        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="16" cy="16" r="14" stroke="#C0C0C0" stroke-width="2" />
                            <circle cx="16" cy="16" r="9" stroke="#C0C0C0" stroke-width="1.5" />
                            <text x="16" y="20" font-size="11" text-anchor="middle" fill="#C0C0C0" font-weight="700">Ⅱ</text>
                        </svg>
                    </td>
                    <td>白银 IV~I</td>
                    <td>1000–1399</td>
                </tr>
                <tr>
                    <td>
                        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="16" cy="16" r="14" stroke="#FFD700" stroke-width="2" />
                            <circle cx="16" cy="16" r="9" stroke="#FFD700" stroke-width="1.5" />
                            <text x="16" y="20" font-size="11" text-anchor="middle" fill="#FFD700" font-weight="700">Ⅰ</text>
                        </svg>
                    </td>
                    <td>黄金 IV~I</td>
                    <td>1400–1799</td>
                </tr>
                <tr>
                    <td>
                        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="4" y="4" width="24" height="24" rx="4" stroke="#E5E4E2" stroke-width="1.5" fill="none" />
                            <path d="M16 8 L20 14 L27 15 L22 20 L23 27 L16 23 L9 27 L10 20 L5 15 L12 14 L16 8Z" stroke="#E5E4E2" stroke-width="1.2" fill="none" />
                            <circle cx="16" cy="16" r="3" fill="#E5E4E2" opacity="0.3" />
                        </svg>
                    </td>
                    <td>铂金 IV~I</td>
                    <td>1800–2199</td>
                </tr>
                <tr>
                    <td>
                        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <polygon points="16,4 20,12 28,14 22,20 24,28 16,24 8,28 10,20 4,14 12,12" stroke="#4FC3F7" stroke-width="1.5" fill="none" />
                            <polygon points="16,8 18,13 23,14 19,18 20,23 16,20 12,23 13,18 9,14 14,13" stroke="#4FC3F7" stroke-width="1" fill="none" opacity="0.5" />
                            <circle cx="16" cy="16" r="2" fill="#4FC3F7" />
                        </svg>
                    </td>
                    <td>钻石 IV~I</td>
                    <td>2200–2599</td>
                </tr>
                <tr>
                    <td>
                        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M16 4 L19 12 L27 12 L21 17 L24 25 L16 20 L8 25 L11 17 L5 12 L13 12 L16 4Z" stroke="#FFD54F" stroke-width="1.5" fill="none" />
                            <path d="M16 8 L17.5 12.5 L22 13 L18.5 16 L19.5 20.5 L16 18 L12.5 20.5 L13.5 16 L10 13 L14.5 12.5 L16 8Z" stroke="#FFD54F" stroke-width="1" fill="#FFD54F" opacity="0.15" />
                            <circle cx="16" cy="16" r="1.5" fill="#FFD54F" />
                        </svg>
                    </td>
                    <td>大师 IV~I</td>
                    <td>2600–2999</td>
                </tr>
                <tr>
                    <td>
                        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M16 2 L20 12 L30 12 L22 19 L26 29 L16 23 L6 29 L10 19 L2 12 L12 12 L16 2Z" stroke="#FF6B35" stroke-width="1.5" fill="none" />
                            <path d="M16 6 L18 13 L25 13 L20 17 L22 24 L16 20 L10 24 L12 17 L7 13 L14 13 L16 6Z" stroke="#FF6B35" stroke-width="1" fill="#FF6B35" opacity="0.12" />
                            <text x="16" y="19" font-size="8" text-anchor="middle" fill="#FF6B35" font-weight="700">王</text>
                        </svg>
                    </td>
                    <td>宗师 IV~I</td>
                    <td>3000–3399</td>
                </tr>
                <tr>
                    <td>
                        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M16 2 L18 10 L26 10 L20 15 L23 23 L16 18 L9 23 L12 15 L6 10 L14 10 L16 2Z" stroke="#FFD700" stroke-width="1.5" fill="none" />
                            <path d="M16 4 L17 9 L22 9 L18 13 L20 18 L16 15 L12 18 L14 13 L10 9 L15 9 L16 4Z" stroke="#FFD700" stroke-width="1" fill="#FFD700" opacity="0.2" />
                            <text x="16" y="24" font-size="7" text-anchor="middle" fill="#FFD700" font-weight="700">☆</text>
                        </svg>
                    </td>
                    <td>王者（星数制）</td>
                    <td>3400+</td>
                </tr>
            </tbody>
        </table>
        <ul>
            <li><strong>小段跨度：</strong>每 <span class="highlight">100 积分</span> 一小段（例：青铜 IV 为 600–699），一大段四小段共 400 分。段位不是抽象标签，而是你当前积分的直接读数。</li>
            <li><strong>晋升：</strong>积分越过小段上沿即刻升段；打满某大段的 I 段后再赢一局，正式跨入下一大段。</li>
            <li><strong>降级：</strong>积分跌破当前小段下沿会退回上一小段。跌势会在结算界面明确提示，不会悄悄发生。</li>
            <li><strong>保底：</strong>积分下限为 <span class="highlight">0 分</span>，不会出现负分，新手不会被连败按到无底洞里。</li>
            <li><strong>王者段位：</strong>3400 分以上进入星数制，每 100 分 = 1 星，<strong>星数无上限</strong>。顶端玩家之间的差距由星数持续拉开，天花板永远在上面。</li>
        </ul>
    </div>
    <div>
        <h3>积分结算：赢多少，看你怎么赢</h3>
        <p>烽火棋不采用「赢就固定 +25」的粗放算法。加减多少分，取决于你这一局<strong>打得有多好</strong>——同样是胜利，摧枯拉朽和苟到最后一步，回报并不一样。</p>
        <ul>
            <li><strong>基础变化：</strong><code>25 + (本局评分 - 8.0) / 8.0 × 15</code>，取整生效。评分越高加分越多，胜方通常落在 <span class="highlight">+25 ~ +40</span> 区间。</li>
            <li><strong>特殊局型：</strong>
                <ul>
                    <li><strong>碾压局</strong>（双方评分差 &gt; 3.0）：胜方 +15~+25，负方 -15~-25。实力悬殊的对局，赢家少赚、输家少赔。</li>
                    <li><strong>翻盘局</strong>（评分处于劣势却拿下胜利）：胜方 <span class="highlight">+35~+50</span>，负方 -35~-50。逆风翻盘是本作回报最高的一类胜利。</li>
                    <li><strong>和棋</strong>：双方 0 分，谁都不吃亏。</li>
                    <li><strong>投降</strong>：胜方 +20~+30，投降方 -30~-45（含额外惩罚）。</li>
                    <li><strong>对方逃跑</strong>：胜方 +25，逃跑方按失败标准扣分。</li>
                </ul>
            </li>
            <li><strong>连胜奖励：</strong>3 连胜起，每场额外 <strong>+3</strong>，状态好的时候上分更快。</li>
            <li><strong>连败保护：</strong>3 连败起，每场<strong>少扣 5 分</strong>，避免一波逆风就掉穿段位。</li>
            <li><strong>上下限：</strong>上限不封顶（王者以星数持续累积），下限锁死在 0 分。</li>
        </ul>
    </div>

    <div>
        <h3>六维评分：把一局棋拆开给你看</h3>
        <p>每局结束后，系统会从六个维度复盘你的表现，满分合计 <span class="highlight">16.0</span> 分。它不只看你赢没赢，而是看你<strong>是怎么下的</strong>——这也是排位积分的计算依据。</p>
        <table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; text-align: center;">
            <thead>
                <tr>
                    <th>维度</th>
                    <th>满分</th>
                    <th>考察什么</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>击杀贡献</strong></td>
                    <td>4.0</td>
                    <td>吃子数量、被吃棋子的价值、以及攻入核心区拿下的战果</td>
                </tr>
                <tr>
                    <td><strong>生存能力</strong></td>
                    <td>3.2</td>
                    <td>残存兵力、高价值棋子（母棋 / 军棋 / 盾棋）是否保住、自身损失多少</td>
                </tr>
                <tr>
                    <td><strong>战略视野</strong></td>
                    <td>3.2</td>
                    <td>探棋存活数、是否解除了自己的迷雾、是否让对方一直蒙在雾里、行动许可区的利用率</td>
                </tr>
                <tr>
                    <td><strong>资源控制</strong></td>
                    <td>2.4</td>
                    <td>母棋增益的运用、盾棋成功防守次数、子棋继位次数、兵力在各区块是否均衡</td>
                </tr>
                <tr>
                    <td><strong>进攻效率</strong></td>
                    <td>1.6</td>
                    <td>渡河次数、核心区进攻次数、平均推进距离</td>
                </tr>
                <tr>
                    <td><strong>纪律性</strong></td>
                    <td>1.6</td>
                    <td>满分起算，核心区违规受罚、被迫跳过回合、投降都会扣减；全程零违规另有加成</td>
                </tr>
            </tbody>
        </table>
        <ul>
            <li><strong>会被看见的不只是杀敌：</strong>探棋铺视野、盾棋挡刀、兵力不失衡，这些「不显眼但正确」的操作都有独立计分。</li>
            <li><strong>纪律是倒扣制：</strong>干干净净下完一局，本身就是加分项；乱冲核心区吃罚，账会记在这里。</li>
            <li><strong>雷达图对照：</strong>结算界面以雷达图并列双方六维，长板短板一眼可见，比单看胜负更有指导意义。</li>
        </ul>
    </div>

    <div>
        <h3>S 级评价（七档）</h3>
        <p>六维总分会折算成一个称号，作为这局的整体评价：</p>
        <table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; text-align: center;">
            <thead>
                <tr>
                    <th>评级</th>
                    <th>称号</th>
                    <th>总分门槛</th>
                </tr>
            </thead>
            <tbody>
                <tr><td><strong>S+</strong></td><td>传奇统帅</td><td>14.0 以上</td></tr>
                <tr><td><strong>S</strong></td><td>战术大师</td><td>12.0 ~ 14.0</td></tr>
                <tr><td><strong>A</strong></td><td>精锐战士</td><td>10.0 ~ 12.0</td></tr>
                <tr><td><strong>B</strong></td><td>铁血军士</td><td>8.0 ~ 10.0</td></tr>
                <tr><td><strong>C</strong></td><td>列兵</td><td>6.0 ~ 8.0</td></tr>
                <tr><td><strong>D</strong></td><td>民兵</td><td>4.0 ~ 6.0</td></tr>
                <tr><td><strong>E</strong></td><td>炮灰</td><td>4.0 以下</td></tr>
            </tbody>
        </table>
        <ul>
            <li><strong>S+ 是硬门槛：</strong>16 分制下要拿到 14.0，几乎要求六维同时在线，含金量高，且单独计入排行榜的 S+ 榜。</li>
            <li><strong>评级独立于胜负：</strong>输掉的对局同样可能打出高评级，说明问题不在你的操作而在结果；反之赢下的低评级局，也提醒你这局赢得侥幸。</li>
        </ul>
    </div>
    <div>
        <h3>结算界面能看到什么</h3>
        <ul>
            <li><strong>段位与积分：</strong>当前段位图标、本局积分增减、晋升 / 降级提示，以及距离下一小段还差多少分。</li>
            <li><strong>本局评价：</strong>S 级称号 + 六维雷达图，与对手并列对照。</li>
            <li><strong>超越百分比：</strong>这局的表现在全体玩家中处于什么位置，一个数字告诉你今天是不是真的打得好。</li>
            <li><strong>关键数据：</strong>击杀、阵亡、渡河次数、核心区进攻次数等硬指标全部列明，评分不是黑箱。</li>
            <li><strong>后续动作：</strong>可直接导出回放、回看历史对局、或跳转排行榜查看自己的名次。</li>
        </ul>
    </div>

    <h2>四、单机与人机试炼</h2>
    <p>不想面对真人时的两条练习路线。都不计分，但同样给出完整的六维评分——练习的反馈质量和排位赛一致。</p>

    <h3>本地对战</h3>
    <ul>
        <li>同一设备上轮流落子，红方先手、蓝方后手，适合两人面对面切磋。</li>
        <li><strong>完全离线</strong>，无需网络与账号，随时开局。</li>
        <li>结束后同样输出六维评分，可用来复盘刚才那盘到底谁下得更扎实。</li>
    </ul>

    <h3>人机对战（AI）</h3>
    <ul>
        <li><strong>阵营自选：</strong>可选红方（先手）或蓝方（后手），专门练某一侧的开局也可以。</li>
        <li><strong>四档难度：</strong>
            <ul>
                <li><strong>炼狱</strong>：算得最深的一档，会主动经营视野、抓你的失误，供高手压力测试战术。</li>
                <li><strong>大师</strong>：具备战略眼光，懂得取舍与保子，适合稳定段位的玩家找手感。</li>
                <li><strong>棋手</strong>：中等水平，攻守都有章法，适合熟悉规则后过渡。</li>
                <li><strong>新手</strong>：随机走子，纯粹用来熟悉操作与棋子特性。</li>
            </ul>
        </li>
        <li><strong>拟真节奏：</strong>AI 会显示「思考中」状态，落子间隔模拟真人思考停顿，不会瞬间应招打断你的节奏。</li>
        <li><strong>同一套规则约束：</strong>AI 的重子调动同样受行动许可与探棋视野限制，也必须靠探棋经营视野、争夺迷雾优势。人机试炼是练棋理的地方，胜负参考价值以「你的六维评分」为准。</li>
    </ul>
    <hr>
    <h2>五、新手教程</h2>
    <p>第一次进大厅时会先问你一句「玩过没有」。选<strong>玩过</strong>就直接进大厅，选<strong>没玩过</strong>会把你送进教程页——不硬拦，但建议走一遍，几分钟就能过完，能省掉之后几十局的困惑。答过一次就不再问；想改主意，大厅里的「新手教程」入口随时可以再进。</p>
    <ul>
        <li><strong>五节小课，按顺序解锁：</strong>过了上一节才开下一节，依次讲清<strong>走子与吃子、盾棋的侧面弱点、探棋许可区、军棋渡河、子棋继位</strong>。每节都是一个小局面，不是三十枚对三十枚的完整开局。</li>
        <li><strong>每节都有唯一解法：</strong>课程局面都保证有解，卡住了点「提示」，会直接把该走的那一步在棋盘上标出来。你自己走岔了它就不再乱指，重开一遍即可。</li>
        <li><strong>限步数：</strong>每节只给一到两步，逼你一眼看出该走哪里，而不是靠试。步数剩 3 步以内会变红提醒。</li>
        <li><strong>都是执红：</strong>五节课统一让你执红方（先手）。执蓝的手感留到官方残局里练——那边有一题专门要你守蓝方的土。</li>
        <li><strong>随时可跳过：</strong>课程目录页有「我已有经验，跳过教程」；全部通关后会给出「进入大厅」与「去残局挑战」两个出口。</li>
    </ul>
    <hr>
    <h2>六、残局挑战</h2>
    <p>给定一个半途的局面，在限定步数内完成指定目标。对手是会真思考的 AI，不是摆着不动的靶子。分三块：<strong>制作残局 / 官方残局 / 挑战残局</strong>。</p>

    <h3>官方残局</h3>
    <ul>
        <li><strong>十道题，覆盖十种局面类型：</strong>从盾阵突破、许可区调度、缺口穿渡，到继位母棋反攻、执蓝守土、二十子以下的无雾终盘、孤子直取王城。</li>
        <li><strong>目标不只有「赢」：</strong>常见的三类是<strong>斩杀</strong>（在限定步数内吃掉指定数量的某类棋子）、<strong>抵达</strong>（把指定棋子送进指定区域）、<strong>守住</strong>（撑过指定回合数不被击破）。每题顶部会写清目标、进度和剩余步数。</li>
        <li><strong>每题均经机器实测：</strong>上架前都由程序反复实战验证过在给定步数内确实做得到，不会出现「无论怎么走都不可能过」的题。</li>
        <li><strong>过关记录留在本机：</strong>通关状态存在你当前这台设备的浏览器里，换设备或清了浏览器数据会重新开始。</li>
    </ul>

    <h3>制作残局</h3>
    <p>自己摆一个局面出题。左键放子、右键删子，红蓝两方随意布置。</p>
    <ul>
        <li><strong>棋子数量按正式规则卡着：</strong>每方母棋 1、子棋 1、盾棋 4、军棋 6、探棋 8、白板棋 20，合计不超过 20 枚；母棋与继位母棋、继位母棋与子棋不能同时存在。超了会直接告诉你哪一项超了，不会让你摆出一个根本不合法的局面。</li>
        <li><strong>可设的项：</strong>局面名称、先行方、起始回合数、你执哪一方、对手 AI 难度、目标类型（常规取胜 / 斩杀 / 守住）与步数上限。</li>
        <li><strong>迷雾与行动权不用你管：</strong>这两项由局面本身决定——按正式规则，一方的子棋阵亡后对方解雾，全场棋子降到 20 枚以下时全体自由行动。摆完自动生效，避免出现规则上自相矛盾的题。</li>
        <li><strong>摆完先试玩：</strong>点「试玩」当场打一遍，确认这题真做得到再发出去。<strong>系统不会替你验证自制残局有解</strong>，这一步得你自己把关。</li>
        <li><strong>草稿自动留着：</strong>摆到一半关了页面，下次进编辑器还在。</li>
    </ul>

    <h3>信息码：残局怎么传给别人</h3>
    <p>一个残局就是一串<strong>信息码</strong>——一行文本，包含了局面、执子方、目标与对手难度的全部信息。</p>
    <ul>
        <li><strong>生成：</strong>摆好后点「生成信息码」，得到那一行文本。</li>
        <li><strong>两种带走方式：</strong><strong>复制</strong>到剪贴板（发群里、贴聊天窗都行），或<strong>存成文件</strong>下载为 <code>.fhq</code>（和回放文件同一个后缀，但内容是局面不是过程）。</li>
        <li><strong>回来继续改：</strong>下次进编辑器，把信息码粘进输入框点「载入」，或直接<strong>上传那个文件</strong>，局面连同全部设定一起回到编辑器里，接着改。</li>
        <li><strong>纯局面也能用：</strong>只描述局面、不带目标设定的短码同样认，载入后目标沿用你当前的设定。</li>
    </ul>

    <h3>挑战残局</h3>
    <ul>
        <li><strong>两种入题方式：</strong>粘贴别人给的信息码，或上传 <code>.fhq</code> 文件。</li>
        <li><strong>开打前先看清：</strong>解析成功后会显示这题的名称、你执哪一方、对手难度和目标，确认了再开始。</li>
        <li><strong>纯局面码可自选条件：</strong>如果对方给的是不带目标的短码，你可以自己挑执子方、AI 难度和目标再打。</li>
        <li><strong>随时重开：</strong>残局是练定式的地方，同一题反复打到闭着眼都能走才算真会了。</li>
    </ul>
    <hr>
    <h2>七、对局回放</h2>
    <p>每一局都可以留档重看。回放不是截图集，而是逐步重演的完整棋局。</p>
    <ul>
        <li><strong>存档：</strong>对局结束后可保存为回放文件（<code>.fhq</code> 紧凑格式或 <code>.json</code>），文件体积很小，方便分享给朋友或发到群里求指点。</li>
        <li><strong>打开方式：</strong>在回放页面拖拽文件进窗口，或点击上传即可，无需登录。</li>
        <li><strong>播放控制：</strong><strong>倍速 0.5× ~ 8×</strong>、单步前进后退、进度跳转，想反复琢磨的那三步可以来回拉。</li>
        <li><strong>视角切换：</strong>可切到<strong>红方视角 / 蓝方视角 / 跟随当前行动方</strong>。切到某一方，就只看见那一方当时能看见的战场——这正是复盘的关键：你能重新体会当时的信息盲区，而不是用上帝视角事后诸葛。</li>
        <li><strong>忠实还原：</strong>迷雾范围、行动许可区、探棋视野都与当时一致，包括那些「当时不知道所以走错」的瞬间。</li>
    </ul>
    <hr>
    <h2>八、排位精选 · 大神观战</h2>
    <p>不必自己去翻战绩找好局。系统会持续从排位对局里挑出高质量的那一批，公开供所有人学习。</p>
    <ul>
        <li><strong>Q 值筛选：</strong>每局排位赛会算出一个 <strong>Q 值</strong>（对局质量系数），只有高分对局才会进入精选池。</li>
        <li><strong>Q 值怎么来的：</strong>综合双方的<strong>击杀贡献、生存能力、战略视野、资源控制、进攻效率、纪律性</strong>六维表现——也就是说，入选的标准是「两个人都下得好」，不是「有人被打爆」。</li>
        <li><strong>势均力敌加权：</strong>双方实力越接近，Q 值越高。精选里因此多是拉锯到最后的硬仗，观赏与学习价值都更高。</li>
        <li><strong>按日期浏览：</strong>可切换日期查看不同时间段的精选对局，追着看当前版本的主流打法。</li>
        <li><strong>一键回放：</strong>点「查看回放」直接进回放器，一比一复原，支持切换到任一方视角，看高手在信息不全时是怎么做判断的。</li>
    </ul>
    <hr>
    <h2>九、排行榜</h2>
    <p>四个维度分别立榜，因为「分最高」和「打得最好」并不总是同一个人。</p>
    <ul>
        <li><strong>综合榜</strong>：以排位分为主轴的综合排名，最能代表整体实力。</li>
        <li><strong>排位分榜</strong>：纯积分排序，看谁站在数字的最顶端。</li>
        <li><strong>连胜榜</strong>：当前连胜场次排名，衡量的是状态与稳定性，掉一局就得从头再来。</li>
        <li><strong>S+ 榜</strong>：累计拿到 S+（传奇统帅）评价的次数排名。这一榜和胜负无关，只认表现质量——它给「下得漂亮」单独留了一条上榜通道。</li>
    </ul>
    <ul>
        <li><strong>榜单信息：</strong>展示玩家昵称、胜场、胜率、排位分等数据，可点进去看具体战绩。</li>
        <li><strong>规模：</strong>每榜取 <span class="highlight">TOP 50</span>，位置有限，掉出榜外就得再打回来。</li>
        <li><strong>数据来源：</strong>榜单只统计排位赛，且全部依据服务端入库的战绩生成，娱乐房间与人机对局不会污染榜单。</li>
    </ul>
    <hr>
    <h2>十、上手路线建议</h2>
    <ol>
        <li><strong>先走一遍新手教程</strong>，五节小课把走子、盾棋、许可区、渡河、继位这五件事各练一次，比干读规则快得多。</li>
        <li><strong>再读玩法规则</strong>，把探棋、盾棋、军棋渡河和核心区惩罚这四件事搞清楚——它们决定了这盘棋的全部张力。</li>
        <li><strong>人机「棋手」难度打两三局</strong>，重点体会视野是怎么一格一格铺开的。</li>
        <li><strong>打几道官方残局</strong>，这是把「知道规则」变成「会用规则」的一步，尤其是缺口穿渡与无雾终盘那几题。</li>
        <li><strong>切到「大师」难度</strong>，开始练重子调动与行动许可的配合，看六维评分里哪一维一直偏低。</li>
        <li><strong>娱乐房间约人对练</strong>，真人的心理博弈和 AI 完全不同，尤其是迷雾下的虚张声势。</li>
        <li><strong>进排位赛定级</strong>，前 10 局积分变化 ×1.5，尽快落到你真实的段位。</li>
        <li><strong>用回放复盘</strong>，切到对手视角看他当时能看见什么，这是提升最快的一步。</li>
        <li><strong>看排位精选</strong>，学当前高段位的主流开局与视野经营思路。</li>
    </ol>
</div>
</div>
</body>
<script>
    function generateTOC() {
        const container = document.getElementById('toc-container');
        if (!container) return;
        const headings = document.querySelectorAll('#fhq_rule h1, #fhq_rule h2, #fhq_rule h3, #ranking_rule h1,#ranking_rule h2, #ranking_rule h3');
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