<?php
/**
 * logout.php — Destroy session and redirect
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/User.php';

if (!empty($_SESSION['user_id'])) {
    $user = new User();
    $user->logout();
}
header('Location: ../auth/login.php');
exit;
