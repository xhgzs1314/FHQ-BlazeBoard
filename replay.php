<?php
$FHQ_INJECT = '';  
$FHQ_ERR = '';
if (isset($_GET['rid'])) {
    $rid = trim((string)$_GET['rid']);
    $sig = (string)($_GET['t'] ?? '');
    $secret = '';
    $__autoload = __DIR__ . '/vendor/autoload.php';
    if (is_file($__autoload)) {
        require_once $__autoload;
        if (class_exists('Dotenv\\Dotenv')) {
            try { \Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad(); } catch (\Throwable $e) {}
        }
    }
    $secret = $_ENV['API_SECRET_KEY_mok'] ?? getenv('API_SECRET_KEY_mok') ?: '';
    $expect = $secret === '' ? '' : hash_hmac('sha256', $rid, $secret);
    if ($rid === '' || $sig === '' || $expect === '' || !hash_equals($expect, $sig)) {
        $FHQ_ERR = '回放链接无效或已失效';
    } else {
        require __DIR__ . '/cofd/common.php';  
        if ($st = $conn->prepare("SELECT replay_data FROM mok_replay WHERE replay_id=? LIMIT 1")) {
            $st->bind_param('s', $rid);
            $st->execute();
            $st->bind_result($blob);
            if ($st->fetch() && $blob !== null && $blob !== '') {
                $FHQ_INJECT = base64_encode($blob);  
            } else {
                $FHQ_ERR = '回放不存在';
            }
            $st->close();
        } else {
            $FHQ_ERR = '回放读取失败';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
  <title>烽火棋 · 对局回放</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="assets/game.css">
</head>
<style>
  .win-buttons {
    display: flex;
    gap: 12px;
    margin-top: 20px;
    justify-content: center;
    flex-wrap: wrap;
  }

  .win-btn {
    padding: 10px 22px;
    border: none;
    border-radius: 9px;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    background: linear-gradient(135deg, #7fd0ff, #4a9bff);
    color: #071018;
    transition: transform 0.15s, box-shadow 0.15s;
    letter-spacing: 0.05em;
  }

  .win-btn:hover {
    transform: scale(1.03);
    box-shadow: 0 0 20px rgba(74, 155, 255, 0.5);
  }

  .win-btn:active {
    transform: scale(0.95);
  }

  #winLobby {
    background: linear-gradient(135deg, #b7c8e0, #7f96b8);
  }
</style>

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
    <div class="fog-hint">回放按所选视角还原当时可见范围</div>
  </div>

  <div id="legend" class="panel">
    <h3>◈ 棋子图例</h3>
    <div class="legend-grid" id="legendGrid"></div>
  </div>

  <div id="logpanel" class="panel">
    <h3>◈ 操作记录</h3>
    <ul class="log-list" id="logList">
      <li>请载入回放文件</li>
    </ul>
    <button class="rp-back" onclick="window.close();setTimeout(function() {location.href = 'index.php';}, 200);">回 到 大
      厅</button>
  </div>

  <div id="board"></div>

  <div id="winOverlay">
    <div class="win-inner">
      <div class="win-card" id="winCard">获胜</div>
      <div class="win-buttons">
        <button class="win-btn" id="winReplay"> 重新播放</button>
        <button class="win-btn" id="winLoad"> 选择回放文件</button>
        <button class="win-btn" id="winLobby"> 返回</button>
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


  <div id="loadOverlay">
    <div class="load-card">
      <h2>烽火棋 · 对局回放</h2>
      <p class="load-tip">上传回放文件，一比一复原对局（支持倍速）</p>
      <label id="fileLabel" for="replayFile">选择回放文件</label>
      <input type="file" id="replayFile" accept=".json,.fhq,application/json" hidden />
      <div id="loadMsg" class="load-msg"></div>
      <button class="rp-back"
        onclick="window.close();setTimeout(function() {location.href = 'index.php';}, 200);">返回</button>
    </div>
  </div>
  <div id="replayBar" style="display:none">
    <div class="rb-meta" id="rbMeta"></div>
    <div class="rb-controls">
      <button id="rbFirst" title="回到开局">⏮</button>
      <button id="rbPrev" title="上一步">◀</button>
      <button id="rbPlay" title="播放/暂停">▶</button>
      <button id="rbNext" title="下一步">▶</button>
      <button id="rbLast" title="跳到结束">⏭</button>
      <input type="range" id="rbSeek" min="0" max="0" value="0" />
      <span class="rb-idx" id="rbIdx">0 / 0</span>
      <select id="rbSpeed" title="倍速">
        <option value="0.5">0.5×</option>
        <option value="1" selected>1×</option>
        <option value="2">2×</option>
        <option value="4">4×</option>
        <option value="8">8×</option>
      </select>
      <select id="rbView" title="视角">
        <option value="red">红方视角</option>
        <option value="blue">蓝方视角</option>
        <option value="follow">跟随当前方</option>
        <option value="both">全局视角</option>
      </select>
      <button id="rbOpen" title="载入其它回放">载入</button>
    </div>
  </div>

  <style>
    #loadOverlay {
      position: fixed;
      inset: 0;
      z-index: 60;
      display: flex;
      align-items: center;
      justify-content: center;
      background: radial-gradient(circle at 50% 40%, rgba(6, 16, 28, .85), rgba(2, 8, 14, .95));
      backdrop-filter: blur(5px);
    }

    #loadOverlay.hidden {
      display: none;
    }

    .load-card {
      width: 360px;
      padding: 30px 26px;
      border-radius: 16px;
      text-align: center;
      background: linear-gradient(155deg, rgba(20, 40, 64, .92), rgba(10, 22, 38, .96));
      border: 1px solid rgba(140, 190, 255, .45);
      box-shadow: 0 0 50px rgba(60, 140, 240, .4);
    }

    .load-card h2 {
      color: #eaf2ff;
      letter-spacing: .18em;
      margin-bottom: 12px;
      text-shadow: 0 0 18px rgba(90, 160, 255, .6);
    }

    .load-tip {
      color: #9fb4d4;
      font-size: 12.5px;
      line-height: 1.7;
      margin-bottom: 20px;
    }

    #fileLabel,
    .rp-back {
      display: inline-block;
      padding: 11px 22px;
      border: none;
      border-radius: 9px;
      font-weight: 700;
      letter-spacing: .1em;
      cursor: pointer;
      color: #071018;
      font-family: inherit;
      font-size: 14px;
      background: linear-gradient(135deg, #7fd0ff, #4a9bff);
    }

    .rp-back {
      margin-top: 14px;
      background: linear-gradient(135deg, #b7c8e0, #7f96b8);
    }

    .load-msg {
      min-height: 18px;
      color: #ff9b9e;
      font-size: 13px;
      margin: 16px 0 4px;
    }

    #replayBar {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      z-index: 40;
      opacity: 0.05;
      padding: 10px 18px 12px;
      background: linear-gradient(0deg, rgba(6, 14, 26, .96), rgba(10, 22, 38, .82));
      border-top: 1px solid rgba(140, 190, 255, .35);
      backdrop-filter: blur(6px);
    }

    #replayBar:hover {
      opacity: 1;
    }

    .rb-meta {
      text-align: center;
      color: #9cc4ff;
      font-size: 12px;
      letter-spacing: .08em;
      margin-bottom: 8px;
      min-height: 15px;
    }

    .rb-controls {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      flex-wrap: wrap;
    }

    .rb-controls button,
    .rb-controls select {
      padding: 7px 12px;
      border: 1px solid rgba(140, 190, 255, .4);
      border-radius: 8px;
      background: rgba(8, 18, 30, .8);
      color: #dbe9ff;
      font-family: inherit;
      font-size: 13px;
      cursor: pointer;
      transition: background .15s, transform .1s;
    }

    .rb-controls button:hover {
      background: rgba(74, 155, 255, .28);
    }

    .rb-controls button:active {
      transform: scale(.94);
    }

    #rbPlay {
      min-width: 46px;
      font-weight: 800;
      color: #8effc0;
    }

    #rbNext {
      transform: none;
    }

    #rbSeek {
      flex: 1;
      min-width: 180px;
      max-width: 420px;
      accent-color: #4a9bff;
      cursor: pointer;
    }

    .rb-idx {
      color: #b7c8e0;
      font-size: 12px;
      min-width: 70px;
      text-align: center;
    }

    #rbOpen {
      color: #9cc4ff;
    }
  </style>

  <script src="assets/core/engine.js"></script>
  <script src="assets/core/rating.js"></script>
  <script src="assets/core/ui.js"></script>
  <script src="assets/core/replay.js"></script>
  <script>
    (function () {
      const $ = id => document.getElementById(id);
      const loadOverlay = $("loadOverlay"), loadMsg = $("loadMsg");
      const bar = $("replayBar");

      const player = Replay.createPlayer({ Engine, UI }, {
        onFrame: (idx, total, data, f) => {
          $("rbSeek").max = total - 1;
          $("rbSeek").value = idx;
          $("rbIdx").textContent = (idx) + " / " + (total - 1);
          $("rbPlay").textContent = player.playing ? "❚❚" : "▶";
        },
        onEnd: () => { $("rbPlay").textContent = "▶"; },
      });

      function fmtDate(iso) { try { return new Date(iso).toLocaleString("zh-CN"); } catch (_) { return ""; } }
      function modeCN(m) { return m === "ranked" ? "排位" : m === "casual" ? "娱乐匹配" : "本地对战"; }
      function winnerCN(w) { return w === "red" ? "红方胜" : w === "blue" ? "蓝方胜" : w === "draw" ? "和棋" : "—"; }
      function showMeta(d) {
        $("rbMeta").textContent =
          `${modeCN(d.mode)}　红：${d.red}　蓝：${d.blue}　结果：${winnerCN(d.winner)}　共 ${d.moves} 步　录于 ${fmtDate(d.savedAt)}`;
      }

      function handleFile(file) {
        if (!file) return;
        const reader = new FileReader();
        reader.onload = () => {
          try {
            const d = player.load(reader.result);
            showMeta(d);
            loadOverlay.classList.add("hidden");
            bar.style.display = "block";
          } catch (e) {
            loadMsg.textContent = "无法解析：" + (e && e.message || "文件损坏");
          }
        };
        reader.onerror = () => { loadMsg.textContent = "读取文件失败"; };
        reader.readAsText(file);
      }

      $("replayFile").addEventListener("change", e => handleFile(e.target.files[0]));
      $("rbOpen").onclick = () => { loadOverlay.classList.remove("hidden"); loadMsg.textContent = ""; $("replayFile").value = ""; };
      ["dragover", "drop"].forEach(ev => document.addEventListener(ev, e => { e.preventDefault(); }));
      document.addEventListener("drop", e => {
        const f = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
        if (f) handleFile(f);
      });
      $("rbPlay").onclick = () => {
        try { $("board").dispatchEvent(new MouseEvent("click", { bubbles: true })); } catch (_) { }
        player.toggle();
        $("rbPlay").textContent = player.playing ? "❚❚" : "▶";
      };
      $("rbFirst").onclick = () => player.seek(0);
      $("rbLast").onclick = () => player.seek(player.total - 1);
      $("rbPrev").onclick = () => player.step(-1);
      $("rbNext").onclick = () => player.step(1);
      $("rbSeek").oninput = e => player.seek(+e.target.value);
      $("rbSpeed").onchange = e => player.setSpeed(+e.target.value);
      $("rbView").onchange = e => player.setPerspective(e.target.value);
      $("winReplay").addEventListener('click', function () {
        if (window.player) {
          window.player.seek(0);
          window.player.play();
        }
        $("winOverlay").classList.remove('show');
      });
      $("winLoad").addEventListener('click', function () {
        $('replayFile').click();
        $("winOverlay").classList.remove('show');
      });
      $("winLobby").addEventListener('click', function () {
        window.close();
        setTimeout(function () {
          location.href = 'index.php';
        }, 200);
      });
      var __fhqData = <?php echo json_encode($FHQ_INJECT, JSON_UNESCAPED_SLASHES); ?>;
      var __fhqErr  = <?php echo json_encode($FHQ_ERR, JSON_UNESCAPED_UNICODE); ?>;
      if (__fhqErr) {
        loadMsg.textContent = __fhqErr;
      } else if (__fhqData) {
        try {
          const d = player.load(__fhqData);  
          showMeta(d);
          loadOverlay.classList.add("hidden");
          bar.style.display = "block";
          try { $("board").dispatchEvent(new MouseEvent("click", { bubbles: true })); } catch (_) {}
          player.play();
          $("rbPlay").textContent = player.playing ? "❚❚" : "▶";
        } catch (e) {
          loadMsg.textContent = "无法解析回放：" + (e && e.message || "数据损坏");
        }
      }
    })();
  </script>
</body>

</html>