<?php
$pageTitle = "Home";
$bodyClass = "home-page";
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

// Zorg voor ingelogde gebruiker (automatisch demo-inlog voor soepele review indien nog niet ingelogd)
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

// Haal vrienden op
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

// Haal wedstrijdgeschiedenis op
$stmtHistory = $pdo->prepare("
    SELECT g.*, 
           w.username AS white_username, w.elo AS white_elo, w.avatar AS white_avatar,
           b.username AS black_username, b.elo AS black_elo, b.avatar AS black_avatar
    FROM games g
    JOIN users w ON g.white_id = w.id
    JOIN users b ON g.black_id = b.id
    WHERE (g.white_id = ? OR g.black_id = ?) AND g.status = 'completed'
    ORDER BY g.id DESC
    LIMIT 10
");
$stmtHistory->execute([$userId, $userId]);
$historyGames = $stmtHistory->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="home-wrapper">
    <!-- Top Kaart: Speelmodus en Tijdskeuze -->
    <section class="home-card play-controls-card">
        <h2 class="visually-hidden">Spel starten</h2>
        <div class="time-selector" role="radiogroup" aria-label="Kies speeltijd">
            <button type="button" class="btn btn-time active" data-time="10" aria-checked="true">10 min</button>
            <button type="button" class="btn btn-time" data-time="5" aria-checked="false">5 min</button>
            <button type="button" class="btn btn-time" data-time="3" aria-checked="false">3 min</button>
        </div>

        <div class="random-opponent-wrapper">
            <button type="button" id="btn-random-opponent" class="btn btn-pill btn-primary btn-large btn-full">
                Random tegenstander
            </button>
        </div>
    </section>

    <!-- Middelste Kaart: Vrienden -->
    <section class="home-card friends-card">
        <h2 class="section-title-badge">Vrienden</h2>

        <div class="friends-list">
            <?php if (!empty($friends)): ?>
                <?php foreach ($friends as $friend): ?>
                    <article class="friend-item">
                        <div class="avatar-frame">
                            <img src="../<?= htmlspecialchars($friend['avatar']) ?>" alt="Avatar van <?= htmlspecialchars($friend['username']) ?>" class="friend-avatar-img">
                            <span class="status-indicator status-<?= htmlspecialchars($friend['status']) ?>" title="<?= htmlspecialchars($friend['status']) ?>"></span>
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
                <p class="empty-notice">Geen vrienden gevonden. Zoek spelers op de <a href="vrienden.php">vriendenpagina</a>!</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Onderste Kaart: Geschiedenis -->
    <section class="home-card history-card">
        <h2 class="section-title-badge">Geschiedenis</h2>

        <div class="history-list">
            <?php if (!empty($historyGames)): ?>
                <?php foreach ($historyGames as $game): 
                    $isWhite = ($game['white_id'] == $userId);
                    $opponentName = $isWhite ? $game['black_username'] : $game['white_username'];
                    $opponentElo = $isWhite ? $game['black_elo'] : $game['white_elo'];
                    $opponentAvatar = $isWhite ? $game['black_avatar'] : $game['white_avatar'];
                    $opponentId = $isWhite ? $game['black_id'] : $game['white_id'];
                    $myEloChange = $isWhite ? $game['elo_change_white'] : $game['elo_change_black'];
                    $isWin = $myEloChange >= 0;
                ?>
                    <article class="history-item">
                        <div class="history-left">
                            <div class="avatar-frame">
                                <img src="../<?= htmlspecialchars($opponentAvatar) ?>" alt="Avatar van <?= htmlspecialchars($opponentName) ?>" class="history-avatar-img">
                            </div>
                            <div class="elo-badge-row <?= $isWin ? 'rating-gain' : 'rating-loss' ?>">
                                <span class="elo-symbol-box"><?= $isWin ? '+' : '-' ?></span>
                                <span class="elo-value"><?= $myEloChange >= 0 ? '+' . $myEloChange : $myEloChange ?></span>
                            </div>
                        </div>

                        <div class="history-details">
                            <h3 class="history-name-rating"><?= htmlspecialchars($opponentName) ?> (<?= htmlspecialchars($opponentElo) ?>)</h3>
                            <div class="history-actions">
                                <a href="game.php?opponent=<?= $opponentId ?>&time=10" class="btn btn-action-pill">Rematch</a>
                                <button type="button" class="btn btn-action-pill btn-friend-req" data-user-id="<?= $opponentId ?>">Vriendverzoek</button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Fallback mockup items matching Figma home.png -->
                <article class="history-item">
                    <div class="history-left">
                        <div class="avatar-frame">
                            <img src="../image/Profielfoto.png" alt="Avatar" class="history-avatar-img">
                        </div>
                        <div class="elo-badge-row rating-loss">
                            <span class="elo-symbol-box">-</span>
                            <span class="elo-value">-8</span>
                        </div>
                    </div>
                    <div class="history-details">
                        <h3 class="history-name-rating">Naam Naam (1928)</h3>
                        <div class="history-actions">
                            <a href="game.php?time=10" class="btn btn-action-pill">Rematch</a>
                            <button type="button" class="btn btn-action-pill btn-friend-req">Vriendverzoek</button>
                        </div>
                    </div>
                </article>

                <article class="history-item">
                    <div class="history-left">
                        <div class="avatar-frame">
                            <img src="../image/Profielfoto.png" alt="Avatar" class="history-avatar-img">
                        </div>
                        <div class="elo-badge-row rating-gain">
                            <span class="elo-symbol-box">+</span>
                            <span class="elo-value">+8</span>
                        </div>
                    </div>
                    <div class="history-details">
                        <h3 class="history-name-rating">Naam Naam (1928)</h3>
                        <div class="history-actions">
                            <a href="game.php?time=10" class="btn btn-action-pill">Rematch</a>
                            <button type="button" class="btn btn-action-pill btn-friend-req">Vriendverzoek</button>
                        </div>
                    </div>
                </article>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
