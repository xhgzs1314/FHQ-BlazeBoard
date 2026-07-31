<?php
/** 操作日志 */
if (!defined('FHQ_ADMIN')) { http_response_code(403); exit('forbidden'); }
adm_title('操作日志');

$dir = __DIR__ . '/../logs';
$files = array();
if (is_dir($dir)) {
    foreach ((array)glob($dir . '/audit-*.log') as $f) {
        $files[basename($f)] = $f;
    }
    krsort($files);
}

if (empty($files)) {
    echo '<div class="card"><span class="nil">暂无操作日志。管理员执行写操作后会在此显示。</span></div>';
    return;
}

// 只允许打开本目录
$pick = isset($_GET['f']) ? basename((string)$_GET['f']) : '';
if ($pick === '' || !isset($files[$pick]) || !preg_match('/^audit-\d{6}\.log$/', $pick)) {
    $pick = key($files);
}

echo '<div class="card"><div class="bar">';
foreach ($files as $name => $_) {
    $on = ($name === $pick) ? ' pri' : '';
    echo '<a class="btn sm' . $on . '" href="index.php?p=audit&f=' . urlencode($name) . '">'
       . adm_h(substr($name, 6, 6)) . '</a>';
}
echo '</div>';

$lines = @file($files[$pick], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if ($lines === false) $lines = array();
$lines = array_reverse(array_slice($lines, -500));

echo '<div class="scroll"><table><thead><tr><th>时间</th><th>管理员</th><th>IP</th>'
   . '<th>表</th><th>动作</th><th>记录</th><th>字段</th></tr></thead><tbody>';
foreach ($lines as $ln) {
    $j = json_decode($ln, true);
    if (!is_array($j)) continue;
    $get = function ($k) use ($j) { return isset($j[$k]) ? $j[$k] : ''; };
    $fields = $get('fields');
    echo '<tr><td>' . adm_h(str_replace('T', ' ', substr($get('ts'), 0, 19))) . '</td>'
       . '<td>' . adm_h($get('admin')) . '</td><td>' . adm_h($get('ip')) . '</td>'
       . '<td>' . adm_h($get('table')) . '</td>'
       . '<td><span class="badge">' . adm_h($get('action')) . '</span></td>'
       . '<td>' . adm_h($get('pk')) . '</td>'
       . '<td><span class="ro">' . adm_h(is_array($fields) ? implode(', ', $fields) : '') . '</span></td></tr>';
}
echo '</tbody></table></div>';
echo '<div class="who" style="margin-top:10px">最多显示最近 500 条</div></div>';
