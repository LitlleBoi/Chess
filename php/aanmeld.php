<?php
$pageTitle = "Aanmelden";
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
    $avatarPath = 'image/Profielfoto.png';

    if (empty($usernameVal) || empty($password)) {
        $error = 'Vul alle vereiste velden in.';
    } elseif (strlen($usernameVal) < 3) {
        $error = 'Gebruikersnaam moet minimaal 3 tekens lang zijn.';
    } elseif (strlen($password) < 4) {
        $error = 'Wachtwoord moet minimaal 4 tekens lang zijn.';
    } else {
        // Controleer of gebruikersnaam al bestaat
        $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmtCheck->execute([$usernameVal]);
        if ($stmtCheck->fetch()) {
            $error = 'Deze gebruikersnaam is al bezet.';
        } else {
            // Verwerk foto-upload indien aanwezig
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $fileTmp = $_FILES['avatar']['tmp_name'];
                $fileName = basename($_FILES['avatar']['name']);
                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

                if (in_array($ext, $allowed)) {
                    $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'image' . DIRECTORY_SEPARATOR . 'uploads';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $newFileName = 'avatar_' . time() . '_' . rand(100, 999) . '.' . $ext;
                    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $newFileName;
                    if (move_uploaded_file($fileTmp, $targetPath)) {
                        $avatarPath = 'image/uploads/' . $newFileName;
                    }
                }
            }

            // Nieuwe gebruiker opslaan in database
            $hashedPass = password_hash($password, PASSWORD_DEFAULT);
            $stmtInsert = $pdo->prepare("INSERT INTO users (username, password, elo, avatar, status) VALUES (?, ?, 1200, ?, 'online')");
            $stmtInsert->execute([$usernameVal, $hashedPass, $avatarPath]);

            $newUserId = $pdo->lastInsertId();

            // Automatisch bevriend maken met demo bot/tegenstanders voor leuke spelervaring
            $pdo->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, 1, 'accepted'), (1, ?, 'accepted')")->execute([$newUserId, $newUserId]);
            $pdo->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, 3, 'accepted'), (3, ?, 'accepted')")->execute([$newUserId, $newUserId]);

            $_SESSION['user_id'] = $newUserId;
            $_SESSION['username'] = $usernameVal;

            header("Location: home.php");
            exit;
        }
    }
}

require_once __DIR__ . '/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card form-card">
        <h1 class="visually-hidden">Aanmelden bij Chess</h1>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="aanmeld.php" method="POST" enctype="multipart/form-data" class="auth-form" autocomplete="off">
            <div class="form-group">
                <label for="reg-username" class="visually-hidden">Gebruikersnaam</label>
                <input 
                    type="text" 
                    id="reg-username" 
                    name="username" 
                    class="form-control form-input-pill" 
                    placeholder="Gebruikersnaam" 
                    value="<?= htmlspecialchars($usernameVal) ?>" 
                    required 
                    autofocus
                >
            </div>

            <div class="form-group">
                <label for="reg-password" class="visually-hidden">Wachtwoord</label>
                <input 
                    type="password" 
                    id="reg-password" 
                    name="password" 
                    class="form-control form-input-pill" 
                    placeholder="Wachtwoord" 
                    required
                >
            </div>

            <div class="form-group upload-group">
                <div class="upload-box">
                    <label for="avatar-input" class="upload-label">
                        <span class="upload-text">Upload foto</span>
                    </label>
                    <div class="avatar-preview-box">
                        <img id="avatar-preview" src="../image/Profielfoto.png" alt="Profielfoto voorvertoning" class="avatar-preview-img">
                        <input type="file" id="avatar-input" name="avatar" accept="image/*" class="visually-hidden">
                    </div>
                </div>
            </div>

            <div class="form-actions text-center">
                <button type="submit" class="btn btn-pill btn-primary btn-submit">Klaar</button>
            </div>

            <div class="auth-switch-link">
                <span>Heb je al een account? </span>
                <a href="inlog.php">Inloggen</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
