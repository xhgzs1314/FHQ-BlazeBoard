<?php
require($_SERVER['DOCUMENT_ROOT'] . '/cofd/functions.php');
$qx_max_tmp1 = true;
$tcodelogins = $_COOKIE[generateAutoWebsiteIdentifier(true) . "_log"] ?? 'null';
if ($tcodelogins == 'null') {
    $qx_max_tmp1 = false;
} else {
    require($_SERVER['DOCUMENT_ROOT'] . '/cofd/tauth.php');
    $decodeers = new TmdbaseauthdownyhoDecrypt(60000 * 60 * 2);
    $decodeddata = $decodeers->writebacknewwords($tcodelogins);
    if (!$decodeddata) {
        $qx_max_tmp1 = false;
    }
    require_once($_SERVER['DOCUMENT_ROOT'] . '/cofd/functions.php');
    $decodeddata2 = encrypt($decodeddata, 'D', generateAutoWebsiteIdentifier(true));
    if (!$decodeddata2) {
        $qx_max_tmp1 = false;
    }
    $tarray = explode('<:>', $decodeddata2);
    if (!isset($tarray[0]) || !isset($tarray[1]) || empty($tarray[0]) || empty($tarray[1]) || !isset($tarray[2]) || empty($tarray[2])) {
        $qx_max_tmp1 = false;
    }
    $q_suname = trim($tarray[2]);
}

if (!$qx_max_tmp1) {
    mokim_ttl_elegant_exit(
        '您当前未登录 <a href="/use/user/">点我登录</a>',
        null,
        'error'
    );
}

$match_id = isset($_GET['match_id']) ? trim($_GET['match_id']) : '';
if (empty($match_id)) {
    mokim_ttl_elegant_exit('缺少对局ID', null, 'error');
}

