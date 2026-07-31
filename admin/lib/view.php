<?php

/** 布局与样式 */
if (!defined('FHQ_ADMIN')) {
    http_response_code(403);
    exit('forbidden');
}

function adm_head($title, $me = null)
{
    $t = adm_h($title);
    echo <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$t} · 烽火棋后台</title>
<style>
:root{--ink:#f0e6d8;--gold:#e8a838;--gold-dim:#c88a2a;--bg:#14100a;--surface:#1e1810;
      --elev:#282017;--line:rgba(232,168,56,.18);--muted:rgba(240,230,216,.55);
      --ok:#7ee6a2;--bad:#ff8f8f}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--ink);
     font:14px/1.6 "Microsoft YaHei","PingFang SC",system-ui,sans-serif}
body::before{content:"";position:fixed;inset:0;pointer-events:none;z-index:0;
     background:radial-gradient(ellipse at 20% 0%,rgba(232,168,56,.07),transparent 60%)}
a{color:var(--gold);text-decoration:none}
a:hover{text-decoration:underline}
.wrap{display:flex;min-height:100vh;position:relative;z-index:1}
.side{width:210px;flex:none;background:var(--surface);border-right:1px solid var(--line);
      padding:18px 0;position:sticky;top:0;height:100vh;overflow-y:auto}
.brand{font-size:17px;font-weight:800;color:var(--gold);padding:0 18px 14px;
       border-bottom:1px solid var(--line);margin-bottom:12px}
.grp{font-size:11px;color:var(--muted);padding:12px 18px 5px;letter-spacing:.08em}
.side a{display:block;padding:8px 18px;color:var(--ink);border-left:2px solid transparent}
.side a:hover{background:var(--elev);text-decoration:none}
.side a.on{background:var(--elev);border-left-color:var(--gold);color:var(--gold);font-weight:700}
.main{flex:1;padding:22px 26px;min-width:0}
.top{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;
     padding-bottom:12px;border-bottom:1px solid var(--line)}
h1{font-size:19px;margin:0;color:var(--gold);font-weight:700}
.who{font-size:12px;color:var(--muted)}
.card{background:var(--surface);border:1px solid var(--line);border-radius:10px;
      padding:16px;margin-bottom:16px;box-shadow:0 2px 12px rgba(0,0,0,.3)}
table{width:100%;border-collapse:collapse;font-size:13px}
th,td{padding:8px 10px;text-align:left;border-bottom:1px solid rgba(240,230,216,.07);
      white-space:nowrap;max-width:280px;overflow:hidden;text-overflow:ellipsis}
th{color:var(--gold-dim);font-weight:600;font-size:12px;background:rgba(0,0,0,.2);position:sticky;top:0}
th a{color:var(--gold-dim)}
tbody tr:hover{background:rgba(232,168,56,.05)}
.scroll{overflow-x:auto;border-radius:8px}
.btn{display:inline-block;padding:6px 13px;border-radius:6px;border:1px solid var(--line);
     background:var(--elev);color:var(--ink);cursor:pointer;font-size:13px;font-family:inherit}
.btn:hover{border-color:var(--gold);text-decoration:none}
.btn.pri{background:var(--gold);color:#241a08;border-color:var(--gold);font-weight:700}
.btn.danger{border-color:rgba(255,143,143,.4);color:var(--bad)}
.btn.sm{padding:3px 9px;font-size:12px}
HTML;
    adm_head_css2();
    adm_head_body($me);
}

