<?php
/** 服务端配置 */
if (!defined('FHQ_ADMIN')) { http_response_code(403); exit('forbidden'); }
adm_title('服务端配置');

$flash = '';
$flashOk = true;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_op']) && $_POST['_op'] === 'saveEnv') {
    list($flashOk, $flash) = adm_env_save($_POST);
}
adm_msg($flash, $flashOk);

$cur = adm_env_read();
$schema = adm_env_schema();
$path = adm_env_path();

if (!empty($cur['missing'])) {
    adm_msg('.env 不存在：' . $path, false);
    return;
}
if (!is_writable($path)) {
    adm_msg('.env 当前不可写，保存会失败。路径：' . $path, false);
}
if (!empty($cur['dupes'])) {
    adm_msg('检测到重复键：' . implode('、', $cur['dupes'])
        . '。server.js 取首次出现的值、PHP 取最后一次，两者相反。保存一次即可自动清理。', false);
}

echo '<div class="card"><div class="sec">说明</div>';
echo '<div class="who" style="line-height:1.9">';
echo '这份文件被 <b>server.js</b> 和 <b>PHP</b> 同时读取。改完保存后：<br>';
echo '· 仅 PHP 用到的键（API 密钥被 PHP 侧读取）即时生效<br>';
echo '· server.js 用到的键（WS 端口、CORS、各类超时）<b>需重启 Node 服务端</b><br>';
echo '· 值里不能出现 <code>#</code> —— server.js 会把它之后的内容当注释截断';
echo '</div></div>';

echo '<form method="post" action="index.php?p=env">';
echo adm_csrf_field();
echo '<input type="hidden" name="_op" value="saveEnv">';

echo '<div class="card"><div class="sec">已识别配置</div><div class="grid2">';
foreach ($schema as $key => $meta) {
    $val = isset($cur['pairs'][$key]) ? $cur['pairs'][$key] : '';
    $fk = 'env__' . $key;
    echo '<div class="field"><label>' . adm_h($meta['label'])
       . ' <span class="ro">' . adm_h($key) . '</span></label>';
    if ($meta['type'] === 'secret') {
        $has = ($val !== '');
        echo '<input type="password" name="' . adm_h($fk) . '" value="" autocomplete="new-password"'
           . ' placeholder="' . ($has ? '已设置，留空则不修改' : '未设置') . '">';
    } elseif ($meta['type'] === 'int') {
        echo '<input type="number" step="1" min="0" name="' . adm_h($fk) . '" value="' . adm_h($val) . '">';
    } else {
        echo '<input type="text" name="' . adm_h($fk) . '" value="' . adm_h($val) . '">';
    }
    if (!empty($meta['hint'])) echo '<div class="hint">' . adm_h($meta['hint']) . '</div>';
    echo '</div>';
}
echo '</div></div>';

/* 文件里存在但 schema 未收录的键 */
$unknown = array_diff(array_keys($cur['pairs']), array_keys($schema));
if (!empty($unknown)) {
    echo '<div class="card"><div class="sec">其它键 <span class="ro">未在后台登记，原样保留</span></div><div class="grid2">';
    foreach ($unknown as $key) {
        $fk = 'env__' . $key;
        echo '<div class="field"><label>' . adm_h($key) . '</label>';
        echo '<input type="text" name="' . adm_h($fk) . '" value="' . adm_h($cur['pairs'][$key]) . '">';
        echo '</div>';
    }
    echo '</div></div>';
}

echo '<div class="bar"><button class="btn pri" type="submit">保存配置</button>';
echo '<span class="who">保存时会先备份为 .env.bak</span></div>';
echo '</form>';
