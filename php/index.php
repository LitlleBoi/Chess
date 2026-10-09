<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if (isLoggedIn()) {
    header("Location: home.php");
} else {
    header("Location: login-aanmeld.php");
}
exit;