require($_SERVER['DOCUMENT_ROOT'] . '/cofd/common.php');
$conn->set_charset('utf8mb4');
$stmt = $conn->prepare("SELECT match_id, mode, red_id, blue_id, winner, round_count, create_time,
                               red_score_before, red_score_after, blue_score_before, blue_score_after,
                               red_rating, blue_rating
                        FROM mok_match_record 
                        WHERE match_id = ?");
$stmt->bind_param('s', $match_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    mokim_ttl_elegant_exit('对局不存在', null, 'error');
}
if ($row['red_id'] !== $q_suname && $row['blue_id'] !== $q_suname) {
    mokim_ttl_elegant_exit('无权查看此对局', null, 'error');
}
$isRed = ($row['red_id'] === $q_suname);
function getUserName($conn, $userId)
{
    $stmt = $conn->prepare("SELECT uname FROM mok_user WHERE id = ?");
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = $res->fetch_assoc();
    $stmt->close();
    return $data ? $data['uname'] : $userId;
}
function parseRating($jsonStr)
{
    if (empty($jsonStr)) return null;
    $data = json_decode($jsonStr, true);
    if (!$data) return null;
    if (!isset($data['breakdown'])) {
        $data['breakdown'] = [
            'kill' => 0,
            'offense' => 0,
            'survive' => 0,
            'resource' => 0,
            'strategy' => 0,
            'discipline' => 0
        ];
    }
    return $data;
}
$redRating = parseRating($row['red_rating']);
$blueRating = parseRating($row['blue_rating']);
$winner = $row['winner'];
$myResult = 'draw';
if ($winner === 'red' && $isRed) $myResult = 'win';
else if ($winner === 'blue' && !$isRed) $myResult = 'win';
else if ($winner !== null) $myResult = 'lose';

$myScoreBefore = $isRed ? $row['red_score_before'] : $row['blue_score_before'];
$myScoreAfter = $isRed ? $row['red_score_after'] : $row['blue_score_after'];
$myScoreChange = $myScoreAfter - $myScoreBefore;

$oppScoreBefore = $isRed ? $row['blue_score_before'] : $row['red_score_before'];
$oppScoreAfter = $isRed ? $row['blue_score_after'] : $row['red_score_after'];
$oppScoreChange = $oppScoreAfter - $oppScoreBefore;

$myRating = $isRed ? $redRating : $blueRating;
$oppRating = $isRed ? $blueRating : $redRating;

$myId = $isRed ? $row['red_id'] : $row['blue_id'];
$oppId = $isRed ? $row['blue_id'] : $row['red_id'];
$myName = getUserName($conn, $myId);
$oppName = getUserName($conn, $oppId);

$gradeMap = [
    'S+' => 'grade-s',
    'S' => 'grade-s',
    'A' => 'grade-a',
    'B' => 'grade-b',
    'C' => 'grade-c',
    'D' => 'grade-d',
    'E' => 'grade-e'
];
$labels = ['击杀', '进攻', '生存', '资源', '策略', '纪律'];
$keys = ['kill', 'offense', 'survive', 'resource', 'strategy', 'discipline'];
$colors = ['#ff6b6b', '#ffa94d', '#51cf66', '#4dabf7', '#cc5de8', '#fcc419'];
$page_title = '对局详情';
require($_SERVER['DOCUMENT_ROOT'] . '/use/set.php');
require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT'] . '/');
$dotenv->load();
function fhqReplaySign($rid)
{
    $s = $_ENV['API_SECRET_KEY_mok'] ?? getenv('API_SECRET_KEY_mok') ?: '';
    return $s === '' ? '' : hash_hmac('sha256', (string)$rid, $s);
}
?>
<link rel="stylesheet" href="/assets/profile-prestige.css?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/profile-prestige.css'); ?>">
<div class="detail-container">
    <div class="detail-back">
        <a onclick="window.history.back();" class="back-btn">
            <i class="fas fa-arrow-left"></i> 返回数据总览
        </a>
        <?php if ($row['mode'] == 1): ?>
            <a onclick="window.open('/replay.php?rid=<?php echo $match_id; ?>&t=<?php echo fhqReplaySign($match_id);?>');" class="back-btn">
                <i class="fas fa-clock-rotate-left"></i> 查看对局回放
            </a>
        <?php endif; ?>
    </div>


    <div class="detail-header">
        <div class="detail-player-card <?php echo $myResult === 'win' ? 'winner-card' : ''; ?>">
            <div class="player-avatar">
                <?php

                $avatarSql = "SELECT tximg FROM mok_user WHERE id = ?";
                $avatarStmt = $conn ? $conn->prepare($avatarSql) : null;
                if ($avatarStmt) {
                    $avatarStmt->bind_param('s', $myId);
                    $avatarStmt->execute();
                    $avatarRes = $avatarStmt->get_result();
                    $avatarRow = $avatarRes->fetch_assoc();
                    $avatarStmt->close();
                    $avatar = $avatarRow['tximg'] ?? '';
                    if ($avatar && strpos($avatar, '(&&)::') === 0) {
                        $avatar = '/assets/photo/' . substr($avatar, 6);
                    }
                    $safeAvatar = htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8');
                    $fallback = htmlspecialchars('/assets/photo/avatar.jpg', ENT_QUOTES, 'UTF-8');
                    if ($safeAvatar) {
                        echo '<img src="' . $safeAvatar . '" alt="avatar" onerror="this.src=\'' . $fallback . '\'; this.onerror=null;">';
                    } else {
                        echo '<i class="fas fa-user"></i>';
                    }
                } else {
                    echo '<i class="fas fa-user"></i>';
                }
                ?>
            </div>
            <div class="player-info">
                <div class="player-name"><?php echo htmlspecialchars($myName); ?></div>
                <div class="player-id"><?php echo htmlspecialchars($myId); ?></div>
                <div class="player-score-change">
                    <?php echo $myScoreBefore; ?> → <?php echo $myScoreAfter; ?>
                    <span class="<?php echo $myScoreChange >= 0 ? 'change-pos' : 'change-neg'; ?>">
                        <?php echo $myScoreChange >= 0 ? '+' : ''; ?><?php echo $myScoreChange; ?>
                    </span>
                </div>
            </div>
            <div class="player-result">
                <?php if ($myResult === 'win'): ?>
                    <span class="result-badge win">胜利</span>
                <?php elseif ($myResult === 'lose'): ?>
                    <span class="result-badge lose">失败</span>
                <?php else: ?>
                    <span class="result-badge draw">平局</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="detail-vs">VS</div>

        <div class="detail-player-card <?php echo $myResult === 'lose' ? 'winner-card' : ''; ?>">
            <div class="player-avatar">
                <?php
                $avatarSql2 = "SELECT tximg FROM mok_user WHERE id = ?";
                $avatarStmt2 = $conn ? $conn->prepare($avatarSql2) : null;
                if ($avatarStmt2) {
                    $avatarStmt2->bind_param('s', $oppId);
                    $avatarStmt2->execute();
                    $avatarRes2 = $avatarStmt2->get_result();
                    $avatarRow2 = $avatarRes2->fetch_assoc();
                    $avatarStmt2->close();
                    $avatar2 = $avatarRow2['tximg'] ?? '';
                    if ($avatar2 && strpos($avatar2, '(&&)::') === 0) {
                        $avatar2 = '/assets/photo/' . substr($avatar2, 6);
                    }
                    $safeAvatar = htmlspecialchars($avatar2, ENT_QUOTES, 'UTF-8');
                    $fallback = htmlspecialchars('/assets/photo/avatar.jpg', ENT_QUOTES, 'UTF-8');
                    if ($safeAvatar) {
                        echo '<img src="' . $safeAvatar . '" alt="avatar" onerror="this.src=\'' . $fallback . '\'; this.onerror=null;">';
                    } else {
                        echo '<i class="fas fa-user"></i>';
                    }
                } else {
                    echo '<i class="fas fa-user"></i>';
                }
                $conn->close();
                ?>
            </div>
            <div class="player-info">
                <div class="player-name"><?php echo htmlspecialchars($oppName); ?></div>
                <div class="player-id"><?php echo htmlspecialchars($oppId); ?></div>
                <div class="player-score-change">
                    <?php echo $oppScoreBefore; ?> → <?php echo $oppScoreAfter; ?>
                    <span class="<?php echo $oppScoreChange >= 0 ? 'change-pos' : 'change-neg'; ?>">
                        <?php echo $oppScoreChange >= 0 ? '+' : ''; ?><?php echo $oppScoreChange; ?>
                    </span>
                </div>
            </div>
            <div class="player-result">
                <?php if ($myResult === 'win'): ?>
                    <span class="result-badge lose"> 败北</span>
                <?php elseif ($myResult === 'lose'): ?>
                    <span class="result-badge win"> 胜利</span>
                <?php else: ?>
                    <span class="result-badge draw"> 平局</span>
                <?php endif; ?>
            </div>
        </div>
    </div>


    <div class="detail-meta-bar">
        <span><i class="fas fa-hashtag"></i> <?php echo $row['match_id']; ?></span>
        <span><i class="fas fa-tag"></i> <?php echo $row['mode'] == 1 ? '排位' : '休闲'; ?></span>
        <span><i class="fas fa-clock"></i> <?php echo $row['round_count']; ?> 回合</span>
        <span><i class="fas fa-calendar-alt"></i> <?php echo $row['create_time']; ?></span>
    </div>


    <div class="detail-ratings-section">
        <h3><i class="fas fa-chart-radar"></i> 六维能力评分</h3>
        <div class="detail-ratings-grid">

            <div class="rating-card">
                <div class="rating-header">
                    <span class="rating-player-name"><?php echo htmlspecialchars($myName); ?></span>
                    <span class="rating-grade <?php echo $gradeMap[$myRating['grade'] ?? 'E'] ?? 'grade-e'; ?>">
                        <?php echo $myRating['grade'] ?? 'E'; ?>
                        <span class="rating-score"><?php echo number_format($myRating['rating'] ?? 0, 1); ?>分</span>
                    </span>
                </div>
                <div class="rating-bars">
                    <?php foreach ($keys as $i => $key): ?>
                        <?php $val = $myRating['breakdown'][$key] ?? 0; ?>
                        <?php $pct = min(100, ($val / 5) * 100); ?>
                        <div class="rating-bar">
                            <span class="bar-label"><?php echo $labels[$i]; ?></span>
                            <div class="bar-track">
                                <div class="bar-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $colors[$i]; ?>;"></div>
                            </div>
                            <span class="bar-value"><?php echo number_format($val, 1); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (isset($myRating['eloDelta'])): ?>
                    <div class="elo-change <?php echo $myRating['eloDelta'] >= 0 ? 'elo-pos' : 'elo-neg'; ?>">
                        Score <?php echo $myRating['eloDelta'] >= 0 ? '+' : ''; ?><?php echo $myRating['eloDelta']; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="rating-card">
                <div class="rating-header">
                    <span class="rating-player-name"><?php echo htmlspecialchars($oppName); ?></span>
                    <span class="rating-grade <?php echo $gradeMap[$oppRating['grade'] ?? 'E'] ?? 'grade-e'; ?>">
                        <?php echo $oppRating['grade'] ?? 'E'; ?>
                        <span class="rating-score"><?php echo number_format($oppRating['rating'] ?? 0, 1); ?>分</span>
                    </span>
                </div>
                <div class="rating-bars">
                    <?php foreach ($keys as $i => $key): ?>
                        <?php $val = $oppRating['breakdown'][$key] ?? 0; ?>
                        <?php $pct = min(100, ($val / 5) * 100); ?>
                        <div class="rating-bar">
                            <span class="bar-label"><?php echo $labels[$i]; ?></span>
                            <div class="bar-track">
                                <div class="bar-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $colors[$i]; ?>;"></div>
                            </div>
                            <span class="bar-value"><?php echo number_format($val, 1); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (isset($oppRating['eloDelta'])): ?>
                    <div class="elo-change <?php echo $oppRating['eloDelta'] >= 0 ? 'elo-pos' : 'elo-neg'; ?>">
                        Score <?php echo $oppRating['eloDelta'] >= 0 ? '+' : ''; ?><?php echo $oppRating['eloDelta']; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="detail-chart-section">
        <h3><i class="fas fa-chart-pie"></i> 六维对比雷达图</h3>
        <div class="chart-wrapper">
            <canvas id="compareChart" height="300"></canvas>
        </div>
    </div>
</div>

<script src="/assets/plugin/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const labels = ['击杀', '进攻', '生存', '资源', '策略', '纪律'];
        const myData = [
            <?php echo $myRating['breakdown']['kill'] ?? 0; ?>,
            <?php echo $myRating['breakdown']['offense'] ?? 0; ?>,
            <?php echo $myRating['breakdown']['survive'] ?? 0; ?>,
            <?php echo $myRating['breakdown']['resource'] ?? 0; ?>,
            <?php echo $myRating['breakdown']['strategy'] ?? 0; ?>,
            <?php echo $myRating['breakdown']['discipline'] ?? 0; ?>
        ];
        const oppData = [
            <?php echo $oppRating['breakdown']['kill'] ?? 0; ?>,
            <?php echo $oppRating['breakdown']['offense'] ?? 0; ?>,
            <?php echo $oppRating['breakdown']['survive'] ?? 0; ?>,
            <?php echo $oppRating['breakdown']['resource'] ?? 0; ?>,
            <?php echo $oppRating['breakdown']['strategy'] ?? 0; ?>,
            <?php echo $oppRating['breakdown']['discipline'] ?? 0; ?>
        ];
        const maxVal = Math.max(5, ...myData, ...oppData);

        new Chart(document.getElementById('compareChart'), {
            type: 'radar',
            data: {
                labels: labels,
                datasets: [{
                        label: '<?php echo htmlspecialchars($myName); ?>',
                        data: myData,
                        backgroundColor: 'rgba(240, 212, 136, 0.15)',
                        borderColor: '#f0d488',
                        borderWidth: 2,
                        pointBackgroundColor: '#f0d488',
                        pointRadius: 4,
                    },
                    {
                        label: '<?php echo htmlspecialchars($oppName); ?>',
                        data: oppData,
                        backgroundColor: 'rgba(232, 116, 116, 0.15)',
                        borderColor: '#e87474',
                        borderWidth: 2,
                        pointBackgroundColor: '#e87474',
                        pointRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: 'rgba(236,226,205,0.85)',
                            font: {
                                size: 13,
                                weight: '600'
                            },
                            padding: 16,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 14
                        }
                    }
                },
                scales: {
                    r: {
                        min: 0,
                        max: Math.ceil(maxVal / 1) * 1,
                        ticks: {
                            stepSize: Math.max(1, Math.ceil(maxVal / 5)),
                            color: 'rgba(236,226,205,0.3)',
                            backdropColor: 'transparent',
                            font: {
                                size: 10
                            }
                        },
                        grid: {
                            color: 'rgba(232,197,110,0.08)'
                        },
                        angleLines: {
                            color: 'rgba(232,197,110,0.08)'
                        },
                        pointLabels: {
                            color: 'rgba(236,226,205,0.7)',
                            font: {
                                size: 12,
                                weight: '600'
                            }
                        }
                    }
                }
            }
        });
    });
