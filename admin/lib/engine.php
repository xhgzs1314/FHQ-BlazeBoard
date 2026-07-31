<?php
/**
 * 通用 CRUD 引擎：列表 / 搜索 / 分页 / 排序 / 编辑 / 删除
 */
if (!defined('FHQ_ADMIN')) { http_response_code(403); exit('forbidden'); }

define('ADM_PAGE_SIZE', 25);

function adm_h($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** 列表查询 */
function adm_list($cfg, $q)
{
    $table = $cfg['table'];
    $cols  = $cfg['cols'];

    $page = isset($q['page']) ? max(1, (int)$q['page']) : 1;
    $kw   = isset($q['kw']) ? trim((string)$q['kw']) : '';

    // 排序列必须是本表真实列
    $sort = isset($q['sort']) && isset($cols[$q['sort']]) ? $q['sort'] : null;
    $dir  = (isset($q['dir']) && strtolower($q['dir']) === 'asc') ? 'ASC' : 'DESC';
    if ($sort !== null) {
        $orderSql = qi($sort) . ' ' . $dir;
    } else {
        $orderSql = adm_default_order($cfg);
    }

    // 搜索：仅在配置声明过的列上做 LIKE
    $where = '';
    $params = array();
    if ($kw !== '' && !empty($cfg['search'])) {
        $parts = array();
        foreach ($cfg['search'] as $c) {
            $parts[] = qi($c) . ' LIKE ?';
            $params[] = '%' . $kw . '%';
        }
        $where = ' WHERE (' . implode(' OR ', $parts) . ')';
    }
    $total = (int)aq_val('SELECT COUNT(*) FROM ' . qi($table) . $where, $params, 0);
    $pages = max(1, (int)ceil($total / ADM_PAGE_SIZE));
    if ($page > $pages) $page = $pages;
    $offset = ($page - 1) * ADM_PAGE_SIZE;

    // 只 SELECT 列表需要的列 + 主键
    $sel = $cfg['list'];
    if (!in_array($cfg['pk'], $sel, true)) $sel[] = $cfg['pk'];
    $selSql = implode(', ', array_map('qi', $sel));

    // LIMIT/OFFSET 强制转 int
    $sql = 'SELECT ' . $selSql . ' FROM ' . qi($table) . $where
         . ' ORDER BY ' . $orderSql . ' LIMIT ' . (int)ADM_PAGE_SIZE . ' OFFSET ' . (int)$offset;

    return array(
        'rows'  => aq_all($sql, $params),
        'total' => $total,
        'page'  => $page,
        'pages' => $pages,
        'kw'    => $kw,
        'sort'  => $sort,
        'dir'   => $dir,
    );
}

/** 校验并规范化配置里的 orderBy */
function adm_default_order($cfg)
{
    $raw = isset($cfg['orderBy']) ? trim((string)$cfg['orderBy']) : '';
    if ($raw !== '' && preg_match('/^([A-Za-z0-9_]+)\s*(ASC|DESC)?$/i', $raw, $m)) {
        if (isset($cfg['cols'][$m[1]])) {
            $d = (isset($m[2]) && strtoupper($m[2]) === 'ASC') ? 'ASC' : 'DESC';
            return qi($m[1]) . ' ' . $d;
        }
    }
    return qi($cfg['pk']) . ' DESC';
}

/** 取单行 */
function adm_row($cfg, $pkValue)
{
    $sel = array();
    foreach ($cfg['cols'] as $name => $c) {
        if (in_array($name, $cfg['hide'], true)) continue;
        if ($c['control'] === 'binary') continue;
        $sel[] = $name;
    }
    if (empty($sel)) return null;
    $sql = 'SELECT ' . implode(', ', array_map('qi', $sel)) . ' FROM ' . qi($cfg['table'])
         . ' WHERE ' . qi($cfg['pk']) . ' = ? LIMIT 1';
    return aq_one($sql, array($pkValue));
}
/**
 * 列类型规范化返回 array(ok, value|error)
 */
function adm_coerce($col, $raw, $table)
{
    $s = is_string($raw) ? trim($raw) : $raw;

    // 空串：可空列写 NULL
    if (($s === '' || $s === null) && $col['nullable']) {
        return array(true, null);
    }

    switch ($col['control']) {
        case 'number':
        case 'select':
            if ($s === '' || !preg_match('/^-?\d+$/', (string)$s)) {
                return array(false, $col['label'] . '：需要整数');
            }
            if (!empty($col['enum']) && !isset($col['enum'][(string)(int)$s])) {
                return array(false, $col['label'] . '：取值不在允许范围内');
            }
            return array(true, (int)$s);

        case 'decimal':
            if (!is_numeric($s)) return array(false, $col['label'] . '：需要数字');
            return array(true, (float)$s);

        case 'datetime':
            if ($s === '') return array(false, $col['label'] . '：不能为空');
            $t = strtotime(str_replace('T', ' ', (string)$s));
            if ($t === false) return array(false, $col['label'] . '：时间格式无法解析');
            return array(true, date('Y-m-d H:i:s', $t));

        case 'date':
            if ($s === '') return array(false, $col['label'] . '：不能为空');
            $t = strtotime((string)$s);
            if ($t === false) return array(false, $col['label'] . '：日期格式无法解析');
            return array(true, date('Y-m-d', $t));

        case 'json':
            if ($s === '') return array(true, $col['nullable'] ? null : '{}');
            json_decode((string)$s);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return array(false, $col['label'] . '：JSON 格式非法（' . json_last_error_msg() . '）');
            }
            return array(true, (string)$s);

        case 'binary':
            return array(false, $col['label'] . '：二进制列不可通过后台编辑');
        default:
            $s = (string)$s;
            if ($col['maxLen'] !== null && mb_strlen($s, 'UTF-8') > $col['maxLen']) {
                return array(false, $col['label'] . '：超出长度上限 ' . $col['maxLen']);
            }
            if (!$col['nullable'] && $s === '') {
                return array(false, $col['label'] . '：不能为空');
            }
            return array(true, $s);
    }
}

