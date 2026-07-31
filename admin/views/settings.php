<?php
/** 站点配置 */
if (!defined('FHQ_ADMIN')) { http_response_code(403); exit('forbidden'); }
adm_title('站点配置');

$flash = '';
$flashOk = true;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_op']) && $_POST['_op'] === 'saveSettings') {
    list($flashOk, $flash) = adm_setting_save($_POST);
}
adm_msg($flash, $flashOk);

$path = adm_setting_path();
if (!is_writable($path)) {
    adm_msg('setting.php 当前不可写，保存会失败。路径：' . $path, false);
}

$cur = adm_setting_read();
$schema = adm_setting_schema();

echo '<form method="post" action="index.php?p=settings">';
echo adm_csrf_field();
echo '<input type="hidden" name="_op" value="saveSettings">';

foreach ($schema as $varName => $group) {
    echo '<div class="card"><div class="sec">' . adm_h($group['label'])
       . ' <span class="ro">' . adm_h($varName) . '</span></div><div class="grid2">';
    foreach ($group['fields'] as $key => $meta) {
        $formKey = ltrim($varName, '$') . '__' . $key;
        $val = isset($cur[$varName][$key]) ? $cur[$varName][$key] : '';
        echo '<div class="field"><label>' . adm_h($meta['label'])
           . ' <span class="ro">' . adm_h($key) . '</span></label>';

        if ($meta['type'] === 'bool') {
            $ck = !empty($val) ? ' checked' : '';
            echo '<label style="display:flex;align-items:center;gap:8px;color:var(--ink)">';
            echo '<input type="checkbox" name="' . adm_h($formKey) . '" value="1" style="width:auto"'
               . $ck . '> 启用</label>';
        } elseif ($meta['type'] === 'secret') {
            $has = ($val !== '' && $val !== null);
            echo '<input type="password" name="' . adm_h($formKey) . '" value="" autocomplete="new-password"'
               . ' placeholder="' . ($has ? '已设置，留空则不修改' : '未设置') . '">';
        } elseif ($meta['type'] === 'int') {
            echo '<input type="number" step="1" name="' . adm_h($formKey) . '" value="' . adm_h($val) . '">';
        } else {
            echo '<input type="text" name="' . adm_h($formKey) . '" value="' . adm_h($val) . '">';
        }
        if (!empty($meta['hint'])) echo '<div class="hint">' . adm_h($meta['hint']) . '</div>';
        echo '</div>';
    }
    echo '</div></div>';
}

echo '<div class="bar"><button class="btn pri" type="submit">保存配置</button>';
echo '<span class="who">保存时会先备份为 setting.php.bak</span></div>';
echo '</form>';
