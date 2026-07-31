<?php
require($_SERVER['DOCUMENT_ROOT'] . '/cofd/functions.php');
$qx_max_tmp1 = true;
$q_suname = null;
$tcodelogins = $_COOKIE[generateAutoWebsiteIdentifier((true)) . "_log"] ?? 'null';
if ($tcodelogins == 'null') {
    $qx_max_tmp1 = false;
} else {
    require($_SERVER['DOCUMENT_ROOT'] . '/cofd/tauth.php');
    $decodeers = new TmdbaseauthdownyhoDecrypt(60000 * 60 * 2);
    $decodeddata = $decodeers->writebacknewwords($tcodelogins);
    if (!$decodeddata) {
        $qx_max_tmp1 = false;
    }
    require_once($_SERVER['DOCUMENT_ROOT'] . '/cofd/functions.php');
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
$user_nickname = $_COOKIE['mokim_usergname'] ?? getStableName($q_suname);
$user_email = (isset($_COOKIE['mokim_useremail']) && $_COOKIE['mokim_useremail'] !== 'null' && $_COOKIE['mokim_useremail'] !== '') ? $_COOKIE['mokim_useremail'] : '未绑定';
$page_title = '设置';
require($_SERVER['DOCUMENT_ROOT'] . '/use/set.php');
$avatar_files = [];
if ($qx_max_tmp1) {
    $avatar_dir = $_SERVER['DOCUMENT_ROOT'] . '/assets/photo/';
    $avatar_files = [];
    if (is_dir($avatar_dir)) {
        $files = scandir($avatar_dir);
        foreach ($files as $file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $avatar_files[] = $file;
            }
        }
    }
    if (empty($avatar_files)) {
        $avatar_files = ['avatar.jpg'];
    }
    $current_avatar = $_COOKIE['fhq_avatar'] ?? ($avatar_files[0] ?? 'avatar.jpg');
    if (!in_array($current_avatar, $avatar_files)) {
        $current_avatar = $avatar_files[0];
    }
}
?>
<link rel='stylesheet' href='/assets/message/message.min.css'>
<script src='/assets/message/message.min.js'></script>