function adm_head_css2()
{
    echo <<<HTML
input,select,textarea{width:100%;padding:7px 10px;border-radius:6px;background:#0f0c07;
     color:var(--ink);border:1px solid var(--line);font:13px/1.5 inherit;font-family:inherit}
input:focus,select,textarea:focus{outline:none;border-color:var(--gold)}
input:disabled,textarea:disabled,select:disabled{opacity:.5;cursor:not-allowed}
textarea.mono{font-family:Consolas,"Courier New",monospace;font-size:12px}
label{display:block;font-size:12px;color:var(--muted);margin-bottom:4px}
.field{margin-bottom:13px}
.field .hint{font-size:11px;color:var(--muted);margin-top:3px}
.grid2{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:0 18px}
.bar{display:flex;gap:9px;align-items:center;flex-wrap:wrap;margin-bottom:14px}
.bar input[type=search]{width:230px}
.badge{display:inline-block;padding:1px 8px;border-radius:20px;font-size:12px;
       background:rgba(240,230,216,.1);border:1px solid rgba(240,230,216,.15)}
.badge.ok{color:var(--ok);background:rgba(126,230,162,.1);border-color:rgba(126,230,162,.3)}
.badge.bad{color:var(--bad);background:rgba(255,143,143,.1);border-color:rgba(255,143,143,.3)}
.nil{color:rgba(240,230,216,.28);font-style:italic;font-size:12px}
.json{font-family:Consolas,monospace;font-size:11px;color:#c7a1ff;background:rgba(199,161,255,.08);
      padding:1px 6px;border-radius:4px}
.msg{padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:13px}
.msg.ok{background:rgba(126,230,162,.1);border:1px solid rgba(126,230,162,.35);color:var(--ok)}
.msg.err{background:rgba(255,143,143,.1);border:1px solid rgba(255,143,143,.35);color:var(--bad)}
.pager{display:flex;gap:6px;align-items:center;margin-top:14px;font-size:13px;flex-wrap:wrap}
.pager a,.pager span{padding:4px 10px;border-radius:5px;border:1px solid var(--line)}
.pager .cur{background:var(--gold);color:#241a08;border-color:var(--gold);font-weight:700}
.pager .gap{border:none}
.stat{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:13px}
.stat .box{background:var(--elev);border:1px solid var(--line);border-radius:9px;padding:14px}
.stat .n{font-size:25px;font-weight:800;color:var(--gold);line-height:1.2}
.stat .k{font-size:12px;color:var(--muted);margin-top:3px}
.acts{display:flex;gap:6px;flex-wrap:wrap}
.sec{font-size:13px;font-weight:700;color:var(--gold-dim);margin:4px 0 12px;
     padding-bottom:7px;border-bottom:1px solid var(--line)}
.ro{font-size:11px;color:var(--muted);margin-left:5px}
@media(max-width:760px){.wrap{flex-direction:column}.side{width:100%;height:auto;position:static}
  .main{padding:16px}}
</style>
</head>
HTML;
}
/** 侧栏 */
function adm_head_body($me)
{
    $cur = isset($_GET['t']) ? (string)$_GET['t'] : '';
    $page = isset($_GET['p']) ? (string)$_GET['p'] : 'home';
    $who = $me ? adm_h($me['user']) : '';

    echo '<body><div class="wrap"><nav class="side">';
    echo '<div class="brand">烽火棋 · 后台</div>';

    $onHome = ($page === 'home') ? ' class="on"' : '';
    echo '<a href="index.php?p=home"' . $onHome . '>总览</a>';

    $groups = array();
    foreach (adm_tables() as $name => $cfg) {
        $g = isset($cfg['group']) ? $cfg['group'] : '其它';
        $groups[$g][$name] = $cfg;
    }
    foreach ($groups as $gName => $items) {
        echo '<div class="grp">' . adm_h($gName) . '</div>';
        foreach ($items as $name => $cfg) {
            $on = ($page === 'table' && $cur === $name) ? ' class="on"' : '';
            echo '<a href="index.php?p=table&t=' . adm_h($name) . '"' . $on . '>'
                . adm_h($cfg['label']) . '</a>';
        }
    }

    $onSet = ($page === 'settings') ? ' class="on"' : '';
    $onEnv = ($page === 'env') ? ' class="on"' : '';
    $onLog = ($page === 'audit') ? ' class="on"' : '';
    echo '<div class="grp">系统</div>';
    echo '<a href="index.php?p=settings"' . $onSet . '>站点配置</a>';
    echo '<a href="index.php?p=env"' . $onEnv . '>服务端配置</a>';
    echo '<a href="index.php?p=audit"' . $onLog . '>操作日志</a>';
    echo '<a href="logout.php">退出登录</a>';
    echo '</nav><main class="main">';
    echo '<div class="top"><h1 id="pgTitle"></h1><div class="who">' . $who . '</div></div>';
}

function adm_title($t)
{
    echo '<script>document.getElementById("pgTitle").textContent='
        . json_encode($t, JSON_UNESCAPED_UNICODE) . ';</script>';
}

function adm_foot()
{
    echo '</main></div></body></html>';
}

function adm_msg($msg, $ok = true)
{
    if ($msg === '' || $msg === null) return;
    echo '<div class="msg ' . ($ok ? 'ok' : 'err') . '">' . adm_h($msg) . '</div>';
}
?>
<style>
    ::-webkit-scrollbar{
        width: 8px;
        height: px;
        background-color:aqua;
    }
</style>