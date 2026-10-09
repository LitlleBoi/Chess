<?php
$pageTitle = "Inloggen";
$bodyClass = "auth-page";
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if (isLoggedIn()) {
    header("Location: home.php");
    exit;
}

$error = '';
$usernameVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameVal = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($usernameVal) || empty($password)) {
        $error = 'Vul zowel je gebruikersnaam als wachtwoord in.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$usernameVal]);
        $foundUser = $stmt->fetch();

        if ($foundUser && password_verify($password, $foundUser['password'])) {
            // Update online status
            $pdo->prepare("UPDATE users SET status = 'online' WHERE id = ?")->execute([$foundUser['id']]);
            loginUser($foundUser);
            header("Location: home.php");
            exit;
        } else {
            $error = 'Ongeldige gebruikersnaam of wachtwoord.';
        }
    }
}

require_once __DIR__ . '/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card form-card">
        <h1 class="visually-hidden">Inloggen bij Chess</h1>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="inlog.php" method="POST" class="auth-form" autocomplete="off">
            <div class="form-group">
                <label for="username" class="visually-hidden">Gebruikersnaam</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="form-control form-input-pill" 
                    placeholder="Gebruikersnaam" 
                    value="<?= htmlspecialchars($usernameVal) ?>" 
                    required 
                    autofocus
                >
            </div>

            <div class="form-group">
                <label for="password" class="visually-hidden">Wachtwoord</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control form-input-pill" 
                    placeholder="Wachtwoord" 
                    required
                >
            </div>

            <div class="form-actions text-center">
                <button type="submit" class="btn btn-pill btn-primary btn-submit">Klaar</button>
            </div>

            <div class="auth-switch-link">
                <span>Nog geen account? </span>
                <a href="aanmeld.php">Aanmelden</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
