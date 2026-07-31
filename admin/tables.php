<?php
/**
 * 后台表配置
 *   pk / list / search / edit / actions / orderBy。
 * 字段中文名、控件类型、枚举下拉全部由 lib/schema.php 从列 COMMENT 推导，不在此重复。
 */
if (!defined('FHQ_ADMIN')) { http_response_code(403); exit('forbidden'); }

function adm_tables()
{
    return array(

        /* ---------- 用户管理 ---------- */
        'mok_user' => array(
            'label'   => '用户',
            'group'   => '用户管理',
            'pk'      => 'id',
            'list'    => array('id', 'uname', 'username', 'bdmail', 'credit', 'isban', 'regtime'),
            'search'  => array('id', 'uname', 'username', 'bdmail'),
            'edit'    => array('uname', 'sayed', 'tximg', 'bdmail', 'credit', 'isban'),
            'orderBy' => 'regtime DESC',
            'actions' => array('ban', 'unban', 'resetPwd'),
            'hide'    => array('password'),          // 永不显示
        ),

        'mok_email_verify' => array(
            'label'   => '邮箱验证',
            'group'   => '用户管理',
            'pk'      => 'id',
            'list'    => array('id', 'user_id', 'email', 'status', 'expire_time', 'create_time'),
            'search'  => array('user_id', 'email'),
            'edit'    => array('status'),
            'orderBy' => 'create_time DESC',
            'hide'    => array('token'),
        ),

        /* ---------- 排位与对局 ---------- */
        'mok_player_rank' => array(
            'label'   => '排位积分',
            'group'   => '排位与对局',
            'pk'      => 'user_id',
            'list'    => array('user_id', 'score', 'matches', 'wins', 'draws', 'streak',
                               'max_streak', 's_plus_count', 'season_high_score', 'update_time'),
            'search'  => array('user_id'),
            'edit'    => array('score', 'matches', 'wins', 'draws', 'streak',
                               'max_streak', 's_plus_count', 'season_high_score'),
            'orderBy' => 'score DESC',
            'actions' => array('recalcRank'),
        ),
        'mok_match_record' => array(
            'label'   => '对局记录',
            'group'   => '排位与对局',
            'pk'      => 'id',
            'list'    => array('id', 'match_id', 'mode', 'red_id', 'red_score_before', 'red_score_after',
                               'blue_id', 'blue_score_before', 'blue_score_after',
                               'winner', 'round_count', 'create_time'),
            'search'  => array('match_id', 'red_id', 'blue_id'),
            'edit'    => array(),                    // 只读
            'orderBy' => 'create_time DESC',
            'json'    => array('red_rating', 'blue_rating'),
            'labels'  => array(
                'mode'    => '模式',                 // 注释里只有枚举没有标签
                'red_id'  => '红方',
                'blue_id' => '蓝方',
                'winner'  => '结果',
            ),
        ),

        /* ---------- 录像与文件 ---------- */
        'mok_replay' => array(
            'label'   => '对局录像',
            'group'   => '录像与文件',
            'pk'      => 'replay_id',
            'list'    => array('replay_id', 'red_name', 'red_score', 'blue_name', 'blue_score',
                               'winner', 'create_time'),
            'search'  => array('replay_id', 'red_id', 'blue_id', 'red_name', 'blue_name'),
            'edit'    => array(),                    // 只读
            'orderBy' => 'create_time DESC',
            'actions' => array('delReplay'),
            'hide'    => array('replay_data'),       // longblob
            'links'   => array('replay_id' => '/replay.php?id={v}'),
        ),

        'mok_file_archive' => array(
            'label'   => '文件归档',
            'group'   => '录像与文件',
            'pk'      => 'id',
            'list'    => array('id', 'file_id', 'user_id', 'file_name', 'file_size',
                               'mime_type', 'upload_status', 'download_count', 'upload_time'),
            'search'  => array('file_id', 'user_id', 'file_name', 'object_key'),
            'edit'    => array('remark', 'upload_status', 'expire_time'),
            'orderBy' => 'upload_time DESC',
            'actions' => array('softDelFile'),
            'json'    => array('metadata'),
        ),

        /* ---------- 管理员 ---------- */
        'mok_admin' => array(
            'label'   => '管理员',
            'group'   => '系统',
            'pk'      => 'id',
            'list'    => array('id', 'username'),
            'search'  => array('username'),
            'edit'    => array(),                    // 增删改走 admins.php 专用页
            'orderBy' => 'id ASC',
            'hide'    => array('password'),
        ),
    );
}

/** 取单表配置 */
function adm_table_cfg($table)
{
    $all = adm_tables();
    if (!isset($all[$table])) return null;
    if (!adm_table_exists($table)) return null;
    $cfg = $all[$table];
    $cols = adm_schema($table);
    $hide = isset($cfg['hide']) ? $cfg['hide'] : array();
    // 交集过滤
    $keep = function ($names) use ($cols, $hide) {
        $out = array();
        foreach ((array)$names as $n) {
            if (isset($cols[$n]) && !in_array($n, $hide, true)) $out[] = $n;
        }
        return $out;
    };
    // labels
    if (!empty($cfg['labels'])) {
        foreach ($cfg['labels'] as $col => $label) {
            if (isset($cols[$col])) $cols[$col]['label'] = $label;
        }
    }
    $cfg['table']  = $table;
    $cfg['cols']   = $cols;
    $cfg['list']   = $keep(isset($cfg['list']) ? $cfg['list'] : array_keys($cols));
    $cfg['search'] = $keep(isset($cfg['search']) ? $cfg['search'] : array());
    $cfg['edit']   = $keep(isset($cfg['edit']) ? $cfg['edit'] : array());
    $cfg['hide']   = $hide;
    if (!isset($cfg['pk']) || !isset($cols[$cfg['pk']])) return null;
    return $cfg;
}

