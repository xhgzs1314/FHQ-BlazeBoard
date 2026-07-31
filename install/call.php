<?php
header('Content-Type: application/json; charset=utf-8');

function out($ok, $msg, $extra = array())
{
    echo json_encode(array_merge(array('ok' => $ok, 'msg' => $msg), $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(false, '仅接受 POST');

$dbHost   = isset($_POST['dbHost'])     ? trim((string)$_POST['dbHost'])     : '';
$dbName   = isset($_POST['dbName'])     ? trim((string)$_POST['dbName'])     : '';
$dbUser   = isset($_POST['dbUsername']) ? trim((string)$_POST['dbUsername']) : '';
$dbPass   = isset($_POST['dbPassword']) ? (string)$_POST['dbPassword']       : '';
$admUser  = isset($_POST['adminUser'])  ? trim((string)$_POST['adminUser'])  : '';
$admPass  = isset($_POST['adminPass'])  ? (string)$_POST['adminPass']        : '';

/* ---------- 校验 ---------- */
if ($dbHost === '' || $dbName === '' || $dbUser === '') out(false, '数据库信息不完整');
if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $dbName)) out(false, '数据库名只能是字母、数字、下划线');
if ($admUser === '' || $admPass === '') out(false, '请填写后台管理员账号与密码');
if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $admUser)) {
    out(false, '管理员账号需为 3–50 位字母、数字或下划线');
}
if (strlen($admPass) < 10) out(false, '管理员密码至少 10 位');
$classes = (preg_match('/[a-z]/', $admPass) ? 1 : 0) + (preg_match('/[A-Z]/', $admPass) ? 1 : 0)
         + (preg_match('/\d/', $admPass) ? 1 : 0) + (preg_match('/[^A-Za-z0-9]/', $admPass) ? 1 : 0);
if ($classes < 3) out(false, '管理员密码需包含大写、小写、数字、符号中的至少三类');

/* ---------- 连接 ---------- */
mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) out(false, '数据库连接失败：' . $conn->connect_error);
$conn->set_charset('utf8mb4');
/* ---------- 防重复 ---------- */
$already = false;
$chk = @$conn->query("SHOW TABLES LIKE 'mok_admin'");
if ($chk && $chk->num_rows > 0) {
    $c = @$conn->query('SELECT COUNT(*) AS n FROM `mok_admin`');
    if ($c) {
        $row = $c->fetch_assoc();
        if ((int)$row['n'] > 0) $already = true;
    }
}
if ($already) {
    out(false, '检测到本站已安装完成（mok_admin 已有账号）。'
        . '如需重装请先手动清空数据库，并删除 install/ 目录以免被他人利用。');
}

/* ---------- 导入 SQL ---------- */
$sqlFile = __DIR__ . '/inl.sql';
if (!is_readable($sqlFile)) out(false, '无法读取 inl.sql');

$errors = array();
$stmt = '';
$fh = fopen($sqlFile, 'r');
while (($line = fgets($fh)) !== false) {
    $t = trim($line);
    if ($t === '' || preg_match('/^\s*(--|#)/', $t)) continue;
    $stmt .= $t . " ";
    if (preg_match('/;\s*$/', $stmt)) {
        $sql = trim($stmt, " \t\n\r\0\x0B;");
        if ($sql !== '' && !$conn->query($sql)) {
            $errors[] = $conn->error;
        }
        $stmt = '';
    }
}
fclose($fh);
if (!empty($errors)) {
    out(false, '建表过程中出错：' . implode('；', array_slice($errors, 0, 3)));
}

/* ---------- 写入 ---------- */
$hash = password_hash($admPass, PASSWORD_BCRYPT, array('cost' => 12));
$ins = $conn->prepare('INSERT INTO `mok_admin` (`username`, `password`) VALUES (?, ?)');
if (!$ins) out(false, '写入管理员失败：' . $conn->error);
$ins->bind_param('ss', $admUser, $hash);
if (!$ins->execute()) out(false, '写入管理员失败：' . $ins->error);
$ins->close();
/* ---------- 生成 config.php ---------- */
$cfg = "<?php\n"
     . '$db_host = ' . var_export($dbHost, true) . ";\n"
     . '$db_user = ' . var_export($dbUser, true) . ";\n"
     . '$db_pass = ' . var_export($dbPass, true) . ";\n"
     . '$db_name = ' . var_export($dbName, true) . ";\n";

$cfgPath = dirname(__DIR__) . '/config.php';
$tmp = $cfgPath . '.tmp' . getmypid();
if (@file_put_contents($tmp, $cfg, LOCK_EX) === false) {
    out(false, '无法写入 config.php，请检查站点根目录权限');
}
if (!@rename($tmp, $cfgPath)) {
    @unlink($tmp);
    out(false, '无法替换 config.php，请检查文件权限');
}
$envNote = '.env(yes)';

$conn->close();
out(true, '安装完成', array(
    'adminUrl' => '/admin/',
    'note'     => $envNote,
    'todo'     => array(
        '立刻删除 install/ 整个目录',
        '配置 Nginx 伪静态（install/nginx-rewrite.conf）或确认 .htaccess 生效',
        '启动联机服务端：npm install && npm start',
    ),
));