<style>
    .edit-drawer {
        position: relative;
        overflow: hidden;
        transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        max-height: 0;
        opacity: 0;
        padding: 0 28px;
        margin: 0;
        background: rgba(0, 0, 0, 0.02);
        border-top: 1px solid transparent;
    }

    .edit-drawer.open {
        max-height: 420px;
        opacity: 1;
        padding: 24px 28px 28px;
        margin-top: 4px;
        border-top-color: rgba(232, 197, 110, 0.15);
    }

    .edit-drawer .field-group {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 10px 0;
        border-bottom: 1px solid rgba(232, 197, 110, 0.06);
    }

    .edit-drawer .field-group:last-child {
        border-bottom: none;
    }

    .edit-drawer .field-label {
        width: 80px;
        font-size: 14px;
        font-weight: 500;
        color: var(--ink-soft);
        flex-shrink: 0;
    }

    .edit-drawer .field-value {
        flex: 1;
        font-size: 15px;
        color: var(--ink);
    }

    .edit-drawer .field-input {
        flex: 1;
        padding: 8px 14px;
        border-radius: 8px;
        border: 1px solid var(--line);
        background: var(--surface);
        color: var(--ink);
        font-size: 14px;
        transition: border 0.2s;
        outline: none;
    }

    .edit-drawer .field-input:focus {
        border-color: var(--gold);
        box-shadow: 0 0 0 3px rgba(232, 197, 110, 0.15);
    }

    .edit-drawer .field-action {
        padding: 6px 18px;
        border-radius: 8px;
        border: none;
        background: var(--gold);
        color: #1a1208;
        font-weight: 600;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.2s;
        flex-shrink: 0;
    }

    .edit-drawer .field-action:hover {
        transform: scale(1.03);
        box-shadow: 0 2px 12px rgba(201, 154, 63, 0.3);
    }

    .edit-drawer .field-action:disabled {
        opacity: 0.4;
        cursor: not-allowed;
        transform: none;
    }

    .edit-drawer .avatar-grid {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        padding: 8px 0 4px;
    }

    .edit-drawer .avatar-option {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        border: 2px solid transparent;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        background: rgba(232, 197, 110, 0.08);
        color: var(--gold);
        flex-shrink: 0;
    }

    .edit-drawer .avatar-option:hover {
        transform: scale(1.08);
        border-color: rgba(232, 197, 110, 0.3);
    }

    .edit-drawer .avatar-option.active {
        border-color: var(--gold);
        background: rgba(232, 197, 110, 0.2);
        box-shadow: 0 0 0 3px rgba(232, 197, 110, 0.15);
    }

    .edit-drawer .avatar-option img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }

    .edit-drawer .drawer-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        margin-top: 18px;
        padding-top: 16px;
        border-top: 1px solid rgba(232, 197, 110, 0.08);
    }

    .edit-drawer .drawer-actions button {
        padding: 8px 28px;
        border-radius: 10px;
        border: none;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .edit-drawer .drawer-actions .btn-cancel {
        background: rgba(255, 255, 255, 0.05);
        color: var(--ink-soft);
    }

    .edit-drawer .drawer-actions .btn-cancel:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    .edit-drawer .drawer-actions .btn-save {
        background: linear-gradient(135deg, #f0d488, #c99a3f);
        color: #1a1208;
        box-shadow: 0 2px 12px rgba(201, 154, 63, 0.2);
    }

    .edit-drawer .drawer-actions .btn-save:hover {
        transform: scale(1.02);
        box-shadow: 0 4px 20px rgba(201, 154, 63, 0.3);
    }

    .avatar-modal-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        border-radius: 16px;
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 10;
    }

    .avatar-modal-overlay.active {
        display: flex;
    }

    .avatar-modal-box {
        background: var(--surface);
        border-radius: 16px;
        padding: 28px 32px 32px;
        max-width: 380px;
        width: 90%;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);
        border: 1px solid rgba(232, 197, 110, 0.15);
    }

    .avatar-modal-box h4 {
        margin: 0 0 6px 0;
        font-size: 18px;
        color: var(--ink);
    }

    .avatar-modal-box p {
        margin: 0 0 18px 0;
        font-size: 14px;
        color: var(--ink-soft);
    }

    .avatar-modal-box .avatar-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 10px;
        margin-bottom: 20px;
    }

    .avatar-modal-box .avatar-opt {
        aspect-ratio: 1;
        border-radius: 50%;
        border: 2px solid transparent;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        background: rgba(232, 197, 110, 0.06);
        color: var(--gold);
    }

    .avatar-modal-box .avatar-opt:hover {
        transform: scale(1.06);
        border-color: rgba(232, 197, 110, 0.25);
    }

    .avatar-modal-box .avatar-opt.active {
        border-color: var(--gold);
        background: rgba(232, 197, 110, 0.15);
        box-shadow: 0 0 0 3px rgba(232, 197, 110, 0.12);
    }

    .avatar-modal-box .avatar-opt img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }

    .avatar-modal-box .modal-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
    }

    .avatar-modal-box .modal-actions button {
        padding: 8px 28px;
        border-radius: 10px;
        border: none;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .avatar-modal-box .modal-actions .btn-cancel {
        background: rgba(255, 255, 255, 0.05);
        color: var(--ink-soft);
    }

    .avatar-modal-box .modal-actions .btn-cancel:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    .avatar-modal-box .modal-actions .btn-confirm {
        background: linear-gradient(135deg, #f0d488, #c99a3f);
        color: #1a1208;
        box-shadow: 0 2px 12px rgba(201, 154, 63, 0.2);
    }

    .avatar-modal-box .modal-actions .btn-confirm:hover {
        transform: scale(1.02);
    }

    .profile-card {
        position: relative;
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: 16px;
        padding: 6px 0;
        backdrop-filter: blur(10px);
        box-shadow: var(--elev);
        margin-bottom: 20px;
        overflow: hidden;
    }

    .profile-card .profile-header {
        padding: 18px 28px;
        border-bottom: 1px solid rgba(232, 197, 110, 0.08);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .profile-card .profile-header .title {
        font-size: 16px;
        font-weight: 500;
    }

    .profile-card .profile-header .title i {
        margin-right: 14px;
        color: var(--gold);
    }

    .profile-card .profile-header .edit-trigger {
        padding: 6px 18px;
        border-radius: 20px;
        border: 1px solid rgba(232, 197, 110, 0.2);
        background: transparent;
        color: var(--gold);
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .profile-card .profile-header .edit-trigger:hover {
        background: rgba(232, 197, 110, 0.08);
        border-color: rgba(232, 197, 110, 0.4);
    }

    .profile-card .profile-body {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 18px 28px;
    }

    .profile-card .profile-body .avatar-wrapper {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: linear-gradient(135deg, #f0d488, #c99a3f);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        color: #1a1208;
        font-weight: 600;
        flex-shrink: 0;
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
    }

    .profile-card .profile-body .avatar-wrapper:hover {
        transform: scale(1.04);
        box-shadow: 0 0 0 3px rgba(232, 197, 110, 0.2);
    }

    .profile-card .profile-body .avatar-wrapper .avatar-hint {
        position: absolute;
        bottom: -4px;
        right: -4px;
        background: var(--gold);
        color: #1a1208;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 10px;
        font-weight: 600;
        opacity: 0;
        transition: opacity 0.2s;
    }

    .profile-card .profile-body .avatar-wrapper:hover .avatar-hint {
        opacity: 1;
    }

    .profile-card .profile-body .info-area {
        flex: 1;
    }

    .profile-card .profile-body .info-area .nickname {
        font-size: 18px;
        font-weight: 600;
    }

    .profile-card .profile-body .info-area .sub-info {
        font-size: 13px;
        color: var(--ink-soft);
        margin-top: 4px;
    }

    .profile-card .profile-body .info-area .sub-info span {
        color: var(--ink);
        font-weight: 500;
    }

    .profile-card .locked-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.25);
        backdrop-filter: blur(6px);
        z-index: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
        border-radius: 16px;
    }

    .avatar-opt {
        aspect-ratio: 1;
        border-radius: 50%;
        border: 2px solid transparent;
        cursor: pointer;
        transition: all 0.2s;
        overflow: hidden;
        background: rgba(232, 197, 110, 0.06);
    }

    .avatar-opt img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }

    .avatar-opt:hover {
        transform: scale(1.06);
        border-color: rgba(232, 197, 110, 0.25);
    }

    .avatar-opt.active {
        border-color: var(--gold);
        background: rgba(232, 197, 110, 0.15);
        box-shadow: 0 0 0 3px rgba(232, 197, 110, 0.12);
    }
</style>

<?php if ($qx_max_tmp1): ?>
    <div class="profile-card" id="profileCard">
        <div class="profile-header">
            <div class="title">
                <i class="fa-solid fa-user"></i>个人信息
            </div>
            <button class="edit-trigger" id="editToggleBtn">
                <i class="fa-solid fa-pen"></i> 修改资料
            </button>
        </div>

        <div class="profile-body">
            <div class="avatar-wrapper" id="avatarClickArea">
                <img src="/assets/photo/<?php echo htmlspecialchars($current_avatar); ?>"
                    alt="头像"
                    style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                <span class="avatar-hint">点击更换</span>
            </div>
            <div class="info-area">
                <div class="nickname" id="displayNickname">
                    <?php echo htmlspecialchars($user_nickname ?? '玩家'); ?>
                </div>
                <div class="sub-info">
                    用户名：<span><?php echo htmlspecialchars($q_suname); ?></span>
                </div>
                <div class="sub-info">
                    邮箱：<span id="displayEmail"><?php echo htmlspecialchars($user_email); ?></span>
                </div>
            </div>
        </div>
        <div class="edit-drawer" id="editDrawer">
            <div class="field-group">
                <div class="field-label">头像</div>
                <div class="field-value">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <span id="editAvatarPreview" style="width:48px;height:48px;border-radius:50%;overflow:hidden;display:inline-block;">
                            <img src="/assets/photo/<?php echo htmlspecialchars($current_avatar); ?>"
                                style="width:100%;height:100%;object-fit:cover;">
                        </span>
                        <button class="field-action" id="openAvatarModalBtn" style="padding:4px 14px;font-size:12px;">选择</button>
                    </div>
                </div>
            </div>
            <div class="field-group">
                <div class="field-label">昵称</div>
                <input class="field-input" id="editNickname" type="text" value="<?php echo htmlspecialchars($user_nickname ?? '玩家'); ?>" placeholder="输入新昵称">
            </div>
            <div class="field-group">
                <div class="field-label">邮箱</div>
                <input class="field-input" id="editEmail" type="email" value="<?php echo htmlspecialchars($user_email); ?>" placeholder="输入新邮箱">
            </div>

            <div class="drawer-actions">
                <button class="btn-cancel" id="closeDrawerBtn">取消</button>
                <button class="btn-save" id="saveAllBtn">保存全部</button>
            </div>
        </div>
        <div class="avatar-modal-overlay" id="avatarModal">
            <div class="avatar-modal-box">
                <h4>选择头像</h4>
                <p>点击下方图片选择你的头像(素材来源于网络)</p>
                <div class="avatar-grid" id="avatarGrid">
                    <?php foreach ($avatar_files as $file): ?>
                        <div class="avatar-opt <?php echo ($file === $current_avatar) ? 'active' : ''; ?>"
                            data-avatar="<?php echo htmlspecialchars($file); ?>">
                            <img src="/assets/photo/<?php echo htmlspecialchars($file); ?>"
                                alt="头像"
                                style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="modal-actions">
                    <button class="btn-cancel" id="closeAvatarModalBtn">取消</button>
                    <button class="btn-confirm" id="confirmAvatarBtn">确认</button>
                </div>
            </div>
        </div>
        <button onclick="location.href='/logout.php';" style="margin-top:24px;margin-left:20px;padding:14px 48px;border:none;border-radius:12px;background:linear-gradient(135deg,#f0d488,#c99a3f);color:#1a1208;font-weight:700;font-size:16px;cursor:pointer;transition:all 0.2s;box-shadow:0 4px 16px rgba(201,154,63,0.25);">退出登录</button>

    </div>


<?php else: ?>
    <div class="profile-card" style="position:relative;overflow:hidden;">
        <div class="profile-header">
            <div class="title"><i class="fa-solid fa-user"></i>个人信息</div>
        </div>
        <div class="profile-body" style="min-height:100px;opacity:0.3;">
            <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#f0d488,#c99a3f);flex-shrink:0;"></div>
            <div style="flex:1;">
                <div style="height:24px;background:var(--line);border-radius:4px;width:120px;"></div>
                <div style="height:16px;background:var(--line);border-radius:4px;width:200px;margin-top:8px;"></div>
                <div style="height:16px;background:var(--line);border-radius:4px;width:160px;margin-top:6px;"></div>
            </div>
        </div>
        <div class="locked-overlay">
            <i class="fa-solid fa-lock" style="font-size:36px;color:var(--gold);opacity:0.9;"></i>
            <span style="color:#fff;font-size:14px;font-weight:500;text-shadow:0 2px 8px rgba(0,0,0,0.5);">登录后查看个人信息</span>
        </div>
    </div>
<?php endif; ?>
<div style="background:var(--surface);border:1px solid var(--line);border-radius:16px;padding:6px 0;backdrop-filter:blur(10px);box-shadow:var(--elev);margin-bottom:20px;">
    <div style="padding:18px 28px;border-bottom:1px solid rgba(232,197,110,0.08);">
        <div style="font-size:16px;font-weight:500;">
            <i class="fa-solid fa-cog" style="margin-right:14px;color:var(--gold);"></i>修改选项
        </div>
    </div>
    <div style="padding:30px 40px 60px;color:var(--ink);max-width:100%;">
        <div style="background:var(--surface);border:1px solid var(--line);border-radius:16px;padding:6px 0;backdrop-filter:blur(10px);box-shadow:var(--elev);">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 28px;border-bottom:1px solid rgba(232,197,110,0.08);">
                <div>
                    <div style="font-size:16px;font-weight:500;"><i class="fa-solid fa-circle" style="margin-right:14px;color:var(--gold);"></i>我的状态</div>
                    <div style="font-size:13px;color:var(--ink-soft);margin-top:4px;margin-left:32px;">设置后其他玩家可见，空闲时更容易收到对战邀请</div>
                </div>
                <div style="display:flex;gap:12px;">
                    <button class="status-btn active" data-status="idle" onclick="setStatus('idle',this)" style="padding:8px 28px;border-radius:20px;border:1px solid rgba(232,197,110,0.2);background:rgba(76,175,80,0.15);color:#7acc7a;font-size:14px;font-weight:600;cursor:pointer;transition:all 0.2s;">空闲</button>
                    <button class="status-btn" data-status="busy" onclick="setStatus('busy',this)" style="padding:8px 28px;border-radius:20px;border:1px solid rgba(232,197,110,0.1);background:transparent;color:var(--ink-soft);font-size:14px;font-weight:600;cursor:pointer;transition:all 0.2s;">忙碌</button>
                </div>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 28px;border-bottom:1px solid rgba(232,197,110,0.08);">
                <div>
                    <div style="font-size:16px;font-weight:500;"><i class="fa-solid fa-music" style="margin-right:14px;color:var(--gold);"></i>对局内音乐</div>
                    <div style="font-size:13px;color:var(--ink-soft);margin-top:4px;margin-left:32px;">开启后对局时将播放背景音乐，沉浸式下棋体验</div>
                </div>
                <label style="position:relative;width:50px;height:26px;display:inline-block;flex-shrink:0;cursor:pointer;">
                    <input type="checkbox" id="musicToggle" checked style="opacity:0;width:0;height:0;">
                    <span class="toggle-bg" style="position:absolute;cursor:pointer;inset:0;background:rgba(90,80,70,0.5);border-radius:26px;transition:0.25s;"></span>
                    <span class="toggle-slider" style="position:absolute;height:20px;width:20px;left:3px;bottom:3px;background:#d0c0a0;border-radius:50%;transition:0.25s;"></span>
                </label>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 28px;">
                <div>
                    <div style="font-size:16px;font-weight:500;"><i class="fa-solid fa-comment" style="margin-right:14px;color:var(--gold);"></i>对局内聊天</div>
                    <div style="font-size:13px;color:var(--ink-soft);margin-top:4px;margin-left:32px;">关闭后将隐藏聊天窗口，不受他人消息打扰</div>
                </div>
                <label style="position:relative;width:50px;height:26px;display:inline-block;flex-shrink:0;cursor:pointer;">
                    <input type="checkbox" id="chatToggle" checked style="opacity:0;width:0;height:0;">
                    <span class="toggle-bg" style="position:absolute;cursor:pointer;inset:0;background:rgba(90,80,70,0.5);border-radius:26px;transition:0.25s;"></span>
                    <span class="toggle-slider" style="position:absolute;height:20px;width:20px;left:3px;bottom:3px;background:#d0c0a0;border-radius:50%;transition:0.25s;"></span>
                </label>
            </div>
        </div>
    </div>
</div>
<script src="/assets/authwrite.js"></script>
<?php if ($qx_max_tmp1): ?>
    <script>
        (function() {
            'use strict';
            const tmd_newcontroler = new tmdbaseauthdownyho();
            const user_id_global = '<?php echo $q_suname ?>';
            const editToggleBtn = document.getElementById('editToggleBtn');
            const editDrawer = document.getElementById('editDrawer');
            const closeDrawerBtn = document.getElementById('closeDrawerBtn');
            const saveAllBtn = document.getElementById('saveAllBtn');
            const avatarClickArea = document.getElementById('avatarClickArea');
            const avatarModal = document.getElementById('avatarModal');
            const openAvatarModalBtn = document.getElementById('openAvatarModalBtn');
            const closeAvatarModalBtn = document.getElementById('closeAvatarModalBtn');
            const confirmAvatarBtn = document.getElementById('confirmAvatarBtn');
            const avatarGrid = document.getElementById('avatarGrid');
            const editAvatarPreview = document.getElementById('editAvatarPreview');
            const editNickname = document.getElementById('editNickname');
            const displayNickname = document.getElementById('displayNickname');
            const editEmail = document.getElementById('editEmail');
            const displayEmail = document.getElementById('displayEmail');
            let selectedAvatar = '<?php echo $current_avatar; ?>';
            let currentAvatar = selectedAvatar;

            function toggleDrawer(open) {
                if (!editDrawer) return;
                if (open === undefined) {
                    editDrawer.classList.toggle('open');
                } else if (open) {
                    editDrawer.classList.add('open');
                } else {
                    editDrawer.classList.remove('open');
                }

                if (editToggleBtn) {
                    const isOpen = editDrawer.classList.contains('open');
                    editToggleBtn.innerHTML = isOpen ?
                        '<i class="fa-solid fa-chevron-up"></i> 收起' :
                        '<i class="fa-solid fa-pen"></i> 修改资料';
                }
            }

            if (editToggleBtn) {
                editToggleBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    toggleDrawer();
                });
            }
            if (closeDrawerBtn) {
                closeDrawerBtn.addEventListener('click', function() {
                    toggleDrawer(false);

                    if (editNickname) editNickname.value = displayNickname ? displayNickname.textContent : '';
                    if (editEmail) editEmail.value = displayEmail ? displayEmail.textContent : '';
                    if (editAvatarPreview) {
                        selectedAvatar = currentAvatar;
                    }
                });
            }


            function openAvatarModal() {
                if (avatarModal) avatarModal.classList.add('active');

                document.querySelectorAll('#avatarGrid .avatar-opt').forEach(el => {
                    el.classList.toggle('active', el.dataset.avatar === selectedAvatar);
                });
            }

            function closeAvatarModal() {
                if (avatarModal) avatarModal.classList.remove('active');
            }

            if (avatarClickArea) {
                avatarClickArea.addEventListener('click', openAvatarModal);
            }
            if (openAvatarModalBtn) {
                openAvatarModalBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    openAvatarModal();
                });
            }
            if (closeAvatarModalBtn) {
                closeAvatarModalBtn.addEventListener('click', closeAvatarModal);
            }
            if (avatarModal) {
                avatarModal.addEventListener('click', function(e) {
                    if (e.target === this) closeAvatarModal();
                });
            }


            if (avatarGrid) {
                avatarGrid.addEventListener('click', function(e) {
                    const opt = e.target.closest('.avatar-opt');
                    if (!opt) return;
                    document.querySelectorAll('#avatarGrid .avatar-opt').forEach(el => el.classList.remove('active'));
                    opt.classList.add('active');
                    selectedAvatar = opt.dataset.avatar;
                });
            }

            if (confirmAvatarBtn) {
                confirmAvatarBtn.addEventListener('click', function() {
                    if (selectedAvatar) {
                        currentAvatar = selectedAvatar;
                        if (editAvatarPreview) {
                            editAvatarPreview.innerHTML = '<img src="/assets/photo/' + currentAvatar + '" style="width:100%;height:100%;object-fit:cover;">';
                        }
                        if (avatarClickArea) {
                            avatarClickArea.innerHTML = '<img src="/assets/photo/' + currentAvatar + '" style="width:100%;height:100%;border-radius:50%;object-fit:cover;"><span class="avatar-hint">点击更换</span>';
                        }
                        closeAvatarModal();
                    }
                });
            }

            if (saveAllBtn) {
                saveAllBtn.addEventListener('click', async function() {
                    const nickVal = editNickname.value.trim();
                    if (!nickVal || nickVal.length > 8) {
                        Qmsg.error('昵称不能为空或昵称字数超出8字');
                        return;
                    }
                    const emailVal = editEmail.value.trim();
                    if (!emailVal || !(/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal))) {
                        Qmsg.error('邮箱内容不符合格式！');
                        return;
                    }
                    const avatarValue = currentAvatar || selectedAvatar || 'avatar.jpg';
                    const authdatas = await tmd_newcontroler.writenewwords(user_id_global);
                    var Qmsg_loading_handle = Qmsg.loading('数据提交中......');
                    plugin_post_requests({
                        email: emailVal,
                        nickname:nickVal,
                        avatarv:avatarValue,
                        UserId: authdatas
                    }, async (error, response) => {
                        Qmsg_loading_handle.close();
                        if (error) {
                            Qmsg.error('提交失败！请尝试联系站点管理员');
                            return;
                        }
                        if (response && response.success) {
                            if (avatarClickArea) {
                                avatarClickArea.innerHTML = '<img src="/assets/photo/' + avatarValue + '" style="width:100%;height:100%;border-radius:50%;object-fit:cover;"><span class="avatar-hint">点击更换</span>';
                            }
                            displayNickname.textContent = nickVal;
                            displayEmail.textContent = emailVal;
                            localStorage.setItem('fhq_avatar', currentAvatar);
                            localStorage.setItem('fhq_nickname', displayNickname ? displayNickname.textContent : '');
                            localStorage.setItem('fhq_email', displayEmail ? displayEmail.textContent : '');
                            Qmsg.success('个人资料已保存');
                            toggleDrawer(false);
                        } else {
                            Qmsg.error('资料保存失败：' + (response ? response.message : '未知错误'));
                        }
                    }, {
                        url: '/api/applyinfo/',
                        timeout: 10000
                    });
                });
            }

            function restoreSavedData() {
                const savedAvatar = localStorage.getItem('fhq_avatar');
                if (savedAvatar && avatarClickArea) {
                    currentAvatar = savedAvatar;
                    avatarClickArea.innerHTML = '<img src="/assets/photo/' + savedAvatar + '" style="width:100%;height:100%;border-radius:50%;object-fit:cover;"><span class="avatar-hint">点击更换</span>';
                    if (editAvatarPreview) editAvatarPreview.innerHTML = '<img src="/assets/photo/' + savedAvatar + '" style="width:100%;height:100%;border-radius:50%;object-fit:cover;"><span class="avatar-hint">点击更换</span>';
                    selectedAvatar = savedAvatar;
                }
                const savedNick = localStorage.getItem('fhq_nickname');
                if (savedNick && displayNickname) {
                    displayNickname.textContent = savedNick;
                    if (editNickname) editNickname.value = savedNick;
                }

                const savedEmail = localStorage.getItem('fhq_email');
                if (savedEmail && displayEmail) {
                    displayEmail.textContent = savedEmail;
                    if (editEmail) editEmail.value = savedEmail;
                }
            }
            restoreSavedData();

        })();
    </script>
