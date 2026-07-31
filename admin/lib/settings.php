<?php
/**
 * 站点配置可视化编辑
 */
if (!defined('FHQ_ADMIN')) { http_response_code(403); exit('forbidden'); }

function adm_setting_path()
{
    return dirname(dirname(__DIR__)) . '/setting.php';
}

/** 字段定义*/
function adm_setting_schema()
{
    return array(
        '$index_array' => array(
            'label'  => '站点信息',
            'fields' => array(
                'title'   => array('label' => '站点标题', 'type' => 'text'),
                'beian'   => array('label' => '备案号', 'type' => 'text'),
                'keyword' => array('label' => '关键字', 'type' => 'text'),
                'call'    => array('label' => '联系邮箱', 'type' => 'text'),
            ),
        ),
        '$setting_mail_array' => array(
            'label'  => '邮件 SMTP',
            'fields' => array(
                'valid'       => array('label' => '启用邮件', 'type' => 'bool'),
                'smtp_host'   => array('label' => 'SMTP 服务器', 'type' => 'text'),
                'smtp_port'   => array('label' => '端口', 'type' => 'int'),
                'smtp_secure' => array('label' => '加密方式', 'type' => 'text', 'hint' => 'ssl 或 tls'),
                'smtp_user'   => array('label' => '邮箱账号', 'type' => 'text'),
                'smtp_pass'   => array('label' => '授权码', 'type' => 'secret'),
            ),
        ),
        '$cosforall_oss' => array(
            'label'  => '对象存储 OSS',
            'fields' => array(
                'access_key'        => array('label' => 'AccessKey', 'type' => 'text'),
                'access_key_secret' => array('label' => 'AccessKeySecret', 'type' => 'secret'),
                'bucket'            => array('label' => 'Bucket', 'type' => 'text'),
                'endpoint'          => array('label' => 'Endpoint', 'type' => 'text'),
                'region'            => array('label' => 'Region', 'type' => 'text'),
                'max_upload_size'   => array('label' => '上传上限', 'type' => 'text', 'hint' => '如 50MB'),
            ),
        ),
    );
}
/** 隔离作用域 require */
function adm_setting_read()
{
    $p = adm_setting_path();
    if (!is_file($p)) return array();
    $loader = function ($file) {
        $setting_mail_array = array();
        $index_array = array();
        $cosforall_oss = array();
        require $file;
        return array(
            '$setting_mail_array' => $setting_mail_array,
            '$index_array'        => $index_array,
            '$cosforall_oss'      => $cosforall_oss,
        );
    };
    try {
        return $loader($p);
    } catch (Throwable $e) {
        return array();
    }
}

/** 生成 全文 */
function adm_setting_render($data)
{
    $schema = adm_setting_schema();
    $out = "<?php\n";
    $out .= "/* 由后台「站点配置」生成，可手工编辑；结构需与 admin/lib/settings.php 的 schema 一致 */\n";
    foreach ($schema as $varName => $group) {
        $arr = isset($data[$varName]) ? $data[$varName] : array();
        $out .= "\n" . $varName . ' = array(   // ' . $group['label'] . "\n";
        foreach ($group['fields'] as $key => $meta) {
            $v = array_key_exists($key, $arr) ? $arr[$key] : '';
            if ($meta['type'] === 'int' || $meta['type'] === 'bool') {
                $lit = (string)(int)$v;
            } else {
                $lit = var_export((string)$v, true);
            }
            $out .= '    ' . var_export($key, true) . ' => ' . $lit . ',';
            $out .= ' // ' . $meta['label'] . "\n";
        }
        $out .= ");\n";
    }
    return $out;
}
/**
 * 保存
 */
function adm_setting_save($post)
{
    $schema = adm_setting_schema();
    $cur = adm_setting_read();
    $next = array();
    $errors = array();

    foreach ($schema as $varName => $group) {
        $next[$varName] = array();
        foreach ($group['fields'] as $key => $meta) {
            $formKey = ltrim($varName, '$') . '__' . $key;
            $raw = isset($post[$formKey]) ? $post[$formKey] : null;
            $old = isset($cur[$varName][$key]) ? $cur[$varName][$key] : '';

            if ($meta['type'] === 'bool') {
                $next[$varName][$key] = !empty($raw) ? 1 : 0;
                continue;
            }
            if ($meta['type'] === 'secret' && ($raw === null || trim((string)$raw) === '')) {
                $next[$varName][$key] = $old;            // 留空=不改
                continue;
            }
            if ($raw === null) { $next[$varName][$key] = $old; continue; }
            $val = trim((string)$raw);
            if ($meta['type'] === 'int') {
                if ($val !== '' && !preg_match('/^\d+$/', $val)) {
                    $errors[] = $meta['label'] . '：需要整数';
                    $val = (string)(int)$old;
                }
                $next[$varName][$key] = (int)$val;
            } else {
                $next[$varName][$key] = $val;
            }
        }
    }

    if (!empty($errors)) return array(false, implode('；', $errors));

    $php = adm_setting_render($next);
    $path = adm_setting_path();
    $tmp = $path . '.tmp' . getmypid();
    if (@file_put_contents($tmp, $php, LOCK_EX) === false) {
        return array(false, '无法写入临时文件，检查目录权限');
    }
    @copy($path, $path . '.bak');
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return array(false, '替换 setting.php 失败，检查文件权限');
    }
    adm_audit('setting.php', 'update', '-', array_keys($schema));
    return array(true, '站点配置已保存（旧版本存为 setting.php.bak）');
}


