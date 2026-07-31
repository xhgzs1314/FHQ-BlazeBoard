<?php
$page_title = $page_title ?? '烽火棋';
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($page_title); ?> - 烽火棋</title>
    <script src="/assets/marked.umd.js"></script>
    <link rel="stylesheet" href="/assets/fontawe/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --ink: #ece2cd;
            --ink-soft: #bcab8b;
            --gold: #e8c56e;
            --gold-deep: #c99a3f;
            --line: rgba(232, 197, 110, 0.16);
            --surface: linear-gradient(158deg, rgba(62, 47, 28, 0.52), rgba(41, 30, 18, 0.62));
            --elev: 0 14px 44px rgba(0, 0, 0, 0.34), inset 0 1px 0 rgba(255, 236, 193, 0.06);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background:
                repeating-linear-gradient(0deg, rgba(210, 180, 140, 0.04) 0px, rgba(210, 180, 140, 0.04) 1px, transparent 1px, transparent 20px),
                repeating-linear-gradient(90deg, rgba(210, 180, 140, 0.04) 0px, rgba(210, 180, 140, 0.04) 1px, transparent 1px, transparent 20px),
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160'%3E%3Ctext x='40' y='100' font-size='52' font-family='Apple Symbols, serif' fill='rgba(210,180,140,0.06)'%3E♚%3C/text%3E%3C/svg%3E") 0 0 / 160px 160px repeat,
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160'%3E%3Ctext x='40' y='100' font-size='52' font-family='Apple Symbols, serif' fill='rgba(210,180,140,0.06)'%3E♛%3C/text%3E%3C/svg%3E") 80px 0 / 160px 160px repeat,
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160'%3E%3Ctext x='40' y='100' font-size='52' font-family='Apple Symbols, serif' fill='rgba(210,180,140,0.06)'%3E♜%3C/text%3E%3C/svg%3E") 0 80px / 160px 160px repeat,
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160'%3E%3Ctext x='40' y='100' font-size='52' font-family='Apple Symbols, serif' fill='rgba(210,180,140,0.06)'%3E♞%3C/text%3E%3C/svg%3E") 80px 80px / 160px 160px repeat,
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160'%3E%3Ctext x='40' y='100' font-size='52' font-family='Apple Symbols, serif' fill='rgba(210,180,140,0.06)'%3E♟%3C/text%3E%3C/svg%3E") 0 0 / 160px 160px repeat,
                radial-gradient(1100px 560px at 50% -12%, #4a3820 0%, rgba(74, 56, 32, 0) 60%),
                radial-gradient(900px 700px at 100% 8%, rgba(90, 70, 40, 0.18) 0%, transparent 55%),
                linear-gradient(180deg, #271c11 0%, #1c140c 56%, #231910 100%);
            background-blend-mode: normal, normal, normal, normal, normal, normal, normal, normal;
            color: var(--ink);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .top-bar {
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(30, 21, 12, 0.5);
            border-bottom: 1px solid var(--line);
            backdrop-filter: blur(10px);
            font-size: 24px;
            font-weight: 600;
            letter-spacing: 6px;
            color: var(--gold);
            flex-shrink: 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .main-content {
            flex: 1;
            overflow-y: auto;
            position: relative;
            padding: 16px;
            margin: 0 auto;
            width: 100%;
        }

        @media (min-width: 600px) {
            .main-content {
                padding: 24px 20px;
            }
        }

        .decorations {
            text-align: center;
            font-size: 19px;
            margin-bottom: 14px;
            color: var(--gold);
            opacity: 0.42;
            letter-spacing: 14px;
        }

        .game-title {
            text-align: center;
            margin-bottom: 24px;
        }

        .game-title h1 {
            font-size: 38px;
            font-weight: 800;
            background: linear-gradient(180deg, #fbe6b4 0%, #d9ab54 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 4px 24px rgba(232, 197, 110, 0.28);
            letter-spacing: 8px;
        }

        .game-title .subtitle {
            font-size: 13px;
            color: var(--ink-soft);
            margin-top: 6px;
            letter-spacing: 3px;
        }

        .card-container {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 22px;
            backdrop-filter: blur(10px);
            box-shadow: var(--elev);
        }

        .btn {
            padding: 14px 16px;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #fff;
            position: relative;
            overflow: hidden;
            text-decoration: none;
        }

        .btn:active {
            transform: scale(0.96);
        }

        .btn-primary {
            background: linear-gradient(135deg, #f0d488 0%, #c99a3f 100%);
            color: #2a1e0c;
            box-shadow: 0 6px 18px rgba(201, 154, 63, 0.32);
        }

        .btn-primary:hover {
            box-shadow: 0 10px 26px rgba(232, 197, 110, 0.42);
            transform: translateY(-2px);
        }

        .btn-success {
            background: linear-gradient(135deg, #4a8c4a 0%, #2d6a2d 100%);
            box-shadow: 0 4px 16px rgba(74, 140, 74, 0.3);
        }

        .btn-success:hover {
            box-shadow: 0 6px 20px rgba(74, 140, 74, 0.5);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #5a6a8a 0%, #3a4a6a 100%);
            box-shadow: 0 4px 16px rgba(90, 106, 138, 0.3);
        }

        .btn-secondary:hover {
            box-shadow: 0 6px 20px rgba(90, 106, 138, 0.5);
            transform: translateY(-1px);
        }

        .btn-outline {
            background: rgba(236, 226, 205, 0.03);
            border: 1px solid rgba(232, 197, 110, 0.28);
            color: var(--gold);
        }

        .btn-outline:hover {
            background: rgba(232, 197, 110, 0.1);
            border-color: rgba(232, 197, 110, 0.5);
            transform: translateY(-2px);
        }

        .btn-danger {
            background: linear-gradient(135deg, #8c4a4a 0%, #6a2d2d 100%);
            box-shadow: 0 4px 16px rgba(140, 74, 74, 0.3);
        }

        .btn-danger:hover {
            box-shadow: 0 6px 20px rgba(140, 74, 74, 0.5);
            transform: translateY(-1px);
        }

        .btn-gold {
            background: linear-gradient(135deg, #c9a84c 0%, #a08030 100%);
            color: #1a1208;
            box-shadow: 0 4px 16px rgba(201, 168, 76, 0.3);
        }

        .btn-gold:hover {
            box-shadow: 0 6px 20px rgba(201, 168, 76, 0.5);
            transform: translateY(-2px);
        }

        .button-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .button-grid .btn {
            width: 100%;
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s;
            padding: 20px;
        }

        .modal-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-box {
            background: linear-gradient(168deg, #35271588, #1f160c 88%), linear-gradient(180deg, #2d1f0e, #1a1208);
            border: 1px solid rgba(232, 197, 110, 0.24);
            border-radius: 20px;
            padding: 24px;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 24px 70px rgba(0, 0, 0, 0.55), inset 0 1px 0 rgba(255, 236, 193, 0.07);
            transform: scale(0.92);
            transition: transform 0.3s;
        }

        .modal-overlay.active .modal-box {
            transform: scale(1);
        }

        .modal-title {
            font-size: 18px;
            font-weight: 700;
            color: #f0d080;
            margin-bottom: 12px;
            text-align: center;
        }

        .modal-content {
            color: #d0c0a0;
            font-size: 14px;
            line-height: 1.7;
            max-height: 60vh;
            overflow-y: auto;
        }

        .modal-content h1,
        .modal-content h2,
        .modal-content h3 {
            color: #f0d080;
            margin: 16px 0 8px;
        }

        .modal-content p {
            margin: 8px 0;
        }

        .modal-content ul {
            padding-left: 20px;
            margin: 8px 0;
        }

        .modal-content li {
            margin: 4px 0;
        }

        .modal-content code {
            background: rgba(240, 208, 128, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 13px;
        }

        .modal-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            font-size: 13px;
        }

        .modal-content th,
        .modal-content td {
            border: 1px solid rgba(240, 208, 128, 0.15);
            padding: 8px;
            text-align: left;
        }

        .modal-content th {
            background: rgba(240, 208, 128, 0.1);
            color: #f0d080;
        }

        .modal-btn {
            margin-top: 16px;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #c9a84c, #a08030);
            color: #1a1208;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .modal-btn:hover {
            box-shadow: 0 4px 16px rgba(201, 168, 76, 0.4);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--gold);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 16px;
            transition: opacity 0.2s;
        }

        .back-link:hover {
            opacity: 0.7;
        }

        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(232, 197, 110, 0.22);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(232, 197, 110, 0.4);
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #a09070;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 12px;
            opacity: 0.5;
        }

        .empty-state p {
            font-size: 14px;
        }

        .tab-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }

        .tab-header h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--ink);
        }

        .tab-header h2 i {
            color: var(--gold);
        }

        .tab-header .badge {
            background: rgba(232, 197, 110, 0.16);
            color: var(--gold);
            border: 1px solid rgba(232, 197, 110, 0.22);
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .search-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
        }

        .search-bar input {
            flex: 1;
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid var(--line);
            background: rgba(20, 14, 8, 0.5);
            color: var(--ink);
            font-size: 14px;
            outline: none;
        }

        .search-bar input::placeholder {
            color: var(--ink-soft);
        }

        .search-bar input:focus {
            border-color: rgba(240, 208, 128, 0.4);
            box-shadow: 0 0 0 3px rgba(240, 208, 128, 0.1);
        }

        .lb-subtabs {
            display: flex;
            gap: 6px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .lb-subtab {
            flex: 1;
            min-width: 52px;
            padding: 8px 6px;
            border-radius: 9px;
            border: 1px solid rgba(240, 208, 128, 0.15);
            background: rgba(240, 208, 128, 0.04);
            color: #a09070;
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.18s;
        }

        .lb-subtab:hover {
            background: rgba(240, 208, 128, 0.1);
        }

        .lb-subtab.active {
            background: linear-gradient(135deg, #c9a84c, #a08030);
            color: #1a1208;
            border-color: transparent;
        }

        .lb-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            background: rgba(236, 226, 205, 0.04);
            border-radius: 14px;
            border: 1px solid rgba(232, 197, 110, 0.1);
            transition: background 0.2s, transform 0.2s;
            margin-bottom: 6px;
        }

        .lb-item:hover {
            background: rgba(232, 197, 110, 0.09);
            transform: translateY(-1px);
        }

        .lb-rank {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: 800;
            font-size: 14px;
            flex-shrink: 0;
        }

        .lb-rank.gold {
            background: linear-gradient(135deg, #ffd700, #c9a84c);
            color: #1a1208;
        }

        .lb-rank.silver {
            background: linear-gradient(135deg, #c0c0c0, #909090);
            color: #1a1208;
        }

        .lb-rank.bronze {
            background: linear-gradient(135deg, #cd7f32, #a06020);
            color: #1a1208;
        }

        .lb-rank.normal {
            background: rgba(240, 208, 128, 0.1);
            color: #a09070;
        }

        .lb-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #5a4a3a, #3a2a1a);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
            overflow: hidden;
        }

        .lb-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .lb-info {
            flex: 1;
        }

        .lb-name {
            font-weight: 600;
            font-size: 15px;
            color: var(--ink);
        }

        .lb-stats {
            font-size: 12px;
            color: var(--ink-soft);
            margin-top: 2px;
        }

        .lb-score {
            font-weight: 800;
            font-size: 16px;
            color: var(--gold);
        }

        .lb-empty,
        .lb-loading {
            text-align: center;
            color: #a09070;
            font-size: 13px;
            padding: 30px 0;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 20px;
            background: rgba(240, 208, 128, 0.06);
            border-radius: 16px;
            border: 1px solid rgba(240, 208, 128, 0.1);
            margin-bottom: 16px;
        }

        .profile-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, #c9a84c, #a08030);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #1a1208;
            flex-shrink: 0;
        }

        .profile-info h3 {
            font-size: 20px;
            color: #f0d080;
            margin-bottom: 4px;
        }

        .profile-info p {
            font-size: 13px;
            color: #a09070;
        }

        .profile-stats {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            margin-bottom: 16px;
        }

        .stat-box {
            background: rgba(240, 208, 128, 0.05);
            border: 1px solid rgba(240, 208, 128, 0.1);
            border-radius: 12px;
            padding: 14px 8px;
            text-align: center;
        }

        .stat-box .num {
            font-size: 22px;
            font-weight: 800;
            color: #f0d080;
        }

        .stat-box .label {
            font-size: 11px;
            color: #a09070;
            margin-top: 4px;
        }

        .menu-list {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            background: rgba(240, 208, 128, 0.04);
            border-radius: 12px;
            border: 1px solid rgba(240, 208, 128, 0.08);
            cursor: pointer;
            transition: all 0.2s;
        }

        .menu-item:hover {
            background: rgba(240, 208, 128, 0.1);
        }

        .menu-item i {
            font-size: 18px;
            color: #c9a84c;
            width: 24px;
            text-align: center;
        }

        .menu-item span {
            font-size: 15px;
            color: #e0d0b0;
        }

        .menu-item .arrow {
            margin-left: auto;
            color: #a09070;
            font-size: 14px;
        }

        .text-center {
            text-align: center;
        }

        .mt-8 {
            margin-top: 8px;
        }

        .mt-16 {
            margin-top: 16px;
        }

        .mb-16 {
            margin-bottom: 16px;
        }

        .gap-8 {
            gap: 8px;
        }

        .gap-12 {
            gap: 12px;
        }

        .flex {
            display: flex;
        }

        .flex-col {
            flex-direction: column;
        }

        .items-center {
            align-items: center;
        }

        .justify-between {
            justify-content: space-between;
        }

        .w-full {
            width: 100%;
        }

        .text-gold {
            color: var(--gold);
        }

        .text-soft {
            color: var(--ink-soft);
        }
    </style>
    <?php /* 必须排在上面的内联 style 之后：档位要覆盖 body 的棋纹 background */ ?>
    <?php if (!empty($prestige)): ?>
        <link rel="stylesheet" href="/assets/profile-prestige.css?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/profile-prestige.css'); ?>">
    <?php endif; ?>
</head>

<body<?php echo !empty($prestige) ? ' data-prestige="' . htmlspecialchars($prestige, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>
    <?php /* 整页背景氛围。必须是 body 首个子元素：position:fixed + z-index:0
             才能压在 body 棋纹之上、top-bar/main-content 之下。放进
             .main-content 会被它的 overflow-y:auto 裁掉。 */ ?>
    <?php if (!empty($prestige)): ?>
        <div class="pg-backdrop" data-tier="<?php echo htmlspecialchars($prestige, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
            <div class="pg-wash"></div>
            <span class="pg-beam"></span>
            <span class="pg-beam"></span>
            <div class="pg-vignette"></div>
        </div>
    <?php endif; ?>
    <div class="top-bar">
        <div style="position: absolute; left: 16px;cursor:pointer;">
            <i id="arrow-left-backtohall" class="fa-solid fa-arrow-left fa-2x"></i>
        </div>
        <?php echo $page_title ?? '烽火棋'; ?>
    </div>
    <div class="main-content" id="mainContent">
        <script>
            document.getElementById('arrow-left-backtohall').addEventListener('click', function() {
                window.close();
                setTimeout(function() {
                    location.href = "index.php";
                }, 200);
            });

            function plugin_post_requests(data, callback, options = {}) {
                const {
                    url = '/api/',
                        timeout = 10000,
                        headers = {},
                        withCredentials = false
                } = options;
                const defaultHeaders = {
                    'Content-Type': 'application/json',
                    ...headers
                };
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), timeout);
                fetch(url, {
                        method: 'POST',
                        headers: defaultHeaders,
                        body: JSON.stringify(data),
                        credentials: withCredentials ? 'include' : 'same-origin',
                        signal: controller.signal
                    })
                    .then(async response => {
                        clearTimeout(timeoutId);

                        const contentType = response.headers.get('content-type');
                        let result;

                        if (contentType && contentType.includes('application/json')) {
                            result = await response.json();
                        } else {
                            result = await response.text();
                        }

                        if (!response.ok) {
                            throw new Error(result.message || `请求失败: ${response.status}`);
                        }

                        callback(null, result);
                    })
                    .catch(error => {
                        clearTimeout(timeoutId);

                        if (error.name === 'AbortError') {
                            callback(new Error('请求超时'), null);
                        } else {
                            callback(error, null);
                        }
                    });
            }
        </script>