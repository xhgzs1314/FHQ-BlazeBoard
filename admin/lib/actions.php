<?php
/**
 * 业务动作
 * 每个动作声明 label / confirm / danger
 */
if (!defined('FHQ_ADMIN')) { http_response_code(403); exit('forbidden'); }

function adm_actions()
{
    return array(
        'ban' => array(
            'label'   => '封禁',
            'confirm' => '确认封禁该用户？封禁后无法登录，排行榜也会剔除。',
            'danger'  => true,
            'run'     => 'adm_do_ban',
        ),
        'unban' => array(
            'label'   => '解封',
            'confirm' => '确认解封该用户？',
            'run'     => 'adm_do_unban',
        ),
        'resetPwd' => array(
            'label'   => '重置密码',
            'confirm' => '将生成一个随机新密码并显示一次，确认继续？',
            'danger'  => true,
            'run'     => 'adm_do_reset_pwd',
        ),
        'recalcRank' => array(
            'label'   => '按对局重算',
            'confirm' => '将依据 mok_match_record 重算该玩家的场次/胜场/连胜，积分不动。确认？',
            'run'     => 'adm_do_recalc_rank',
        ),
        'delReplay' => array(
            'label'   => '删除录像',
            'confirm' => '录像数据不可恢复，确认删除？',
            'danger'  => true,
            'run'     => 'adm_do_del_replay',
        ),
        'softDelFile' => array(
            'label'   => '标记删除',
            'confirm' => '将 upload_status 置为 3（已删除）并写入 delete_time，不动 S3 对象。确认？',
            'run'     => 'adm_do_soft_del_file',
        ),
    );
}

function adm_do_ban($pk)
{
    aq_exec('UPDATE mok_user SET isban = 1 WHERE id = ? LIMIT 1', array($pk));
    adm_audit('mok_user', 'ban', $pk, array('isban'));
    return array(true, '已封禁 ' . $pk);
}
function adm_do_unban($pk)
{
    aq_exec('UPDATE mok_user SET isban = 0 WHERE id = ? LIMIT 1', array($pk));
    adm_audit('mok_user', 'unban', $pk, array('isban'));
    return array(true, '已解封 ' . $pk);
}

function adm_do_reset_pwd($pk)
{
    // 排除易混淆字符
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
    $pwd = '';
    for ($i = 0; $i < 12; $i++) $pwd .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    $hash = password_hash($pwd, PASSWORD_BCRYPT, array('cost' => 10));
    $n = aq_exec('UPDATE mok_user SET password = ? WHERE id = ? LIMIT 1', array($hash, $pk));
    if ($n === 0) return array(false, '未找到该用户');
    adm_audit('mok_user', 'resetPwd', $pk, array('password'));
    // 明文只在本次响应里出现一次
    return array(true, '新密码（仅显示一次）：' . $pwd);
}

function adm_do_recalc_rank($pk)
{
    $rows = aq_all(
        'SELECT winner, red_id, blue_id FROM mok_match_record
          WHERE mode = 1 AND (red_id = ? OR blue_id = ?)
          ORDER BY create_time ASC',
        array($pk, $pk)
    );
    $matches = count($rows);
    $wins = 0; $draws = 0; $streak = 0; $maxStreak = 0;
    foreach ($rows as $r) {
        $mySide = ($r['red_id'] === $pk) ? 'red' : 'blue';
        if ($r['winner'] === null || $r['winner'] === '' || $r['winner'] === 'draw') {
            $draws++;
            continue;                                  // 和棋不断连胜也不加
        }
        if ($r['winner'] === $mySide) {
            $wins++; $streak++;
            if ($streak > $maxStreak) $maxStreak = $streak;
        } else {
            $streak = 0;
        }
    }
    $n = aq_exec(
        'UPDATE mok_player_rank
            SET matches = ?, wins = ?, draws = ?, streak = ?, max_streak = ?
          WHERE user_id = ? LIMIT 1',
        array($matches, $wins, $draws, $streak, max($maxStreak, 0), $pk)
    );
    if ($n === 0) return array(false, '该玩家无排位记录');
    adm_audit('mok_player_rank', 'recalc', $pk, array('matches', 'wins', 'draws', 'streak', 'max_streak'));
    return array(true, "已重算：{$matches} 场 / {$wins} 胜 / {$draws} 平 / 当前连胜 {$streak} / 最高 {$maxStreak}");
}
function adm_do_del_replay($pk)
{
    $n = aq_exec('DELETE FROM mok_replay WHERE replay_id = ? LIMIT 1', array($pk));
    adm_audit('mok_replay', 'delete', $pk, array());
    return array($n > 0, $n > 0 ? '已删除录像 ' . $pk : '未找到该录像');
}

function adm_do_soft_del_file($pk)
{
    $n = aq_exec(
        'UPDATE mok_file_archive SET upload_status = 3, delete_time = NOW() WHERE id = ? LIMIT 1',
        array($pk)
    );
    adm_audit('mok_file_archive', 'softDelete', $pk, array('upload_status', 'delete_time'));
    return array($n > 0, $n > 0 ? '已标记删除（S3 对象未动）' : '未找到该文件');
}

/** 派发：动作名必须在白名单里 */
function adm_run_action($cfg, $actionKey, $pk)
{
    $all = adm_actions();
    $allowed = isset($cfg['actions']) ? $cfg['actions'] : array();
    if (!isset($all[$actionKey]) || !in_array($actionKey, $allowed, true)) {
        return array(false, '未授权的操作');
    }
    $fn = $all[$actionKey]['run'];
    if (!is_callable($fn)) return array(false, '操作未实现');
    try {
        return call_user_func($fn, $pk);
    } catch (Throwable $e) {
        return array(false, '操作失败：' . $e->getMessage());
    }
}