</script>
<script src="/assets/profile-prestige.js?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/profile-prestige.js'); ?>" defer></script>
<style>
    .detail-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 10px 0 30px;
    }

    .detail-back {
        margin-bottom: 18px;
    }

    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 20px;
        background: rgba(232, 197, 110, 0.08);
        border: 1px solid rgba(232, 197, 110, 0.12);
        border-radius: 10px;
        color: #bcab8b;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.2s;
    }

    .back-btn:hover {
        background: rgba(232, 197, 110, 0.18);
        color: #f0d488;
        transform: translateX(-4px);
    }

    .detail-header {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        gap: 16px;
        align-items: stretch;
        background: linear-gradient(158deg, rgba(62, 47, 28, 0.5), rgba(41, 30, 18, 0.6));
        border: 1px solid rgba(232, 197, 110, 0.1);
        border-radius: 18px;
        padding: 20px 24px;
        margin-bottom: 16px;
        backdrop-filter: blur(8px);
        box-shadow: 0 8px 28px rgba(0, 0, 0, 0.2);
    }

    .detail-player-card {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 12px 16px;
        border-radius: 14px;
        background: rgba(0, 0, 0, 0.2);
        border: 1px solid rgba(232, 197, 110, 0.05);
        transition: all 0.3s;
    }

    .detail-player-card.winner-card {
        border-color: rgba(240, 212, 136, 0.3);
        box-shadow: 0 0 30px rgba(240, 212, 136, 0.05);
    }

    .player-avatar {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        background: linear-gradient(135deg, #f0d488, #c99a3f);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: #1a1208;
        flex-shrink: 0;
        overflow: hidden;
    }

    .player-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .player-info {
        flex: 1;
    }

    .player-name {
        font-size: 17px;
        font-weight: 700;
        color: #ece2cd;
    }

    .player-id {
        font-size: 11px;
        color: #bcab8b;
        opacity: 0.5;
    }

    .player-score-change {
        font-size: 14px;
        color: #bcab8b;
        margin-top: 2px;
    }

    .player-score-change .change-pos {
        color: #6aaa6a;
        font-weight: 700;
    }

    .player-score-change .change-neg {
        color: #e87474;
        font-weight: 700;
    }

    .player-result {
        flex-shrink: 0;
    }

    .result-badge {
        padding: 4px 14px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 700;
        white-space: nowrap;
    }

    .result-badge.win {
        background: rgba(240, 212, 136, 0.15);
        color: #f0d488;
    }

    .result-badge.lose {
        background: rgba(232, 116, 116, 0.15);
        color: #e87474;
    }

    .result-badge.draw {
        background: rgba(138, 138, 122, 0.15);
        color: #8a8a7a;
    }

    .detail-vs {
        font-size: 28px;
        font-weight: 900;
        color: rgba(232, 197, 110, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 4px;
    }

    .detail-meta-bar {
        display: flex;
        gap: 24px;
        flex-wrap: wrap;
        padding: 12px 20px;
        background: rgba(62, 47, 28, 0.3);
        border-radius: 12px;
        border: 1px solid rgba(232, 197, 110, 0.06);
        margin-bottom: 18px;
        font-size: 13px;
        color: #bcab8b;
    }

    .detail-meta-bar span i {
        margin-right: 6px;
        color: #f0d488;
        opacity: 0.5;
    }

    .detail-ratings-section {
        background: linear-gradient(158deg, rgba(62, 47, 28, 0.4), rgba(41, 30, 18, 0.5));
        border: 1px solid rgba(232, 197, 110, 0.08);
        border-radius: 16px;
        padding: 18px 22px 22px;
        margin-bottom: 18px;
    }

    .detail-ratings-section h3 {
        font-size: 16px;
        font-weight: 700;
        color: #f0d488;
        margin-bottom: 14px;
    }

    .detail-ratings-section h3 i {
        margin-right: 8px;
    }

    .detail-ratings-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .rating-card {
        background: rgba(0, 0, 0, 0.25);
        border-radius: 12px;
        padding: 14px 16px;
        border: 1px solid rgba(232, 197, 110, 0.05);
    }

    .rating-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .rating-player-name {
        font-size: 14px;
        font-weight: 700;
        color: #ece2cd;
    }

    .rating-grade {
        font-size: 13px;
        font-weight: 700;
        padding: 2px 12px;
        border-radius: 12px;
    }

    .rating-grade .rating-score {
        font-weight: 400;
        font-size: 11px;
        opacity: 0.7;
        margin-left: 4px;
    }

    .rating-grade.grade-s {
        background: rgba(240, 212, 136, 0.2);
        color: #f0d488;
    }

    .rating-grade.grade-a {
        background: rgba(77, 171, 247, 0.2);
        color: #4dabf7;
    }

    .rating-grade.grade-b {
        background: rgba(81, 207, 102, 0.2);
        color: #51cf66;
    }

    .rating-grade.grade-c {
        background: rgba(255, 169, 77, 0.2);
        color: #ffa94d;
    }

    .rating-grade.grade-d {
        background: rgba(232, 116, 116, 0.2);
        color: #e87474;
    }

    .rating-grade.grade-e {
        background: rgba(232, 116, 116, 0.1);
        color: #bcab8b;
    }

    .rating-bars {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .rating-bar {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .rating-bar .bar-label {
        font-size: 11px;
        color: #bcab8b;
        width: 28px;
        text-align: right;
        flex-shrink: 0;
    }

    .rating-bar .bar-track {
        flex: 1;
        height: 5px;
        background: rgba(255, 255, 255, 0.04);
        border-radius: 4px;
        overflow: hidden;
    }

    .rating-bar .bar-fill {
        height: 100%;
        border-radius: 4px;
        transition: width 0.8s ease;
    }

    .rating-bar .bar-value {
        font-size: 11px;
        color: #bcab8b;
        width: 28px;
        text-align: right;
        flex-shrink: 0;
    }

    .elo-change {
        text-align: center;
        font-size: 14px;
        font-weight: 700;
        padding-top: 8px;
        margin-top: 6px;
        border-top: 1px solid rgba(232, 197, 110, 0.06);
    }

    .elo-change.elo-pos {
        color: #6aaa6a;
    }

    .elo-change.elo-neg {
        color: #e87474;
    }

    .detail-chart-section {
        background: linear-gradient(158deg, rgba(62, 47, 28, 0.4), rgba(41, 30, 18, 0.5));
        border: 1px solid rgba(232, 197, 110, 0.08);
        border-radius: 16px;
        padding: 18px 22px 22px;
    }

    .detail-chart-section h3 {
        font-size: 16px;
        font-weight: 700;
        color: #f0d488;
        margin-bottom: 10px;
    }

    .detail-chart-section h3 i {
        margin-right: 8px;
    }

    .chart-wrapper {
        max-width: 500px;
        margin: 0 auto;
    }

    .chart-wrapper canvas {
        max-width: 100%;
        height: auto;
    }

    @media (max-width: 768px) {
        .detail-header {
            grid-template-columns: 1fr;
            gap: 12px;
            padding: 16px;
        }

        .detail-vs {
            font-size: 20px;
            padding: 0;
        }

        .detail-player-card {
            flex-wrap: wrap;
            justify-content: center;
            text-align: center;
        }

        .player-info {
            text-align: center;
        }

        .detail-ratings-grid {
            grid-template-columns: 1fr;
        }

        .detail-meta-bar {
            gap: 10px;
            font-size: 12px;
            justify-content: center;
        }
    }
</style>