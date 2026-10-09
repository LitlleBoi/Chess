<?php
$pageTitle = "Welkom";
$bodyClass = "auth-page";
require_once __DIR__ . '/header.php';

// Als gebruiker al is ingelogd, doorsturen naar home
if (isLoggedIn()) {
    header("Location: home.php");
    exit;
}
?>

<div class="auth-wrapper">
    <div class="auth-card welcome-card">
        <h1 class="visually-hidden">Inloggen of Aanmelden</h1>
        <div class="welcome-buttons">
            <a href="inlog.php" class="btn btn-pill btn-primary btn-large">Log in</a>
            <a href="aanmeld.php" class="btn btn-pill btn-primary btn-large">Aanmelden</a>
        </div>
        <div class="guest-login-helper">
            <p>Of speel direct als demo-speler:</p>
            <form action="inlog.php" method="POST" class="quick-demo-form">
                <input type="hidden" name="username" value="GrootmeesterNL">
                <input type="hidden" name="password" value="welkom123">
                <button type="submit" class="btn-demo-link">Snel inloggen als GrootmeesterNL (1973)</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