/**
 * 保存编辑
 */
function adm_save($cfg, $pkValue, $post)
{
    if (empty($cfg['edit'])) return array(false, '该表为只读');

    $sets = array();
    $params = array();
    $errors = array();

    foreach ($cfg['edit'] as $name) {
        $col = $cfg['cols'][$name];
        if ($col['isPk'] || $col['autoInc']) continue;          // 主键不给改
        if (!array_key_exists($name, $post)) continue;           // 未提交则不动
        list($ok, $val) = adm_coerce($col, $post[$name], $cfg['table']);
        if (!$ok) { $errors[] = $val; continue; }
        $sets[] = qi($name) . ' = ?';
        $params[] = $val;
    }

    if (!empty($errors)) return array(false, implode('；', $errors));
    if (empty($sets)) return array(false, '没有需要保存的改动');

    $params[] = $pkValue;
    $sql = 'UPDATE ' . qi($cfg['table']) . ' SET ' . implode(', ', $sets)
         . ' WHERE ' . qi($cfg['pk']) . ' = ? LIMIT 1';
    try {
        aq_exec($sql, $params);
    } catch (Throwable $e) {
        return array(false, '写入失败：' . $e->getMessage());
    }
    adm_audit($cfg['table'], 'update', $pkValue, array_keys(array_slice($sets, 0)));
    return array(true, '已保存');
}
/** 删除单行 */
function adm_delete($cfg, $pkValue)
{
    if (empty($cfg['allowDelete'])) return array(false, '该表不允许删除');
    $sql = 'DELETE FROM ' . qi($cfg['table']) . ' WHERE ' . qi($cfg['pk']) . ' = ? LIMIT 1';
    try {
        $n = aq_exec($sql, array($pkValue));
    } catch (Throwable $e) {
        return array(false, '删除失败：' . $e->getMessage());
    }
    adm_audit($cfg['table'], 'delete', $pkValue, array());
    return array(true, $n > 0 ? '已删除' : '未找到该记录');
}

/** 审计日志 */
function adm_audit($table, $action, $pk, $fields)
{
    $me = adm_current();
    $line = json_encode(array(
        'ts'     => date('c'),
        'admin'  => $me ? $me['user'] : '?',
        'ip'     => adm_client_ip(),
        'table'  => $table,
        'action' => $action,
        'pk'     => (string)$pk,
        'fields' => $fields,
    ), JSON_UNESCAPED_UNICODE);
    $dir = __DIR__ . '/../logs';
    if (!is_dir($dir)) @mkdir($dir, 0700, true);
    @file_put_contents($dir . '/audit-' . date('Ym') . '.log', $line . "\n", FILE_APPEND | LOCK_EX);
}

