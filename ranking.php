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
if (!$qx_max_tmp1) {
  mokim_ttl_elegant_exit(
    '您当前未登录<a href="/use/user/">点我登录</a>',
    null,
    'error'
  );
}
function verifyFhqReplaySign($rid, $signature, $timestamp)
{
  $s = $_ENV['API_SECRET_KEY_mok'] ?? getenv('API_SECRET_KEY_mok') ?: '';
  if ($s === '') {
    return false;
  }
  if (time() > $timestamp) {
    return false;
  }
  $data = (string)$rid . '|' . (string)$timestamp;
  $expectedSignature = hash_hmac('sha256', $data, $s);
  return hash_equals($expectedSignature, $signature);
}
$sig = (string)($_GET['d'] ?? '');
if (empty($sig)) {
  mokim_ttl_elegant_exit('令牌异常！', null, 'error');
}
list($signature, $expireTime) = explode('|', $sig);
if (!verifyFhqReplaySign($q_suname, $signature, $expireTime)) {
  mokim_ttl_elegant_exit('令牌异常！', null, 'error');
}
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/ticket.php');
$gout_identity_ticket = make_identity_ticket((string)$q_suname);
$user_nickname = $_COOKIE['mokim_usergname'] ?? getStableName($q_suname);
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
  <title>烽火棋 · 排位竞技</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="assets/game.css">
</head>

