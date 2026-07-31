<?php
require($_SERVER['DOCUMENT_ROOT'] . '/api/quot.php');
requireApiAuth();
require_once($_SERVER['DOCUMENT_ROOT'] . '/cofd/common.php');
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
// 回放数据
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
function finalDelta(int $baseDelta, string $result, int $streakBefore, int $matchesBefore): int
{
    $d = $baseDelta;
    if ($result === 'win' && ($streakBefore + 1) >= 3) {
        $d += 3;
    }
    if ($matchesBefore < 10) {
        $d = (int)round($d * 1.5);
    }
    return $d;
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
        "SELECT user_id, score, matches, wins, draws, streak, max_streak, s_plus_count, season_high_score
         FROM mok_player_rank WHERE user_id = ? FOR UPDATE"
    );
    foreach ($ids as $id) {
        $sel->bind_param('s', $id);
        $sel->execute();
        $r = $sel->get_result()->fetch_assoc();
        if (!$r) {
            $init = 0;
            $mk = $conn->prepare("INSERT INTO mok_player_rank (user_id, score, season_high_score) VALUES (?, ?, ?)");
            $mk->bind_param('sii', $id, $init, $init);
            $mk->execute();
            $mk->close();
            $r = [
                'user_id' => $id,
                'score' => $init,
                'matches' => 0,
                'wins' => 0,
                'draws' => 0,
                'streak' => 0,
                'max_streak' => 0,
                's_plus_count' => 0,
                'season_high_score' => $init
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
        $res = $winner === null ? 'draw' : ($winner === $side ? 'win' : 'loss');
        $base = (int)($info['data']['eloDelta'] ?? 0);
        $delta = finalDelta($base, $res, (int)$rk['streak'], (int)$rk['matches']);
        $after = max(0, $before + $delta);
        $matches = (int)$rk['matches'] + 1;
        $wins = (int)$rk['wins'] + ($res === 'win' ? 1 : 0);
        $draws = (int)$rk['draws'] + ($res === 'draw' ? 1 : 0);
        $streak = $res === 'win' ? (int)$rk['streak'] + 1 : 0;
        $maxStreak = max((int)$rk['max_streak'], $streak);
        $sPlus = (int)$rk['s_plus_count'] + (($info['data']['grade'] ?? '') === 'S+' ? 1 : 0);
        $seasonHigh = max((int)$rk['season_high_score'], $after);
        $upd = $conn->prepare(
            "UPDATE mok_player_rank
             SET score=?, matches=?, wins=?, draws=?, streak=?, max_streak=?, s_plus_count=?, season_high_score=?
             WHERE user_id=?"
        );
        $upd->bind_param('iiiiiiiis', $after, $matches, $wins, $draws, $streak, $maxStreak, $sPlus, $seasonHigh, $id);
        $upd->execute();
        $upd->close();
        $result[$side] = ['uid' => $id, 'scoreBefore' => $before, 'scoreAfter' => $after, 'delta' => $after - $before];
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
                $winnerStr = $winner === null ? 'draw' : $winner;   // red/blue/draw
                $rp = $conn->prepare(
                    "INSERT IGNORE INTO mok_replay
     (replay_id, red_id, red_name, red_score, blue_id, blue_name, blue_score, winner, replay_data)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $rp->bind_param(
                    'sssissisb',
                    $matchId,
                    $redId,
                    $redName,
                    $redScoreBefore,
                    $blueId,
                    $blueName,
                    $blueScoreBefore,
                    $winnerStr,
                    $null
                );
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
