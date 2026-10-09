<?php
$pageTitle = "Profiel";
$bodyClass = "profile-page";
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if (!isLoggedIn()) {
    $demoUser = $pdo->query("SELECT * FROM users WHERE username = 'GrootmeesterNL'")->fetch();
    if ($demoUser) {
        loginUser($demoUser);
    } else {
        header("Location: login-aanmeld.php");
        exit;
    }
}

$currentUser = currentUser($pdo);
$userId = $currentUser['id'];
$message = '';
$error = '';

// Update profielfoto of instellingen
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['new_avatar']) && $_FILES['new_avatar']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['new_avatar']['tmp_name'];
        $fileName = basename($_FILES['new_avatar']['name']);
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (in_array($ext, $allowed)) {
            $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'image' . DIRECTORY_SEPARATOR . 'uploads';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $newFileName = 'avatar_' . $userId . '_' . time() . '.' . $ext;
            $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $newFileName;
            if (move_uploaded_file($fileTmp, $targetPath)) {
                $avatarPath = 'image/uploads/' . $newFileName;
                $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?")->execute([$avatarPath, $userId]);
                $currentUser['avatar'] = $avatarPath;
                $message = "Profielfoto succesvol gewijzigd!";
            }
        } else {
            $error = "Ongeldig bestandsformaat voor profielfoto.";
        }
    }
}

// Statistieken berekenen
$stmtStats = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_games,
        SUM(CASE WHEN winner_id = ? THEN 1 ELSE 0 END) AS wins,
        SUM(CASE WHEN winner_id IS NOT NULL AND winner_id != ? THEN 1 ELSE 0 END) AS losses,
        SUM(CASE WHEN status = 'draw' OR (status = 'completed' AND winner_id IS NULL) THEN 1 ELSE 0 END) AS draws
    FROM games 
    WHERE (white_id = ? OR black_id = ?) AND status IN ('completed', 'draw', 'resigned')
");
$stmtStats->execute([$userId, $userId, $userId, $userId]);
$stats = $stmtStats->fetch();

$totalGames = (int)($stats['total_games'] ?? 0);
$wins = (int)($stats['wins'] ?? 0);
$losses = (int)($stats['losses'] ?? 0);
$draws = (int)($stats['draws'] ?? 0);
$winrate = $totalGames > 0 ? round(($wins / $totalGames) * 100) : 50;

// Alle games van deze gebruiker ophalen
$stmtMyGames = $pdo->prepare("
    SELECT g.*, 
           w.username AS white_username, w.elo AS white_elo,
           b.username AS black_username, b.elo AS black_elo
    FROM games g
    JOIN users w ON g.white_id = w.id
    JOIN users b ON g.black_id = b.id
    WHERE (g.white_id = ? OR g.black_id = ?)
    ORDER BY g.id DESC
    LIMIT 20
");
$stmtMyGames->execute([$userId, $userId]);
$userGames = $stmtMyGames->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="subpage-wrapper">
    <div class="subpage-header">
        <h1 class="subpage-title">Spelersprofiel</h1>
        <p class="subpage-subtitle">Bekijk je spelstatistieken, rating en behaalde resultaten.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Profiel Hero Kaart -->
    <section class="home-card profile-hero-card">
        <div class="profile-hero-content">
            <div class="profile-avatar-wrapper">
                <img src="../<?= htmlspecialchars($currentUser['avatar']) ?>" alt="Profielfoto" class="profile-avatar-large">
                <form action="profiel.php" method="POST" enctype="multipart/form-data" class="avatar-quick-form">
                    <label for="new-avatar-upload" class="btn-change-avatar" title="Foto wijzigen">📷 Wijzig</label>
                    <input type="file" id="new-avatar-upload" name="new_avatar" accept="image/*" class="visually-hidden" onchange="this.form.submit()">
                </form>
            </div>
            <div class="profile-meta-info">
                <h2 class="profile-username"><?= htmlspecialchars($currentUser['username']) ?></h2>
                <div class="profile-elo-highlight">
                    <span class="elo-label">ELO Rating:</span>
                    <span class="elo-number"><?= htmlspecialchars($currentUser['elo']) ?></span>
                </div>
                <div class="profile-sub-badges">
                    <span class="status-badge-inline status-<?= htmlspecialchars($currentUser['status']) ?>">
                        ● <?= ucfirst($currentUser['status']) ?>
                    </span>
                    <span class="member-since">Lid sinds <?= date('d-m-Y', strtotime($currentUser['created_at'])) ?></span>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistieken Raster -->
    <section class="home-card stats-overview-card">
        <h2 class="section-title-badge">Statistieken</h2>
        <div class="stats-grid">
            <div class="stat-box">
                <span class="stat-value"><?= $totalGames ?></span>
                <span class="stat-label">Gespeeld</span>
            </div>
            <div class="stat-box stat-win">
                <span class="stat-value"><?= $wins ?></span>
                <span class="stat-label">Gewonnen</span>
            </div>
            <div class="stat-box stat-loss">
                <span class="stat-value"><?= $losses ?></span>
                <span class="stat-label">Verloren</span>
            </div>
            <div class="stat-box stat-draw">
                <span class="stat-value"><?= $draws ?></span>
                <span class="stat-label">Gelijkspel</span>
            </div>
            <div class="stat-box stat-rate">
                <span class="stat-value"><?= $winrate ?>%</span>
                <span class="stat-label">Winratio</span>
            </div>
        </div>
    </section>

    <!-- Complete Wedstrijdgeschiedenis -->
    <section class="home-card full-history-card">
        <h2 class="section-title-badge">Recente Wedstrijden</h2>
        <div class="history-table-container">
            <?php if (!empty($userGames)): ?>
                <table class="games-history-table">
                    <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Tegenstander</th>
                            <th>Kleur</th>
                            <th>Uitslag</th>
                            <th>Rating</th>
                            <th>Actie</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($userGames as $g): 
                            $isWhite = ($g['white_id'] == $userId);
                            $opponent = $isWhite ? $g['black_username'] : $g['white_username'];
                            $opponentId = $isWhite ? $g['black_id'] : $g['white_id'];
                            $eloDiff = $isWhite ? $g['elo_change_white'] : $g['elo_change_black'];
                            $won = ($g['winner_id'] == $userId);
                            $draw = ($g['status'] == 'draw' || (!$g['winner_id'] && $g['status'] == 'completed'));
                        ?>
                            <tr>
                                <td><?= date('d-m-Y H:i', strtotime($g['created_at'])) ?></td>
                                <td><strong><?= htmlspecialchars($opponent) ?></strong></td>
                                <td><?= $isWhite ? '⚪ Wit' : '⚫ Zwart' ?></td>
                                <td>
                                    <?php if ($won): ?>
                                        <span class="badge-result win">Gewonnen</span>
                                    <?php elseif ($draw): ?>
                                        <span class="badge-result draw">Gelijkspel</span>
                                    <?php else: ?>
                                        <span class="badge-result loss">Verloren</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong class="<?= $eloDiff >= 0 ? 'text-gain' : 'text-loss' ?>">
                                        <?= $eloDiff >= 0 ? '+' . $eloDiff : $eloDiff ?>
                                    </strong>
                                </td>
                                <td>
                                    <a href="game.php?opponent=<?= $opponentId ?>&time=10" class="btn btn-mini-action">Rematch</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="empty-notice">Nog geen wedstrijden gespeeld. Start je eerste partij!</p>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
