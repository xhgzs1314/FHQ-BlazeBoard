<?php
require __DIR__ . '/cofd/common.php';  
$__autoload = __DIR__ . '/vendor/autoload.php';
if (is_file($__autoload)) {
    require_once $__autoload;
    if (class_exists('Dotenv\\Dotenv')) {
        try { \Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad(); } catch (\Throwable $e) {}
    }
}
function fhqReplaySign($rid)
{
    $s = $_ENV['API_SECRET_KEY_mok'] ?? getenv('API_SECRET_KEY_mok') ?: '';
    return $s === '' ? '' : hash_hmac('sha256', (string)$rid, $s);
}
$Q_WEIGHTS = [
    'kill'       => 1.0,   // 击杀
    'offense'    => 1.0,   // 进攻
    'survive'    => 1.0,   // 存活
    'resource'   => 1.0,   // 资源
    'strategy'   => 1.0,   // 谋略
    'discipline' => 1.0,   // 纪律
];
$Q_THRESHOLD      = 8.0;   // Q ≥ 阈值 → 入选
$Q_BALANCE_WEIGHT = 0.4;   // 双方实力平衡度对 Q 的加成占比 (0~1)
$PAGE_SIZE        = 8;     // 每页对局数

/* ---------- 日期筛选 ---------- */
$today = date('Y-m-d');
$selDate = isset($_GET['d']) ? trim((string)$_GET['d']) : $today;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selDate) || strtotime($selDate) === false) {
    $selDate = $today;                       
}
$dayStart = $selDate . ' 00:00:00';
$dayEnd   = date('Y-m-d 00:00:00', strtotime($selDate . ' +1 day'));

$page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$offset = ($page - 1) * $PAGE_SIZE;

/* ---------- Q 值 SQL 表达式---------- */
$dimExpr = function (string $col) use ($Q_WEIGHTS) {
    $terms = [];
    foreach ($Q_WEIGHTS as $dim => $w) {
        $wf = (float)$w;
        $terms[] = "COALESCE($col->>'$.breakdown.$dim' + 0, 0) * $wf";
    }
    return '(' . implode(' + ', $terms) . ')';
};
$sR = $dimExpr('mr.red_rating');
$sB = $dimExpr('mr.blue_rating');
$bw = (float)$Q_BALANCE_WEIGHT;
$balance = "(CASE WHEN GREATEST($sR,$sB) > 0 THEN LEAST($sR,$sB)/GREATEST($sR,$sB) ELSE 0 END)";
$qExpr = "(($sR + $sB)/2.0) * (" . (1 - $bw) . " + $bw * $balance)";

// 公共 FROM/WHERE
$fromWhere =
    "FROM mok_replay rp
     JOIN mok_match_record mr ON mr.match_id = rp.replay_id
     WHERE mr.mode = 1
       AND mr.create_time >= ? AND mr.create_time < ?
       AND mr.red_rating IS NOT NULL AND mr.blue_rating IS NOT NULL
       AND $qExpr >= ?";

/* ---------- 统计总数 ---------- */
$total = 0;
if ($cs = $conn->prepare("SELECT COUNT(*) $fromWhere")) {
    $cs->bind_param('ssd', $dayStart, $dayEnd, $Q_THRESHOLD);
    $cs->execute();
    $cs->bind_result($total);
    $cs->fetch();
    $cs->close();
}
$totalPages = max(1, (int)ceil($total / $PAGE_SIZE));
if ($page > $totalPages) { $page = $totalPages; $offset = ($page - 1) * $PAGE_SIZE; }

/* ---------- 拉取当页对局 ---------- */
$rows = [];
$sql = "SELECT rp.replay_id, rp.red_id, rp.red_name, rp.red_score,
               rp.blue_id, rp.blue_name, rp.blue_score, rp.winner,
               mr.round_count,
               ROUND($qExpr, 2) AS q
        $fromWhere
        ORDER BY mr.create_time DESC
        LIMIT ? OFFSET ?";
if ($ps = $conn->prepare($sql)) {
    $ps->bind_param('ssdii', $dayStart, $dayEnd, $Q_THRESHOLD, $PAGE_SIZE, $offset);
    $ps->execute();
    $res = $ps->get_result();
    while ($r = $res->fetch_assoc()) { $rows[] = $r; }
    $ps->close();
}

/* ---------- 渲染 ---------- */
function wtName($name, $uid)
{
    $n = trim((string)$name);
    if ($n === '') $n = '玩家' . substr((string)$uid, -4);
    return htmlspecialchars($n, ENT_QUOTES, 'UTF-8');
}
function wtQuery($extra = [])
{
    $q = array_merge(['d' => $_GET['d'] ?? null, 'p' => $_GET['p'] ?? null], $extra);
    $q = array_filter($q, function ($v) { return $v !== null && $v !== ''; });
    return htmlspecialchars('?' . http_build_query($q), ENT_QUOTES, 'UTF-8');
}

