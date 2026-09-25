<?php
require($_SERVER['DOCUMENT_ROOT'] . '/api/quot.php');
requireApiAuth();
require_once($_SERVER['DOCUMENT_ROOT'] . '/cofd/common.php');

function clampInt($value, $min, $max)
{
    return max($min, min($max, (int)$value));
}

function tierNameForScore($score)
{
    if ($score >= 1600) return 'S+';
    if ($score >= 1300) return 'S';
    if ($score >= 1000) return 'A';
    if ($score >= 700) return 'B';
    if ($score >= 450) return 'C';
    if ($score >= 200) return 'D';
    return 'E';
}

function computeRankDelta($playerScore, $opponentScore, $result, $matchesBefore, $streakBefore, $tier, $performance)
{
    $gap = abs((int)$playerScore - (int)$opponentScore);
    $gapFactor = min(1.4, $gap / 260.0);
    $expected = 1 / (1 + pow(10, (($opponentScore - $playerScore) / 400.0)));
    $base = 12 + (1 - $expected) * 30 + $gapFactor * 12;
    $tierRules = [
        'S+' => ['win' => 0.82, 'loss' => 1.42, 'draw' => 1.0],
        'S' => ['win' => 0.9, 'loss' => 1.34, 'draw' => 1.0],
        'A' => ['win' => 0.97, 'loss' => 1.24, 'draw' => 1.0],
        'B' => ['win' => 1.04, 'loss' => 1.16, 'draw' => 1.0],
        'C' => ['win' => 1.14, 'loss' => 1.08, 'draw' => 1.0],
        'D' => ['win' => 1.22, 'loss' => 1.02, 'draw' => 1.0],
        'E' => ['win' => 1.38, 'loss' => 0.96, 'draw' => 1.0],
    ];
    $cfg = $tierRules[$tier] ?? $tierRules['E'];
    $newbieFactor = $matchesBefore < 10 ? 1.7 : 1.0;
    $streakFactor = $streakBefore >= 3 ? 1.18 : 1.0;
    $performanceFactor = $performance >= 0.9 ? 1.16 : ($performance <= 0.55 ? 0.84 : 1.0);
    $underdog = $playerScore < $opponentScore ? 1.24 : 0.88;

    if ($result === 'draw') {
        $delta = $base * 0.35 * $cfg['draw'] * $newbieFactor;
        return (int)round(clampInt($delta, -8, 8));
    }

    if ($result === 'win') {
        $delta = $base * (1.08 + $gapFactor) * $cfg['win'] * $newbieFactor * $streakFactor * $performanceFactor * $underdog;
        return max(8, (int)round($delta));
    }

    $delta = $base * (1.2 + $gapFactor * 0.65) * $cfg['loss'] * $newbieFactor * $performanceFactor * (1.08 + ($playerScore >= $opponentScore ? 0.18 : 0));
    return -(int)round(max(10, $delta));
}

$raw = file_get_contents('php://input');
$in = json_decode($raw, true);
if (!is_array($in)) {
    sendResponse(400, null, '请求体无效');
}
$matchId = trim((string)($in['matchId'] ?? ''));
$mode = (int)($in['mode'] ?? 1);
$winner = $in['winner'] ?? null;
$round = (int)($in['round'] ?? 0);
$red = is_array($in['red'] ?? null) ? $in['red'] : null;
$blue = is_array($in['blue'] ?? null) ? $in['blue'] : null;

if ($matchId === '' || !$red || !$blue) {
    sendResponse(400, null, '参数不完整');
}
$redId = trim((string)($red['uid'] ?? ''));
$blueId = trim((string)($blue['uid'] ?? ''));
if ($redId === '' || $blueId === '') {
    sendResponse(400, null, '缺少玩家标识');
}
if ($winner !== 'red' && $winner !== 'blue' && $winner !== null) {
    sendResponse(400, null, '非法的 winner');
}

$redRatingJson = json_encode($red, JSON_UNESCAPED_UNICODE);
$blueRatingJson = json_encode($blue, JSON_UNESCAPED_UNICODE);
$replayB64 = (string)($in['replay'] ?? '');
$redScoreBefore = (int)($in['redScore'] ?? 0);
$blueScoreBefore = (int)($in['blueScore'] ?? 0);

