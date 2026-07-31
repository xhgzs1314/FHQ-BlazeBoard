<?php
/** 通用表页：列表 / 编辑 / 动作*/
if (!defined('FHQ_ADMIN')) { http_response_code(403); exit('forbidden'); }

$tname = isset($_GET['t']) ? (string)$_GET['t'] : '';
$cfg = adm_table_cfg($tname);
if ($cfg === null) {
    adm_title('未知表');
    adm_msg('未配置或不存在的表：' . $tname, false);
    return;
}
adm_title($cfg['label']);

$mode = isset($_GET['m']) ? (string)$_GET['m'] : 'list';
$pk = isset($_GET['id']) ? (string)$_GET['id'] : null;
$flash = '';
$flashOk = true;

/* ---------- 写操作 ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $op = isset($_POST['_op']) ? (string)$_POST['_op'] : '';
    $target = isset($_POST['_id']) ? (string)$_POST['_id'] : '';

    if ($op === 'save') {
        list($flashOk, $flash) = adm_save($cfg, $target, $_POST);
        if ($flashOk) $mode = 'list';
    } elseif ($op === 'action') {
        $key = isset($_POST['_act']) ? (string)$_POST['_act'] : '';
        list($flashOk, $flash) = adm_run_action($cfg, $key, $target);
        $mode = 'list';
    }
}

adm_msg($flash, $flashOk);

/* ---------- 编辑页 ---------- */
if ($mode === 'edit' && $pk !== null) {
    $row = adm_row($cfg, $pk);
    if ($row === null) {
        adm_msg('未找到该记录', false);
    } else {
        $readonly = empty($cfg['edit']);
        echo '<form method="post" action="index.php?p=table&t=' . adm_h($tname) . '">';
        echo adm_csrf_field();
        echo '<input type="hidden" name="_op" value="save">';
        echo '<input type="hidden" name="_id" value="' . adm_h($pk) . '">';
        echo '<div class="card"><div class="sec">' . adm_h($cfg['label']) . ' · '
           . adm_h($cfg['cols'][$cfg['pk']]['label']) . ' = ' . adm_h($pk);
        if ($readonly) echo '<span class="ro">（只读表）</span>';
        echo '</div><div class="grid2">';
        foreach ($row as $name => $val) {
            $col = $cfg['cols'][$name];
            $editable = in_array($name, $cfg['edit'], true) && !$col['isPk'] && !$col['autoInc'];
            echo '<div class="field"><label>' . adm_h($col['label']);
            echo ' <span class="ro">' . adm_h($col['name']) . ' · ' . adm_h($col['colType']) . '</span>';
            if (!$editable) echo ' <span class="ro">只读</span>';
            echo '</label>';
            echo adm_input($col, $val, $editable);
            echo '</div>';
        }
        echo '</div><div class="bar" style="margin:14px 0 0">';
        if (!$readonly) echo '<button class="btn pri" type="submit">保存</button>';
        echo '<a class="btn" href="index.php?p=table&t=' . adm_h($tname) . '">返回列表</a>';
        echo '</div></div></form>';
    }
    return;
}

/* ---------- 列表页 ---------- */
$res = adm_list($cfg, $_GET);
$acts = adm_actions();
$myActs = isset($cfg['actions']) ? $cfg['actions'] : array();

echo '<div class="card">';
echo '<form class="bar" method="get" action="index.php">';
echo '<input type="hidden" name="p" value="table"><input type="hidden" name="t" value="' . adm_h($tname) . '">';
if (!empty($cfg['search'])) {
    $ph = array();
    foreach ($cfg['search'] as $c) $ph[] = $cfg['cols'][$c]['label'];
    echo '<input type="search" name="kw" value="' . adm_h($res['kw'])
       . '" placeholder="搜索 ' . adm_h(implode(' / ', $ph)) . '">';
    echo '<button class="btn" type="submit">搜索</button>';
    if ($res['kw'] !== '') {
        echo '<a class="btn" href="index.php?p=table&t=' . adm_h($tname) . '">清空</a>';
    }
}
echo '<span class="who" style="margin-left:auto">共 ' . (int)$res['total'] . ' 条</span>';
echo '</form>';