$page_title = '排位精选-大神观战';
require('use/set.php');  
?>
<div class="wt-wrap">
    <div class="tab-header">
        <h2><i class="fa-solid fa-chess-king"></i> 大神观战</h2>
        <span class="badge">Q ≥ <?php echo htmlspecialchars((string)$Q_THRESHOLD); ?></span>
        <span class="badge"><?php echo (int)$total; ?> 场精选</span>
    </div>
    <p class="wt-desc">
        从排位对局中按对战质量系数 <b>Q</b> 精选高水平比赛。Q 由双方六维数据（击杀·进攻·存活·资源·谋略·纪律）综合得出，
        并对实力越接近的对局加权；排位分仅作展示，不参与 Q 计算。
    </p>

    <form class="wt-filter" method="get" action="wtpp.php">
        <i class="fa-solid fa-calendar-day"></i>
        <input type="date" name="d" value="<?php echo htmlspecialchars($selDate, ENT_QUOTES, 'UTF-8'); ?>" max="<?php echo $today; ?>">
        <button type="submit" class="wt-btn-sm"><i class="fa-solid fa-filter"></i> 筛选</button>
        <a class="wt-btn-sm wt-btn-ghost" href="wtpp.php"><i class="fa-solid fa-rotate-right"></i> 今日</a>
    </form>

    <?php if (empty($rows)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-chess-board"></i>
            <p><?php echo htmlspecialchars($selDate); ?> 暂无达到精选标准的对局</p>
        </div>
    <?php else: ?>
        <div class="wt-list">
            <?php foreach ($rows as $m):
                $avg = (int)round(((int)$m['red_score'] + (int)$m['blue_score']) / 2);
                $winner = $m['winner'];
                $resTxt = $winner === 'red' ? '红方胜' : ($winner === 'blue' ? '蓝方胜' : '和棋');
                $resCls = $winner === 'red' ? 'win-red' : ($winner === 'blue' ? 'win-blue' : 'win-draw');
            ?>
            <div class="wt-card">
                <div class="wt-players">
                    <div class="wt-side red">
                        <div class="wt-ava red"><i class="fa-solid fa-user-ninja"></i></div>
                        <div class="wt-nm"><?php echo wtName($m['red_name'], $m['red_id']); ?></div>
                        <?php if ($winner === 'red'): ?><span class="wt-crown"><i class="fa-solid fa-crown"></i></span><?php endif; ?>
                    </div>
                    <div class="wt-vs">VS</div>
                    <div class="wt-side blue">
                        <div class="wt-ava blue"><i class="fa-solid fa-user-astronaut"></i></div>
                        <div class="wt-nm"><?php echo wtName($m['blue_name'], $m['blue_id']); ?></div>
                        <?php if ($winner === 'blue'): ?><span class="wt-crown"><i class="fa-solid fa-crown"></i></span><?php endif; ?>
                    </div>
                </div>
                <div class="wt-meta">
                    <span class="wt-chip"><i class="fa-solid fa-ranking-star"></i> 均分 <?php echo $avg; ?></span>
                    <span class="wt-chip <?php echo $resCls; ?>"><i class="fa-solid fa-flag-checkered"></i> <?php echo $resTxt; ?></span>
                    <span class="wt-chip"><i class="fa-solid fa-hourglass-half"></i> <?php echo (int)$m['round_count']; ?> 回合</span>
                    <span class="wt-chip q"><i class="fa-solid fa-gem"></i> Q <?php echo htmlspecialchars((string)$m['q']); ?></span>
                </div>
                <?php $watchUrl = 'replay.php?rid=' . urlencode($m['replay_id']) . '&t=' . fhqReplaySign($m['replay_id']); ?>
                <a class="wt-watch" href="<?php echo htmlspecialchars($watchUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                    <i class="fa-solid fa-circle-play"></i> 查看回放
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="wt-pager">
            <?php if ($page > 1): ?>
                <a class="wt-btn-sm" href="<?php echo wtQuery(['p' => $page - 1]); ?>"><i class="fa-solid fa-angle-left"></i> 上一页</a>
            <?php else: ?>
                <span class="wt-btn-sm disabled"><i class="fa-solid fa-angle-left"></i> 上一页</span>
            <?php endif; ?>
            <span class="wt-pageno"><?php echo $page; ?> / <?php echo $totalPages; ?></span>
            <?php if ($page < $totalPages): ?>
                <a class="wt-btn-sm" href="<?php echo wtQuery(['p' => $page + 1]); ?>">下一页 <i class="fa-solid fa-angle-right"></i></a>
            <?php else: ?>
                <span class="wt-btn-sm disabled">下一页 <i class="fa-solid fa-angle-right"></i></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <p class="wt-hint"><i class="fa-solid fa-circle-info"></i> 点击「查看回放」进入回放器，自动 1:1 复原观战（支持倍速、切换视角）。</p>
</div>
<style>
    .wt-wrap { max-width: 720px; margin: 0 auto; }
    .wt-desc { color: var(--ink-soft); font-size: 13px; line-height: 1.7; margin-bottom: 16px; }
    .wt-desc b { color: var(--gold); }
    .wt-filter { display: flex; align-items: center; gap: 10px; margin-bottom: 18px; flex-wrap: wrap; }
    .wt-filter > i { color: var(--gold); }
    .wt-filter input[type="date"] {
        padding: 10px 14px; border-radius: 11px; border: 1px solid var(--line);
        background: rgba(20,14,8,.5); color: var(--ink); font-size: 14px; outline: none;
        font-family: inherit; color-scheme: dark;
    }
    .wt-filter input[type="date"]:focus { border-color: rgba(240,208,128,.4); box-shadow: 0 0 0 3px rgba(240,208,128,.1); }
    .wt-btn-sm {
        display: inline-flex; align-items: center; gap: 6px; padding: 10px 15px;
        border-radius: 11px; border: 1px solid transparent; cursor: pointer;
        font-size: 13px; font-weight: 700; font-family: inherit; text-decoration: none;
        background: linear-gradient(135deg, #c9a84c, #a08030); color: #1a1208; transition: all .18s;
    }
    .wt-btn-sm:hover { box-shadow: 0 4px 16px rgba(201,168,76,.4); transform: translateY(-1px); }
    .wt-btn-ghost { background: rgba(236,226,205,.04); border-color: rgba(232,197,110,.28); color: var(--gold); }
    .wt-btn-sm.disabled { opacity: .38; pointer-events: none; }

    .wt-list { display: flex; flex-direction: column; gap: 12px; }
    .wt-card {
        background: var(--surface); border: 1px solid var(--line); border-radius: 16px;
        padding: 16px 18px; box-shadow: var(--elev);
    }
    .wt-players { display: flex; align-items: center; justify-content: center; gap: 14px; margin-bottom: 14px; }
    .wt-side { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 8px; min-width: 0; position: relative; }
    .wt-ava {
        width: 52px; height: 52px; border-radius: 50%; display: flex; align-items: center;
        justify-content: center; font-size: 24px; flex-shrink: 0;
    }
    .wt-ava.red { background: linear-gradient(135deg, #ffb0a8, #8f1620); color: #fff; box-shadow: 0 4px 14px rgba(143,22,32,.4); }
    .wt-ava.blue { background: linear-gradient(135deg, #a8d0ff, #153a86); color: #fff; box-shadow: 0 4px 14px rgba(21,58,134,.4); }
    .wt-nm {
        font-size: 14px; font-weight: 700; color: var(--ink); max-width: 100%;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-align: center;
    }
    .wt-crown { position: absolute; top: -8px; right: calc(50% - 38px); color: #ffd45e; font-size: 15px; filter: drop-shadow(0 1px 3px rgba(0,0,0,.5)); }
    .wt-vs { font-size: 13px; font-weight: 800; color: var(--gold-deep); letter-spacing: 2px; flex-shrink: 0; }

    .wt-meta { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; margin-bottom: 14px; }
    .wt-chip {
        display: inline-flex; align-items: center; gap: 6px; padding: 5px 11px; border-radius: 20px;
        font-size: 12px; font-weight: 600; background: rgba(236,226,205,.05);
        border: 1px solid rgba(232,197,110,.14); color: var(--ink-soft);
    }
    .wt-chip i { color: var(--gold); }
    .wt-chip.q { border-color: rgba(232,197,110,.4); color: var(--gold); }
    .wt-chip.q i { color: var(--gold); }
    .wt-chip.win-red { color: #ff9b9e; border-color: rgba(255,109,109,.3); }
    .wt-chip.win-red i { color: #ff9b9e; }
    .wt-chip.win-blue { color: #8ab6ff; border-color: rgba(90,160,255,.3); }
    .wt-chip.win-blue i { color: #8ab6ff; }

    .wt-watch {
        display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%;
        padding: 12px; border-radius: 11px; text-decoration: none; font-weight: 700; font-size: 14px;
        background: linear-gradient(135deg, #f0d488, #c99a3f); color: #2a1e0c; transition: all .2s;
    }
    .wt-watch:hover { box-shadow: 0 8px 22px rgba(232,197,110,.42); transform: translateY(-2px); }

    .wt-pager { display: flex; align-items: center; justify-content: center; gap: 14px; margin-top: 20px; }
    .wt-pageno { font-size: 13px; color: var(--ink-soft); font-weight: 600; }
    .wt-hint { margin-top: 22px; text-align: center; font-size: 12px; color: var(--ink-soft); line-height: 1.7; }
    .wt-hint a { color: var(--gold); }
    .wt-hint i { color: var(--gold); }
</style>
</div>
</body>
</html>



