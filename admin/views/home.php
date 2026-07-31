<?php
/** 总览 */
if (!defined('FHQ_ADMIN')) { http_response_code(403); exit('forbidden'); }
adm_title('总览');

$stats = array();
try {
    $stats['用户总数']   = (int)aq_val('SELECT COUNT(*) FROM mok_user', array(), 0);
    $stats['封禁用户']   = (int)aq_val('SELECT COUNT(*) FROM mok_user WHERE isban = 1', array(), 0);
    $stats['排位玩家']   = (int)aq_val('SELECT COUNT(*) FROM mok_player_rank', array(), 0);
    $stats['对局总数']   = (int)aq_val('SELECT COUNT(*) FROM mok_match_record', array(), 0);
    $stats['今日对局']   = (int)aq_val(
        'SELECT COUNT(*) FROM mok_match_record WHERE create_time >= CURDATE()', array(), 0);
    $stats['录像存档']   = (int)aq_val('SELECT COUNT(*) FROM mok_replay', array(), 0);
} catch (Throwable $e) {
    adm_msg('统计查询失败：' . $e->getMessage(), false);
}

echo '<div class="card"><div class="sec">运营概览</div><div class="stat">';
foreach ($stats as $k => $v) {
    echo '<div class="box"><div class="n">' . number_format($v) . '</div><div class="k">'
       . adm_h($k) . '</div></div>';
}
echo '</div></div>';

/* 最近对局 */
try {
    $recent = aq_all(
        'SELECT match_id, mode, red_id, blue_id, winner, round_count, create_time
           FROM mok_match_record ORDER BY create_time DESC LIMIT 10'
    );
    echo '<div class="card"><div class="sec">最近 10 场对局</div><div class="scroll"><table>';
    echo '<thead><tr><th>对局号</th><th>模式</th><th>红方</th><th>蓝方</th><th>结果</th>'
       . '<th>回合</th><th>时间</th></tr></thead><tbody>';
    if (empty($recent)) echo '<tr><td colspan="7"><span class="nil">暂无对局</span></td></tr>';
    foreach ($recent as $r) {
        $mode = ((int)$r['mode'] === 1) ? '<span class="badge">排位</span>' : '<span class="badge">娱乐</span>';
        if ($r['winner'] === null || $r['winner'] === '') $win = '<span class="badge">和棋</span>';
        elseif ($r['winner'] === 'red') $win = '<span class="badge bad">红胜</span>';
        else $win = '<span class="badge ok">蓝胜</span>';
        echo '<tr><td>' . adm_h($r['match_id']) . '</td><td>' . $mode . '</td><td>'
           . adm_h($r['red_id']) . '</td><td>' . adm_h($r['blue_id']) . '</td><td>' . $win
           . '</td><td>' . (int)$r['round_count'] . '</td><td>' . adm_h($r['create_time']) . '</td></tr>';
    }
    echo '</tbody></table></div></div>';
} catch (Throwable $e) {
    adm_msg('最近对局查询失败：' . $e->getMessage(), false);
}
