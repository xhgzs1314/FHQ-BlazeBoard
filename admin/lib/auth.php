<?php
if (!defined('FHQ_ADMIN')) { http_response_code(403); exit('forbidden'); }

define('ADM_SESS_KEY', 'fhq_adm');
define('ADM_IDLE_MAX', 7200);          // 2h 无操作登出
define('ADM_TRY_MAX', 5);              // 连续失败上限
define('ADM_LOCK_SEC', 900);           // 锁定 15 分钟

function adm_session_start()
{
    if (session_status() === PHP_SESSION_NONE) {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params(array(
                'lifetime' => 0, 'path' => '/', 'httponly' => true,
                'secure' => $secure, 'samesite' => 'Lax',
            ));
        } else {
            session_set_cookie_params(0, '/', '', $secure, true);
        }
        session_start();
    }
}

function adm_client_ip()
{
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
}

/** 失败计数落文件 */
function adm_lock_file()
{
    $dir = sys_get_temp_dir() . '/fhq_admin_lock';
    if (!is_dir($dir)) @mkdir($dir, 0700, true);
    return $dir . '/' . hash('sha256', adm_client_ip()) . '.json';
}

function adm_lock_state()
{
    $f = adm_lock_file();
    if (!is_file($f)) return array('n' => 0, 'until' => 0);
    $j = json_decode((string)@file_get_contents($f), true);
    if (!is_array($j)) return array('n' => 0, 'until' => 0);
    return array('n' => isset($j['n']) ? (int)$j['n'] : 0, 'until' => isset($j['until']) ? (int)$j['until'] : 0);
}
function adm_lock_remaining()
{
    $s = adm_lock_state();
    return max(0, $s['until'] - time());
}

function adm_note_fail()
{
    $s = adm_lock_state();
    $s['n'] = $s['n'] + 1;
    if ($s['n'] >= ADM_TRY_MAX) { $s['until'] = time() + ADM_LOCK_SEC; $s['n'] = 0; }
    @file_put_contents(adm_lock_file(), json_encode($s), LOCK_EX);
}

function adm_clear_fail()
{
    @unlink(adm_lock_file());
}

function adm_login($username, $password)
{
    if (adm_lock_remaining() > 0) return false;
    $row = aq_one('SELECT id, username, password FROM mok_admin WHERE username = ? LIMIT 1',
        array((string)$username));
    // 无此用户时也跑一次 hash
    $hash = $row ? $row['password'] : '$2y$12$' . str_repeat('.', 53);
    if (!password_verify((string)$password, $hash) || !$row) {
        adm_note_fail();
        return false;
    }
    adm_clear_fail();
    session_regenerate_id(true);
    $_SESSION[ADM_SESS_KEY] = array(
        'id'   => (int)$row['id'],
        'user' => $row['username'],
        'seen' => time(),
        'ua'   => hash('sha256', isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''),
    );
    return true;
}

function adm_current()
{
    if (empty($_SESSION[ADM_SESS_KEY])) return null;
    $s = $_SESSION[ADM_SESS_KEY];
    if (time() - (int)$s['seen'] > ADM_IDLE_MAX) { adm_logout(); return null; }
    $ua = hash('sha256', isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
    if (!hash_equals((string)$s['ua'], $ua)) { adm_logout(); return null; }
    $_SESSION[ADM_SESS_KEY]['seen'] = time();
    return $s;
}
function adm_logout()
{
    unset($_SESSION[ADM_SESS_KEY]);
    // 输出已开始时无法换 ID
    if (!headers_sent()) session_regenerate_id(true);
}

function adm_require_login()
{
    if (adm_current() === null) {
        header('Location: login.php');
        exit;
    }
}

/* ---------------- CSRF ---------------- */
function adm_csrf()
{
    if (empty($_SESSION['fhq_adm_csrf'])) {
        $_SESSION['fhq_adm_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['fhq_adm_csrf'];
}

function adm_csrf_field()
{
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(adm_csrf(), ENT_QUOTES, 'UTF-8') . '">';
}


function adm_check_csrf()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    $got = isset($_POST['_csrf']) ? (string)$_POST['_csrf'] : '';
    if (empty($_SESSION['fhq_adm_csrf']) || !hash_equals((string)$_SESSION['fhq_adm_csrf'], $got)) {
        http_response_code(419);
        exit('CSRF 校验失败，请返回重试');
    }
}


