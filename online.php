<?php
require($_SERVER['DOCUMENT_ROOT'] . '/setting.php');
require($_SERVER['DOCUMENT_ROOT'] . '/cofd/functions.php');
$gout_uid = null;
$tcodelogins = $_COOKIE[generateAutoWebsiteIdentifier(true) . "_log"] ?? 'null';
if ($tcodelogins !== 'null') {
  require_once($_SERVER['DOCUMENT_ROOT'] . '/cofd/tauth.php');
  $decodeers = new TmdbaseauthdownyhoDecrypt(60000 * 60 * 2);
  $decodeddata = $decodeers->writebacknewwords($tcodelogins);
  if ($decodeddata) {
    $decodeddata2 = encrypt($decodeddata, 'D', generateAutoWebsiteIdentifier(true));
    if ($decodeddata2) {
      $tarray = explode('<:>', $decodeddata2);
      if (isset($tarray[2]) && !empty($tarray[2])) $gout_uid = trim($tarray[2]);
    }
  }
}
require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT'] . '/');
$dotenv->load();
$gout_api_wslinking_address =  $_ENV['WS_LINKING_ADDRESS'] ?? 'localhost:8080';
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/ticket.php');
$gout_identity_ticket = $gout_uid ? make_identity_ticket((string)$gout_uid) : '';
$gout_display_name = '游客';
if ($gout_uid) {
  require_once($_SERVER['DOCUMENT_ROOT'] . '/cofd/common.php');
  if (isset($conn) && $conn) {
    $ns = $conn->prepare("SELECT uname FROM mok_user WHERE id = ? LIMIT 1");
    if ($ns) {
      $ns->bind_param('s', $gout_uid);
      $ns->execute();
      $nr = $ns->get_result();
      if ($nr && $nr->num_rows > 0) {
        $row = $nr->fetch_assoc();
        if (!empty($row['uname'])) $gout_display_name = $row['uname'];
      }
      $ns->close();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
  <title>烽火棋</title>
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
      <div id="ratingPanel" style="display:none"></div>
      <div class="win-actions">
        <button id="winExit" class="hub-btn">退 出</button>
        <button id="winContinue" class="hub-btn primary">继 续 游 戏</button>
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


  <div id="lobby">
    <div class="lobby-card">
      <h2>烽火棋 · 联机</h2>
      <div id="lobbyMsg" class="lobby-msg"></div>
      <button id="btnCreate" class="lobby-btn primary">创建房间</button>
      <button onclick="location.href='index.php';" class="lobby-btn primary">返回大厅</button>
      <div class="lobby-row">
        <input id="roomInput" placeholder="房间号" maxlength="6" />
        <button id="btnJoin" class="lobby-btn">加入</button>
      </div>
      <input id="pwdInput" placeholder="密码（可选）" />
      <div id="lobbyStatus" class="lobby-status"></div>
    </div>
  </div>

  <div id="toastContainer"
    style="position:fixed;bottom:80px;left:50%;transform:translateX(-50%);z-index:999;background:rgba(0,0,0,.8);color:#fff;padding:12px 24px;border-radius:8px;display:none;">
  </div>
  <div id="netbar"></div>
  <div id="roomHub" class="panel" style="display:none;top:57%;">
    <div class="rh-row">
      <span class="rh-label">房间</span>
      <span class="rh-room" id="hubRoom">------</span>
      <button class="rh-copy" id="hubCopy" title="复制房间号">复制</button>
    </div>
    <div class="rh-seats">
      <div class="rh-seat red" id="hubSeatRed"><span class="rh-dot"></span>红方 <b id="hubRedState">空缺</b></div>
      <div class="rh-seat blue" id="hubSeatBlue"><span class="rh-dot"></span>蓝方 <b id="hubBlueState">空缺</b></div>
    </div>
    <div class="rh-you"><span id="hubYou">观战中</span></div>
  </div>

  <style>
    #lobby {
      position: fixed;
      inset: 0;
      z-index: 60;
      display: flex;
      align-items: center;
      justify-content: center;
      background: radial-gradient(circle at 50% 40%, rgba(6, 16, 28, .85), rgba(2, 8, 14, .95));
      backdrop-filter: blur(5px);
    }

    #lobby.hidden {
      display: none;
    }

    .lobby-card {
      width: 320px;
      padding: 26px;
      border-radius: 16px;
      text-align: center;
      background: linear-gradient(155deg, rgba(20, 40, 64, .92), rgba(10, 22, 38, .96));
      border: 1px solid rgba(140, 190, 255, .45);
      box-shadow: 0 0 50px rgba(60, 140, 240, .4);
    }

    .lobby-card h2 {
      color: #eaf2ff;
      letter-spacing: .2em;
      margin-bottom: 16px;
      text-shadow: 0 0 18px rgba(90, 160, 255, .6);
    }

    .lobby-msg {
      min-height: 18px;
      color: #ff9b9e;
      font-size: 13px;
      margin-bottom: 12px;
    }

    .lobby-row {
      display: flex;
      gap: 8px;
      margin: 10px 0;
    }

    #lobby input {
      flex: 1;
      width: 100%;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid rgba(140, 190, 255, .4);
      background: rgba(8, 18, 30, .8);
      color: #dbe9ff;
      font-size: 14px;
      margin: 6px 0;
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
    }

    .lobby-status {
      margin-top: 12px;
      font-size: 12px;
      color: #8fa6c4;
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


    #roomHub {
      top: 57%;
      left: 18px;
      transform: translateY(-50%);
      width: 232px;
      padding: 10px 14px;
      text-align: center;
    }

    #roomHub .rh-row {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin-bottom: 8px;
    }

    #roomHub .rh-label {
      font-size: 12px;
      letter-spacing: .16em;
      color: #86b4ff;
    }

    #roomHub .rh-room {
      font-size: 20px;
      font-weight: 800;
      letter-spacing: .18em;
      color: #eaf2ff;
      text-shadow: 0 0 14px rgba(120, 180, 255, .5);
    }

    #roomHub .rh-copy {
      border: 1px solid rgba(140, 190, 255, .4);
      background: rgba(8, 18, 30, .7);
      color: #9cc4ff;
      font-size: 11px;
      padding: 3px 8px;
      border-radius: 6px;
      cursor: pointer;
      font-family: inherit;
      transition: all .15s;
    }

    #roomHub .rh-copy:hover {
      background: rgba(74, 155, 255, .25);
      color: #eaf2ff;
    }

    #roomHub .rh-seats {
      display: flex;
      gap: 8px;
      margin-bottom: 7px;
    }

    #roomHub .rh-seat {
      flex: 1;
      font-size: 12px;
      padding: 5px 6px;
      border-radius: 7px;
      border: 1px solid rgba(150, 195, 255, .2);
      background: rgba(255, 255, 255, .03);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 5px;
    }

    #roomHub .rh-seat b {
      font-weight: 700;
    }

    #roomHub .rh-seat.red b {
      color: #ff9b9e;
    }

    #roomHub .rh-seat.blue b {
      color: #9cc4ff;
    }



    #roomHub .rh-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #4a5a70;
    }

    #roomHub .rh-seat.red .rh-dot {
      background: #ff5d62;
      box-shadow: 0 0 8px #ff5d62;
    }

    #roomHub .rh-seat.blue .rh-dot {
      background: #5aa0ff;
      box-shadow: 0 0 8px #5aa0ff;
    }

    #roomHub .rh-seat.empty .rh-dot {
      background: #3a4657;
      box-shadow: none;
    }

    #roomHub .rh-you {
      font-size: 12px;
      color: #b7c8e0;
      letter-spacing: .06em;
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
    window.DISPLAY_NAME = '<?php echo isset($gout_display_name) ? addslashes($gout_display_name) : ""; ?>';
  </script>
  <script src="assets/core/wsmanager.js"></script>
  <script src="assets/core/engine.js"></script>
  <script src="assets/core/rating.js"></script>
  <script src="assets/core/ui.js"></script>
  <script src="assets/core/scorefx.js"></script>
  <script src="assets/core/replay.js"></script>
  <script>
    (function() {
      const saved_msgshow = (localStorage.getItem('fhq_chatToggle') === 'true') ?? true;
      const $ = id => document.getElementById(id);
      const lobby = $("lobby"),
        msg = $("lobbyMsg"),
        status = $("lobbyStatus"),
        netbar = $("netbar");
      const params = new URLSearchParams(location.search);

      function toast(t) {
        msg.textContent = t || "";
        if (t) setTimeout(() => {
          if (msg.textContent === t) msg.textContent = "";
        }, 2500);
      }

      function toasts_v2(t) {
        const el = document.getElementById("toastContainer");
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

      function themedConfirm(title, msg, onYes) {
        let ov = document.getElementById("confirmOverlay");
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
        onError: () => {
          status.textContent = "无法连接服务端...嗷了个嗷";
        },
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
        const rec = Replay.attachRecorder({
          mode: "casual"
        });

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
                if (res && !res.ok) toast(res.reason);
              });
              return null;
            },
            onRestart: () => socket.emit("restart"),
          });
        }

        function toast(t) {
          const msgEl = document.getElementById("lobbyMsg");
          if (msgEl) msgEl.textContent = t || "";
          if (t) setTimeout(() => {
            const m = document.getElementById("lobbyMsg");
            if (m && m.textContent === t) m.textContent = "";
          }, 2500);
        }

        function sideCN(s) {
          return s === "red" ? "红方" : s === "blue" ? "蓝方" : "观战";
        }

        function updateHub() {
          const over = engine.state.over;
          const active = readyToPlay && mySide !== "spectator" && !over;
          const bs = document.getElementById("btnSurrender"),
            bd = document.getElementById("btnDraw");
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
            UI.settle({
              panel: "ratingPanel",
              match: (engine.state.over && data.rating) ? data.rating : null,
              side: view,
              cardSide: view,
              extra: {
                hint: "点击任意处关闭"
              },
            });
          }
          updateRoomHub(data.seats || {});
        }

        function updateRoomHub(seats) {
          const hub = document.getElementById("roomHub");
          if (!hub) return;
          if (!roomId) {
            hub.style.display = "none";
            return;
          }
          hub.style.display = "block";
          const setRoom = document.getElementById("hubRoom");
          if (setRoom) setRoom.textContent = roomId;

          const redOn = !!seats.red,
            blueOn = !!seats.blue;
          const redEl = document.getElementById("hubSeatRed"),
            blueEl = document.getElementById("hubSeatBlue");
          if (redEl) redEl.classList.toggle("empty", !redOn);
          if (blueEl) blueEl.classList.toggle("empty", !blueOn);
          const rs = document.getElementById("hubRedState"),
            bs = document.getElementById("hubBlueState");
          if (rs) rs.textContent = redOn ? "在座" : "空缺";
          if (bs) bs.textContent = blueOn ? "在座" : "空缺";

          const you = document.getElementById("hubYou");
          if (you) {
            if (mySide === "spectator") you.textContent = "你正在观战";
            else if (engine.state.over) you.textContent = `你：${sideCN(mySide)}　· 对局结束`;
            else if (!readyToPlay) you.textContent = `你：${sideCN(mySide)}　· 等待对手就位`;
            else you.textContent = `你：${sideCN(mySide)}　· ` + (engine.state.turn === mySide ? "轮到你落子" : "等待对方落子");
          }
        }

        function enterRoom(res) {
          if (!res || !res.ok) {
            toast(res && res.reason || "操作失败");
            return;
          }
          roomId = res.room;
          mySide = res.side;
          const lobby = document.getElementById("lobby");
          if (lobby) lobby.classList.add("hidden");
          socket.emit("sync", null, s => {
            if (s && s.ok) applyState(s);
          });
        }
        const action = params.get("action");
        const roomParam = params.get("room");
        (function autoEnter() {
          if (action === "create") {
            params.set('action', 'fu99');
            history.replaceState(null, '', '?' + params.toString());
            socket.emit("createRoom", {
              name: params.get("name") || "",
              pwd: params.get("pwd") || "",
              spectate: params.get("spectate") !== "0",
              host: window.DISPLAY_NAME || "",
            }, enterRoom);
          } else if (action === "join" && roomParam) {
            socket.emit("joinRoom", {
              room: roomParam.toUpperCase(),
              pwd: params.get("pwd") || ""
            }, enterRoom);
          }
        })();
        const btnCreate = document.getElementById("btnCreate");
        const btnJoin = document.getElementById("btnJoin");
        const roomInput = document.getElementById("roomInput");
        const pwdInput = document.getElementById("pwdInput");

        if (btnCreate) {
          btnCreate.onclick = () => {
            socket.emit("createRoom", {
              pwd: (pwdInput && pwdInput.value.trim()) || ""
            }, enterRoom);
          };
        }

        if (btnJoin) {
          btnJoin.onclick = () => {
            const room = roomInput && roomInput.value.trim().toUpperCase();
            if (!room) return toast("请输入房间号");
            socket.emit("joinRoom", {
              room,
              pwd: (pwdInput && pwdInput.value.trim()) || ""
            }, enterRoom);
          };
        }

        function returnToLobby(reason) {
          socket.emit("leaveRoom");
          roomId = null;
          mySide = "spectator";
          readyToPlay = false;
          const ov = document.getElementById("winOverlay");
          if (ov) ov.classList.remove("show");
          const dov = document.getElementById("drawOverlay");
          if (dov) dov.classList.remove("show");
          const lobby = document.getElementById("lobby");
          if (lobby) lobby.classList.remove("hidden");
          const nb = document.getElementById("netbar");
          if (nb) nb.textContent = "";
          const hub = document.getElementById("roomHub");
          if (hub) hub.style.display = "none";
          if (reason) toast(reason);
        }
        (function wireHubCopy() {
          const btn = document.getElementById("hubCopy");
          if (!btn) return;
          btn.onclick = () => {
            if (!roomId) return;
            const ok = () => {
              btn.textContent = "已复制";
              setTimeout(() => {
                btn.textContent = "复制";
              }, 1200);
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
              navigator.clipboard.writeText(roomId).then(ok).catch(() => {});
            } else {
              const ta = document.createElement("textarea");
              ta.value = roomId;
              document.body.appendChild(ta);
              ta.select();
              try {
                document.execCommand("copy");
                ok();
              } catch (_) {}
              document.body.removeChild(ta);
            }
          };
        })();

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
          const list = document.getElementById("chatList");
          if (!list) return;
          const li = document.createElement("li");
          li.innerHTML = `<span class="who ${side}">${sideCN(side)}</span>${escapeHTML(text)}`;
          list.appendChild(li);
          while (list.children.length > 60) list.removeChild(list.firstChild);
          list.scrollTop = list.scrollHeight;
        }

        function sendChat() {
          const input = document.getElementById("chatInput");
          const text = input && input.value.trim();
          if (!text) return;
          socket.emit("chat", {
            text
          }, res => {
            if (res && !res.ok) toast(res.reason);
          });
          if (input) input.value = "";
        }
        const chatSend = document.getElementById("chatSend"),
          chatInput = document.getElementById("chatInput");
        if (chatSend) chatSend.onclick = sendChat;
        if (chatInput) chatInput.addEventListener("keydown", e => {
          if (e.key === "Enter") sendChat();
        });
        const btnSurrender = document.getElementById("btnSurrender"),
          btnDraw = document.getElementById("btnDraw");
        if (btnSurrender) btnSurrender.onclick = () => {
          themedConfirm("确认投降", "本局将判负，确定投降？", () => {
            socket.emit("surrender", null, res => {
              if (res && !res.ok) toast(res.reason);
            });
          });
        };
        if (btnDraw) btnDraw.onclick = () => {
          socket.emit("drawOffer", null, res => {
            if (res && !res.ok) toasts_v2(res.reason);
            else toasts_v2("已发送和棋请求");
          });
        };
        const winExit = document.getElementById("winExit"),
          winContinue = document.getElementById("winContinue");
        if (winExit) winExit.onclick = () => returnToLobby();
        if (winContinue) winContinue.onclick = () => {
          const ov = document.getElementById("winOverlay");
          if (ov) ov.classList.remove("show");
          socket.emit("restart", null, res => {
            if (res && !res.ok) toast(res.reason);
          });
        };
        const drawAccept = document.getElementById("drawAccept"),
          drawDecline = document.getElementById("drawDecline");

        function hideDrawPrompt() {
          const d = document.getElementById("drawOverlay");
          if (d) d.classList.remove("show");
        }
        if (drawAccept) drawAccept.onclick = () => {
          socket.emit("drawRespond", {
            accept: true
          });
          hideDrawPrompt();
        };
        if (drawDecline) drawDecline.onclick = () => {
          socket.emit("drawRespond", {
            accept: false
          });
          hideDrawPrompt();
        };
        socket.on("state", applyState);
        socket.on("opponentLeft", ({
          side
        }) => {
          toasts_v2(`${sideCN(side)}离开了房间`);
        });

        socket.on("chat", ({
          side,
          text
        }) => appendChat(side, text));
        socket.on("drawOffered", () => {
          const d = document.getElementById("drawOverlay");
          if (d) d.classList.add("show");
        });
        socket.on("drawDeclined", () => toasts_v2("对方拒绝了和棋"));
        socket.on("kicked", ({
          reason
        }) => returnToLobby(reason || "你已被移出房间"));
        socket.on("roomClosed", ({
          reason
        }) => returnToLobby(reason || "房间已关闭"));
        socket.on("becameHost", ({
          side,
          reason
        }) => {
          mySide = side || "red";
          readyToPlay = false;
          toasts_v2(reason || "你已成为房主");
        });
        socket.on("disconnect", () => {
          const netbar = document.getElementById("netbar");
          if (netbar) netbar.textContent = "连接断开，重连中…";
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
        socket.on("connect_error", () => {
          const status = document.getElementById("lobbyStatus");
          if (status) status.textContent = "连接服务端失败...嗷了个嗷";
        });
      }
    }());
  </script>
</body>

</html>