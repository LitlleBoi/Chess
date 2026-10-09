<?php
$pageTitle = "Vrienden & Ranglijst";
$bodyClass = "friends-page";
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

// Acties verwerken (vriend toevoegen, uitnodigen, etc.)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_friend') {
        $friendName = trim($_POST['friend_username'] ?? '');
        $stmtSearch = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmtSearch->execute([$friendName, $userId]);
        $target = $stmtSearch->fetch();

        if (!$target) {
            $error = "Gebruiker niet gevonden.";
        } else {
            $targetId = $target['id'];
            // Check of al vrienden
            $stmtCheck = $pdo->prepare("SELECT id FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
            $stmtCheck->execute([$userId, $targetId, $targetId, $userId]);
            if ($stmtCheck->fetch()) {
                $error = "Jullie zijn al vrienden of er is al een verzoek verstuurd.";
            } else {
                $pdo->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 'accepted')")->execute([$userId, $targetId]);
                $message = "Vriend succesvol toegevoegd!";
            }
        }
    }
}

// Haal vriendenlijst op
$stmtFriends = $pdo->prepare("
    SELECT u.id, u.username, u.elo, u.avatar, u.status 
    FROM friends f 
    JOIN users u ON (f.friend_id = u.id AND f.user_id = ?) OR (f.user_id = u.id AND f.friend_id = ?)
    WHERE u.id != ? AND f.status = 'accepted'
    GROUP BY u.id
    ORDER BY u.elo DESC
");
$stmtFriends->execute([$userId, $userId, $userId]);
$friends = $stmtFriends->fetchAll();

// Ranglijst: alle spelers gesorteerd op ELO (conform SMART leerdoel)
$stmtLeaderboard = $pdo->query("SELECT id, username, elo, avatar, status FROM users ORDER BY elo DESC LIMIT 20");
$leaderboard = $stmtLeaderboard->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="subpage-wrapper">
    <div class="subpage-header">
        <h1 class="subpage-title">Vrienden & Spelers</h1>
        <p class="subpage-subtitle">Daag vrienden uit of ontdek topspelers op de ELO ranglijst.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Vriend toevoegen formulier -->
    <section class="home-card add-friend-card">
        <h2 class="section-title-badge">Vriend Zoeken</h2>
        <form action="vrienden.php" method="POST" class="add-friend-form">
            <input type="hidden" name="action" value="add_friend">
            <div class="input-with-btn">
                <input 
                    type="text" 
                    name="friend_username" 
                    class="form-control form-input-pill" 
                    placeholder="Typ een gebruikersnaam..." 
                    required
                >
                <button type="submit" class="btn btn-pill btn-primary">Toevoegen</button>
            </div>
        </form>
    </section>

    <div class="two-column-grid">
        <!-- Vrienden Kolom -->
        <section class="home-card friends-card-full">
            <h2 class="section-title-badge">Mijn Vrienden (<?= count($friends) ?>)</h2>
            <div class="friends-list">
                <?php if (!empty($friends)): ?>
                    <?php foreach ($friends as $friend): ?>
                        <article class="friend-item">
                            <div class="avatar-frame">
                                <img src="../<?= htmlspecialchars($friend['avatar']) ?>" alt="Avatar" class="friend-avatar-img">
                                <span class="status-indicator status-<?= htmlspecialchars($friend['status']) ?>"></span>
                            </div>
                            <div class="friend-details">
                                <h3 class="friend-name-rating"><?= htmlspecialchars($friend['username']) ?> (<?= htmlspecialchars($friend['elo']) ?>)</h3>
                                <div class="friend-challenge-times">
                                    <a href="game.php?opponent=<?= $friend['id'] ?>&time=10" class="btn-mini-time">10 min</a>
                                    <a href="game.php?opponent=<?= $friend['id'] ?>&time=5" class="btn-mini-time">5 min</a>
                                    <a href="game.php?opponent=<?= $friend['id'] ?>&time=3" class="btn-mini-time">3 min</a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="empty-notice">Je hebt nog geen vrienden toegevoegd.</p>
                <?php endif; ?>
            </div>
        </section>

        <!-- ELO Leaderboard Kolom (SMART leerdoel: Players sorteren met ELO) -->
        <section class="home-card leaderboard-card">
            <h2 class="section-title-badge">ELO Ranglijst</h2>
            <div class="leaderboard-list">
                <?php $rank = 1; foreach ($leaderboard as $player): ?>
                    <article class="leaderboard-item <?= ($player['id'] == $userId) ? 'is-current-user' : '' ?>">
                        <div class="leaderboard-rank">
                            <?php if ($rank === 1): ?>
                                <span class="rank-badge rank-gold">#1 👑</span>
                            <?php elseif ($rank === 2): ?>
                                <span class="rank-badge rank-silver">#2 🥈</span>
                            <?php elseif ($rank === 3): ?>
                                <span class="rank-badge rank-bronze">#3 🥉</span>
                            <?php else: ?>
                                <span class="rank-badge">#<?= $rank ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="avatar-frame-small">
                            <img src="../<?= htmlspecialchars($player['avatar']) ?>" alt="Avatar" class="leaderboard-avatar-img">
                        </div>
                        <div class="leaderboard-player-meta">
                            <span class="player-uname"><?= htmlspecialchars($player['username']) ?></span>
                            <span class="player-elo-tag">Rating: <strong><?= htmlspecialchars($player['elo']) ?></strong></span>
                        </div>
                        <div class="leaderboard-action">
                            <?php if ($player['id'] != $userId): ?>
                                <a href="game.php?opponent=<?= $player['id'] ?>&time=10" class="btn btn-action-pill btn-challenge-lead">Uitdagen</a>
                            <?php else: ?>
                                <span class="badge-you">Jij</span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php $rank++; endforeach; ?>
            </div>
        </section>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
