<?php
define('FHQ_ADMIN', 1);
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/auth.php';

adm_session_start();
adm_logout();
header('Location: login.php');
exit;
