<?php
require($_SERVER['DOCUMENT_ROOT'] . '/cofd/functions.php');
$qx_max_tmp1 = true;
$q_suname = null;
$tcodelogins = $_COOKIE[generateAutoWebsiteIdentifier((true)) . "_log"] ?? 'null';
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
$userlogin_expiretime_used = $_COOKIE['mokim_log_expire'] ?? time();
if (!$qx_max_tmp1) {
    mokim_ttl_elegant_exit(
        '您当前未登录<a href="/use/user/">点我登录</a>',
        null,
        'error'
    );
}
require($_SERVER['DOCUMENT_ROOT'] . '/cofd/common.php');
$conn->set_charset('utf8mb4');
$selfId  = $q_suname;
$ridRaw  = isset($_GET['rid']) ? trim((string)$_GET['rid']) : '';
if ($ridRaw !== '' && !preg_match('/^[A-Za-z0-9_-]{1,30}$/', $ridRaw)) {
    $conn->close();
    mokim_ttl_elegant_exit('玩家 ID 不合法<a href="/index.php">返回大厅</a>', null, 'error');
}
$isOwnProfile = ($ridRaw === '' || $ridRaw === $selfId);
$user_id      = $isOwnProfile ? $selfId : $ridRaw;

if (!$isOwnProfile) {
    $chkStmt = $conn->prepare("SELECT id, isban FROM mok_user WHERE id = ? LIMIT 1");
    $chkStmt->bind_param('s', $user_id);
    $chkStmt->execute();
    $chkRow = $chkStmt->get_result()->fetch_assoc();
    $chkStmt->close();
    if (!$chkRow) {
        $conn->close();
        mokim_ttl_elegant_exit('该玩家不存在<a href="/index.php">返回大厅</a>', null, 'error');
    }
    if ((int)($chkRow['isban'] ?? 0) === 2) {
        $conn->close();
        mokim_ttl_elegant_exit('该玩家已注销<a href="/index.php">返回大厅</a>', null, 'error');
    }
}

