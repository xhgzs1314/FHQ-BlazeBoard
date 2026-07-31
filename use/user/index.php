<?php
require($_SERVER['DOCUMENT_ROOT'] . '/cofd/SilentVerify.php');
SilentVerify::protect();
?>
<?php
require($_SERVER['DOCUMENT_ROOT'] . '/cofd/functions.php');
$cookie_name = generateAutoWebsiteIdentifier(true) . "_log";
$tcodelogins = isset($_COOKIE[$cookie_name]) ?
    htmlspecialchars($_COOKIE[$cookie_name], ENT_QUOTES, 'UTF-8') : 'null';
$qx_max_tmp1 = true;
if ($tcodelogins == 'null') {
    $qx_max_tmp1 = false;
}
if ($qx_max_tmp1) {
    header('Location: /');
    exit();
}
require($_SERVER['DOCUMENT_ROOT'] . '/setting.php');
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $index_array['title']; ?> 登录&注册</title>
    <style>
        :root {
            --primary: #e8a838;
            --primary-dark: #c88a2a;
            --primary-light: #f0c860;
            --secondary: #2c1810;
            --bg-main: #1a1a2e;
            --bg-card: rgba(255, 255, 255, 0.06);
            --text-light: #f0e6d8;
            --text-muted: rgba(255, 255, 255, 0.6);
            --border-glow: rgba(232, 168, 56, 0.3);
            --radius: 16px;
            --radius-sm: 10px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --shadow-glow: 0 0 30px rgba(232, 168, 56, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Inter", system-ui, -apple-system, sans-serif;
        }

        body {
            min-height: 100vh;
            background: var(--bg-main);
            background-image:
                radial-gradient(ellipse at 20% 50%, rgba(232, 168, 56, 0.05) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 50%, rgba(232, 168, 56, 0.05) 0%, transparent 60%);
            color: var(--text-light);
            overflow-x: hidden;
        }

        .login-wrapper {
            display: flex;
            min-height: 100vh;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            gap: 30px;
            align-items: center;
        }


        .brand-section {
            flex: 1;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 30px;
            min-height: 500px;
            position: relative;
        }

        .brand-section::before {
            content: "♔ ♕ ♖ ♗ ♘ ♙";
            position: absolute;
            font-size: 80px;
            opacity: 0.06;
            bottom: 20px;
            right: 0;
            letter-spacing: 10px;
            pointer-events: none;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .logo-icon {
            width: 52px;
            height: 52px;
            background: linear-gradient(145deg, var(--primary), var(--primary-dark));
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            box-shadow: 0 4px 20px rgba(232, 168, 56, 0.3);
            animation: logo-pulse 3s ease-in-out infinite;
        }

        @keyframes logo-pulse {

            0%,
            100% {
                box-shadow: 0 4px 20px rgba(232, 168, 56, 0.3);
            }

            50% {
                box-shadow: 0 4px 40px rgba(232, 168, 56, 0.5);
            }
        }

        .logo-text {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-content {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .brand-title {
            font-size: 44px;
            font-weight: 800;
            line-height: 1.1;
        }

        .brand-title .highlight {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-desc {
            font-size: 16px;
            color: var(--text-muted);
            line-height: 1.6;
            max-width: 400px;
        }

        .brand-features {
            display: flex;
            gap: 24px;
            margin-top: 10px;
        }

        .brand-features span {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            color: var(--text-muted);
            background: var(--bg-card);
            padding: 6px 14px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .brand-footer {
            font-size: 13px;
            color: var(--text-muted);
            opacity: 0.5;
            margin-top: 20px;
        }


        .form-section {
            flex: 0 0 440px;
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: var(--radius);
            padding: 40px 36px;
            border: 1px solid rgba(255, 255, 255, 0.06);
            box-shadow: var(--shadow-glow);
            max-height: 90vh;
            overflow-y: auto;
        }

        .form-section::-webkit-scrollbar {
            width: 4px;
        }

        .form-section::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }

        .form-header {
            margin-bottom: 28px;
        }

        .form-title {
            font-size: 26px;
            font-weight: 700;
        }

        .form-title .emoji {
            font-size: 28px;
            margin-right: 6px;
        }

        .form-subtitle {
            font-size: 14px;
            color: var(--text-muted);
            margin-top: 4px;
        }


        .tab-nav {
            display: flex;
            gap: 6px;
            margin-bottom: 28px;
            background: rgba(255, 255, 255, 0.04);
            border-radius: var(--radius-sm);
            padding: 4px;
            border: 1px solid rgba(255, 255, 255, 0.04);
        }

        .tab-item {
            flex: 1;
            text-align: center;
            padding: 10px 0;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            border-radius: 8px;
            transition: var(--transition);
            border: none;
            background: transparent;
        }

        .tab-item.active {
            background: linear-gradient(145deg, var(--primary), var(--primary-dark));
            color: #1a1a2e;
            box-shadow: 0 4px 15px rgba(232, 168, 56, 0.3);
        }

        .tab-item:hover:not(.active) {
            color: var(--text-light);
            background: rgba(255, 255, 255, 0.05);
        }


        .form-panel {
            display: none;
            animation: slideUp 0.35s ease-out;
        }

        .form-panel.active {
            display: block;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-item {
            margin-bottom: 18px;
        }

        .form-item label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .form-item input {
            width: 100%;
            height: 46px;
            padding: 0 16px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: var(--radius-sm);
            font-size: 15px;
            color: var(--text-light);
            transition: var(--transition);
            outline: none;
        }

        .form-item input:focus {
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 3px rgba(232, 168, 56, 0.1);
        }

        .form-item input::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        .form-item input:-webkit-autofill {
            -webkit-box-shadow: 0 0 0 30px #1a1a2e inset !important;
            -webkit-text-fill-color: var(--text-light) !important;
        }

        .captcha-wrap {
            display: flex;
            gap: 12px;
        }

        .captcha-input {
            flex: 1;
        }

        .captcha-img {
            width: 110px;
            height: 46px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 6px;
            color: var(--primary-light);
            cursor: pointer;
            user-select: none;
            transition: var(--transition);
            font-family: "Courier New", monospace;
            position: relative;
        }

        .captcha-img:hover {
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.08);
            transform: scale(1.02);
        }

        .captcha-img .refresh-hint {
            position: absolute;
            bottom: 2px;
            right: 6px;
            font-size: 10px;
            opacity: 0.2;
            letter-spacing: 0;
        }

        .submit-btn {
            width: 100%;
            height: 50px;
            background: linear-gradient(145deg, var(--primary), var(--primary-dark));
            border: none;
            border-radius: var(--radius-sm);
            font-size: 16px;
            font-weight: 700;
            color: #1a1a2e;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 6px;
            position: relative;
            overflow: hidden;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(232, 168, 56, 0.3);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .submit-btn .icon {
            margin-right: 6px;
        }


        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
        }

        .modal.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background: #1e1e36;
            border-radius: var(--radius);
            border: 1px solid rgba(255, 255, 255, 0.06);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            width: 90%;
            max-width: 420px;
            padding: 28px 32px;
            transform: translateY(-20px) scale(0.96);
            transition: var(--transition);
        }

        .modal.active .modal-content {
            transform: translateY(0) scale(1);
        }

        .alert-modal .modal-header {
            margin-bottom: 12px;
        }

        .alert-modal .modal-title {
            font-size: 18px;
            font-weight: 600;
        }

        .alert-modal .modal-body {
            font-size: 15px;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .alert-modal .modal-footer {
            display: flex;
            justify-content: flex-end;
        }

        .data-modal .modal-header {
            margin-bottom: 16px;
        }

        .data-modal .modal-title {
            font-size: 18px;
            font-weight: 600;
        }

        .data-modal .modal-body {
            margin-bottom: 20px;
            max-height: 50vh;
            overflow-y: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .data-table th,
        .data-table td {
            padding: 10px 14px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .data-table th {
            width: 35%;
            color: var(--text-muted);
            font-weight: 500;
        }

        .data-table td {
            color: var(--text-light);
            word-break: break-all;
        }

        .data-table tr:last-child td,
        .data-table tr:last-child th {
            border-bottom: none;
        }

        .data-modal .modal-content {
            max-width: 480px;
            max-height: 80vh;
            padding: 28px 32px;
        }

        .modal-btn {
            padding: 8px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
        }

        .primary-btn {
            background: linear-gradient(145deg, var(--primary), var(--primary-dark));
            color: #1a1a2e;
        }

        .primary-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(232, 168, 56, 0.3);
        }


        @media (max-width: 992px) {
            .login-wrapper {
                flex-direction: column;
                padding: 16px;
                gap: 20px;
            }

            .brand-section {
                flex: none;
                padding: 30px 24px;
                min-height: auto;
                text-align: center;
                align-items: center;
            }

            .brand-section::before {
                font-size: 50px;
                bottom: 10px;
                right: 10px;
            }

            .brand-title {
                font-size: 32px;
            }

            .brand-desc {
                max-width: 100%;
            }

            .brand-features {
                flex-wrap: wrap;
                justify-content: center;
            }

            .form-section {
                flex: none;
                width: 100%;
                max-width: 440px;
                padding: 28px 24px;
                margin: 0 auto;
            }
        }

        @media (max-width: 480px) {
            .brand-section {
                padding: 20px 16px;
            }

            .brand-title {
                font-size: 26px;
            }

            .logo-text {
                font-size: 22px;
            }

            .form-section {
                padding: 20px 16px;
            }

            .form-title {
                font-size: 22px;
            }

            .captcha-img {
                width: 90px;
                font-size: 18px;
                letter-spacing: 4px;
            }

            .tab-item {
                font-size: 13px;
                padding: 8px 0;
            }

            .modal-content {
                padding: 20px 18px;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="login-wrapper">

        <div class="brand-section">
            <div class="brand-header">
                <div class="brand-logo">
                    <div class="logo-text"><?php echo $index_array['title'] ?></div>
                </div>
            </div>

            <div class="brand-content">
                <h1 class="brand-title">
                    落子无悔<br>胜负在此一举
                </h1>
                <p class="brand-desc">
                    纵横棋盘，智取天下，<br>每一步皆见锋芒
                </p>
            </div>

            <div class="brand-footer">
                © 2026 <?php echo $index_array['title']; ?>. All rights reserved.
            </div>
        </div>


        <div class="form-section">
            <div class="form-header">
                <h2 class="form-title">欢迎回来</h2>
                <p class="form-subtitle">请登录您的账号，来一把↑对弈</p>
            </div>


            <div class="tab-nav active-login">
                <div class="tab-item active" data-tab="login">登录</div>
                <div class="tab-item" data-tab="register">注册</div>
                <div class="tab-item" data-tab="retrieve">找回</div>
            </div>


            <div class="form-panel active" id="login-panel">
                <div class="form-item">
                    <label for="auth-code">身份验证码</label>
                    <input type="text" id="auth-code" placeholder="请输入您的身份验证码">
                </div>
                <div class="form-item">
                    <label>图形验证码</label>
                    <div class="captcha-wrap">
                        <input type="text" class="captcha-input" id="login-captcha" placeholder="请输入4位验证码">
                        <div class="captcha-img" id="login-captcha-img"></div>
                    </div>
                </div>
                <button class="submit-btn" id="login-btn">登录</button>
            </div>


            <div class="form-panel" id="register-panel">
                <div class="form-item">
                    <label for="username">用户名</label>
                    <input type="text" id="username" placeholder="请输入6-20位用户名（字母/数字/下划线）">
                </div>
                <div class="form-item">
                    <label for="password">密码</label>
                    <input type="text" id="password" placeholder="请输入8-12位密码（字母/数字/下划线）">
                </div>
                <div class="form-item">
                    <label for="emailbd">邮箱</label>
                    <input type="text" id="emailbd" placeholder="请输入您的邮箱(xxx@xxx.com)">
                </div>
                <div class="form-item">
                    <label>图形验证码</label>
                    <div class="captcha-wrap">
                        <input type="text" class="captcha-input" id="register-captcha" placeholder="请输入4位验证码">
                        <div class="captcha-img" id="register-captcha-img"></div>
                    </div>
                </div>
                <button class="submit-btn" id="register-btn">注册</button>
            </div>


            <div class="form-panel" id="retrieve-panel">
                <div class="form-item">
                    <label for="retrieve-username">用户名</label>
                    <input type="text" id="retrieve-username" placeholder="请输入您的用户名">
                </div>
                <div class="form-item">
                    <label for="retrieve-password">密码</label>
                    <input type="text" id="retrieve-password" placeholder="请输入您的密码">
                </div>
                <div class="form-item">
                    <label for="emailbd2">邮箱</label>
                    <input type="text" id="emailbd2" placeholder="请输入您的邮箱(xxx@xxx.com)">
                </div>
                <div class="form-item">
                    <label>图形验证码</label>
                    <div class="captcha-wrap">
                        <input type="text" class="captcha-input" id="retrieve-captcha" placeholder="请输入4位验证码">
                        <div class="captcha-img" id="retrieve-captcha-img"></div>
                    </div>
                </div>
                <button class="submit-btn" id="retrieve-btn">找回</button>
            </div>
        </div>
    </div>
    <div class="modal alert-modal" id="alertModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">提示</h3>
            </div>
            <div class="modal-body" id="alertModalContent">
            </div>
            <div class="modal-footer">
                <button class="modal-btn primary-btn" id="alertModalConfirm">确定</button>
            </div>
        </div>
    </div>


    <div class="modal data-modal" id="dataModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">操作成功！以下是账号信息(请妥善保管好账号数据)</h3>
            </div>
            <div class="modal-body">
                <table class="data-table" id="dataTable">
                    <thead>
                        <tr>
                            <th>信息</th>
                            <th>值</th>
                        </tr>
                    </thead>
                    <tbody id="dataTableBody">
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button class="modal-btn primary-btn" id="dataModalConfirm">关闭</button>
            </div>
        </div>
    </div>
    <script>
        function showAlert(message) {
            const modal = document.getElementById('alertModal');
            const content = document.getElementById('alertModalContent');
            content.textContent = message;
            modal.classList.add('active');
            const confirmBtn = document.getElementById('alertModalConfirm');
            confirmBtn.onclick = function() {
                modal.classList.remove('active');
            };
            modal.onclick = function(e) {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            };
        }

        function showDataModal(...dataList) {
            const modal = document.getElementById('dataModal');
            const tableBody = document.getElementById('dataTableBody');
            tableBody.innerHTML = '';
            dataList.forEach(item => {
                if (Array.isArray(item) && item.length === 2) {
                    const tr = document.createElement('tr');
                    const thTd = document.createElement('td');
                    const valTd = document.createElement('td');
                    thTd.textContent = item[0];
                    valTd.textContent = item[1];
                    tr.appendChild(thTd);
                    tr.appendChild(valTd);
                    tableBody.appendChild(tr);
                }
            });
            modal.classList.add('active');
            const confirmBtn = document.getElementById('dataModalConfirm');
            confirmBtn.onclick = function() {
                modal.classList.remove('active');
            };


            modal.onclick = function(e) {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            };
        }
    </script>
    <script src="../../assets/console.js"></script>
    <script src="../../assets/authwrite.js"></script>
    <script src="../../assets/YHMOLKFETCH_SDK.js"></script>
    <script>
        console.log = function() {};
        console.info = function() {};
        console.warn = function() {};
        console.error = function() {};
        const newfuckingao = new ConsoleDetector();
        newfuckingao.startDetection();
        sessionStorage.removeItem('kicked_out');
        const newcontroler = new tmdbaseauthdownyho();
        const newcontroler2 = new tmdbaseauthdownyho(60000 * 60 * 2);
        const tabNav = document.querySelector('.tab-nav');
        const tabItems = document.querySelectorAll('.tab-item');
        const formPanels = document.querySelectorAll('.form-panel');
        tabItems.forEach(item => {
            item.addEventListener('click', () => {
                tabItems.forEach(tab => tab.classList.remove('active'));
                formPanels.forEach(panel => panel.classList.remove('active'));
                item.classList.add('active');
                const tabId = item.dataset.tab;
                document.getElementById(`${tabId}-panel`).classList.add('active');
                tabNav.className = 'tab-nav';
                tabNav.classList.add(`active-${tabId}`);
                const formTitle = document.querySelector('.form-title');
                const formSubtitle = document.querySelector('.form-subtitle');
                if (tabId === 'login') {
                    formTitle.textContent = '欢迎回来';
                    formSubtitle.textContent = '请登录您的账号 来把对弈';
                } else if (tabId === 'register') {
                    formTitle.textContent = '创建账号';
                    formSubtitle.textContent = '注册新账号 纵横棋局';
                } else if (tabId === 'retrieve') {
                    formTitle.textContent = '找回账号';
                    formSubtitle.textContent = '验证账号信息 重返战场';
                }
            });
        });

        function generateCaptcha(elementId) {
            const captchaEl = document.getElementById(elementId);
            const chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz';
            let captchaCode = '';
            for (let i = 0; i < 4; i++) {
                captchaCode += chars[Math.floor(Math.random() * chars.length)];
            }
            captchaEl.textContent = captchaCode;
            captchaEl.setAttribute('data-code', captchaCode);
            const inputId = elementId.replace('-img', '');
            document.getElementById(inputId).value = '';
        }
        generateCaptcha('login-captcha-img');
        generateCaptcha('register-captcha-img');
        generateCaptcha('retrieve-captcha-img');
        document.getElementById('login-captcha-img').addEventListener('click', () => {
            generateCaptcha('login-captcha-img');
        });
        document.getElementById('register-captcha-img').addEventListener('click', () => {
            generateCaptcha('register-captcha-img');
        });
        document.getElementById('retrieve-captcha-img').addEventListener('click', () => {
            generateCaptcha('retrieve-captcha-img');
        });


        document.getElementById('login-btn').addEventListener('click', () => {
            const authCode = document.getElementById('auth-code').value.trim();
            const inputCaptcha = document.getElementById('login-captcha').value.trim().toUpperCase();
            const realCaptcha = document.getElementById('login-captcha-img').getAttribute('data-code').toUpperCase();

            if (!authCode) {
                showAlert('请输入身份验证代码！');
                generateCaptcha('login-captcha-img');
                document.getElementById('auth-code').focus();
                return;
            }
            if (!inputCaptcha) {
                showAlert('请输入图形验证码！');
                generateCaptcha('login-captcha-img');
                document.getElementById('login-captcha').focus();
                return;
            }
            if (inputCaptcha !== realCaptcha) {
                showAlert('图形验证码错误，请重新输入！');
                generateCaptcha('login-captcha-img');
                document.getElementById('login-captcha').focus();
                return;
            }

            async function sendRequest() {
                try {
                    const xnewdata = await newcontroler.writenewwords(authCode);
                    const xnewdata2 = await newcontroler2.writenewwords(authCode);
                    const result = await yhmolk_fetchpull('logs/getlog.php', {
                        authdata: xnewdata,
                        authdata2: xnewdata2
                    });
                    if (result.status === 200) {
                        showAlert('登录成功！本页面将在3秒后关闭...');
                        setTimeout(() => {
                            location.href = '../../';
                        }, 3000);
                    } else {
                        showAlert(result.message);
                        generateCaptcha('login-captcha-img');
                    }
                } catch (error) {
                    generateCaptcha('login-captcha-img');
                    showAlert('请求失败，请稍后重试！');
                }
            }
            sendRequest();
        });
        document.getElementById('register-btn').addEventListener('click', () => {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            const emailget = document.getElementById('emailbd').value.trim();
            const inputCaptcha = document.getElementById('register-captcha').value.trim().toUpperCase();
            const realCaptcha = document.getElementById('register-captcha-img').getAttribute('data-code').toUpperCase();
            const usernameReg = /^[a-zA-Z0-9_]{6,20}$/;
            const passwordReg = /^[a-zA-Z0-9_]{8,12}$/;
            const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/;
            if (!username) {
                showAlert('请输入用户名！');
                generateCaptcha('register-captcha-img');
                document.getElementById('username').focus();
                return;
            }
            if (!password) {
                showAlert('请输入密码！');
                generateCaptcha('register-captcha-img');
                document.getElementById('password').focus();
                return;
            }
            if (!emailget) {
                showAlert('请输入邮箱！');
                generateCaptcha('register-captcha-img');
                document.getElementById('emailbd').focus();
                return;
            }
            if (!usernameReg.test(username)) {
                showAlert('用户名需为6-20位字母、数字或下划线！');
                generateCaptcha('register-captcha-img');
                document.getElementById('username').focus();
                return;
            }
            if (!passwordReg.test(password)) {
                showAlert('密码需为8-12位字母、数字或下划线！');
                generateCaptcha('register-captcha-img');
                document.getElementById('password').focus();
                return;
            }
            if (!emailPattern.test(emailget)) {
                showAlert('邮箱格式错误！');
                generateCaptcha('register-captcha-img');
                document.getElementById('emailbd').focus();
                return;
            }
            if (!inputCaptcha) {
                showAlert('请输入图形验证码！');
                generateCaptcha('register-captcha-img');
                document.getElementById('register-captcha').focus();
                return;
            }
            if (inputCaptcha !== realCaptcha) {
                showAlert('图形验证码错误，请重新输入！');
                generateCaptcha('register-captcha-img');
                document.getElementById('register-captcha').focus();
                return;
            }
            async function sendRequest() {
                try {
                    const xnewdata = await newcontroler.writenewwords(username);
                    const result = await yhmolk_fetchpull('logs/getreg.php', {
                        password: password,
                        email: emailget,
                        authdata: xnewdata
                    });
                    if (result.status === 200) {
                        showDataModal(
                            ['身份验证码', result.data['usercode']],
                            ['用户名', username],
                            ['密码', password],
                            ['注册时间', result.data['regtime']],
                            ['昵称', result.data['nickname']]
                        );
                        generateCaptcha('register-captcha-img');
                    } else {
                        showAlert(result.message);
                        generateCaptcha('register-captcha-img');
                    }
                } catch (error) {
                    generateCaptcha('register-captcha-img');
                    showAlert('注册请求失败，请稍后重试！');
                }
            }
            sendRequest();
        });
        document.getElementById('retrieve-btn').addEventListener('click', () => {
            const username = document.getElementById('retrieve-username').value.trim();
            const password = document.getElementById('retrieve-password').value.trim();
            const emailget = document.getElementById('emailbd2').value.trim();
            const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/;
            const inputCaptcha = document.getElementById('retrieve-captcha').value.trim().toUpperCase();
            const realCaptcha = document.getElementById('retrieve-captcha-img').getAttribute('data-code').toUpperCase();
            if (!username) {
                showAlert('请输入用户名！');
                generateCaptcha('retrieve-captcha-img');
                document.getElementById('retrieve-username').focus();
                return;
            }
            if (!password) {
                showAlert('请输入密码！');
                generateCaptcha('retrieve-captcha-img');
                document.getElementById('retrieve-password').focus();
                return;
            }
            if (!emailget) {
                showAlert('请输入邮箱！');
                generateCaptcha('retrieve-captcha-img');
                document.getElementById('emailbd2').focus();
                return;
            }
            if (!emailPattern.test(emailget)) {
                showAlert('邮箱格式错误！');
                generateCaptcha('retrieve-captcha-img');
                document.getElementById('emailbd2').focus();
                return;
            }
            if (!inputCaptcha) {
                showAlert('请输入图形验证码！');
                generateCaptcha('retrieve-captcha-img');
                document.getElementById('retrieve-captcha').focus();
                return;
            }
            if (inputCaptcha !== realCaptcha) {
                showAlert('图形验证码错误，请重新输入！');
                generateCaptcha('retrieve-captcha-img');
                document.getElementById('retrieve-captcha').focus();
                return;
            }

            async function sendRequest() {
                try {
                    const xnewdata = await newcontroler.writenewwords(username);
                    const result = await yhmolk_fetchpull('logs/getretrieve.php', {
                        password: password,
                        authdata: xnewdata,
                        email: emailget
                    });
                    if (result.status === 200) {
                        showDataModal(
                            ['身份验证码', result.data['usercode']],
                            ['用户名', username],
                            ['找回时间', result.data['retrievetime']],
                            ['提示', result.data['tips']]
                        );
                        generateCaptcha('retrieve-captcha-img');
                    } else {
                        showAlert(result.message);
                        generateCaptcha('retrieve-captcha-img');
                    }
                } catch (error) {
                    showAlert('找回请求失败，请稍后重试！');
                    generateCaptcha('retrieve-captcha-img');
                }
            }
            sendRequest();
        });
    </script>
</body>

</html>