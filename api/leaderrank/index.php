<?php
require($_SERVER['DOCUMENT_ROOT'] . '/config.php');
header('Content-Type: application/json; charset=utf-8');
if (function_exists('isSameOriginRequest') && !isSameOriginRequest()) {
  http_response_code(403);
  echo json_encode(['code' => 403, 'msg' => '仅同源访问', 'data' => null]);
  exit;
}

const LB_TOP = 50;
const LB_CACHE_TTL = 20;                 // second
$cacheFile = sys_get_temp_dir() . '/fhq_leaderboard.json';

// 命中缓存
if (is_file($cacheFile) && (time() - @filemtime($cacheFile) < LB_CACHE_TTL)) {
  $cached = @file_get_contents($cacheFile);
  if ($cached !== false && $cached !== '') {
    echo $cached;
    exit;
  }
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/cofd/common.php');
function lb_query($conn, $orderExpr, $whereExtra = '')
{
  $where = "(u.isban IS NULL OR u.isban = 0)" . ($whereExtra ? (" AND " . $whereExtra) : "");
  $sql = "SELECT r.user_id, r.score, r.matches, r.wins, r.draws, r.streak, r.max_streak, r.s_plus_count,
                 u.uname,u.tximg
          FROM mok_player_rank r
          JOIN mok_user u ON u.id = r.user_id
          WHERE $where
          ORDER BY $orderExpr
          LIMIT " . LB_TOP;
  $rows = [];
  if ($res = $conn->query($sql)) {
    while ($row = $res->fetch_assoc()) {
      $matches = (int)$row['matches'];
      $wins = (int)$row['wins'];
      $rate = $matches > 0 ? round($wins / $matches * 100) : 0;
      $rows[] = [
        'name'      => ($row['uname'] !== null && $row['uname'] !== '') ? $row['uname'] : ('玩家' . substr((string)$row['user_id'], -4)),
        'score'     => (int)$row['score'],
        'matches'   => $matches,
        'avatar' => $row['tximg'] ?? '/assets/photo/skz1.jpg',
        'wins'      => $wins,
        'winRate'   => $rate,
        'streak'    => (int)$row['streak'],
        'maxStreak' => (int)$row['max_streak'],
        'sPlus'     => (int)$row['s_plus_count'],
        'uid' => $row['user_id'] ?? 'u10086'
      ];
    }
    $res->free();
  }
  return $rows;
}

try {
  $boards = [
    // 综合：排位分为主，叠加胜率/最高连胜/S+ 加权
    'total'   => lb_query($conn, "(r.score + IF(r.matches>0, r.wins/r.matches*400, 0) + r.max_streak*10 + r.s_plus_count*20) DESC, r.score DESC"),
    'score'   => lb_query($conn, "r.score DESC, r.wins DESC"),
    'winrate' => lb_query($conn, "r.wins/r.matches DESC, r.matches DESC", "r.matches >= 10"),
    'streak'  => lb_query($conn, "r.streak DESC, r.max_streak DESC", "r.streak > 0"),
    'splus'   => lb_query($conn, "r.s_plus_count DESC, r.score DESC", "r.s_plus_count > 0"),
  ];
  $out = json_encode(['code' => 200, 'msg' => '成功', 'data' => $boards], JSON_UNESCAPED_UNICODE);
  @file_put_contents($cacheFile . '.tmp', $out, LOCK_EX) && @rename($cacheFile . '.tmp', $cacheFile);
  echo $out;
} catch (\Throwable $e) {
  error_log('leaderboard 失败: ' . $e->getMessage());
  echo json_encode(['code' => 500, 'msg' => '读取失败', 'data' => null], JSON_UNESCAPED_UNICODE);
}