<?php endif; ?>
<script>
    (function() {
        'use strict';
        let statusChannel = null;
        try {
            statusChannel = new BroadcastChannel('fhq_status_channel');
        } catch (e) {
            console.warn('[设置页] BroadcastChannel异常');
        }

        function safeGetElement(id) {
            const el = document.getElementById(id);
            if (!el) {
                console.warn('[设置页] 元素不存在:', id);
                return null;
            }
            return el;
        }

        function getToggleElements(id) {
            const input = safeGetElement(id);
            if (!input) return null;
            const label = input.closest('label');
            if (!label) return null;
            const bg = label.querySelector('.toggle-bg');
            const slider = label.querySelector('.toggle-slider');
            if (!bg || !slider) return null;
            return {
                input,
                bg,
                slider
            };
        }

        window.setStatus = function(status, btn) {
            document.querySelectorAll('.status-btn').forEach(b => {
                b.classList.remove('active');
                b.style.background = 'transparent';
                b.style.color = 'var(--ink-soft)';
                b.style.borderColor = 'rgba(232,197,110,0.1)';
            });
            btn.classList.add('active');
            btn.style.background = status === 'idle' ? 'rgba(76,175,80,0.15)' : 'rgba(255,107,107,0.15)';
            btn.style.color = status === 'idle' ? '#7acc7a' : '#ff6b6b';
            btn.style.borderColor = status === 'idle' ? 'rgba(76,175,80,0.3)' : 'rgba(255,107,107,0.3)';
            localStorage.setItem('fhq_status', status);
            if (statusChannel) {
                try {
                    statusChannel.postMessage({
                        type: 'setStatus',
                        status: status,
                        timestamp: Date.now(),
                        source: 'settings_page'
                    });
                } catch (e) {
                    try {
                        localStorage.setItem('fhq_status_trigger', JSON.stringify({
                            status: status,
                            timestamp: Date.now()
                        }));
                    } catch (_) {}
                }
            } else {
                try {
                    localStorage.setItem('fhq_status_trigger', JSON.stringify({
                        status: status,
                        timestamp: Date.now()
                    }));
                } catch (_) {}
            }
            document.dispatchEvent(new CustomEvent('fhqStatusChanged', {
                detail: {
                    status: status
                }
            }));
        };

        function restoreUI() {
            const savedStatus = localStorage.getItem('fhq_status') || 'idle';
            const btn = document.querySelector('.status-btn[data-status="' + savedStatus + '"]');
            if (btn) btn.click();
            else {
                const defaultBtn = document.querySelector('.status-btn[data-status="idle"]');
                if (defaultBtn) defaultBtn.click();
            }
        }

        function initToggle(id) {
            const elements = getToggleElements(id);
            if (!elements) return;
            const {
                input,
                bg,
                slider
            } = elements;

            function updateToggle(checked) {
                if (checked) {
                    bg.style.background = 'var(--gold)';
                    slider.style.transform = 'translateX(24px)';
                    slider.style.background = '#1a1208';
                } else {
                    bg.style.background = 'rgba(90,80,70,0.5)';
                    slider.style.transform = 'translateX(0)';
                    slider.style.background = '#d0c0a0';
                }
            }
            input.addEventListener('change', function() {
                updateToggle(this.checked);
                localStorage.setItem('fhq_' + this.id, String(this.checked));
            });
            const saved = localStorage.getItem('fhq_' + id);
            if (saved !== null) {
                input.checked = saved === 'true';
            } else {
                input.checked = true;
            }
            updateToggle(input.checked);
        }

        initToggle('musicToggle');
        initToggle('chatToggle');

        if (statusChannel) {
            statusChannel.onmessage = function(event) {
                const data = event.data;
                if (!data || typeof data !== 'object') return;
                if (data.source === 'settings_page') return;
                if (data.type === 'setStatus' && data.status) {
                    const currentStatus = localStorage.getItem('fhq_status') || 'idle';
                    if (currentStatus !== data.status) {
                        const btn = document.querySelector('.status-btn[data-status="' + data.status + '"]');
                        if (btn) btn.click();
                    }
                }
            };
        }

        window.addEventListener('storage', function(event) {
            if (event.key === 'fhq_status_trigger' && event.newValue) {
                try {
                    const data = JSON.parse(event.newValue);
                    if (data && data.status) {
                        const currentStatus = localStorage.getItem('fhq_status') || 'idle';
                        if (currentStatus !== data.status) {
                            const btn = document.querySelector('.status-btn[data-status="' + data.status + '"]');
                            if (btn) btn.click();
                        }
                    }
                } catch (_) {}
            }
        });

        restoreUI();

        window.addEventListener('beforeunload', function() {
            if (statusChannel) {
                try {
                    statusChannel.close();
                } catch (_) {}
            }
        });

    })();
</script>
