<?php
/**
 * .env 可视化编辑
 */
if (!defined('FHQ_ADMIN')) { http_response_code(403); exit('forbidden'); }

function adm_env_path()
{
    return dirname(dirname(__DIR__)) . '/.env';
}

/** 键定义 */
function adm_env_schema()
{
    return array(
        'WS_LINKING_ADDRESS'  => array('label' => 'WS 连接地址', 'type' => 'text',
            'hint' => '浏览器要连的 socket.io 地址，如 http://1.2.3.4:8080 或 https://ws.example.com'),
        'port'                => array('label' => 'WS 监听端口', 'type' => 'int',
            'hint' => 'server.js 监听端口，默认 8080'),
        'API_SECRET_KEY_mok'  => array('label' => 'API 密钥', 'type' => 'secret',
            'hint' => 'Node 与 PHP 之间的共享密钥，同时用于签发身份票据。留空则不修改'),
        'PHP_API_BASE'        => array('label' => 'PHP 回调地址', 'type' => 'text',
            'hint' => 'server.js 回调 PHP 的基址，同机部署填 http://127.0.0.1'),
        'CORS_ORIGIN'         => array('label' => 'CORS 来源', 'type' => 'text',
            'hint' => '* 表示不限；多个用英文逗号分隔'),
        'TURN_AFK_MS'         => array('label' => '回合挂机判负', 'type' => 'int', 'unit' => 'ms',
            'hint' => '默认 120000（2 分钟）'),
        'ROOM_IDLE_MS'        => array('label' => '空房回收', 'type' => 'int', 'unit' => 'ms',
            'hint' => '默认 600000（10 分钟）'),
        'LOBBY_PUSH_MS'       => array('label' => '大厅推送间隔', 'type' => 'int', 'unit' => 'ms',
            'hint' => '默认 5000；调小更实时但更耗带宽'),
        'REPORT_MAX_ATTEMPTS' => array('label' => '结算重试次数', 'type' => 'int',
            'hint' => '排位结算回调 PHP 失败后的最大重试次数，默认 6'),
    );
}
/**
 * 逐行解析结构
 */
function adm_env_parse($text)
{
    $out = array();
    foreach (preg_split("/\r\n|\n|\r/", (string)$text) as $line) {
        $t = trim($line);
        if ($t === '') { $out[] = array('kind' => 'blank', 'raw' => ''); continue; }
        if ($t[0] === '#') { $out[] = array('kind' => 'comment', 'raw' => $line); continue; }
        $eq = strpos($line, '=');
        if ($eq === false) { $out[] = array('kind' => 'comment', 'raw' => $line); continue; }

        $key = trim(substr($line, 0, $eq));
        $rest = substr($line, $eq + 1);
        $comment = '';
        $hash = strpos($rest, '#');
        if ($hash !== false) {
            $comment = substr($rest, $hash);          // 含 '#' 本身
            $rest = substr($rest, 0, $hash);
        }
        $value = trim($rest);
        // 去掉成对引号
        $len = strlen($value);
        if ($len >= 2 && (($value[0] === '"' && $value[$len-1] === '"')
                       || ($value[0] === "'" && $value[$len-1] === "'"))) {
            $value = substr($value, 1, -1);
        }
        $out[] = array('kind' => 'pair', 'raw' => $line, 'key' => $key,
                       'value' => $value, 'comment' => rtrim($comment));
    }
    return $out;
}

