<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$user = currentUser($pdo);
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#5c8642">
    <meta name="description" content="Online Schaakplatform - Speel direct, daag vrienden uit en verbeter je rating.">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - Chess' : 'Chess' ?></title>
    <link rel="manifest" href="../manifest.json">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="page-<?= htmlspecialchars($currentPage) ?> <?= isset($bodyClass) ? htmlspecialchars($bodyClass) : '' ?>">

<header class="main-header">
    <div class="header-container">
        <a href="home.php" class="chess-logo">
            <svg class="logo-pawn" viewBox="0 0 45 45" width="34" height="34">
                <path d="m 22.5,9 c -2.21,0 -4,1.79 -4,4 0,0.89 0.29,1.71 0.78,2.38 C 17.33,16.5 16,18.59 16,21 c 0,2.03 0.94,3.84 2.41,5.03 C 15.41,27.09 11,31.58 11,39.5 l 23,0 c 0,-7.92 -4.41,-12.41 -7.41,-13.47 C 28.06,24.84 29,23.03 29,21 29,18.59 27.67,16.5 25.72,15.38 26.21,14.71 26.5,13.89 26.5,13 c 0,-2.21 -1.79,-4 -4,-4 z" fill="#81b64c" stroke="#365314" stroke-width="2"/>
            </svg>
            <span class="logo-text">Chess<span class="logo-dot">.com</span></span>
        </a>

        <nav class="nav-links">
            <a href="home.php" class="nav-pill <?= in_array($currentPage, ['home', 'game', 'chess']) ? 'active' : '' ?>">Spelen</a>
            <a href="vrienden.php" class="nav-pill <?= $currentPage === 'vrienden' ? 'active' : '' ?>">Vrienden</a>
            <a href="profiel.php" class="nav-pill <?= $currentPage === 'profiel' ? 'active' : '' ?>">Profiel</a>
            <?php if ($user): ?>
                <a href="uitloggen.php" class="nav-pill logout-pill">Uitloggen</a>
            <?php else: ?>
                <a href="login-aanmeld.php" class="nav-pill <?= in_array($currentPage, ['login-aanmeld', 'inlog', 'aanmeld']) ? 'active' : '' ?>">Inloggen</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="main-content">
