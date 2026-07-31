<?php
require('setting.php');
require('cofd/functions.php');
$qx_max_tmp1 = true;
$q_suname = null;
$tcodelogins = $_COOKIE[generateAutoWebsiteIdentifier((true)) . "_log"] ?? 'null';
if ($tcodelogins == 'null') {
    $qx_max_tmp1 = false;
} else {
    require('cofd/tauth.php');
    $decodeers = new TmdbaseauthdownyhoDecrypt(60000 * 60 * 2); //2h验证
    $decodeddata = $decodeers->writebacknewwords($tcodelogins);
    if (!$decodeddata) {
        $qx_max_tmp1 = false;
    }
    require_once('cofd/functions.php');
    $decodeddata2 = encrypt($decodeddata, 'D', generateAutoWebsiteIdentifier(true));
    if (!$decodeddata2) {
        $qx_max_tmp1 = false;
    }
    $tarray = explode('<:>', $decodeddata2);
    if (!isset($tarray[0]) || !isset($tarray[1]) || empty($tarray[0]) || empty($tarray[1]) || !isset($tarray[2]) || empty($tarray[2])) {
        $qx_max_tmp1 = false;
    }
    $q_suname = trim($tarray[2]);
}
$userlogin_expiretime_used = $_COOKIE['mokim_log_expire'] ?? time();
require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT'] . '/');
$dotenv->load();
$gout_api_wslinking_address =  $_ENV['WS_LINKING_ADDRESS'] ?? 'localhost:8080';
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/ticket.php');
$gout_identity_ticket = $q_suname ? make_identity_ticket((string)$q_suname) : '';
$visitor_notlogined_n = '游客NJ8048';
if (!$qx_max_tmp1) {
    $visitor_notlogined_n = getStableVisitorName();
}
function fhqReplaySign($rid, $timestamp = null)
{
    $s = $_ENV['API_SECRET_KEY_mok'] ?? getenv('API_SECRET_KEY_mok') ?: '';
    if ($s === '') {
        return '';
    }
    if ($timestamp === null) {
        $timestamp = time() + 3600;
    }
    $data = (string)$rid . '|' . (string)$timestamp;
    $signature = hash_hmac('sha256', $data, $s);
    return $signature . '|' . $timestamp;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>烽火棋 - 大冒险</title>
    <script src="assets/marked.umd.js"></script>
    <link rel="stylesheet" href="assets/fontawe/css/all.min.css">
    <link rel="stylesheet" href="assets/index2.css">
</head>

<body>

    <div class="top-bar">大厅</div>

    <div class="main-content" id="mainContent">
        <div class="tab-panel active" id="tab-play">
            <div class="decorations">
                <i class="fa-solid fa-chess-king"></i>
                <i class="fa-solid fa-chess-queen"></i>
                <i class="fa-solid fa-chess-knight"></i>
            </div>
            <div class="game-title">
                <h1>烽火棋</h1>
                <div class="subtitle">策略 · 博弈 · 智斗</div>
            </div>
            <div class="card-container">
                <div class="button-grid">
                    <button class="btn btn-danger"
                        <?php if (!$qx_max_tmp1): ?>
                        onclick="openModal('系统提示','您当前还没有登录！请先登录或开房间与好友游玩');"
                        <?php else: ?>
                        onclick="location.href='ranking.php?d=<?php echo fhqReplaySign($q_suname, $userlogin_expiretime_used); ?>';"
                        <?php endif; ?>>
                        <i class="fa-solid fa-chess-board"></i> 排位竞技
                    </button>
                    <button class="btn btn-primary" onclick="window.open('localversus.html');">
                        <i class="fa-solid fa-chess-board"></i> 本地对战
                    </button>
                    <button class="btn btn-success" onclick="location.href='tutorial.html';">
                        <i class="fa-solid fa-graduation-cap"></i> 新手教程
                    </button>
                    <button class="btn btn-custom-orange" onclick="location.href='endgame.html';">
                        <i class="fa-solid fa-puzzle-piece"></i> 残局挑战
                    </button>
                    <button class="btn btn-custom-purple" onclick="window.open('bot.html');">
                        <i class="fa-solid fa-robot"></i> 人机试炼
                    </button>
                    <button class="btn btn-custom-teal" onclick="window.open('wtpp.php')">
                        <i class="fa-solid fa-flag-checkered"></i> 精选对局
                    </button>
                </div>
            </div>
        </div>
        <div class="tab-panel" id="tab-battle">
            <div class="tab-header">
                <h2><i class="fa-solid fa-tower-broadcast"></i> 对战大厅</h2>
                <span class="badge" id="roomCount">0 个房间</span>
            </div>


            <div class="lb-subtabs" style="margin-bottom: 12px;">
                <button class="lb-subtab active" data-subtab="rooms" onclick="switchBattleSubtab('rooms', this)">
                    <i class="fa-solid fa-door-open"></i> 房间
                </button>
                <button class="lb-subtab" data-subtab="players" onclick="switchBattleSubtab('players', this)">
                    <i class="fa-solid fa-users"></i> 玩家
                    <span class="badge" id="playerCountBadge" style="background:rgba(232,197,110,0.16);color:var(--gold);border:1px solid rgba(232,197,110,0.22);padding:0 8px;border-radius:12px;font-size:11px;margin-left:4px;">0</span>
                </button>
            </div>


            <div id="battleRoomsPanel">
                <div class="search-bar">
                    <input type="text" placeholder="搜索房间号或房主..." id="roomSearch">
                    <button class="refresh-btn" onclick="refreshRooms()" title="刷新">
                        <i class="fa-solid fa-rotate"></i>
                    </button>
                </div>

                <div class="card-container">
                    <div class="button-grid" style="margin-bottom: 16px;">
                        <button class="btn btn-success" onclick="showCreateRoom()">
                            <i class="fa-solid fa-plus"></i> 创建房间
                        </button>
                        <button class="btn btn-primary" onclick="showJoinRoom()">
                            <i class="fa-solid fa-right-to-bracket"></i> 加入房间
                        </button>
                    </div>

                    <div class="room-list" id="roomList"></div>
                </div>
            </div>


            <div id="battlePlayersPanel" style="display:none;">
                <div class="search-bar">
                    <input type="text" placeholder="搜索玩家..." id="playerSearch">
                    <button class="refresh-btn" onclick="refreshPlayers()" title="刷新">
                        <i class="fa-solid fa-rotate"></i>
                    </button>
                </div>

                <div class="card-container">
                    <div class="player-list" id="playerList"></div>
                </div>
            </div>
        </div>
        <div class="tab-panel" id="tab-rank">
            <div class="tab-header">
                <h2><i class="fa-solid fa-trophy"></i> 排行榜</h2>
                <span class="badge">TOP 50</span>
            </div>
            <div class="lb-subtabs" id="lbSubtabs">
                <button class="lb-subtab active" data-board="total" onclick="switchBoard('total', this)">综合</button>
                <button class="lb-subtab" data-board="score" onclick="switchBoard('score', this)">排位分</button>
                <button class="lb-subtab" data-board="streak" onclick="switchBoard('streak', this)">连胜</button>
                <button class="lb-subtab" data-board="splus" onclick="switchBoard('splus', this)">S+</button>
            </div>
            <div class="card-container">
                <div class="leaderboard-list" id="leaderboardList"></div>
            </div>
        </div>
        <div class="tab-panel" id="tab-me">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="profile-info">
                    <h3>
                        <?php
                        if ($qx_max_tmp1) {
                            echo $_COOKIE['mokim_usergname'] ?? getStableName($q_suname);
                        } else {
                            echo $visitor_notlogined_n;
                        }
                        ?>
                    </h3>
                    <p>
                        <?php
                        if ($qx_max_tmp1) {
                            echo '您将在 ' . date('Y-m-d H:i:s', $userlogin_expiretime_used) . ' 时离开棋坛~';
                        } else {
                            echo '您当前还未登录哟~嗷了个嗷';
                        }
                        ?>
                    </p>
                </div>
            </div>



            <div class="menu-list">
                <?php if (!$qx_max_tmp1): ?>
                    <div class="menu-item" onclick="location.href='use/user/';">
                        <i class="fa-solid fa-user"></i>
                        <span>前往登录</span>
                        <i class="fa-solid fa-chevron-right arrow"></i>
                    </div>
                <?php endif; ?>
                <div class="menu-item"
                    <?php if (!$qx_max_tmp1): ?>
                    onclick="openModal('系统提示','您当前还没有登录！请先登录再查询数据');"
                    <?php else: ?>
                    onclick="window.open('use/matchs/matchrecord/');"
                    <?php endif; ?>>
                    <i class="fa-solid fa-database"></i>
                    <span>数据总览</span>
                    <i class="fa-solid fa-chevron-right arrow"></i>
                </div>
                <div class="menu-item" onclick="window.open('replay.php');">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>对局回放</span>
                    <i class="fa-solid fa-chevron-right arrow"></i>
                </div>
                <div class="menu-item" onclick="window.open('use/setting/');">
                    <i class="fa-solid fa-cog"></i>
                    <span>设置</span>
                    <i class="fa-solid fa-chevron-right arrow"></i>
                </div>
                <div class="menu-item" onclick="window.open('use/book/notice/');">
                    <i class="fa-solid fa-bullhorn"></i>
                    <span>更新公告</span>
                    <i class="fa-solid fa-chevron-right arrow"></i>
                </div>
                <div class="menu-item" onclick="window.open('use/book/fhq/');">
                    <i class="fa-solid fa-book"></i>
                    <span>玩法&竞技规则</span>
                    <i class="fa-solid fa-chevron-right arrow"></i>
                </div>
                <div class="menu-item" onclick="window.open('use/book/about/');">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>关于</span>
                    <i class="fa-solid fa-chevron-right arrow"></i>
                </div>
            </div>
        </div>

    </div>
    <nav class="bottom-nav">
        <button class="nav-item active" data-tab="tab-play" onclick="switchTab('tab-play', this)">
            <i class="fa-solid fa-chess"></i>
            <span>下棋</span>
        </button>
        <button class="nav-item" data-tab="tab-battle" onclick="switchTab('tab-battle', this)">
            <i class="fa-solid fa-chess-board"></i>
            <span>对战</span>
        </button>
        <button class="nav-item" data-tab="tab-rank" onclick="switchTab('tab-rank', this)">
            <i class="fa-solid fa-ranking-star"></i>
            <span>排行榜</span>
        </button>
        <button class="nav-item" data-tab="tab-me" onclick="switchTab('tab-me', this)">
            <i class="fa-solid fa-user"></i>
            <span>我的</span>
        </button>
    </nav>
    <div class="modal-overlay" id="myModal">
        <div class="modal-box">
            <div class="modal-title" id="modalTitle">提示</div>
            <div class="modal-content" id="modalContent">内容</div>
            <button class="modal-btn" onclick="closeModal()">好的</button>
        </div>
    </div>
    <div class="modal-overlay" id="newbieModal">
        <div class="modal-box">
            <div class="modal-title">第一次来？</div>
            <div class="modal-content">
                烽火棋是 30×30 的双人棋，有迷雾、许可区和渡河这些规则，和常见棋类不太一样。<br><br>
                你玩过烽火棋吗？
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <button class="modal-btn" style="flex:1;min-width:140px" onclick="newbieAnswer(false)">
                    没玩过，带我上手
                </button>
                <button class="modal-btn" style="flex:1;min-width:140px;background:rgba(255,255,255,.08);color:#d8ccb2"
                    onclick="newbieAnswer(true)">
                    玩过，直接进大厅
                </button>
            </div>
        </div>
    </div>

    <script>
        window.SERVER_URL = '<?php echo $gout_api_wslinking_address; ?>';
        window.IDENTITY_TICKET = '<?php echo $gout_identity_ticket; ?>';
        window.IS_LOGGED_IN = <?php echo $qx_max_tmp1 ? 'true' : 'false'; ?>;
        window.DISPLAY_NAME = '<?php echo htmlspecialchars($qx_max_tmp1 ? ($_COOKIE['mokim_usergname'] ?? getStableName($q_suname)) : $visitor_notlogined_n, ENT_QUOTES); ?>';
    </script>
    <script src="assets/core/wsmanager.js"></script>
    <link rel='stylesheet' href='assets/message/message.min.css'>
    <script src='assets/message/message.min.js'></script>
    <script>
        function switchTab(tabId, navBtn) {
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
            navBtn.classList.add('active');
            if (tabId === 'tab-rank') loadLeaderboard();
        }
        const modal = document.getElementById('myModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalContent = document.getElementById('modalContent');

        function openModal(title, content) {
            modalTitle.innerText = title;
            modalContent.innerHTML = content;
            modal.classList.add('active');
        }

        function closeModal() {
            modal.classList.remove('active');
        }
        modal.addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
        const NEWBIE_FLAG = 'fhq.newbie.asked';

        function newbieAnswer(experienced) {
            try {
                localStorage.setItem(NEWBIE_FLAG, experienced ? '1' : '0');
            } catch (e) {}
            document.getElementById('newbieModal').classList.remove('active');
            if (!experienced) location.href = 'tutorial.html';
        }
        (function askNewbie() {
            let asked = null;
            try {
                asked = localStorage.getItem(NEWBIE_FLAG);
            } catch (e) {
                return;
            }
            if (asked === null || asked === '0') document.getElementById('newbieModal').classList.add('active');
        })();
        const ROOM_ROW_H = 74;
        let allRooms = [];
        let roomFilter = '';
        let allPlayers = [];
        let playerFilter = '';
        let playersLoading = false;
        let playerListEl = document.getElementById('playerList');
        const roomListEl = document.getElementById('roomList');
        const roomInner = document.createElement('div');
        roomInner.className = 'room-vscroll-inner';
        roomListEl.appendChild(roomInner);
        const PLAYER_ROW_H = 80;
        let myPlayerId = null;
        let playersSubscribed = false;
        const playerInner = document.createElement('div');
        playerInner.className = 'player-vscroll-inner';
        if (playerListEl) playerListEl.appendChild(playerInner);

        function escapeHtml(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            } [c]));
        }

        function filteredRooms() {
            const f = roomFilter.toLowerCase();
            if (!f) return allRooms;
            return allRooms.filter(r =>
                r.id.toLowerCase().includes(f) || (r.name || '').toLowerCase().includes(f) || (r.host || '').toLowerCase().includes(f));
        }

        function roomRowHtml(r, y) {
            const canJoin = !(r.status === 'playing' && !r.spectate);
            const btnLabel = r.status === 'waiting' ? '加入' : (r.spectate ? '观战' : '满');
            const lock = r.password ? '<i class="fa-solid fa-lock" style="color:#c9a84c;margin-right:6px;font-size:12px;"></i>' : '';
            return `<div class="room-item" style="top:${y}px">
        <div class="room-info">
            <div class="room-name">${lock}${escapeHtml(r.name)}</div>
            <div class="room-meta">
                <span><i class="fa-solid fa-hashtag" style="margin-right:4px;"></i>${escapeHtml(r.id)}</span>
                <span><i class="fa-solid fa-user" style="margin-right:4px;"></i>${escapeHtml(r.host)}</span>
                <span><i class="fa-solid fa-users" style="margin-right:4px;"></i>${r.seated}/2</span>
            </div>
        </div>
        <span class="room-status ${r.status === 'waiting' ? 'status-waiting' : 'status-playing'}">${r.status === 'waiting' ? '等待中' : '对局中'}</span>
        <button class="room-join-btn" ${canJoin ? '' : 'disabled'}
            onclick="joinRoom('${escapeHtml(r.id)}',${r.password ? 'true' : 'false'})">${btnLabel}</button>
    </div>`;
        }

        function renderRooms() {
            const rooms = filteredRooms();
            document.getElementById('roomCount').innerText = rooms.length + ' 个房间';
            if (!rooms.length) {
                roomInner.style.height = 'auto';
                roomInner.innerHTML = `<div class="empty-state"><i class="fa-solid fa-tower-observation"></i><p>暂无房间，快创建一个吧！</p></div>`;
                return;
            }
            roomInner.style.height = (rooms.length * ROOM_ROW_H) + 'px';
            const scrollTop = roomListEl.scrollTop,
                vh = roomListEl.clientHeight || 400;
            const start = Math.max(0, Math.floor(scrollTop / ROOM_ROW_H) - 3);
            const end = Math.min(rooms.length, Math.ceil((scrollTop + vh) / ROOM_ROW_H) + 3);
            let html = '';
            for (let i = start; i < end; i++) html += roomRowHtml(rooms[i], i * ROOM_ROW_H);
            roomInner.innerHTML = html;
        }
        roomListEl.addEventListener('scroll', renderRooms);
        window.addEventListener('resize', renderRooms);

        function refreshRooms() {
            const btn = document.querySelector('.refresh-btn i');
            btn.classList.add('fa-spin');
            WSM.emit('lobbySubscribe', null, () => {});
            setTimeout(() => btn.classList.remove('fa-spin'), 500);
        }

        let _pendingJoinId = null;

        function joinRoom(roomId, hasPwd) {
            if (!roomId) return;
            if (hasPwd) {
                _pendingJoinId = roomId;
                openModal('房间密码', `
            <div style="display:flex;flex-direction:column;gap:12px;">
                <input type="password" placeholder="请输入房间密码" maxlength="32" id="joinPwdInput"
                    style="padding:12px;border-radius:10px;border:1px solid rgba(240,208,128,0.2);background:rgba(0,0,0,0.3);color:#f0d080;outline:none;">
                <button class="modal-btn" onclick="submitJoinPwd()">进入房间</button>
            </div>`);
                setTimeout(() => {
                    const i = document.getElementById('joinPwdInput');
                    if (i) {
                        i.focus();
                        i.addEventListener('keydown', e => {
                            if (e.key === 'Enter') submitJoinPwd();
                        });
                    }
                }, 60);
                return;
            }
            location.href = 'online.php?action=join&room=' + encodeURIComponent(roomId);
        }

        function submitJoinPwd() {
            const i = document.getElementById('joinPwdInput');
            const p = i ? i.value.trim() : '';
            if (!p) {
                if (i) {
                    i.style.borderColor = '#ff6d6d';
                    i.placeholder = '密码不能为空';
                }
                return;
            }
            if (!_pendingJoinId) return;
            location.href = 'online.php?action=join&room=' + encodeURIComponent(_pendingJoinId) + '&pwd=' + encodeURIComponent(p);
        }

        /* ==================== 玩家 tab -------------*/
        function switchBattleSubtab(sub, btn) {
            document.querySelectorAll('.lb-subtabs .lb-subtab[data-subtab]').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');
            const roomsPanel = document.getElementById('battleRoomsPanel');
            const playersPanel = document.getElementById('battlePlayersPanel');
            if (sub === 'players') {
                if (roomsPanel) roomsPanel.style.display = 'none';
                if (playersPanel) playersPanel.style.display = '';
                subscribePlayers();
                renderPlayers();
            } else {
                if (playersPanel) playersPanel.style.display = 'none';
                if (roomsPanel) roomsPanel.style.display = '';
                unsubscribePlayers();
                renderRooms();
            }
        }

        function subscribePlayers() {
            if (typeof WSM === 'undefined' || !WSM.emit) return;
            playersSubscribed = true;
            WSM.emit('playerSubscribe', null, () => {});
        }

        function unsubscribePlayers() {
            if (!playersSubscribed) return;
            playersSubscribed = false;
            if (typeof WSM !== 'undefined' && WSM.emit) WSM.emit('playerUnsubscribe', null, () => {});
        }

        function filteredPlayers() {
            const f = playerFilter.toLowerCase();
            const base = f ? allPlayers.filter(p => (p.name || '').toLowerCase().includes(f)) : allPlayers;
            return base;
        }

        function playerRowHtml(p, y) {
            const isSelf = p.id === myPlayerId;
            const busy = p.status === 'busy';
            const dotClass = busy ? 'dot-busy' : 'dot-idle';
            const statusText = busy ? '忙碌' : '空闲';
            const initial = escapeHtml((p.name || '玩').slice(0, 1));
            let btn;
            if (isSelf) {
                btn = `<span class="player-status-text">这是你</span>`;
            } else {
                const disabled = busy ? 'disabled' : '';
                const label = busy ? '忙碌中' : '邀请';
                btn = `<button class="player-invite-btn" ${disabled}
                    onclick="invitePlayer('${escapeHtml(p.id)}','${escapeHtml(p.name)}')">${label}</button>`;
            }
            return `<div class="player-item" style="top:${y}px">
        <div class="player-avatar">${initial}</div>
        <div class="player-info">
            <div class="player-name">${escapeHtml(p.name)}</div>
            <div class="player-status"><span class="dot ${dotClass}"></span>${statusText}</div>
        </div>
        ${btn}
    </div>`;
        }

        function renderPlayers() {
            if (!playerListEl) return;
            const list = filteredPlayers();
            const badge = document.getElementById('playerCountBadge');
            if (badge) badge.innerText = list.length;
            if (!window.IS_LOGGED_IN) {
                playerInner.style.height = 'auto';
                playerInner.innerHTML = `<div class="player-empty"><i class="fa-solid fa-user-lock"></i><p>登录后即可查看在线玩家并发起邀请</p></div>`;
                return;
            }
            if (!list.length) {
                playerInner.style.height = 'auto';
                playerInner.innerHTML = `<div class="player-empty"><i class="fa-solid fa-users-slash"></i><p>暂无在线玩家</p></div>`;
                return;
            }
            playerInner.style.height = (list.length * PLAYER_ROW_H) + 'px';
            const scrollTop = playerListEl.scrollTop,
                vh = playerListEl.clientHeight || 400;
            const start = Math.max(0, Math.floor(scrollTop / PLAYER_ROW_H) - 3);
            const end = Math.min(list.length, Math.ceil((scrollTop + vh) / PLAYER_ROW_H) + 3);
            let html = '';
            for (let i = start; i < end; i++) html += playerRowHtml(list[i], i * PLAYER_ROW_H);
            playerInner.innerHTML = html;
        }
        if (playerListEl) playerListEl.addEventListener('scroll', renderPlayers);
        window.addEventListener('resize', renderPlayers);

        function refreshPlayers() {
            const btn = document.querySelector('#battlePlayersPanel .refresh-btn i');
            if (btn) btn.classList.add('fa-spin');
            subscribePlayers();
            setTimeout(() => {
                if (btn) btn.classList.remove('fa-spin');
            }, 500);
        }

        function invitePlayer(targetId, name) {
            if (!window.IS_LOGGED_IN) {
                Qmsg.warning('请先登录后再邀请对战');
                return;
            }
            if (!targetId || typeof WSM === 'undefined') return;
            WSM.emit('invite', {
                target: targetId
            }, (res) => {
                if (res && res.ok) {
                    Qmsg.success('已邀请 ' + name + '，正在进入房间…');
                    const url = 'online.php?action=join&room=' + encodeURIComponent(res.room) +
                        '&pwd=' + encodeURIComponent(res.token);
                    setTimeout(() => {
                        location.href = url;
                    }, 400);
                } else {
                    Qmsg.error((res && res.reason) || '邀请失败');
                }
            });
        }

        function onInvited(payload) {
            if (!payload || !payload.room) return;
            const from = escapeHtml(payload.from || '对方');
            openModal('对战邀请', `
        <div style="display:flex;flex-direction:column;gap:16px;text-align:center;">
            <p style="color:#c8b585;font-size:15px;line-height:1.6;"><b style="color:var(--gold);">${from}</b> 邀请你对战一局</p>
            <div style="display:flex;gap:12px;">
                <button class="modal-btn" style="flex:1;" onclick="acceptInvite('${escapeHtml(payload.room)}','${escapeHtml(payload.token)}')">接受</button>
                <button class="modal-btn" style="flex:1;background:rgba(255,109,109,0.15);color:#ff9d9d;" onclick="closeModal()">拒绝</button>
            </div>
        </div>`);
        }

        function acceptInvite(room, token) {
            closeModal();
            location.href = 'online.php?action=join&room=' + encodeURIComponent(room) +
                '&pwd=' + encodeURIComponent(token);
        }

        const playerSearchEl = document.getElementById('playerSearch');
        if (playerSearchEl) playerSearchEl.addEventListener('input', function() {
            playerFilter = this.value;
            if (playerListEl) playerListEl.scrollTop = 0;
            renderPlayers();
        });

        const inputStyle = 'padding:12px;border-radius:10px;border:1px solid rgba(240,208,128,0.2);background:rgba(0,0,0,0.3);color:#f0d080;outline:none;';

        function showCreateRoom() {
            openModal('创建房间', `
        <div style="display:flex;flex-direction:column;gap:12px;text-align:left;">
            <input type="text" placeholder="房间名称（可选）" maxlength="24" style="${inputStyle}" id="createRoomName">
            <input type="password" placeholder="房间密码（留空则公开）" maxlength="32" style="${inputStyle}" id="createRoomPwd">
            <label style="display:flex;align-items:center;gap:8px;color:#c8b585;font-size:14px;cursor:pointer;">
                <input type="checkbox" id="createRoomSpectate" checked style="accent-color:#c9a84c;width:16px;height:16px;">
                允许他人观战
            </label>
            <button class="modal-btn" onclick="doCreateRoom()" style="margin-top:4px;">立即创建</button>
        </div>
    `);
            setTimeout(() => {
                const i = document.getElementById('createRoomName');
                if (i) i.focus();
            }, 60);
        }

        function doCreateRoom() {
            const name = (document.getElementById('createRoomName') || {}).value || '';
            const pwd = (document.getElementById('createRoomPwd') || {}).value || '';
            const spectate = (document.getElementById('createRoomSpectate') || {}).checked ? 1 : 0;
            const qs = 'action=create&name=' + encodeURIComponent(name.trim()) +
                '&pwd=' + encodeURIComponent(pwd) + '&spectate=' + spectate;
            location.href = 'online.php?' + qs;
        }

        function showJoinRoom() {
            openModal('加入房间', `
        <div style="display:flex;flex-direction:column;gap:12px;">
            <input type="text" placeholder="输入6位房间号" maxlength="6" style="${inputStyle}text-align:center;letter-spacing:4px;font-size:18px;font-weight:700;text-transform:uppercase;" id="joinRoomId">
            <input type="password" placeholder="房间密码（无密码可留空）" maxlength="32" style="${inputStyle}" id="joinRoomPwd">
            <button class="modal-btn" onclick="doJoinRoom()" style="margin-top:4px;">加入游戏</button>
        </div>
    `);
            setTimeout(() => {
                const i = document.getElementById('joinRoomId');
                if (i) i.focus();
            }, 60);
        }

        function doJoinRoom() {
            const el = document.getElementById('joinRoomId');
            const room = el && el.value.trim().toUpperCase();
            if (!room) {
                if (el) {
                    el.style.borderColor = '#ff6d6d';
                    el.placeholder = '请输入房间号';
                }
                return;
            }
            const pwd = (document.getElementById('joinRoomPwd') || {}).value || '';
            let url = 'online.php?action=join&room=' + encodeURIComponent(room);
            if (pwd) url += '&pwd=' + encodeURIComponent(pwd);
            location.href = url;
        }

        document.getElementById('roomSearch').addEventListener('input', function() {
            roomFilter = this.value;
            roomListEl.scrollTop = 0;
            renderRooms();
        });
        (function initLobbyWS() {
            if (typeof WSM === 'undefined') return;
            WSM.init({
                url: window.SERVER_URL,
                ticket: window.IDENTITY_TICKET || '',
                onError: () => {
                    Qmsg.error('服务器连接失败！请检查网络后重试或联系站点管理员');
                }
            });
            WSM.on('lobbyRooms', (payload) => {
                allRooms = (payload && payload.rooms) || [];
                renderRooms();
            });
            WSM.on('playerList', (payload) => {
                allPlayers = (payload && payload.players) || [];
                renderPlayers();
            });
            WSM.on('invited', (payload) => {
                onInvited(payload);
            });
            WSM.onReady(() => {
                WSM.emit('lobbySubscribe', null, () => {
                    Qmsg.success('服务器连接成功');
                });
                if (window.IS_LOGGED_IN) {
                    WSM.emit('playerJoin', {
                        name: window.DISPLAY_NAME || ''
                    }, (res) => {
                        if (res && res.ok) {
                            myPlayerId = res.id;
                            if (playersSubscribed) WSM.emit('playerSubscribe', null, () => {});
                        }
                    });
                }
            });
        })();
        let lbData = null;
        let lbBoard = 'total';
        let lbLoading = false;
        const LB_VIEW = {
            total: {
                main: p => p.score,
                sub: p => `${p.wins} 胜 · 胜率 ${p.winRate}%`
            },
            score: {
                main: p => p.score,
                sub: p => `${p.wins} 胜 · ${p.matches} 场`
            },
            streak: {
                main: p => p.streak + ' 连胜',
                sub: p => `历史最高 ${p.maxStreak}`
            },
            splus: {
                main: p => p.sPlus + ' 次',
                sub: p => `S+ · 排位分 ${p.score}`
            },
        };

        function lbAvatar(p) {
            let avatarUrl = '';
            if (p.avatar && p.avatar.trim() !== '') {
                if (p.avatar.indexOf('(&&)::') === 0) {
                    avatarUrl = '/assets/photo/' + p.avatar.substring(6);
                } else {
                    avatarUrl = p.avatar;
                }
                return `<img src="${escapeHtml(avatarUrl)}" alt="avatar" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">`;
            }
            return `<i class="fa-solid fa-chess-pawn" style="color:#c9a84c;"></i>`;
        }
        const LB_BRACKETS = [{
                max: 1,
                cls: 't1',
                honor: '状元',
                crest: 'fa-crown'
            },
            {
                max: 2,
                cls: 't2',
                honor: '榜眼',
                crest: 'fa-medal'
            },
            {
                max: 3,
                cls: 't3',
                honor: '探花',
                crest: 'fa-award'
            },
            {
                max: 10,
                cls: 't4',
                honor: '进士',
                crest: 'fa-gem'
            },
            {
                max: 25,
                cls: 't5',
                honor: '举人',
                crest: 'fa-star'
            },
            {
                max: Infinity,
                cls: 't6',
                honor: '秀才',
                crest: ''
            },
        ];

        function lbBracket(rank) {
            return LB_BRACKETS.find(b => rank <= b.max) || LB_BRACKETS[LB_BRACKETS.length - 1];
        }
        const LB_TIERS = [
            [3400, '王者', '#ffd76a'],
            [3000, '宗师', '#ff9de2'],
            [2600, '大师', '#c9a2e8'],
            [2200, '钻石', '#7fd8ff'],
            [1800, '铂金', '#7fd8c4'],
            [1400, '黄金', '#e8c56e'],
            [1000, '白银', '#dde5ef'],
            [600, '青铜', '#e6a463'],
            [0, '黑铁', '#a09070'],
        ];

        function lbTier(score) {
            const s = Number(score) || 0;
            for (const [min, name, color] of LB_TIERS) {
                if (s >= min) {
                    if (name === '王者') return {
                        label: `王者 ${Math.floor((s - 3400) / 100) + 1} 星`,
                        color
                    };
                    const div = 4 - Math.min(3, Math.floor((s - min) / 100));
                    return {
                        label: `${name} ${['','I','II','III','IV'][div]}`,
                        color
                    };
                }
            }
            return {
                label: '黑铁 IV',
                color: '#a09070'
            };
        }

        function renderBoard() {
            const list = document.getElementById('leaderboardList');
            if (!list) return;
            if (lbLoading) {
                list.innerHTML = `<div class="lb-loading">加载中…</div>`;
                return;
            }
            const rows = (lbData && lbData[lbBoard]) || [];
            if (!rows.length) {
                list.innerHTML = `<div class="lb-empty">暂无数据，快去对局吧！</div>`;
                return;
            }
            const view = LB_VIEW[lbBoard] || LB_VIEW.total;
            list.innerHTML = rows.map((p, i) => {
                const rank = i + 1;
                const b = lbBracket(rank);
                const tier = lbTier(p.score);
                const crest = b.crest ? `<i class="fa-solid ${b.crest} lb-crest"></i>` : '';
                return `
            <div class="lb-item ${b.cls}">
                <div class="lb-rank-wrap">
                    <div class="lb-rank"><span class="lb-rank-num">${rank}</span></div>
                    ${crest}
                </div>
                <div class="lb-avatar">${lbAvatar(p)}</div>
                <div class="lb-info">
                    <div style="cursor:pointer;" onclick="window.open('use/matchs/matchrecord/?rid=${p.uid}')" class="lb-name">${escapeHtml(p.name)}</div>
                    <div class="lb-stats">${escapeHtml(String(view.sub(p)))}</div>
                    <div class="lb-tags">
                        <span class="lb-chip honor">${b.honor}</span>
                        <span class="lb-chip tier" style="--tc:${tier.color}">${escapeHtml(tier.label)}</span>
                    </div>
                </div>
                <div class="lb-score">${escapeHtml(String(view.main(p)))}</div>
            </div>`;
            }).join('');
        }

        function switchBoard(board, btn) {
            lbBoard = board;
            document.querySelectorAll('.lb-subtab').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');
            renderBoard();
        }

        function loadLeaderboard(force) {
            if (lbData && !force) return;
            if (lbLoading) return;
            lbLoading = true;
            renderBoard();
            fetch('api/leaderrank/', {
                    credentials: 'same-origin'
                })
                .then(r => r.json())
                .then(j => {
                    lbData = (j && j.code === 200 && j.data) ? j.data : {
                        total: [],
                        score: [],
                        winrate: [],
                        streak: [],
                        splus: []
                    };
                })
                .catch(() => {
                    lbData = {
                        total: [],
                        score: [],
                        winrate: [],
                        streak: [],
                        splus: []
                    };
                })
                .finally(() => {
                    lbLoading = false;
                    renderBoard();
                });
        }
        renderRooms();
    </script>
    <script>
        (function() {
            'use strict';

            let statusChannel = null;
            let wsReady = false;
            let pendingStatus = null;
            let retryTimer = null;
            const MAX_RETRY_ATTEMPTS = 5;
            let retryCount = 0;
            try {
                statusChannel = new BroadcastChannel('fhq_status_channel');
            } catch (e) {
                console.warn('[首页] BroadcastChannel 不可用，将使用 storage 事件降级方案');
            }

            function syncStatusToServer(status) {
                if (typeof WSM === 'undefined' || !WSM.emit) {
                    console.warn('[首页] WebSocket 未就绪，状态暂存待重试:', status);
                    pendingStatus = status;
                    retryCount = 0;
                    scheduleRetry();
                    return false;
                }
                if (!window.IS_LOGGED_IN) {
                    console.warn('[首页] 未登录，跳过状态同步');
                    return false;
                }

                try {
                    WSM.emit('setStatus', {
                        status: status
                    }, function(res) {
                        if (res && res.ok) {
                            pendingStatus = null;
                            retryCount = 0;
                            if (retryTimer) {
                                clearTimeout(retryTimer);
                                retryTimer = null;
                            }
                        } else {
                            const reason = (res && res.reason) || '未知错误';
                            console.warn('[首页] 状态同步失败:', reason);
                            pendingStatus = status;
                            retryCount = 0;
                            scheduleRetry();
                        }
                    });
                    return true;
                } catch (e) {
                    console.error('[首页] 状态同步异常:', e.message);
                    pendingStatus = status;
                    retryCount = 0;
                    scheduleRetry();
                    return false;
                }
            }

            function scheduleRetry() {
                if (retryTimer) {
                    clearTimeout(retryTimer);
                    retryTimer = null;
                }
                if (pendingStatus === null) return;
                if (retryCount >= MAX_RETRY_ATTEMPTS) {
                    console.warn('[首页] 状态同步重试次数已达上限，放弃:', pendingStatus);
                    pendingStatus = null;
                    retryCount = 0;
                    return;
                }
                const delay = Math.min(1000 * Math.pow(2, retryCount), 30000);
                retryCount++;
                console.log('[首页] 将在 ' + delay + 'ms 后重试状态同步... (第 ' + retryCount + ' 次)');
                retryTimer = setTimeout(function() {
                    retryTimer = null;
                    if (pendingStatus !== null) {
                        const status = pendingStatus;
                        if (typeof WSM !== 'undefined' && WSM.emit && window.IS_LOGGED_IN) {
                            syncStatusToServer(status);
                        } else {
                            scheduleRetry();
                        }
                    }
                }, delay);
            }
            if (statusChannel) {
                statusChannel.onmessage = function(event) {
                    const data = event.data;
                    if (!data || typeof data !== 'object') return;
                    if (data.source === 'settings_page') {
                        if (data.type === 'setStatus' && data.status) {
                            localStorage.setItem('fhq_status', data.status);
                            syncStatusToServer(data.status);
                        }
                    }
                };
            }
            window.addEventListener('storage', function(event) {
                if (event.key === 'fhq_status_trigger' && event.newValue) {
                    try {
                        const data = JSON.parse(event.newValue);
                        if (data && data.status) {
                            const currentStored = localStorage.getItem('fhq_status') || 'idle';
                            if (currentStored !== data.status) {
                                localStorage.setItem('fhq_status', data.status);
                                syncStatusToServer(data.status);
                            }
                        }
                    } catch (_) {}
                }
            });
            if (typeof WSM !== 'undefined' && WSM.onReady) {
                WSM.onReady(function() {
                    wsReady = true;
                    if (pendingStatus !== null) {
                        if (window.IS_LOGGED_IN) {
                            syncStatusToServer(pendingStatus);
                        } else {
                            pendingStatus = null;
                        }
                    }
                });
            }

            function initStatusSync() {
                const localStatus = localStorage.getItem('fhq_status') || 'idle';
                if (window.IS_LOGGED_IN && typeof WSM !== 'undefined' && WSM.emit) {
                    if (pendingStatus === null) {
                        syncStatusToServer(localStatus);
                    }
                }
            }
            if (document.readyState === 'complete') {
                initStatusSync();
            } else {
                window.addEventListener('load', initStatusSync);
            }
        })();
    </script>
</body>

</html>