<body>
  <div id="status" class="panel">
    <h3>◈ 战局状态</h3>
    <div class="turn-badge" id="turnBadge"><span class="turn-dot"></span>红方回合 · 第 1 回合</div>
    <div class="count-row">
      <div class="count-box red">
        <div class="num" id="redCount">20</div>
        <div class="lbl">红方棋子</div>
      </div>
      <div class="count-box blue">
        <div class="num" id="blueCount">20</div>
        <div class="lbl">蓝方棋子</div>
      </div>
    </div>
    <div class="thresh">
      <div><span class="tag" id="coreTag">≤26</span>核心进攻限制：<b id="coreState" style="color:#ff9b9e">生效中</b></div>
      <div><span class="tag on">≤20</span>迷雾解除阈值：<b id="fogThreshState">待触发</b></div>
      <div><span class="tag">步长</span>军棋步长　红<b id="genStepR">3</b>　蓝<b id="genStepB">3</b></div>
    </div>
  </div>

  <div id="fog" class="panel">
    <h3>◈ 视野迷雾</h3>
    <div class="fog-toggle">
      <span class="fog-state" id="fogState">迷雾开启</span>
      <div class="switch" id="fogSwitch"></div>
    </div>
    <div class="fog-hint" id="fogSides"><span class="fog-lamp"></span>红：迷雾　蓝：迷雾</div>
    <div class="fog-hint">对方子棋阵亡 / 总数 ≤20 时解除</div>
  </div>

  <div id="legend" class="panel">
    <h3>◈ 棋子图例</h3>
    <div class="legend-grid" id="legendGrid"></div>
  </div>

  <div id="logpanel" class="panel">
    <h3>◈ 操作记录</h3>
    <ul class="log-list" id="logList">
      <li>游戏开始</li>
    </ul>
    <div class="hub-actions">
      <button id="btnSurrender" class="hub-btn danger">投 降</button>
      <button id="btnDraw" class="hub-btn">和 棋</button>
    </div>
    <div id="chatbox">
      <ul class="chat-list" id="chatList"></ul>
      <div class="chat-input">
        <input id="chatInput" maxlength="200" placeholder="输入消息…" />
        <button id="chatSend">发送</button>
      </div>
    </div>
  </div>

  <div id="board"></div>

  <div id="winOverlay">
    <div class="win-inner">
      <div class="win-card" id="winCard">获胜</div>
      <div id="rankDelta" class="rank-delta" style="display:none"></div>
      <div id="ratingPanel" style="display:none"></div>
      <div class="win-actions">
        <button id="winExit" class="hub-btn">返 回 大 厅</button>
        <button id="winAgain" class="hub-btn primary">再 次 匹 配</button>
      </div>
    </div>
  </div>

  <div id="drawOverlay">
    <div class="draw-card">
      <div class="draw-title">对方请求和棋</div>
      <div class="draw-actions">
        <button id="drawDecline" class="hub-btn">拒 绝</button>
        <button id="drawAccept" class="hub-btn primary">同 意</button>
      </div>
    </div>
  </div>

  <svg width="0" height="0" style="position:absolute">
    <defs>
      <radialGradient id="gRed" cx="35%" cy="28%" r="75%">
        <stop offset="0%" stop-color="#ffd0c9" />
        <stop offset="35%" stop-color="#ff6d6d" />
        <stop offset="100%" stop-color="#8f1620" />
      </radialGradient>
      <radialGradient id="gBlue" cx="35%" cy="28%" r="75%">
        <stop offset="0%" stop-color="#d5ecff" />
        <stop offset="35%" stop-color="#5aa0ff" />
        <stop offset="100%" stop-color="#153a86" />
      </radialGradient>
    </defs>
  </svg>
  <div id="matchLobby">
    <div class="match-card">
      <h2>烽火棋 · 排位竞技</h2>
      <div class="match-rank" id="matchRank">加载资料中…</div>
      <div id="matchIdle">
        <p class="match-desc">进入匹配池，与积分相近的对手进行最公平的对局。</p>
        <button id="btnQueue" class="lobby-btn primary">开 始 匹 配</button>
      </div>
      <div id="matchSearching" style="display:none">
        <div class="match-spinner"></div>
        <div class="match-line">正在匹配对手…</div>
        <div class="match-line small" id="matchElapsed">已等待 0 秒</div>
        <div class="match-line small" id="matchPool">匹配池：— 人</div>
        <button id="btnCancelQueue" class="lobby-btn">取 消 匹 配</button>
      </div>
      <div id="matchMsg" class="match-msg"></div>
      <button id="btnBackHome" class="lobby-btn ghost">返回大厅</button>
    </div>
  </div>

  <div id="toastContainer"
    style="position:fixed;bottom:80px;left:50%;transform:translateX(-50%);z-index:999;background:rgba(0,0,0,.8);color:#fff;padding:12px 24px;border-radius:8px;display:none;">
  </div>
  <div id="netbar"></div>

  <style>
    #matchLobby {
      position: fixed;
      inset: 0;
      z-index: 60;
      display: flex;
      align-items: center;
      justify-content: center;
      background: radial-gradient(circle at 50% 40%, rgba(6, 16, 28, .85), rgba(2, 8, 14, .95));
      backdrop-filter: blur(5px);
    }

    #matchLobby.hidden {
      display: none;
    }

    .match-card {
      width: 340px;
      padding: 30px 26px;
      border-radius: 16px;
      text-align: center;
      background: linear-gradient(155deg, rgba(20, 40, 64, .92), rgba(10, 22, 38, .96));
      border: 1px solid rgba(140, 190, 255, .45);
      box-shadow: 0 0 50px rgba(60, 140, 240, .4);
    }

    .match-card h2 {
      color: #eaf2ff;
      letter-spacing: .2em;
      margin-bottom: 14px;
      text-shadow: 0 0 18px rgba(90, 160, 255, .6);
    }

    .match-rank {
      font-size: 15px;
      font-weight: 700;
      color: #ffd66b;
      margin-bottom: 16px;
      letter-spacing: .05em;
    }

    .match-desc {
      color: #a9bcd8;
      font-size: 13px;
      line-height: 1.7;
      margin-bottom: 18px;
    }

    .match-line {
      color: #cfe0ff;
      font-size: 14px;
      margin: 6px 0;
      letter-spacing: .06em;
    }

    .match-line.small {
      color: #8fa6c4;
      font-size: 12px;
    }

    .match-msg {
      min-height: 18px;
      color: #ff9b9e;
      font-size: 13px;
      margin: 10px 0 4px;
    }

    .match-spinner {
      width: 46px;
      height: 46px;
      margin: 6px auto 14px;
      border: 4px solid rgba(140, 190, 255, .25);
      border-top-color: #5aa0ff;
      border-radius: 50%;
      animation: mspin 0.9s linear infinite;
    }

    @keyframes mspin {
      to {
        transform: rotate(360deg);
      }
    }

    .lobby-btn {
      padding: 11px 18px;
      border: none;
      border-radius: 9px;
      font-weight: 700;
      letter-spacing: .1em;
      cursor: pointer;
      color: #071018;
      font-family: inherit;
      background: linear-gradient(135deg, #7fd0ff, #4a9bff);
    }

    .lobby-btn.primary {
      width: 100%;
      margin-bottom: 6px;
      font-size: 15px;
      padding: 13px;
    }

    .lobby-btn.ghost {
      background: transparent;
      color: #8fa6c4;
      border: 1px solid rgba(140, 190, 255, .3);
      margin-top: 14px;
      font-size: 12px;
    }

    #matchSearching .lobby-btn {
      width: 100%;
      margin-top: 12px;
      background: linear-gradient(135deg, #ff9b7f, #ff5d62);
    }

    .rank-delta {
      font-size: 16px;
      font-weight: 800;
      letter-spacing: .06em;
      margin: 8px 0 4px;
      color: #ffe08a;
    }

    #netbar {
      position: fixed;
      top: 54px;
      left: 50%;
      transform: translateX(-50%);
      z-index: 9;
      font-size: 12px;
      letter-spacing: .12em;
      color: #9cc4ff;
      pointer-events: none;
      text-align: center;
    }

    .hub-actions {
      display: flex;
      gap: 8px;
      margin-top: 12px;
    }

    .hub-btn {
      flex: 1;
      padding: 9px 6px;
      border: none;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 700;
      letter-spacing: .08em;
      cursor: pointer;
      color: #071018;
      font-family: inherit;
      background: linear-gradient(135deg, #7fd0ff, #4a9bff);
      transition: transform .12s, box-shadow .12s, opacity .12s;
    }

    .hub-btn:hover:not(:disabled) {
      transform: translateY(-2px);
    }

    .hub-btn:disabled {
      opacity: .4;
      cursor: not-allowed;
    }

    .hub-btn.danger {
      background: linear-gradient(135deg, #ff9b7f, #ff5d62);
    }

    .hub-btn.primary {
      background: linear-gradient(135deg, #8effc0, #35c98b);
    }

    #chatbox {
      margin-top: 12px;
      border-top: 1px dashed rgba(255, 255, 255, .12);
      padding-top: 10px;
    }

    .chat-list {
      list-style: none;
      font-size: 11.5px;
      line-height: 1.6;
      max-height: 92px;
      overflow-y: auto;
      margin-bottom: 8px;
    }

    .chat-list li {
      padding: 1px 0;
      color: #c3d3ec;
      word-break: break-word;
    }

    .chat-list li .who {
      font-weight: 700;
      margin-right: 5px;
    }

    .chat-list li .who.red {
      color: #ff9b9e;
    }

    .chat-list li .who.blue {
      color: #9cc4ff;
    }

    .chat-list li .who.spectator {
      color: #8fa6c4;
    }

    .chat-input {
      display: flex;
      gap: 6px;
    }

    .chat-input input {
      flex: 1;
      min-width: 0;
      padding: 7px 8px;
      border-radius: 7px;
      border: 1px solid rgba(140, 190, 255, .35);
      background: rgba(8, 18, 30, .8);
      color: #dbe9ff;
      font-size: 12px;
      font-family: inherit;
    }

    .chat-input button {
      padding: 7px 12px;
      border: none;
      border-radius: 7px;
      font-size: 12px;
      font-weight: 700;
      cursor: pointer;
      color: #071018;
      font-family: inherit;
      background: linear-gradient(135deg, #7fd0ff, #4a9bff);
    }

    .win-card.draw {
      color: #ffe08a;
      text-shadow: 0 0 24px rgba(255, 210, 120, .6);
      border-color: rgba(255, 220, 140, .6);
    }

    .win-actions {
      margin-top: 26px;
      display: flex;
      gap: 14px;
      justify-content: center;
    }

    .win-actions .hub-btn {
      flex: 0 0 auto;
      padding: 12px 34px;
      font-size: 15px;
      letter-spacing: .14em;
    }

    #drawOverlay {
      position: fixed;
      inset: 0;
      z-index: 55;
      display: none;
      align-items: center;
      justify-content: center;
      background: radial-gradient(circle at 50% 45%, rgba(6, 16, 28, .6), rgba(2, 8, 14, .8));
      backdrop-filter: blur(3px);
    }

    #drawOverlay.show {
      display: flex;
    }

    .draw-card {
      padding: 26px 34px;
      border-radius: 16px;
      text-align: center;
      background: linear-gradient(155deg, rgba(20, 40, 64, .95), rgba(10, 22, 38, .97));
      border: 1px solid rgba(140, 190, 255, .5);
      box-shadow: 0 0 50px rgba(60, 140, 240, .4);
    }

    .draw-title {
      color: #eaf2ff;
      font-size: 18px;
      letter-spacing: .12em;
      margin-bottom: 18px;
    }

    .draw-actions {
      display: flex;
      gap: 12px;
      justify-content: center;
    }

    .draw-actions .hub-btn {
      flex: 0 0 auto;
      padding: 10px 26px;
    }
  </style>
  <script>
    window.SERVER_URL = '<?php echo $gout_api_wslinking_address; ?>';
    window.IDENTITY_TICKET = '<?php echo $gout_identity_ticket; ?>';
    window.USERNICKNAMESHOWED= '<?php echo $user_nickname; ?>';
  </script>
  <script src="assets/core/wsmanager.js"></script>
  <script src="assets/core/engine.js"></script>
  <script src="assets/core/rating.js"></script>
  <script src="assets/core/ui.js"></script>
  <script src="assets/core/scorefx.js"></script>
  <script src="assets/core/replay.js"></script>
  <script src="assets/core/anim.js"></script>
  <script>
    (function() {
      const saved_msgshow = (localStorage.getItem('fhq_chatToggle') === 'true') ?? true;
      const $ = id => document.getElementById(id);
      $('btnBackHome').addEventListener('click', function() {
        setTimeout(function() {
          location.href = "index.php";
        }, 200);
      });

      function tierLabel(score) {
        const T = [
          [3400, "王者"],
          [3000, "宗师"],
          [2600, "大师"],
          [2200, "钻石"],
          [1800, "铂金"],
          [1400, "黄金"],
          [1000, "白银"],
          [600, "青铜"],
          [0, "黑铁"],
        ];
        for (const [min, name] of T) {
          if (score >= min) {
            if (name === "王者") return `王者 ${Math.floor((score - 3400) / 100) + 1} 星`;
            const div = 4 - Math.min(3, Math.floor((score - min) / 100));
            const roman = ["", "I", "II", "III", "IV"][div];
            return `${name} ${roman}`;
          }
        }
        return "黑铁 IV";
      }

      function toasts_v2(t) {
        const el = $("toastContainer");
        if (!el) return;
        el.textContent = t || "";
        el.style.display = t ? "block" : "none";
        if (t) {
          clearTimeout(el._timer);
          el._timer = setTimeout(() => {
            el.style.display = "none";
          }, 2500);
        }
      }

      function matchMsg(t) {
        const el = $("matchMsg");
        if (el) el.textContent = t || "";
      }

      function themedConfirm(title, msg, onYes) {
        let ov = $("confirmOverlay");
        if (!ov) {
          ov = document.createElement("div");
          ov.id = "confirmOverlay";
          ov.innerHTML =
            '<div class="draw-card"><div class="draw-title" id="cfmTitle"></div>' +
            '<div style="color:#b7c8e0;font-size:13px;margin:-8px 0 16px;" id="cfmMsg"></div>' +
            '<div class="draw-actions"><button class="hub-btn" id="cfmNo">取 消</button>' +
            '<button class="hub-btn danger" id="cfmYes">确 定</button></div></div>';
          ov.setAttribute("style", "position:fixed;inset:0;z-index:70;display:flex;align-items:center;justify-content:center;background:radial-gradient(circle at 50% 45%,rgba(6,16,28,.6),rgba(2,8,14,.8));backdrop-filter:blur(3px);");
          document.body.appendChild(ov);
        }
        ov.querySelector("#cfmTitle").textContent = title;
        ov.querySelector("#cfmMsg").textContent = msg || "";
        ov.style.display = "flex";
        const close = () => {
          ov.style.display = "none";
        };
        ov.querySelector("#cfmNo").onclick = close;
        ov.querySelector("#cfmYes").onclick = () => {
          close();
          onYes && onYes();
        };
      }

      WSM.init({
        url: window.SERVER_URL,
        ticket: window.IDENTITY_TICKET || "",
        onError: () => matchMsg("无法连接服务端…"),
      });
      let _booted = false;
      WSM.onReady((socket) => {
        if (!_booted) {
          _booted = true;
          init(socket);
        }
      });

      function init(socket) {
        const engine = Engine.createGame();
        let mySide = "spectator";
        let ui = null;
        let roomId = null;
        let readyToPlay = false;
        let queued = false;
        let elapsedTimer = null;
        let queueStart = 0;
        let settleGate = null;
        let settleGateResolve = null;
        let settleExtra = null;

        function armSettleGate() {
          if (settleGate) return;
          settleGate = new Promise(res => {
            settleGateResolve = res;
          });
          setTimeout(() => releaseSettleGate(), 6000);
        }

        function releaseSettleGate() {
          if (settleGateResolve) {
            settleGateResolve();
            settleGateResolve = null;
          }
        }
        const rec = Replay.attachRecorder({
          mode: "ranked"
        });

        function maskLog(line) {
          const iR = line.indexOf("红方"),
            iB = line.indexOf("蓝方");
          let owner = null;
          if (iR >= 0 && (iB < 0 || iR < iB)) owner = "red";
          else if (iB >= 0) owner = "blue";
          if (owner === null) return line;
          const hide = mySide === "spectator" ? true : owner !== mySide;
          return hide ? line.replace(/\(\d+,\s*\d+\)/g, "(***)") : line;
        }

        function ensureUI() {
          if (ui) return;
          ui = UI.createUI({
            engine,
            getViewer: () => mySide === "spectator" ? engine.state.turn : mySide,
            canInteract: (p) => readyToPlay && mySide !== "spectator" && p.side === mySide && engine.state.turn === mySide,
            onMove: (id, r, c) => {
              socket.emit("move", {
                id,
                r,
                c
              }, res => {
                if (res && !res.ok) toasts_v2(res.reason);
              });
              return null;
            },
            onRestart: () => {},
            maskLog,
          });
        }

        function sideCN(s) {
          return s === "red" ? "红方" : s === "blue" ? "蓝方" : "观战";
        }

        function updateHub() {
          const over = engine.state.over;
          const active = readyToPlay && mySide !== "spectator" && !over;
          const bs = $("btnSurrender"),
            bd = $("btnDraw");
          if (bs) bs.disabled = !active;
          if (bd) bd.disabled = !active;
        }

        function applyState(data) {
          engine.netApply({
            b: data.b,
            log: data.log
          });
          readyToPlay = !!(data.ready && data.ready.red && data.ready.blue);
          ensureUI();
          ui.clearSelection();
          ui.render();
          rec.observe(engine); // 录制：抓取整盘状态帧
          if (data.events) ui.flashEvents(data.events);
          if (mySide !== "spectator" && !readyToPlay) socket.emit("ready");
          updateHub();
          if (window.Rating) {
            const view = mySide === "spectator" ? null : mySide;
            if (engine.state.over && data.rating) {
              armSettleGate();
              UI.settle({
                panel: "ratingPanel",
                match: data.rating,
                side: view,
                cardSide: view,
                wait: () => settleGate,
                extra: () => settleExtra,
              });
            } else {
              settleGate = null;
              settleGateResolve = null;
              settleExtra = null;
              UI.settle({
                panel: "ratingPanel",
                match: null
              });
            }
          }
          const seats = data.seats || {};
          const netbar = $("netbar");
          if (netbar) {
            let tail = "";
            if (mySide !== "spectator") {
              tail = !readyToPlay ? "　— 等待对手就位" :
                (engine.state.turn === mySide ? "　— 轮到你" : "　— 等待对方");
            }
            netbar.textContent = `排位 ${roomId}　你：${sideCN(mySide)}　红[${seats.red ? "在" : "空"}] 蓝[${seats.blue ? "在" : "空"}]` + tail;
          }
        }

        function enterRoom(res) {
          if (!res || !res.ok) {
            matchMsg(res && res.reason || "进入对局失败");
            return;
          }
          roomId = res.room;
          mySide = res.side;
          stopSearching();
          $("matchLobby").classList.add("hidden");
          socket.emit("sync", null, s => {
            if (s && s.ok) applyState(s);
          });
        }

        // ---------- 匹配流程 ----------
        function showSearching(on) {
          $("matchIdle").style.display = on ? "none" : "block";
          $("matchSearching").style.display = on ? "block" : "none";
        }

        function startSearching() {
          queued = true;
          showSearching(true);
          queueStart = Date.now();
          clearInterval(elapsedTimer);
          elapsedTimer = setInterval(() => {
            const sec = Math.floor((Date.now() - queueStart) / 1000);
            $("matchElapsed").textContent = `已等待 ${sec} 秒`;
          }, 1000);
        }

        function stopSearching() {
          queued = false;
          showSearching(false);
          clearInterval(elapsedTimer);
          elapsedTimer = null;
        }

        $("btnQueue").onclick = () => {
          matchMsg("");
          socket.emit("queueRanked", null, res => {
            if (!res || !res.ok) {
              matchMsg(res && res.reason || "无法进入匹配");
              return;
            }
            if (res.profile) {
              $("matchRank").textContent = `${tierLabel(res.profile.score)} · ${res.profile.score} 分`;
            }
            if (typeof res.poolSize === "number") $("matchPool").textContent = `匹配池：${res.poolSize} 人`;
            startSearching();
          });
        };
        $("btnCancelQueue").onclick = () => {
          socket.emit("cancelRanked", null, () => stopSearching());
        };

        // 匹配成功
        socket.on("matched", async ({
          room,
          side,
          myScore,
          foeScore
        }) => {
          matchMsg("");
          await FHQAnim.ready({
            red: side === "red" ? window.USERNICKNAMESHOWED : "巅峰棋手一",
            blue: side === "blue" ? window.USERNICKNAMESHOWED : "巅峰棋手二"
          });
          toasts_v2(`匹配成功！对手 ${foeScore} 分`);
          socket.emit("enterRanked", {
            room
          }, enterRoom);
        });

        // ---------- 交互 ----------
        function backToLobby(reason) {
          socket.emit("leaveRoom");
          roomId = null;
          mySide = "spectator";
          readyToPlay = false;
          const ov = $("winOverlay");
          if (ov) ov.classList.remove("show");
          const dov = $("drawOverlay");
          if (dov) dov.classList.remove("show");
          $("rankDelta").style.display = "none";
          $("netbar").textContent = "";
          $("matchLobby").classList.remove("hidden");
          stopSearching();
          if (reason) matchMsg(reason);
        }

        function escapeHTML(s) {
          return String(s).replace(/[&<>"']/g, c => ({
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#39;"
          } [c]));
        }

        function appendChat(side, text) {
          if (!saved_msgshow) {
            return;
          }
          const list = $("chatList");
          if (!list) return;
          const li = document.createElement("li");
          li.innerHTML = `<span class="who ${side}">${sideCN(side)}</span>${escapeHTML(text)}`;
          list.appendChild(li);
          while (list.children.length > 60) list.removeChild(list.firstChild);
          list.scrollTop = list.scrollHeight;
        }

        function sendChat() {
          const input = $("chatInput");
          const text = input && input.value.trim();
          if (!text) return;
          socket.emit("chat", {
            text
          }, res => {
            if (res && !res.ok) toasts_v2(res.reason);
          });
          if (input) input.value = "";
        }
        $("chatSend").onclick = sendChat;
        $("chatInput").addEventListener("keydown", e => {
          if (e.key === "Enter") sendChat();
        });

        $("btnSurrender").onclick = () => {
          themedConfirm("确认投降", "本局将判负并扣除积分，确定投降？", () => {
            socket.emit("surrender", null, res => {
              if (res && !res.ok) toasts_v2(res.reason);
            });
          });
        };
        $("btnDraw").onclick = () => {
          socket.emit("drawOffer", null, res => {
            if (res && !res.ok) toasts_v2(res.reason);
            else toasts_v2("已发送和棋请求");
          });
        };
        $("winExit").onclick = () => {
          setTimeout(function() {
            location.href = "index.php";
          }, 200);
        };
        $("winAgain").onclick = () => backToLobby();

        $("drawAccept").onclick = () => {
          socket.emit("drawRespond", {
            accept: true
          });
          $("drawOverlay").classList.remove("show");
        };
        $("drawDecline").onclick = () => {
          socket.emit("drawRespond", {
            accept: false
          });
          $("drawOverlay").classList.remove("show");
        };

        // ---------- socket 事件 ----------
        socket.on("state", applyState);
        socket.on("chat", ({
          side,
          text
        }) => appendChat(side, text));
        socket.on("drawOffered", () => $("drawOverlay").classList.add("show"));
        socket.on("drawDeclined", () => toasts_v2("对方拒绝了和棋"));
        socket.on("opponentLeft", ({
          side,
          ranked
        }) => {
          if (ranked) toasts_v2(`${sideCN(side)}离开，本局结束`);
          else toasts_v2(`${sideCN(side)}离开了`);
        });
        socket.on("kicked", ({
          reason
        }) => backToLobby(reason || "你已离开对局"));
        socket.on("roomClosed", ({
          reason
        }) => backToLobby(reason || "对局已结束"));
        socket.on("rankResult", async (data) => {
          armSettleGate();
          try {
            const state = engine.state;
            let winner = state.over ? (state.winner || "draw") : null;
            let redName = "红方";
            let blueName = "蓝方";
            if (mySide !== "spectator") {
              if (winner === "red") {
                if (mySide === "red") {
                  redName = "你";
                  blueName = "蓝方";
                } else {
                  redName = "对手";
                  blueName = "蓝方";
                }
              } else if (winner === "blue") {
                if (mySide === "blue") {
                  redName = "红方";
                  blueName = "你";
                } else {
                  redName = "红方";
                  blueName = "对手";
                }
              }
            }
            if (winner === "draw") {
              await FHQAnim.result("draw", {
                red: redName,
                blue: blueName
              });
            } else if (winner) {
              await FHQAnim.result(winner, {
                red: redName,
                blue: blueName
              });
            } else {
              await FHQAnim.result("draw", {
                red: redName,
                blue: blueName
              });
            }
          } catch {

          }
          const mine = data[mySide];
          if (mine) {
            const sign = mine.delta > 0 ? "+" : "";
            settleExtra = {
              delta: mine.delta,
              deltaSuffix: " 分",
              sub: `积分 ${mine.scoreBefore} → ${mine.scoreAfter} · ${tierLabel(mine.scoreAfter)}`,
              hint: "点击任意处关闭",
            };
            const el = $("rankDelta");
            el.textContent = `积分 ${mine.scoreBefore} → ${mine.scoreAfter}（${sign}${mine.delta}）· ${tierLabel(mine.scoreAfter)}`;
            el.style.display = "block";
          }
          releaseSettleGate();
        });
        socket.on("disconnect", () => {
          const netbar = $("netbar");
          if (netbar && roomId) netbar.textContent = "连接断开，重连中…";
        });
        socket.on("connect", () => {
          if (roomId) {
            socket.emit("sync", null, s => {
              if (s && s.ok) {
                mySide = s.side;
                applyState(s);
              }
            });
          }
        });
        socket.on("connect_error", () => matchMsg("连接服务端失败…"));
      }
    }());
  </script>
</body>

</html>