/** 列表单元格渲染 */
function adm_cell($cfg, $col, $row)
{
    $name = $col['name'];
    $v = isset($row[$name]) ? $row[$name] : null;
    if ($v === null) return '<span class="nil">NULL</span>';

    $enumText = adm_enum_text($col, $v);
    if ($enumText !== null) {
        $cls = 'badge';
        if (preg_match('/正常|已完成|已验证/u', $enumText)) $cls .= ' ok';
        elseif (preg_match('/封禁|失败|注销|已删除/u', $enumText)) $cls .= ' bad';
        return '<span class="' . $cls . '">' . adm_h($enumText) . '</span>';
    }

    if (!empty($cfg['json']) && in_array($name, $cfg['json'], true)) {
        return '<code class="json" title="' . adm_h($v) . '">' . adm_h(mb_strimwidth((string)$v, 0, 48, '…', 'UTF-8')) . '</code>';
    }
    if (!empty($cfg['links'][$name])) {
        $url = str_replace('{v}', rawurlencode((string)$v), $cfg['links'][$name]);
        return '<a class="lnk" href="' . adm_h($url) . '" target="_blank" rel="noopener">' . adm_h($v) . '</a>';
    }

    if ($col['dataType'] === 'bigint' && preg_match('/size$/', $name)) {
        return '<span title="' . adm_h($v) . ' B">' . adm_h(adm_bytes((int)$v)) . '</span>';
    }

    $s = (string)$v;
    if (mb_strlen($s, 'UTF-8') > 40) {
        return '<span title="' . adm_h($s) . '">' . adm_h(mb_strimwidth($s, 0, 40, '…', 'UTF-8')) . '</span>';
    }
    return adm_h($s);
}

function adm_bytes($n)
{
    $u = array('B', 'KB', 'MB', 'GB', 'TB');
    $i = 0;
    while ($n >= 1024 && $i < count($u) - 1) { $n /= 1024; $i++; }
    return round($n, $i === 0 ? 0 : 1) . ' ' . $u[$i];
}

/** 编辑表单单个控件 */
function adm_input($col, $value, $editable)
{
    $name = adm_h($col['name']);
    $v = $value === null ? '' : (string)$value;
    $ro = $editable ? '' : ' disabled';
    $req = (!$col['nullable'] && $editable) ? ' required' : '';

    if (!$editable && $col['control'] === 'binary') {
        return '<em class="nil">二进制数据（不可编辑）</em>';
    }

    switch ($col['control']) {
        case 'select':
            $h = '<select name="' . $name . '"' . $ro . '>';
            if ($col['nullable']) $h .= '<option value="">（空）</option>';
            foreach ($col['enum'] as $k => $text) {
                $sel = ((string)$k === $v) ? ' selected' : '';
                $h .= '<option value="' . adm_h($k) . '"' . $sel . '>' . adm_h($k . ' · ' . $text) . '</option>';
            }
            return $h . '</select>';

        case 'nativeenum':
            $h = '<select name="' . $name . '"' . $ro . '>';
            if ($col['nullable']) $h .= '<option value="">（空）</option>';
            foreach ((array)$col['enum'] as $k => $text) {
                $sel = ((string)$k === $v) ? ' selected' : '';
                $h .= '<option value="' . adm_h($k) . '"' . $sel . '>' . adm_h($text) . '</option>';
            }
            return $h . '</select>';
        case 'number':
            return '<input type="number" step="1" name="' . $name . '" value="' . adm_h($v) . '"' . $ro . $req . '>';

        case 'decimal':
            return '<input type="number" step="any" name="' . $name . '" value="' . adm_h($v) . '"' . $ro . $req . '>';

        case 'datetime':
            $dv = $v !== '' ? str_replace(' ', 'T', substr($v, 0, 16)) : '';
            return '<input type="datetime-local" name="' . $name . '" value="' . adm_h($dv) . '"' . $ro . $req . '>';

        case 'date':
            return '<input type="date" name="' . $name . '" value="' . adm_h($v) . '"' . $ro . $req . '>';

        case 'json':
            $pretty = $v;
            $d = json_decode($v, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $pretty = json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
            return '<textarea name="' . $name . '" rows="8" class="mono"' . $ro . '>' . adm_h($pretty) . '</textarea>';

        case 'textarea':
            return '<textarea name="' . $name . '" rows="4"' . $ro . $req . '>' . adm_h($v) . '</textarea>';

        case 'binary':
            return '<em class="nil">二进制数据（不可编辑）</em>';

        default:
            $max = $col['maxLen'] !== null ? ' maxlength="' . (int)$col['maxLen'] . '"' : '';
            return '<input type="text" name="' . $name . '" value="' . adm_h($v) . '"' . $max . $ro . $req . '>';
    }
}