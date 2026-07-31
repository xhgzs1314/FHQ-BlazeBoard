<?php
/**
 * 元数据introspect：读 INFORMATION_SCHEMA
 */
if (!defined('FHQ_ADMIN')) { http_response_code(403); exit('forbidden'); }
function adm_parse_comment($comment)
{
    $out = array('label' => '', 'enum' => null);
    $c = trim((string)$comment);
    if ($c === '') return $out;

    $c = str_replace(array("\r\n", "\r"), "\n", $c);
    $lines = array_values(array_filter(array_map('trim', explode("\n", $c)), 'strlen'));
    // 多行式
    if (count($lines) > 1) {
        $enum = array();
        foreach (array_slice($lines, 1) as $ln) {
            if (preg_match('/^(-?\d+)\s*[-:：、.]?\s*(.+)$/u', $ln, $m)) {
                $enum[$m[1]] = trim($m[2]);
            }
        }
        $out['label'] = $lines[0];
        if (count($enum) >= 2) $out['enum'] = $enum;
        return $out;
    }

    // 单行式
    $body = $lines[0];
    if (preg_match('/^([^:：]{1,20})[:：]\s*(.+)$/u', $body, $m)) {
        $maybeLabel = trim($m[1]);
        $rest = trim($m[2]);
        if (preg_match('/-?\d+\s*[-:：]/u', $rest)) {
            $out['label'] = $maybeLabel;
            $body = $rest;
        }
    }
    // "1-排位 0-休闲" / "0上传中 1已完成"
    if (preg_match_all('/(-?\d+)\s*[-:：]\s*([^\s,，;；]+)/u', $body, $ms, PREG_SET_ORDER)) {
        if (count($ms) >= 2) {
            $enum = array();
            foreach ($ms as $m) $enum[$m[1]] = $m[2];
            $out['enum'] = $enum;
            if ($out['label'] === '') {
                $head = trim(preg_replace('/(-?\d+)\s*[-:：]\s*([^\s,，;；]+)/u', '', $body));
                $out['label'] = $head !== '' ? $head : '';
            }
            return $out;
        }
    }
    if ($out['label'] === '') $out['label'] = $body;

    // 非数字枚举（如 'red-红方胜 blue-蓝方胜'）
    if (mb_strlen($out['label'], 'UTF-8') > 12 && preg_match('/^([^:：]{1,12})[:：]/u', $out['label'], $m)) {
        $out['label'] = trim($m[1]);
    }
    return $out;
}

/** 表单控件 */
function adm_control_for($dataType, $colType)
{
    switch ($dataType) {
        case 'tinyint': case 'smallint': case 'mediumint': case 'int': case 'bigint':
            return 'number';
        case 'decimal': case 'float': case 'double':
            return 'decimal';
        case 'datetime': case 'timestamp':
            return 'datetime';
        case 'date':
            return 'date';
        case 'json':
            return 'json';
        case 'text': case 'mediumtext': case 'longtext':
            return 'textarea';
        case 'blob': case 'mediumblob': case 'longblob': case 'binary': case 'varbinary':
            return 'binary';
        case 'enum':
            return 'nativeenum';
        default:
            return 'text';
    }
}

/** 解析 enum */
function adm_native_enum($colType)
{
    if (!preg_match('/^enum\((.*)\)$/i', $colType, $m)) return null;
    $out = array();
    foreach (str_getcsv($m[1], ',', "'") as $v) $out[$v] = $v;
    return $out;
}
/**
 * 取一张表的列元数据
 */
function adm_schema($table)
{
    static $cache = array();
    if (isset($cache[$table])) return $cache[$table];

    $rows = aq_all(
        'SELECT COLUMN_NAME, COLUMN_COMMENT, DATA_TYPE, COLUMN_TYPE, IS_NULLABLE,
                COLUMN_DEFAULT, COLUMN_KEY, EXTRA, CHARACTER_MAXIMUM_LENGTH
           FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
          ORDER BY ORDINAL_POSITION',
        array($table)
    );

    $cols = array();
    foreach ($rows as $r) {
        $meta = adm_parse_comment($r['COLUMN_COMMENT']);
        $dt = strtolower($r['DATA_TYPE']);
        $control = adm_control_for($dt, $r['COLUMN_TYPE']);
        $enum = $meta['enum'];
        if ($enum === null && $control === 'nativeenum') $enum = adm_native_enum($r['COLUMN_TYPE']);
        $name = $r['COLUMN_NAME'];
        $cols[$name] = array(
            'name'     => $name,
            'label'    => $meta['label'] !== '' ? $meta['label'] : $name,
            'dataType' => $dt,
            'colType'  => $r['COLUMN_TYPE'],
            'control'  => ($enum && $control === 'number') ? 'select' : $control,
            'enum'     => $enum,
            'nullable' => ($r['IS_NULLABLE'] === 'YES'),
            'default'  => $r['COLUMN_DEFAULT'],
            'isPk'     => ($r['COLUMN_KEY'] === 'PRI'),
            'autoInc'  => (stripos($r['EXTRA'], 'auto_increment') !== false),
            'maxLen'   => $r['CHARACTER_MAXIMUM_LENGTH'] !== null ? (int)$r['CHARACTER_MAXIMUM_LENGTH'] : null,
        );
    }
    $cache[$table] = $cols;
    return $cols;
}

/** 表在库里是否真实存在 */
function adm_table_exists($table)
{
    $n = aq_val('SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', array($table), 0);
    return ((int)$n) > 0;
}

/** 文案 */
function adm_enum_text($col, $value)
{
    if (empty($col['enum'])) return null;
    $k = (string)$value;
    return isset($col['enum'][$k]) ? $col['enum'][$k] : null;
}


