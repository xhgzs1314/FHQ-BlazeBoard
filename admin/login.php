<?php

/** 后台登录 */
define('FHQ_ADMIN', 1);
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/auth.php';

adm_session_start();
if (adm_current() !== null) {
    header('Location: index.php');
    exit;
}

$err = '';
$lockLeft = adm_lock_remaining();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $got = isset($_POST['_csrf']) ? (string)$_POST['_csrf'] : '';
    if (empty($_SESSION['fhq_adm_csrf']) || !hash_equals((string)$_SESSION['fhq_adm_csrf'], $got)) {
        $err = '会话已过期，请重试';
    } elseif ($lockLeft > 0) {
        $err = '尝试次数过多，请 ' . ceil($lockLeft / 60) . ' 分钟后再试';
    } else {
        $u = isset($_POST['username']) ? (string)$_POST['username'] : '';
        $p = isset($_POST['password']) ? (string)$_POST['password'] : '';
        if ($u === '' || $p === '') {
            $err = '请填写账号和密码';
        } elseif (adm_login($u, $p)) {
            header('Location: index.php');
            exit;
        } else {
            $lockLeft = adm_lock_remaining();
            $err = $lockLeft > 0
                ? '尝试次数过多，已锁定 ' . ceil($lockLeft / 60) . ' 分钟'
                : '账号或密码错误';
        }
    }
}
$csrf = htmlspecialchars(adm_csrf(), ENT_QUOTES, 'UTF-8');
$errH = htmlspecialchars($err, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>后台登录 · 烽火棋</title>
    <style>
        :root {
            --ink: #f0e6d8;
            --gold: #e8a838;
            --bg: #14100a;
            --surface: #1e1810;
            --line: rgba(232, 168, 56, .18);
            --muted: rgba(240, 230, 216, .55);
            --bad: #ff8f8f
        }

        * {
            box-sizing: border-box
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg);
            color: var(--ink);
            font: 14px/1.6 "Microsoft YaHei", "PingFang SC", system-ui, sans-serif;
            background-image: radial-gradient(ellipse at 50% 0%, rgba(232, 168, 56, .09), transparent 60%)
        }

        .box {
            width: 340px;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 30px 26px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, .5)
        }

        h1 {
            font-size: 19px;
            margin: 0 0 4px;
            color: var(--gold);
            text-align: center
        }

        .sub {
            text-align: center;
            color: var(--muted);
            font-size: 12px;
            margin-bottom: 22px
        }

        label {
            display: block;
            font-size: 12px;
            color: var(--muted);
            margin: 0 0 5px
        }

        input {
            width: 100%;
            padding: 9px 11px;
            margin-bottom: 15px;
            border-radius: 7px;
            background: #0f0c07;
            color: var(--ink);
            border: 1px solid var(--line);
            font: 14px inherit;
            font-family: inherit
        }

        input:focus {
            outline: none;
            border-color: var(--gold)
        }

        button {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 7px;
            background: var(--gold);
            color: #241a08;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            font-family: inherit
        }

        button:hover {
            filter: brightness(1.08)
        }

        .err {
            background: rgba(255, 143, 143, .1);
            border: 1px solid rgba(255, 143, 143, .35);
            color: var(--bad);
            padding: 9px 12px;
            border-radius: 7px;
            font-size: 13px;
            margin-bottom: 16px
        }

        .back {
            display: block;
            text-align: center;
            margin-top: 18px;
            font-size: 12px;
            color: var(--muted)
        }
    </style>
</head>

<body>
    <form class="box" method="post" action="login.php">
        <h1>烽火棋 · 后台</h1>
        <div class="sub">管理员登录</div>
        <?php if ($errH !== '') echo '<div class="err">' . $errH . '</div>'; ?>
        <input type="hidden" name="_csrf" value="<?php echo $csrf; ?>">
        <label>账号</label>
        <input type="text" name="username" autocomplete="username" autofocus required>
        <label>密码</label>
        <input type="password" name="password" autocomplete="current-password" required>
        <button type="submit">登 录</button>
        <a class="back" href="/index.php">返回游戏大厅</a>
    </form>
</body>

</html>