$rankStmt = $conn->prepare("SELECT pr.*, u.uname, u.tximg, u.sayed
                              FROM mok_player_rank pr
                              LEFT JOIN mok_user u ON pr.user_id = u.id
                              WHERE pr.user_id = ?");
$rankStmt->bind_param('s', $user_id);
$rankStmt->execute();
$rankResult = $rankStmt->get_result();
$rankData = $rankResult->fetch_assoc();
$rankStmt->close();
$hasRankRow = (bool)$rankData;
if (!$rankData) {
    $uStmt = $conn->prepare("SELECT uname, tximg, sayed FROM mok_user WHERE id = ? LIMIT 1");
    $uStmt->bind_param('s', $user_id);
    $uStmt->execute();
    $uRow = $uStmt->get_result()->fetch_assoc();
    $uStmt->close();
    $rankData = [
        'user_id' => $user_id,
        'score' => 600,
        'matches' => 0,
        'wins' => 0,
        'draws' => 0,
        'streak' => 0,
        'max_streak' => 0,
        's_plus_count' => 0,
        'season_high_score' => 600,
        'uname' => ($uRow['uname'] ?? '') !== '' ? $uRow['uname'] : $user_id,
        'tximg' => $uRow['tximg'] ?? null,
        'sayed' => ($uRow['sayed'] ?? '') !== '' ? $uRow['sayed'] : '这个用户很懒，什么都没有写'
    ];
}
$totalMatches = (int)($rankData['matches'] ?? 0);
$totalWins = (int)($rankData['wins'] ?? 0);
$totalDraws = (int)($rankData['draws'] ?? 0);
$winRate = $totalMatches > 0 ? round(($totalWins / $totalMatches) * 100, 1) : 0;
$allStmt = $conn->prepare("SELECT red_id, blue_id, winner, red_rating, blue_rating 
                             FROM mok_match_record 
                             WHERE red_id = ? OR blue_id = ?
                             ORDER BY create_time DESC");
$allStmt->bind_param('ss', $user_id, $user_id);
$allStmt->execute();
$allResult = $allStmt->get_result();

$breakdownKeys = ['kill', 'offense', 'survive', 'resource', 'strategy', 'discipline'];
$avgBreakdown = array_fill_keys($breakdownKeys, 0);
$matchCountForAvg = 0;

while ($row = $allResult->fetch_assoc()) {
    $isRed = ($row['red_id'] === $user_id);
    $ratingJson = $isRed ? $row['red_rating'] : $row['blue_rating'];
    if ($ratingJson) {
        $ratingData = json_decode($ratingJson, true);
        if ($ratingData && isset($ratingData['breakdown'])) {
            foreach ($breakdownKeys as $key) {
                $avgBreakdown[$key] += ($ratingData['breakdown'][$key] ?? 0);
            }
            $matchCountForAvg++;
        }
    }
}
$allStmt->close();

if ($matchCountForAvg > 0) {
    foreach ($breakdownKeys as $key) {
        $avgBreakdown[$key] = round($avgBreakdown[$key] / $matchCountForAvg, 2);
    }
} else {
    $avgBreakdown = ['kill' => 0, 'offense' => 0, 'survive' => 0, 'resource' => 0, 'strategy' => 0, 'discipline' => 0];
}
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;
$totalCount = 0;
$totalPages = 1;
$matchList = [];
$opponentIds = [];
if ($isOwnProfile) {
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM mok_match_record WHERE red_id = ? OR blue_id = ?");
    $countStmt->bind_param('ss', $user_id, $user_id);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalCount = $countResult->fetch_assoc()['total'] ?? 0;
    $countStmt->close();
    $totalPages = max(1, ceil($totalCount / $limit));
    $listStmt = $conn->prepare("SELECT match_id, mode, red_id, blue_id, winner, round_count, create_time,
                                     red_score_before, red_score_after, blue_score_before, blue_score_after,
                                     red_rating, blue_rating
                              FROM mok_match_record
                              WHERE red_id = ? OR blue_id = ?
                              ORDER BY create_time DESC
                              LIMIT ? OFFSET ?");
    $listStmt->bind_param('ssii', $user_id, $user_id, $limit, $offset);
    $listStmt->execute();
    $listResult = $listStmt->get_result();

    while ($row = $listResult->fetch_assoc()) {
        $isRed = ($row['red_id'] === $user_id);
        $opponentId = $isRed ? $row['blue_id'] : $row['red_id'];
        $opponentIds[] = $opponentId;

        $scoreBefore = $isRed ? $row['red_score_before'] : $row['blue_score_before'];
        $scoreAfter = $isRed ? $row['red_score_after'] : $row['blue_score_after'];
        $scoreChange = $scoreAfter - $scoreBefore;

        $result = 'draw';
        if ($row['winner'] === 'red' && $isRed) $result = 'win';
        else if ($row['winner'] === 'blue' && !$isRed) $result = 'win';
        else if ($row['winner'] !== null) $result = 'lose';

        $matchList[] = [
            'match_id' => $row['match_id'],
            'mode' => $row['mode'] == 1 ? '排位' : '休闲',
            'opponent_id' => $opponentId,
            'result' => $result,
            'score_change' => $scoreChange,
            'round_count' => $row['round_count'],
            'create_time' => $row['create_time']
        ];
    }
    $listStmt->close();
}
$opponentNames = [];
if (!empty($opponentIds)) {
    $uniqueIds = array_unique($opponentIds);
    $placeholders = implode(',', array_fill(0, count($uniqueIds), '?'));
    $oppStmt = $conn->prepare("SELECT id, uname FROM mok_user WHERE id IN ($placeholders)");
    $types = str_repeat('s', count($uniqueIds));
    $oppStmt->bind_param($types, ...$uniqueIds);
    $oppStmt->execute();
    $oppResult = $oppStmt->get_result();
    while ($row = $oppResult->fetch_assoc()) {
        $opponentNames[$row['id']] = $row['uname'];
    }
    $oppStmt->close();
}
$processedMatches = [];
foreach ($matchList as &$match) {
    $match['opponent'] = $opponentNames[$match['opponent_id']] ?? $match['opponent_id'];
    unset($match['opponent_id']);
    $processedMatches[] = $match;
}
unset($match);
$tiers = [
    ['key' => 'black_iron', 'name' => '黑铁', 'icon' => '<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="14" cy="14" r="12" stroke="#6B6B6B" stroke-width="2"/><circle cx="14" cy="14" r="7" stroke="#6B6B6B" stroke-width="1.5"/><circle cx="14" cy="14" r="2.5" fill="#6B6B6B"/><path d="M14 2 L14 5 M14 23 L14 26 M2 14 L5 14 M23 14 L26 14" stroke="#6B6B6B" stroke-width="1.5" stroke-linecap="round"/></svg>', 'title' => '新兵', 'min' => 0, 'max' => 599],
    ['key' => 'bronze', 'name' => '青铜', 'icon' => '<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="14" cy="14" r="12" stroke="#CD7F32" stroke-width="2"/><circle cx="14" cy="14" r="7.5" stroke="#CD7F32" stroke-width="1.5"/><text x="14" y="17.5" font-size="10" text-anchor="middle" fill="#CD7F32" font-weight="700">Ⅲ</text></svg>', 'title' => '列兵', 'min' => 600, 'max' => 999],
    ['key' => 'silver', 'name' => '白银', 'icon' => '<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="14" cy="14" r="12" stroke="#C0C0C0" stroke-width="2"/><circle cx="14" cy="14" r="7.5" stroke="#C0C0C0" stroke-width="1.5"/><text x="14" y="17.5" font-size="10" text-anchor="middle" fill="#C0C0C0" font-weight="700">Ⅱ</text></svg>', 'title' => '老兵', 'min' => 1000, 'max' => 1399],
    ['key' => 'gold', 'name' => '黄金', 'icon' => '<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="14" cy="14" r="12" stroke="#FFD700" stroke-width="2"/><circle cx="14" cy="14" r="7.5" stroke="#FFD700" stroke-width="1.5"/><text x="14" y="17.5" font-size="10" text-anchor="middle" fill="#FFD700" font-weight="700">Ⅰ</text></svg>', 'title' => '铁血战士', 'min' => 1400, 'max' => 1799],
    ['key' => 'platinum', 'name' => '铂金', 'icon' => '<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3.5" y="3.5" width="21" height="21" rx="3.5" stroke="#E5E4E2" stroke-width="1.5" fill="none"/><path d="M14 7 L17.5 12.5 L23.5 13.5 L19 18 L20 24 L14 20.5 L8 24 L9 18 L4.5 13.5 L10.5 12.5 L14 7Z" stroke="#E5E4E2" stroke-width="1.2" fill="none"/><circle cx="14" cy="14" r="2.5" fill="#E5E4E2" opacity="0.3"/></svg>', 'title' => '精锐统帅', 'min' => 1800, 'max' => 2199],
    ['key' => 'diamond', 'name' => '钻石', 'icon' => '<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg"><polygon points="14,3.5 17.5,10.5 24.5,12 19.5,17.5 21,24.5 14,21 7,24.5 8.5,17.5 3.5,12 10.5,10.5" stroke="#4FC3F7" stroke-width="1.5" fill="none"/><polygon points="14,7 15.8,11.5 19.8,12.5 16.8,15.8 17.5,20 14,17.5 10.5,20 11.2,15.8 8.2,12.5 12.2,11.5" stroke="#4FC3F7" stroke-width="1" fill="none" opacity="0.5"/><circle cx="14" cy="14" r="1.8" fill="#4FC3F7"/></svg>', 'title' => '战术大师', 'min' => 2200, 'max' => 2599],
    ['key' => 'master', 'name' => '大师', 'icon' => '<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14 3.5 L16.5 10.5 L23.5 10.5 L18.5 15 L20.5 22 L14 17.5 L7.5 22 L9.5 15 L4.5 10.5 L11.5 10.5 L14 3.5Z" stroke="#FFD54F" stroke-width="1.5" fill="none"/><path d="M14 7 L15.3 11 L19 11.5 L16 14 L16.8 18 L14 15.8 L11.2 18 L12 14 L9 11.5 L12.7 11 L14 7Z" stroke="#FFD54F" stroke-width="1" fill="#FFD54F" opacity="0.15"/><circle cx="14" cy="14" r="1.2" fill="#FFD54F"/></svg>', 'title' => '传奇统帅', 'min' => 2600, 'max' => 2999],
    ['key' => 'grandmaster', 'name' => '宗师', 'icon' => '<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14 2 L17.5 10.5 L26 10.5 L19.5 16.5 L23 25 L14 20 L5 25 L8.5 16.5 L2 10.5 L10.5 10.5 L14 2Z" stroke="#FF6B35" stroke-width="1.5" fill="none"/><path d="M14 5.5 L15.8 11.5 L21.5 11.5 L17.5 15 L19.5 21 L14 17.5 L8.5 21 L10.5 15 L6.5 11.5 L12.2 11.5 L14 5.5Z" stroke="#FF6B35" stroke-width="1" fill="#FF6B35" opacity="0.12"/><text x="14" y="16.5" font-size="7" text-anchor="middle" fill="#FF6B35" font-weight="700">王</text></svg>', 'title' => '烽火霸主', 'min' => 3000, 'max' => 3399],
    ['key' => 'challenger', 'name' => '王者', 'icon' => '<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14 1.5 L15.8 8.5 L22.5 8.5 L17.5 13 L19.8 20 L14 16 L8.2 20 L10.5 13 L5.5 8.5 L12.2 8.5 L14 1.5Z" stroke="#FFD700" stroke-width="1.5" fill="none"/><path d="M14 3.5 L15 8 L19 8 L16 11.5 L17.5 16 L14 13.5 L10.5 16 L12 11.5 L9 8 L13 8 L14 3.5Z" stroke="#FFD700" stroke-width="1" fill="#FFD700" opacity="0.2"/><text x="14" y="21" font-size="6" text-anchor="middle" fill="#FFD700" font-weight="700">☆</text></svg>', 'title' => '千古一帝', 'min' => 3400, 'max' => PHP_INT_MAX]
];
$subTiers = ['IV', 'III', 'II', 'I'];
$currentScore = (int)($rankData['score'] ?? 600);
$currentTier = null;
$currentTierIndex = 0;
$currentSubIndex = 0;
$nextTier = null;
$nextSubIndex = 0;
$progress = 0;
$nextScore = 0;
$stars = 0;
foreach ($tiers as $index => $tier) {
    if ($currentScore >= $tier['min'] && $currentScore <= $tier['max']) {
        $currentTier = $tier;
        $currentTierIndex = $index;
        $subScore = $currentScore - $tier['min'];
        $currentSubIndex = min(3, floor($subScore / 100));
        $nextScore = $tier['min'] + ($currentSubIndex + 1) * 100;
        if ($currentSubIndex == 3) {
            if (isset($tiers[$index + 1])) {
                $nextTier = $tiers[$index + 1];
                $nextSubIndex = 0;
                $nextScore = $nextTier['min'];
            } else {
                $nextTier = $tier;
                $nextSubIndex = $currentSubIndex;
                $nextScore = $currentScore + 100;
            }
        } else {
            $nextTier = $tier;
            $nextSubIndex = $currentSubIndex + 1;
        }

        $range = $nextScore - $tier['min'] - ($currentSubIndex * 100);
        $progress = $range > 0 ? min(100, round(($subScore - $currentSubIndex * 100) / $range * 100)) : 100;
        break;
    }
}
if ($currentScore >= 3400) {
    $currentTier = $tiers[8];
    $currentTierIndex = 8;
    $stars = floor(($currentScore - 3400) / 100) + 1;
    $currentSubIndex = -1;
    $nextTier = $currentTier;
    $nextSubIndex = -1;
    $progress = (($currentScore - 3400) % 100) / 100 * 100;
    $nextScore = $currentScore + 100;
}
$COMPOSITE = "(pr.score + IF(pr.matches>0, pr.wins/pr.matches*400, 0) + pr.max_streak*10 + pr.s_plus_count*20)";
$overallRank = 0;
$rankedTotal = 0;
$MIN_SCORE_FOR_RANK = 2000;
if ($hasRankRow && $currentScore >= $MIN_SCORE_FOR_RANK) {
    $rkStmt = $conn->prepare(
        "SELECT
        (SELECT COUNT(*) FROM mok_player_rank pr
           JOIN mok_user u ON u.id = pr.user_id
          WHERE (u.isban IS NULL OR u.isban = 0)
            AND pr.score >= ?) AS total_players,
        (SELECT COUNT(*) + 1 FROM mok_player_rank pr
           JOIN mok_user u ON u.id = pr.user_id
          WHERE (u.isban IS NULL OR u.isban = 0)
            AND pr.score >= ?
            AND $COMPOSITE > (
                SELECT $COMPOSITE FROM mok_player_rank pr WHERE pr.user_id = ?
            )) AS my_rank"
    );
    $rkStmt->bind_param('iis', $MIN_SCORE_FOR_RANK, $MIN_SCORE_FOR_RANK, $user_id);
    $rkStmt->execute();
    $rkRow = $rkStmt->get_result()->fetch_assoc();
    $rkStmt->close();
    $rankedTotal = (int)($rkRow['total_players'] ?? 0);
    $overallRank = (int)($rkRow['my_rank'] ?? 0);
    if ($overallRank < 1 || $rankedTotal === 0) $overallRank = 0;
}

$conn->close();
$kingStars   = $currentScore >= 3400 ? (int)floor(($currentScore - 3400) / 100) + 1 : 0;
$prestige    = '';
$prestigeName = '';
$prestigeDesc = '';
if ($overallRank > 0 && $overallRank <= 3 && $kingStars > 10) {
    $prestige = 'apex';
    $prestigeName = '不世出';
    $prestigeDesc = '全服前三 · 王者 ' . $kingStars . ' 星';
} elseif ($overallRank > 0 && $overallRank <= 50) {
    $prestige = 'glory';
    $prestigeName = '登堂入室';
    $prestigeDesc = '全服第 ' . $overallRank . ' 名';
} elseif ($currentScore >= 2600) {
    $prestige = 'master';
    $prestigeName = '登峰造极';
    $prestigeDesc = ($currentTier['name'] ?? '大师') . ' 段位认证';
}

$overallRating = 0;
foreach ($avgBreakdown as $val) {
    $overallRating += $val;
}
$overallRating = $matchCountForAvg > 0 ? round($overallRating / count($avgBreakdown), 1) : 0;
$page_title = '数据总览';
require($_SERVER['DOCUMENT_ROOT'] . '/use/set.php');
?>
<div class="dashboard-container<?php echo $prestige ? ' pg-' . $prestige : ''; ?>"
    data-prestige="<?php echo htmlspecialchars($prestige, ENT_QUOTES, 'UTF-8'); ?>"
    data-rank="<?php echo (int)$overallRank; ?>">
    <?php if (!$isOwnProfile): ?>
        <div class="pg-visitor-bar">
            <i class="fas fa-eye"></i>
            <span>正在浏览 <b><?php echo htmlspecialchars($rankData['uname'] ?? $user_id); ?></b> 的主页</span>
            <a href="?" class="pg-visitor-back"><i class="fas fa-user"></i> 回到我的主页</a>
        </div>
    <?php endif; ?>
    <div class="profile-summary">
        <?php if ($prestige): ?>
            <div class="pg-aura" aria-hidden="true">
                <span class="pg-aura-ring"></span>
                <span class="pg-aura-ring"></span>
                <span class="pg-aura-sheen"></span>
            </div>
            <div class="pg-corner tl" aria-hidden="true"></div>
            <div class="pg-corner tr" aria-hidden="true"></div>
            <div class="pg-corner bl" aria-hidden="true"></div>
            <div class="pg-corner br" aria-hidden="true"></div>
            <div class="pg-plate" aria-hidden="true"></div>
        <?php endif; ?>
        <div class="profile-avatar-lg">
            <?php
            $avatar = $rankData['tximg'] ?? '';
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
            ?>
        </div>
        <div class="profile-details">
            <div class="profile-name"><?php echo htmlspecialchars($rankData['uname'] ?? $user_id); ?></div>
            <?php if ($prestige): ?>
                <div class="pg-title">
                    <i class="fas <?php echo $prestige === 'apex' ? 'fa-dragon' : ($prestige === 'glory' ? 'fa-crown' : 'fa-gem'); ?>"></i>
                    <span class="pg-title-name"><?php echo htmlspecialchars($prestigeName); ?></span>
                    <span class="pg-title-sep">·</span>
                    <span class="pg-title-desc"><?php echo htmlspecialchars($prestigeDesc); ?></span>
                </div>
            <?php endif; ?>
            <div class="profile-sayed"><?php echo htmlspecialchars($rankData['sayed'] ?? '这个用户很懒，什么都没有写'); ?></div>
            <div class="profile-id">ID: <?php echo htmlspecialchars($user_id); ?></div>
        </div>
        <div class="pg-rank-pillar<?php echo $overallRank > 0 && $overallRank <= 3 ? ' top3' : ''; ?>">
            <div class="pg-rank-cap">综合排名</div>
            <?php if ($overallRank > 0): ?>
                <div class="pg-rank-value"><span class="pg-rank-hash">#</span><?php echo (int)$overallRank; ?></div>
                <div class="pg-rank-foot">
                    <?php echo $rankedTotal > 0 ? '共 ' . number_format($rankedTotal) . ' 人' : '&nbsp;'; ?>
                </div>
            <?php else: ?>
                <div class="pg-rank-value unranked">—</div>
                <div class="pg-rank-foot">未上榜</div>
            <?php endif; ?>
        </div>
        <div class="profile-rank-badge">
            <div class="tier-icon"><?php echo $currentTier['icon'] ?? '🪨'; ?></div>
            <div class="tier-name"><?php echo $currentTier['name'] ?? '黑铁'; ?></div>
            <div class="tier-sub">
                <?php if ($currentScore >= 3400): ?>
                    ⭐ <?php echo $stars; ?>星
                <?php else: ?>
                    <?php echo $subTiers[$currentSubIndex] ?? 'IV'; ?>
                <?php endif; ?>
            </div>
            <div class="tier-score"><?php echo number_format($currentScore); ?>分</div>
        </div>
    </div>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo number_format($currentScore); ?></div>
            <div class="stat-label">排位分</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $totalMatches; ?></div>
            <div class="stat-label">总场数</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $totalWins; ?></div>
            <div class="stat-label">胜场</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $winRate; ?>%</div>
            <div class="stat-label">胜率</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo (int)($rankData['max_streak'] ?? 0); ?></div>
            <div class="stat-label">最高连胜</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo (int)($rankData['s_plus_count'] ?? 0); ?></div>
            <div class="stat-label">S+次数</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $overallRating; ?></div>
            <div class="stat-label">综合评分</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo (int)($rankData['season_high_score'] ?? 600); ?></div>
            <div class="stat-label">赛季最高</div>
        </div>
    </div>
    <div class="tier-progress-section">
        <div class="tier-progress-header">
            <span>
                <?php echo $currentTier['icon'] ?? '🪨'; ?>
                <?php echo $currentTier['name'] ?? '黑铁'; ?>
                <?php if ($currentScore < 3400): ?>
                    <?php echo $subTiers[$currentSubIndex] ?? 'IV'; ?>
                <?php else: ?>
                    ⭐<?php echo $stars; ?>星
                <?php endif; ?>
            </span>
            <span>
                <?php if ($currentScore >= 3400): ?>
                    王者 <?php echo number_format($currentScore); ?>分
                <?php else: ?>
                    <?php echo number_format($currentScore); ?> / <?php echo number_format($nextScore); ?>
                <?php endif; ?>
            </span>
            <span>
                <?php if ($nextTier && $currentScore < 3400): ?>
                    <?php echo $nextTier['icon']; ?> <?php echo $nextTier['name']; ?>
                    <?php if ($nextSubIndex >= 0 && $nextSubIndex < 4): ?>
                        <?php echo $subTiers[$nextSubIndex]; ?>
                    <?php endif; ?>
                <?php else: ?>
                    👑 王者
                <?php endif; ?>
            </span>
        </div>
        <div class="tier-progress-bar">
            <div class="tier-progress-fill" style="width: <?php echo $progress; ?>%;"></div>
        </div>
        <div class="tier-progress-label">
            <?php if ($currentScore >= 3400): ?>
                已进入王者段位，当前 <?php echo $stars; ?> 星
            <?php else: ?>
                距 <?php echo $nextTier['name'] ?? '王者'; ?> <?php echo $nextSubIndex >= 0 && $nextSubIndex < 4 ? $subTiers[$nextSubIndex] : ''; ?>
                还差 <?php echo number_format(max(0, $nextScore - $currentScore)); ?> 分
            <?php endif; ?>
        </div>
    </div>


    <div class="charts-row">
        <div class="chart-box">
            <div class="chart-title">六维能力图</div>
            <div class="chart-subtitle">基于 <?php echo $matchCountForAvg; ?> 场战绩平均值</div>
            <canvas id="roseChart" height="320"></canvas>
        </div>
        <div class="chart-box">
            <div class="chart-title">数据总览图</div>
            <div class="chart-subtitle">排位分 · 胜率 · 连胜 · S+ · 场数 · 综合评分</div>
            <canvas id="overviewRoseChart" height="320"></canvas>
        </div>
    </div>
    <?php if (!$isOwnProfile): ?>
        <div class="match-list-section">
            <div class="section-header">
                <h2><i class="fas fa-history"></i> 战绩列表</h2>
                <span class="match-count"><i class="fas fa-lock"></i> 不公开</span>
            </div>
            <div class="pg-locked">
                <i class="fas fa-shield-halved"></i>
                <p>战绩仅本人可查看</p>
                <span>其余荣誉与生涯数据均已公开展示</span>
            </div>
        </div>
    <?php else: ?>
        <div class="match-list-section">
            <div class="section-header">
                <h2><i class="fas fa-history"></i> 战绩列表</h2>
                <span class="match-count">共 <?php echo $totalCount; ?> 场</span>
            </div>
            <div class="match-list">
                <?php if (empty($processedMatches)): ?>
                    <div class="empty-state"><i class="fas fa-gamepad"></i>
                        <p>暂无战绩记录，快去对局吧！</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($processedMatches as $match): ?>
                        <a href="detail.php?match_id=<?php echo $match['match_id']; ?>" class="match-item-link">
                            <div class="match-item <?php echo $match['result']; ?>">
                                <div class="match-result-icon">
                                    <?php if ($match['result'] === 'win'): ?>
                                        <i class="fas fa-trophy" style="color: #f0d080;"></i>
                                    <?php elseif ($match['result'] === 'lose'): ?>
                                        <i class="fas fa-times-circle" style="color: #e87474;"></i>
                                    <?php else: ?>
                                        <i class="fas fa-minus-circle" style="color: #8a8a7a;"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="match-info">
                                    <div class="match-opponent">vs <?php echo htmlspecialchars($match['opponent']); ?></div>
                                    <div class="match-meta">
                                        <span class="match-mode <?php echo $match['mode'] === '排位' ? 'ranked' : 'casual'; ?>"><?php echo $match['mode']; ?></span>
                                        <span class="match-rounds"><?php echo $match['round_count']; ?>回合</span>
                                        <span class="match-time"><?php echo date('m-d H:i', strtotime($match['create_time'])); ?></span>
                                    </div>
                                </div>
                                <div class="match-score-change <?php echo $match['score_change'] >= 0 ? 'positive' : 'negative'; ?>">
                                    <?php echo $match['score_change'] >= 0 ? '+' : ''; ?><?php echo $match['score_change']; ?>
                                    <i class="fas fa-chevron-right" style="font-size:12px;opacity:0.4;margin-left:6px;"></i>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="page-btn">&laquo; 上一页</a>
                    <?php endif; ?>
                    <span class="page-info">第 <?php echo $page; ?> / <?php echo $totalPages; ?> 页</span>
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="page-btn">下一页 &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<script src="/assets/profile-prestige.js?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/profile-prestige.js'); ?>" defer></script>
<script src="/assets/plugin/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        function autoScale(data) {
            const maxVal = Math.max(...data);
            const minVal = Math.min(...data.filter(v => v > 0));
            if (minVal > 0 && maxVal / minVal > 5) {
                const logData = data.map(v => v > 0 ? Math.log(v + 1) : 0);
                const logMax = Math.max(...logData);
                return data.map((v, i) => {
                    if (v === 0) return 0;
                    const ratio = v / maxVal;
                    const compressed = Math.sqrt(ratio) * maxVal * 0.8 + 0.2;
                    return Math.round(compressed * 10) / 10;
                });
            }
            return data;
        }

        function checkAndScale(data, labels, chartId) {
            const maxVal = Math.max(...data);
            const minVal = Math.min(...data.filter(v => v > 0));
            const ratio = maxVal / minVal;

            let scaledData = data;
            let scaleNote = '';

            if (minVal > 0 && ratio > 5) {
                scaledData = autoScale(data);
                scaleNote = `已自动缩放`;
            } else if (data.some(v => v === 0)) {
                scaleNote = '部分数据为0';
            }
            return {
                data: scaledData,
                note: scaleNote,
                ratio: ratio
            };
        }
        const rawBreakdown = <?php echo json_encode(array_values($avgBreakdown)); ?>;
        const labels = ['击杀', '进攻', '生存', '资源', '策略', '纪律'];
        const colors = ['#ff6b6b', '#ffa94d', '#51cf66', '#4dabf7', '#cc5de8', '#fcc419'];

        const scaled1 = checkAndScale(rawBreakdown, labels, 'roseChart');
        const maxVal = Math.ceil(Math.max(5, ...scaled1.data) / 1) * 1;
        new Chart(document.getElementById('roseChart'), {
            type: 'polarArea',
            data: {
                labels: labels,
                datasets: [{
                    data: scaled1.data,
                    backgroundColor: colors.map(c => c + '80'),
                    borderColor: colors,
                    borderWidth: 2,
                    hoverBackgroundColor: colors.map(c => c + 'CC')
                }]
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
                                size: 12,
                                weight: '600'
                            },
                            padding: 14,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 12
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                const original = rawBreakdown[ctx.dataIndex];
                                return ctx.label + ': ' + original.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    r: {
                        min: 0,
                        max: maxVal,
                        ticks: {
                            stepSize: Math.max(1, Math.ceil(maxVal / 5)),
                            color: 'rgba(236,226,205,0.3)',
                            backdropColor: 'transparent'
                        },
                        grid: {
                            color: 'rgba(232,197,110,0.08)'
                        },
                        pointLabels: {
                            display: false
                        }
                    }
                }
            }
        });
        const overviewLabels = ['排位分', '胜率', '最高连胜', 'S+次数', '总场数', '综合评分'];
        const overviewColors = ['#ff6b6b', '#ffa94d', '#fcc419', '#51cf66', '#4dabf7', '#cc5de8'];
        const rawOverview = [<?php echo $currentScore; ?>, <?php echo $winRate; ?>, <?php echo (int)($rankData['max_streak'] ?? 0); ?>, <?php echo (int)($rankData['s_plus_count'] ?? 0); ?>, <?php echo $totalMatches; ?>, <?php echo $overallRating; ?>];
        const maxValues = [2200, 100, 20, 20, 100, 10];
        let percentData = rawOverview.map((v, i) => Math.min(100, (v / maxValues[i]) * 100));
        const scaled2 = checkAndScale(percentData, overviewLabels, 'overviewRoseChart');
        new Chart(document.getElementById('overviewRoseChart'), {
            type: 'polarArea',
            data: {
                labels: overviewLabels,
                datasets: [{
                    data: scaled2.data,
                    backgroundColor: overviewColors.map(c => c + '80'),
                    borderColor: overviewColors,
                    borderWidth: 2,
                    hoverBackgroundColor: overviewColors.map(c => c + 'CC')
                }]
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
                                size: 12,
                                weight: '600'
                            },
                            padding: 14,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 12
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ctx.label + ': ' + rawOverview[ctx.dataIndex];
                            }
                        }
                    }
                },
                scales: {
                    r: {
                        min: 0,
                        max: Math.ceil(Math.max(100, ...scaled2.data) / 10) * 10,
                        ticks: {
                            stepSize: 20,
                            color: 'rgba(236,226,205,0.3)',
                            backdropColor: 'transparent'
                        },
                        grid: {
                            color: 'rgba(232,197,110,0.08)'
                        },
                        pointLabels: {
                            display: false
                        }
                    }
                }
            }
        });
    });