if ($mode === 0) {
    $stmt = $conn->prepare(
        "INSERT IGNORE INTO mok_match_record
         (match_id, mode, red_id, red_score_before, red_score_after, red_rating,
          blue_id, blue_score_before, blue_score_after, blue_rating, winner, round_count)
         VALUES (?, 0, ?, 0, 0, ?, ?, 0, 0, ?, ?, ?)"
    );
    $stmt->bind_param('ssssssi', $matchId, $redId, $redRatingJson, $blueId, $blueRatingJson, $winner, $round);
    $stmt->execute();
    $stmt->close();
    sendResponse(200, ['recorded' => true, 'mode' => 0], '成功');
}

$conn->begin_transaction();
try {
    $ins = $conn->prepare(
        "INSERT IGNORE INTO mok_match_record
         (match_id, mode, red_id, red_score_before, red_score_after, red_rating,
          blue_id, blue_score_before, blue_score_after, blue_rating, winner, round_count)
         VALUES (?, 1, ?, 0, 0, ?, ?, 0, 0, ?, ?, ?)"
    );
    $ins->bind_param('ssssssi', $matchId, $redId, $redRatingJson, $blueId, $blueRatingJson, $winner, $round);
    $ins->execute();
    $affected = $ins->affected_rows;
    $recordId = $conn->insert_id;
    $ins->close();
    if ($affected === 0) {
        $conn->rollback();
        sendResponse(200, ['recorded' => false, 'reason' => 'duplicate'], '重复上报');
    }

    $ids = [$redId, $blueId];
    sort($ids);
    $ranks = [];
    $sel = $conn->prepare(
        "SELECT user_id, score, rating, tier, rating_dev, matches, wins, draws, streak, max_streak, s_plus_count, season_high_score, last_match_delta, recent_form
         FROM mok_player_rank WHERE user_id = ? FOR UPDATE"
    );
    foreach ($ids as $id) {
        $sel->bind_param('s', $id);
        $sel->execute();
        $r = $sel->get_result()->fetch_assoc();
        if (!$r) {
            $init = 0;
            $mk = $conn->prepare("INSERT INTO mok_player_rank (user_id, score, rating, tier, rating_dev, season_high_score) VALUES (?, ?, 1200.00, 'E', 180.00, ?)");
            $mk->bind_param('sii', $id, $init, $init);
            $mk->execute();
            $mk->close();
            $r = [
                'user_id' => $id,
                'score' => $init,
                'rating' => 1200.00,
                'tier' => 'E',
                'rating_dev' => 180.00,
                'matches' => 0,
                'wins' => 0,
                'draws' => 0,
                'streak' => 0,
                'max_streak' => 0,
                's_plus_count' => 0,
                'season_high_score' => $init,
                'last_match_delta' => 0,
                'recent_form' => null,
            ];
        }
        $ranks[$id] = $r;
    }
    $sel->close();

    $result = [];
    $sides = [
        'red' => ['id' => $redId, 'data' => $red],
        'blue' => ['id' => $blueId, 'data' => $blue],
    ];

    foreach ($sides as $side => $info) {
        $id = $info['id'];
        $rk = $ranks[$id];
        $before = (int)$rk['score'];
        $beforeRating = (float)($rk['rating'] ?? 1200.00);
        $res = $winner === null ? 'draw' : ($winner === $side ? 'win' : 'loss');
        $oppSide = $side === 'red' ? 'blue' : 'red';
        $oppScore = (int)($ranks[$sides[$oppSide]['id']]['score'] ?? 0);
        $performance = isset($info['data']['rating']) ? clampInt((float)$info['data']['rating'] / 16.0 * 10, 4, 14) / 10.0 : 0.7;
        $delta = computeRankDelta(
            $before,
            $oppScore,
            $res,
            (int)($rk['matches'] ?? 0),
            (int)($rk['streak'] ?? 0),
            strtoupper((string)($rk['tier'] ?? 'E')),
            $performance
        );

        $after = max(0, $before + $delta);
        $matches = (int)($rk['matches'] ?? 0) + 1;
        $wins = (int)($rk['wins'] ?? 0) + ($res === 'win' ? 1 : 0);
        $draws = (int)($rk['draws'] ?? 0) + ($res === 'draw' ? 1 : 0);
        $streak = $res === 'win' ? ((int)($rk['streak'] ?? 0) + 1) : 0;
        $maxStreak = max((int)($rk['max_streak'] ?? 0), $streak);
        $tier = strtoupper(tierNameForScore($after));
        $rating = max(1000.00, min(2200.00, $beforeRating + ($delta * 4.5)));
        $dev = max(60.00, min(400.00, (float)($rk['rating_dev'] ?? 180.00) + ($res === 'win' ? 5 : ($res === 'loss' ? -3 : 0))));
        $seasonHigh = max((int)($rk['season_high_score'] ?? 0), $after);
        $sPlus = (int)($rk['s_plus_count'] ?? 0) + (($tier === 'S+' || ($res === 'win' && $after >= 1600)) ? 1 : 0);

        $upd = $conn->prepare(
            "UPDATE mok_player_rank
             SET score=?, rating=?, tier=?, rating_dev=?, matches=?, wins=?, draws=?, streak=?, max_streak=?, s_plus_count=?, season_high_score=?, last_match_delta=?, last_match_time=NOW()
             WHERE user_id=?"
        );
        $typeString = 'idsd' . str_repeat('i', 8) . 's';
        $upd->bind_param($typeString, $after, $rating, $tier, $dev, $matches, $wins, $draws, $streak, $maxStreak, $sPlus, $seasonHigh, $delta, $id);
        $upd->execute();
        $upd->close();

        $result[$side] = [
            'uid' => $id,
            'scoreBefore' => $before,
            'scoreAfter' => $after,
            'delta' => $after - $before,
            'tier' => $tier,
            'rating' => round($rating, 2),
        ];
    }

    $up2 = $conn->prepare(
        "UPDATE mok_match_record
         SET red_score_before=?, red_score_after=?, blue_score_before=?, blue_score_after=?
         WHERE id=?"
    );
    $up2->bind_param(
        'iiiii',
        $result['red']['scoreBefore'],
        $result['red']['scoreAfter'],
        $result['blue']['scoreBefore'],
        $result['blue']['scoreAfter'],
        $recordId
    );
    $up2->execute();
    $up2->close();
    $conn->commit();

    if ($replayB64 !== '') {
        try {
            $replayBin = base64_decode($replayB64, true);
            if ($replayBin !== false && $replayBin !== '') {
                $redName = null;
                $blueName = null;
                if ($nameStmt = $conn->prepare("SELECT id, uname FROM mok_user WHERE id IN (?, ?)")) {
                    $nameStmt->bind_param('ss', $redId, $blueId);
                    $nameStmt->execute();
                    $nr = $nameStmt->get_result();
                    while ($row = $nr->fetch_assoc()) {
                        if ($row['id'] === $redId) $redName = $row['uname'];
                        if ($row['id'] === $blueId) $blueName = $row['uname'];
                    }
                    $nameStmt->close();
                }
                $winnerStr = $winner === null ? 'draw' : $winner;
                $replayBlob = null;
                $rp = $conn->prepare(
                    "INSERT IGNORE INTO mok_replay
                     (replay_id, red_id, red_name, red_score, blue_id, blue_name, blue_score, winner, replay_data)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $rp->bind_param('sssissisb', $matchId, $redId, $redName, $redScoreBefore, $blueId, $blueName, $blueScoreBefore, $winnerStr, $replayBlob);
                $rp->send_long_data(8, $replayBin);
                $rp->execute();
                $rp->close();
            }
        } catch (\Throwable $re) {
            error_log('ranked replay 落库失败: ' . $re->getMessage());
        }
    }

    sendResponse(200, ['recorded' => true, 'mode' => 1, 'red' => $result['red'], 'blue' => $result['blue']], '成功');
} catch (\Throwable $e) {
    $conn->rollback();
    error_log('ranked report 失败: ' . $e->getMessage());
    sendResponse(500, null, '结算失败');
}
