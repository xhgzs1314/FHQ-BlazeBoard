<?php
/**
 * 后台入口与路由
 * 路由参数：p=home|table|settings|audit，t=表名，a=动作。
 */
define('FHQ_ADMIN', 1);
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/schema.php';
require __DIR__ . '/lib/engine.php';
require __DIR__ . '/lib/actions.php';
require __DIR__ . '/lib/settings.php';
require __DIR__ . '/lib/env.php';
require __DIR__ . '/lib/view.php';
require __DIR__ . '/tables.php';

adm_session_start();
adm_require_login();
adm_check_csrf();

$me = adm_current();
$page = isset($_GET['p']) ? (string)$_GET['p'] : 'home';

// 白名单路由
$routes = array('home', 'table', 'settings', 'env', 'audit');
if (!in_array($page, $routes, true)) $page = 'home';

adm_head(adm_page_title($page), $me);

switch ($page) {
    case 'table':
        require __DIR__ . '/views/table.php';
        break;
    case 'settings':
        require __DIR__ . '/views/settings.php';
        break;
    case 'env':
        require __DIR__ . '/views/env.php';
        break;
    case 'audit':
        require __DIR__ . '/views/audit.php';
        break;
    default:
        require __DIR__ . '/views/home.php';
}

adm_foot();

function adm_page_title($page)
{
    if ($page === 'table') {
        $t = isset($_GET['t']) ? (string)$_GET['t'] : '';
        $all = adm_tables();
        return isset($all[$t]) ? $all[$t]['label'] : '数据表';
    }
    $map = array('home' => '总览', 'settings' => '站点配置',
                 'env' => '服务端配置', 'audit' => '操作日志');
    return isset($map[$page]) ? $map[$page] : '总览';
}