</script>
<style>
    .dashboard-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 4px 0 20px 0;
    }

    .profile-summary {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 20px 24px;
        background: linear-gradient(158deg, rgba(62, 47, 28, 0.6), rgba(41, 30, 18, 0.7));
        border: 1px solid rgba(232, 197, 110, 0.15);
        border-radius: 18px;
        backdrop-filter: blur(12px);
        box-shadow: 0 14px 44px rgba(0, 0, 0, 0.34), inset 0 1px 0 rgba(255, 236, 193, 0.06);
        margin-bottom: 18px;
        flex-wrap: wrap;
    }

    .profile-avatar-lg {
        width: 68px;
        height: 68px;
        border-radius: 50%;
        background: linear-gradient(135deg, #f0d488, #c99a3f);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        color: #1a1208;
        flex-shrink: 0;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(232, 197, 110, 0.25);
    }

    .profile-avatar-lg img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-details {
        flex: 1;
    }

    .match-item-link {
        text-decoration: none;
        display: block;
        transition: transform 0.2s;
    }

    .match-item-link:hover {
        transform: translateX(4px);
    }

    .match-item-link .match-item {
        cursor: pointer;
    }

    .profile-name {
        font-size: 22px;
        font-weight: 700;
        color: #f0d488;
        letter-spacing: 1px;
    }

    .profile-sayed {
        font-size: 13px;
        color: #bcab8b;
        margin-top: 2px;
    }

    .profile-id {
        font-size: 12px;
        color: #bcab8b;
        opacity: 0.5;
        margin-top: 2px;
    }

    .profile-rank-badge {
        text-align: center;
        padding: 10px 24px;
        background: rgba(232, 197, 110, 0.08);
        border: 1px solid rgba(232, 197, 110, 0.18);
        border-radius: 14px;
        min-width: 110px;
        backdrop-filter: blur(4px);
    }

    .tier-icon {
        font-size: 30px;
    }

    .tier-name {
        font-size: 16px;
        font-weight: 700;
        color: #f0d488;
    }

    .tier-sub {
        font-size: 13px;
        color: #bcab8b;
    }

    .tier-score {
        font-size: 13px;
        color: #bcab8b;
        font-weight: 600;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 18px;
    }

    .stat-card {
        background: linear-gradient(158deg, rgba(62, 47, 28, 0.5), rgba(41, 30, 18, 0.6));
        border: 1px solid rgba(232, 197, 110, 0.1);
        border-radius: 14px;
        padding: 16px 10px;
        text-align: center;
        backdrop-filter: blur(8px);
        box-shadow: 0 8px 28px rgba(0, 0, 0, 0.2);
        transition: transform 0.2s, border-color 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        border-color: rgba(232, 197, 110, 0.3);
    }

    .stat-number {
        font-size: 26px;
        font-weight: 800;
        color: #f0d488;
        letter-spacing: 1px;
    }

    .stat-label {
        font-size: 12px;
        color: #bcab8b;
        margin-top: 4px;
        letter-spacing: 1px;
    }

    @media (max-width:600px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .profile-summary {
            flex-direction: column;
            text-align: center;
        }

        .profile-rank-badge {
            width: 100%;
        }
    }

    .tier-progress-section {
        background: linear-gradient(158deg, rgba(62, 47, 28, 0.5), rgba(41, 30, 18, 0.6));
        border: 1px solid rgba(232, 197, 110, 0.12);
        border-radius: 16px;
        padding: 18px 22px;
        margin-bottom: 18px;
        backdrop-filter: blur(8px);
        box-shadow: 0 8px 28px rgba(0, 0, 0, 0.2);
    }

    .tier-progress-header {
        display: flex;
        justify-content: space-between;
        font-size: 14px;
        color: #bcab8b;
        margin-bottom: 8px;
        flex-wrap: wrap;
        gap: 6px;
    }

    .tier-progress-header span:first-child {
        color: #f0d488;
        font-weight: 600;
    }

    .tier-progress-bar {
        width: 100%;
        height: 10px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 8px;
        overflow: hidden;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .tier-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #c9a84c, #f0d488, #ffd700);
        border-radius: 8px;
        transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 0 20px rgba(232, 197, 110, 0.2);
    }

    .tier-progress-label {
        font-size: 13px;
        color: #bcab8b;
        margin-top: 8px;
        text-align: right;
    }

    .charts-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
        margin-bottom: 18px;
    }

    .chart-box {
        background: linear-gradient(158deg, rgba(62, 47, 28, 0.5), rgba(41, 30, 18, 0.6));
        border: 1px solid rgba(232, 197, 110, 0.1);
        border-radius: 16px;
        padding: 20px;
        backdrop-filter: blur(8px);
        box-shadow: 0 8px 28px rgba(0, 0, 0, 0.2);
        text-align: center;
    }

    .chart-title {
        font-size: 17px;
        font-weight: 700;
        color: #f0d488;
        letter-spacing: 1px;
    }

    .chart-subtitle {
        font-size: 12px;
        color: #bcab8b;
        margin-bottom: 12px;
    }

    .chart-box canvas {
        max-width: 100%;
        height: auto;
        max-height: 320px;
    }

    @media (max-width:700px) {
        .charts-row {
            grid-template-columns: 1fr;
        }
    }

    .match-list-section {
        background: linear-gradient(158deg, rgba(62, 47, 28, 0.5), rgba(41, 30, 18, 0.6));
        border: 1px solid rgba(232, 197, 110, 0.1);
        border-radius: 16px;
        padding: 18px 20px;
        backdrop-filter: blur(8px);
        box-shadow: 0 8px 28px rgba(0, 0, 0, 0.2);
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 14px;
    }

    .section-header h2 {
        font-size: 18px;
        font-weight: 700;
        color: #ece2cd;
    }

    .section-header h2 i {
        color: #f0d488;
        margin-right: 10px;
    }

    .match-count {
        font-size: 13px;
        color: #bcab8b;
        background: rgba(232, 197, 110, 0.08);
        padding: 2px 14px;
        border-radius: 20px;
    }

    .match-list {
        display: flex;
        flex-direction: column;
        gap: 6px;
        max-height: 440px;
        overflow-y: auto;
        padding-right: 4px;
    }

    .match-list::-webkit-scrollbar {
        width: 4px;
    }

    .match-list::-webkit-scrollbar-track {
        background: transparent;
    }

    .match-list::-webkit-scrollbar-thumb {
        background: rgba(232, 197, 110, 0.25);
        border-radius: 3px;
    }

    .match-item {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 12px 16px;
        background: rgba(236, 226, 205, 0.03);
        border-radius: 12px;
        border: 1px solid rgba(232, 197, 110, 0.06);
        transition: all 0.25s ease;
    }

    .match-item:hover {
        background: rgba(232, 197, 110, 0.07);
        transform: translateX(4px);
    }

    .match-item.win {
        border-left: 4px solid #f0d488;
    }

    .match-item.lose {
        border-left: 4px solid #e87474;
    }

    .match-item.draw {
        border-left: 4px solid #8a8a7a;
    }

    .match-result-icon {
        font-size: 20px;
        width: 34px;
        text-align: center;
        flex-shrink: 0;
    }

    .match-info {
        flex: 1;
    }

    .match-opponent {
        font-size: 14px;
        font-weight: 600;
        color: #ece2cd;
    }

    .match-meta {
        display: flex;
        gap: 14px;
        font-size: 12px;
        color: #bcab8b;
        margin-top: 2px;
        flex-wrap: wrap;
    }

    .match-mode.ranked {
        color: #f0d488;
        font-weight: 600;
    }

    .match-mode.casual {
        color: #7a8a9a;
    }

    .match-score-change {
        font-size: 17px;
        font-weight: 700;
        min-width: 54px;
        text-align: right;
    }

    .match-score-change.positive {
        color: #6aaa6a;
    }

    .match-score-change.negative {
        color: #e87474;
    }

    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 16px;
        margin-top: 16px;
    }

    .page-btn {
        padding: 8px 20px;
        background: rgba(232, 197, 110, 0.08);
        border: 1px solid rgba(232, 197, 110, 0.15);
        border-radius: 10px;
        color: #f0d488;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .page-btn:hover {
        background: rgba(232, 197, 110, 0.18);
        transform: translateY(-1px);
        box-shadow: 0 4px 16px rgba(232, 197, 110, 0.1);
    }

    .page-info {
        color: #bcab8b;
        font-size: 14px;
    }

    .empty-state {
        text-align: center;
        padding: 36px 0;
        color: #bcab8b;
    }

    .empty-state i {
        font-size: 40px;
        opacity: 0.3;
        margin-bottom: 10px;
        display: block;
    }

    .empty-state p {
        font-size: 14px;
    }
</style>