<?php
/**
 * 后台数据库层
 */
if (!defined('FHQ_ADMIN')) { http_response_code(403); exit('forbidden'); }

function adm_fatal($msg)
{
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><div style="font:14px/1.8 system-ui;padding:40px;'
        . 'background:#14100a;color:#f0e6d8">';
    echo '<b style="color:#ff8f8f">后台错误：</b>' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
    echo '</div>';
    exit;
}

function adb()
{
    static $conn = null;
    if ($conn !== null) return $conn;
    $cfg = dirname(dirname(__DIR__)) . '/config.php';
    if (!is_file($cfg)) adm_fatal('未找到 config.php');
    require $cfg;
    if (!isset($db_host, $db_user, $db_pass, $db_name)) adm_fatal('config.php 缺少数据库变量');
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        $conn->set_charset('utf8mb4');
    } catch (Throwable $e) {
        adm_fatal('数据库连接失败');
    }
    return $conn;
}

/** 反引号包裹标识符 */
function qi($name)
{
    return '`' . str_replace('`', '``', (string)$name) . '`';
}

function adm_types($params)
{
    $t = '';
    foreach ($params as $p) {
        if (is_int($p)) $t .= 'i';
        elseif (is_float($p)) $t .= 'd';
        else $t .= 's';
    }
    return $t;
}
/** 预处理查询 */
function aq($sql, $params = array())
{
    $stmt = adb()->prepare($sql);
    if (!$stmt) adm_fatal('SQL 预处理失败');
    if (!empty($params)) {
        $refs = array(adm_types($params));
        foreach ($params as $k => $v) $refs[] = &$params[$k];
        call_user_func_array(array($stmt, 'bind_param'), $refs);
    }
    $stmt->execute();
    return $stmt;
}

function aq_all($sql, $params = array())
{
    $stmt = aq($sql, $params);
    $res = $stmt->get_result();
    $rows = array();
    if ($res) {
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        $res->free();
    }
    $stmt->close();
    return $rows;
}

function aq_one($sql, $params = array())
{
    $rows = aq_all($sql, $params);
    return empty($rows) ? null : $rows[0];
}

/** 取单标量 */
function aq_val($sql, $params = array(), $default = null)
{
    $row = aq_one($sql, $params);
    if ($row === null) return $default;
    $vals = array_values($row);
    return isset($vals[0]) ? $vals[0] : $default;
}

/** 执行写操作 */
function aq_exec($sql, $params = array())
{
    $stmt = aq($sql, $params);
    $n = $stmt->affected_rows;
    $stmt->close();
    return $n;
}

