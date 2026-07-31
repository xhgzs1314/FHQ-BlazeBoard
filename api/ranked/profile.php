<?php
require($_SERVER['DOCUMENT_ROOT'] . '/api/quot.php');
requireApiAuth();
require_once($_SERVER['DOCUMENT_ROOT'] . '/cofd/common.php');

$raw = file_get_contents('php://input');
$in = json_decode($raw, true);
$uid = is_array($in) ? trim((string)($in['uid'] ?? '')) : '';
if ($uid === '') {
    sendResponse(400, null, '缺少 uid');
}
$us = $conn->prepare("SELECT `isban` FROM mok_user WHERE id = ? LIMIT 1");
$us->bind_param('s', $uid);
$us->execute();
$ur = $us->get_result();
if ($ur->num_rows === 0) {
    $us->close();
    sendResponse(404, null, '玩家不存在');
}
$urow = $ur->fetch_assoc();
$us->close();
if ((int)$urow['isban'] === 1 || (int)$urow['isban'] === 2) {
    sendResponse(403, null, '账号状态异常');
}
$stmt = $conn->prepare(
    "SELECT score, matches, wins, draws, streak, max_streak, s_plus_count, season_high_score
     FROM mok_player_rank WHERE user_id = ? LIMIT 1"
);
$stmt->bind_param('s', $uid);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) {
    $initScore = 0;
    $ins = $conn->prepare(
        "INSERT INTO mok_player_rank (user_id, score, season_high_score) VALUES (?, ?, ?)"
    );
    $ins->bind_param('sii', $uid, $initScore, $initScore);
    $ins->execute();
    $ins->close();
    $row = [
        'score' => $initScore, 'matches' => 0, 'wins' => 0, 'draws' => 0,
        'streak' => 0, 'max_streak' => 0, 's_plus_count' => 0,
        'season_high_score' => $initScore,
    ];
}

sendResponse(200, [
    'uid' => $uid,
    'score' => (int)$row['score'],
    'matches' => (int)$row['matches'],
    'wins' => (int)$row['wins'],
    'draws' => (int)$row['draws'],
    'streak' => (int)$row['streak'],
    'max_streak' => (int)$row['max_streak'],
    's_plus_count' => (int)$row['s_plus_count'],
    'season_high_score' => (int)$row['season_high_score'],
], '成功');