echo '<div class="scroll"><table><thead><tr>';
foreach ($cfg['list'] as $name) {
    $col = $cfg['cols'][$name];
    $nextDir = ($res['sort'] === $name && $res['dir'] === 'DESC') ? 'asc' : 'desc';
    $arrow = '';
    if ($res['sort'] === $name) $arrow = $res['dir'] === 'ASC' ? ' ↑' : ' ↓';
    $q = 'index.php?p=table&t=' . urlencode($tname) . '&sort=' . urlencode($name)
       . '&dir=' . $nextDir . ($res['kw'] !== '' ? '&kw=' . urlencode($res['kw']) : '');
    echo '<th><a href="' . adm_h($q) . '" title="' . adm_h($col['colType']) . '">'
       . adm_h($col['label']) . $arrow . '</a></th>';
}
echo '<th>操作</th></tr></thead><tbody>';
if (empty($res['rows'])) {
    echo '<tr><td colspan="' . (count($cfg['list']) + 1) . '"><span class="nil">没有数据</span></td></tr>';
}
foreach ($res['rows'] as $row) {
    $id = isset($row[$cfg['pk']]) ? (string)$row[$cfg['pk']] : '';
    echo '<tr>';
    foreach ($cfg['list'] as $name) {
        echo '<td>' . adm_cell($cfg, $cfg['cols'][$name], $row) . '</td>';
    }
    echo '<td><div class="acts">';
    if (!empty($cfg['edit'])) {
        echo '<a class="btn sm" href="index.php?p=table&t=' . adm_h($tname)
           . '&m=edit&id=' . urlencode($id) . '">编辑</a>';
    } else {
        echo '<a class="btn sm" href="index.php?p=table&t=' . adm_h($tname)
           . '&m=edit&id=' . urlencode($id) . '">查看</a>';
    }
    foreach ($myActs as $key) {
        if (!isset($acts[$key])) continue;
        $a = $acts[$key];
        $cls = 'btn sm' . (!empty($a['danger']) ? ' danger' : '');
        echo '<form method="post" action="index.php?p=table&t=' . adm_h($tname) . '" style="display:inline">';
        echo adm_csrf_field();
        echo '<input type="hidden" name="_op" value="action">';
        echo '<input type="hidden" name="_act" value="' . adm_h($key) . '">';
        echo '<input type="hidden" name="_id" value="' . adm_h($id) . '">';
        echo '<button class="' . $cls . '" type="submit" data-confirm="'
           . adm_h(isset($a['confirm']) ? $a['confirm'] : '确认执行？') . '">'
           . adm_h($a['label']) . '</button>';
        echo '</form>';
    }
    echo '</div></td></tr>';
}
echo '</tbody></table></div>';

/* 分页 */
if ($res['pages'] > 1) {
    $base = 'index.php?p=table&t=' . urlencode($tname)
          . ($res['kw'] !== '' ? '&kw=' . urlencode($res['kw']) : '')
          . ($res['sort'] !== null ? '&sort=' . urlencode($res['sort']) . '&dir=' . strtolower($res['dir']) : '');
    echo '<div class="pager">';
    if ($res['page'] > 1) echo '<a href="' . adm_h($base . '&page=' . ($res['page'] - 1)) . '">上一页</a>';
    $from = max(1, $res['page'] - 3);
    $to = min($res['pages'], $res['page'] + 3);
    if ($from > 1) echo '<a href="' . adm_h($base . '&page=1') . '">1</a><span class="gap">…</span>';
    for ($i = $from; $i <= $to; $i++) {
        if ($i === $res['page']) echo '<span class="cur">' . $i . '</span>';
        else echo '<a href="' . adm_h($base . '&page=' . $i) . '">' . $i . '</a>';
    }
    if ($to < $res['pages']) {
        echo '<span class="gap">…</span><a href="' . adm_h($base . '&page=' . $res['pages']) . '">'
           . $res['pages'] . '</a>';
    }
    if ($res['page'] < $res['pages']) echo '<a href="' . adm_h($base . '&page=' . ($res['page'] + 1)) . '">下一页</a>';
    echo '<span class="gap who">第 ' . $res['page'] . ' / ' . $res['pages'] . ' 页</span>';
    echo '</div>';
}
echo '</div>';
echo '<script>document.addEventListener("submit",function(e){'
   . 'var b=e.target.querySelector("[data-confirm]");'
   . 'if(b&&!window.confirm(b.dataset.confirm))e.preventDefault();},true);</script>';