/** 读 .env  */
function adm_env_read()
{
    $p = adm_env_path();
    if (!is_file($p)) return array('pairs' => array(), 'dupes' => array(), 'lines' => array(), 'missing' => true);
    $text = (string)@file_get_contents($p);
    $lines = adm_env_parse($text);
    $pairs = array();
    $dupes = array();
    foreach ($lines as $l) {
        if ($l['kind'] !== 'pair') continue;
        if (array_key_exists($l['key'], $pairs)) { $dupes[$l['key']] = true; continue; }  // 保留首次，与 Node 一致
        $pairs[$l['key']] = $l['value'];
    }
    return array('pairs' => $pairs, 'dupes' => array_keys($dupes), 'lines' => $lines, 'missing' => false);
}
/** 值合法性 */
function adm_env_validate($key, $value, $meta)
{
    if (preg_match('/[\r\n]/', $value)) return $key . '：不能包含换行';
    if (strpos($value, '#') !== false) {
        return $key . '：不能包含 # 号（server.js 会把它后面的内容当注释截掉，加引号也无效）';
    }
    if (isset($meta['type']) && $meta['type'] === 'int' && $value !== '' && !preg_match('/^\d+$/', $value)) {
        return $key . '：需要非负整数';
    }
    if ($key === 'WS_LINKING_ADDRESS' && $value !== '' && !preg_match('#^(https?|wss?)://#i', $value)) {
        return 'WS 连接地址：需要以 http:// https:// ws:// wss:// 开头';
    }
    if ($key === 'PHP_API_BASE' && $value !== '' && !preg_match('#^https?://#i', $value)) {
        return 'PHP 回调地址：需要以 http:// 或 https:// 开头';
    }
    return null;
}

/**
 * 写回：沿用原文件的行顺序与注释，只替换值；重复键仅保留首次出现那行。
 */
function adm_env_render($lines, $newValues)
{
    $seen = array();
    $out = array();
    foreach ($lines as $l) {
        if ($l['kind'] !== 'pair') { $out[] = $l['raw']; continue; }
        $k = $l['key'];
        if (isset($seen[$k])) continue;                       // 去重：丢掉后续重复行
        $seen[$k] = true;
        $v = array_key_exists($k, $newValues) ? $newValues[$k] : $l['value'];
        $line = $k . '=' . $v;
        if ($l['comment'] !== '') $line .= '  ' . $l['comment'];
        $out[] = $line;
    }
    // 新增的键（schema 里有但文件里没有）追加到末尾
    foreach ($newValues as $k => $v) {
        if (isset($seen[$k])) continue;
        if ($v === '') continue;
        $out[] = $k . '=' . $v;
    }
    // 末尾空行会在「解析→写回」中每轮多攒一行，先削平再补唯一的换行
    while (!empty($out) && trim(end($out)) === '') array_pop($out);
    return implode("\n", $out) . "\n";
}
/**
 * 保存。secret 留空 = 沿用原值。原子写 + .bak 备份。
 * 返回 array(ok, msg)
 */
function adm_env_save($post)
{
    $cur = adm_env_read();
    if (!empty($cur['missing'])) return array(false, '.env 不存在，请先在项目根目录创建');

    $schema = adm_env_schema();
    $newValues = array();
    $errors = array();

    // 已知键 + 文件里已有的未知键，都允许改
    $editable = array_unique(array_merge(array_keys($schema), array_keys($cur['pairs'])));

    foreach ($editable as $key) {
        $meta = isset($schema[$key]) ? $schema[$key] : array('type' => 'text');
        $formKey = 'env__' . $key;
        $old = isset($cur['pairs'][$key]) ? $cur['pairs'][$key] : '';

        if (!array_key_exists($formKey, $post)) {                 // 未提交则保持原值
            if (array_key_exists($key, $cur['pairs'])) $newValues[$key] = $old;
            continue;
        }
        $raw = trim((string)$post[$formKey]);
        if ($meta['type'] === 'secret' && $raw === '') { $newValues[$key] = $old; continue; }

        $err = adm_env_validate($key, $raw, $meta);
        if ($err !== null) { $errors[] = $err; $newValues[$key] = $old; continue; }
        $newValues[$key] = $raw;
    }

    if (!empty($errors)) return array(false, implode('；', $errors));

    $text = adm_env_render($cur['lines'], $newValues);
    $path = adm_env_path();
    $tmp = $path . '.tmp' . getmypid();
    if (@file_put_contents($tmp, $text, LOCK_EX) === false) {
        return array(false, '无法写入临时文件，检查目录权限');
    }
    @copy($path, $path . '.bak');
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return array(false, '替换 .env 失败，检查文件权限');
    }
    adm_audit('.env', 'update', '-', array_keys($newValues));
    $note = '已保存（旧版本存为 .env.bak）。';
    $note .= 'WS/端口/密钥类改动需重启 Node 服务端才生效：pm2 restart fenghuoqi 或 node server.js';
    if (!empty($cur['dupes'])) {
        $note .= '　已顺带清理重复键：' . implode('、', $cur['dupes']);
    }
    return array(true, $note);
}



