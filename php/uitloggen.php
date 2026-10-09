<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if (isLoggedIn()) {
    $uid = $_SESSION['user_id'];
    $pdo->prepare("UPDATE users SET status = 'offline' WHERE id = ?")->execute([$uid]);
    logoutUser();
}

header("Location: login-aanmeld.php");
exit;
