<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

function sendResponse($success, $message, $data = null)
{
    $response = [
        'success' => $success,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['email']) || !isset($data['UserId']) || !isset($data['nickname']) || !isset($data['avatarv'])) {
    sendResponse(false, '参数不完整');
}

$user_email = $data['email'];
$user_nickname = $data['nickname'];
$user_avatar = $data['avatarv'];
$userIds = $data['UserId'];

require($_SERVER['DOCUMENT_ROOT'] . '/cofd/tauth.php');
$decryptor = new TmdbaseauthdownyhoDecrypt();
$plaintext = $decryptor->writebacknewwords($userIds);

if (!$plaintext) {
    sendResponse(false, '令牌验证失效');
}
if (!conbine_auth_towdouble($plaintext)) {
    sendResponse(false, '令牌验证失效');
}
$userId = $plaintext;
require($_SERVER['DOCUMENT_ROOT'] . '/cofd/common.php');
$user_nickname = htmlspecialchars(trim($user_nickname), ENT_QUOTES, 'UTF-8');
$user_email = htmlspecialchars(trim($user_email), ENT_QUOTES, 'UTF-8');
$user_avatar = htmlspecialchars(trim($user_avatar), ENT_QUOTES, 'UTF-8');
$nickname_len = mb_strlen($user_nickname, 'UTF-8');
if ($nickname_len === 0) {
    sendResponse(false, '昵称不能为空');
}
if ($nickname_len > 8) {
    sendResponse(false, '昵称不能超过8个字符');
}
if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
    sendResponse(false, '邮箱格式不正确');
}
$protocol_pattern = '/^(https?:\/\/|ftp:\/\/|file:\/\/|data:)/i';
if (preg_match($protocol_pattern, $user_avatar)) {
    sendResponse(false, '头像参数包含非法协议头');
}
$avatar_filename = basename($user_avatar);
$avatar_ext = strtolower(pathinfo($avatar_filename, PATHINFO_EXTENSION));
if (!in_array($avatar_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'])) {
    sendResponse(false, '头像文件格式不支持');
}
$avatar_path = $_SERVER['DOCUMENT_ROOT'] . '/assets/photo/' . $avatar_filename;
if (!file_exists($avatar_path)) {
    sendResponse(false, '头像文件不存在');
}

try {
    $conn->begin_transaction();
    $stmt = $conn->prepare("UPDATE mok_user SET uname = ?, bdmail = ?, tximg = ? WHERE id = ?");
    if (!$stmt) {
        throw new Exception('数据库准备失败: ' . $conn->error);
    }
    if ($user_email === '未绑定' || $user_email === '') {
        $user_email = null;
    }
    $avatar_stored = '(&&)::' . $avatar_filename;
    $stmt->bind_param('ssss', $user_nickname, $user_email, $avatar_stored, $userId);
    if (!$stmt->execute()) {
        throw new Exception('数据库更新失败: ' . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        $check_stmt = $conn->prepare("SELECT id FROM mok_user WHERE id = ?");
        $check_stmt->bind_param('s', $userId);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        if ($result->num_rows === 0) {
            throw new Exception('用户不存在');
        }
        $check_stmt->close();
    }
    $conn->commit();
    $stmt->close();
    $expire_time = time() + 3600 * 2;
    setcookie("mokim_usergname", $user_nickname ?? 'null', $expire_time, '/');
    setcookie("mokim_useremail", $user_email ?? 'null', $expire_time, '/');
    sendResponse(true, '个人信息更新成功', [
        'nickname' => $user_nickname,
        'email' => $user_email,
        'avatar' => $avatar_filename
    ]);
} catch (Exception $e) {
    $conn->rollback();
    sendResponse(false, '信息修改失败：' . $e->getMessage());
} finally {
    $conn->close();
}
