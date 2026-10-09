<?php
$pageTitle = "Spelen";
$bodyClass = "chess-game-page";
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

// Zorg voor ingelogde gebruiker
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

// Tegenstander bepalen (via query param of random/standaard)
$opponentId = isset($_GET['opponent']) ? (int)$_GET['opponent'] : 0;
$timeParam = isset($_GET['time']) ? (int)$_GET['time'] : 10;
if (!in_array($timeParam, [3, 5, 10])) {
    $timeParam = 10;
}
$timeInSeconds = $timeParam * 60;

$opponent = null;
if ($opponentId > 0) {
    $stmtOpp = $pdo->prepare("SELECT id, username, elo, avatar FROM users WHERE id = ?");
    $stmtOpp->execute([$opponentId]);
    $opponent = $stmtOpp->fetch();
}

if (!$opponent) {
    // Kies een willekeurige tegenspeler uit de database
    $stmtOpp = $pdo->prepare("SELECT id, username, elo, avatar FROM users WHERE id != ? ORDER BY RANDOM() LIMIT 1");
    $stmtOpp->execute([$userId]);
    $opponent = $stmtOpp->fetch();
    if (!$opponent) {
        $opponent = [
            'id' => 2,
            'username' => 'GrootmeesterNL',
            'elo' => 2150,
            'avatar' => 'image/Profielfoto.png'
        ];
    }
}

$extraScripts = ['../javascript/chess-game.js'];
require_once __DIR__ . '/header.php';
?>

<div class="game-layout-container" 
     data-user-id="<?= $userId ?>" 
     data-username="<?= htmlspecialchars($currentUser['username']) ?>"
     data-user-elo="<?= $currentUser['elo'] ?>"
     data-user-avatar="../<?= htmlspecialchars($currentUser['avatar']) ?>"
     data-opponent-id="<?= $opponent['id'] ?>"
     data-opponent-name="<?= htmlspecialchars($opponent['username']) ?>"
     data-opponent-elo="<?= $opponent['elo'] ?>"
     data-opponent-avatar="../<?= htmlspecialchars($opponent['avatar']) ?>"
     data-time-seconds="<?= $timeInSeconds ?>">

    <!-- Kolom Links: Chat & Opgeven -->
    <aside class="game-sidebar game-left-col">
        <section class="game-panel-card chat-panel">
            <h2 class="panel-badge-title">Chat</h2>
            <div id="chat-messages" class="chat-messages-area" aria-live="polite">
                <div class="chat-bubble system-bubble">
                    <span>Welkom bij de match! Veel speelplezier.</span>
                </div>
                <div class="chat-bubble opponent-bubble">
                    <span class="bubble-sender"><?= htmlspecialchars($opponent['username']) ?>:</span>
                    <span class="bubble-text">Succes met de partij! 👋</span>
                </div>
            </div>
            <form id="chat-form" class="chat-input-row" autocomplete="off">
                <input 
                    type="text" 
                    id="chat-input" 
                    class="form-control chat-input" 
                    placeholder="Stuur een bericht..." 
                    maxlength="150"
                    required
                >
                <button type="submit" class="btn btn-pill btn-chat-send">Stuur</button>
            </form>
        </section>

        <div class="action-btn-row">
            <button type="button" id="btn-resign" class="btn btn-pill btn-danger btn-large">Opgeven</button>
        </div>
    </aside>

    <!-- Kolom Midden: Schaakbord & Spelerbalken -->
    <section class="game-center-col" aria-label="Schaakbord en spelers">
        <!-- Bovenste Speler: Tegenstander (Zwart) -->
        <header class="player-header-bar opponent-bar">
            <div class="player-info-meta">
                <img src="../<?= htmlspecialchars($opponent['avatar']) ?>" alt="Avatar van <?= htmlspecialchars($opponent['username']) ?>" class="player-avatar-small">
                <span class="player-name"><?= htmlspecialchars($opponent['username']) ?> <span class="player-elo">(<?= htmlspecialchars($opponent['elo']) ?>)</span></span>
            </div>
            <div id="black-timer" class="clock-display" aria-label="Klok tegenstander">
                <?= sprintf('%02d:%02d', floor($timeInSeconds / 60), $timeInSeconds % 60) ?>
            </div>
        </header>

        <!-- Interactief Schaakbord -->
        <div class="chessboard-wrapper">
            <div id="chessboard" class="chessboard" role="grid" aria-label="Schaakbord">
                <!-- Wordt dynamisch opgebouwd door chess-game.js -->
            </div>
        </div>

        <!-- Onderste Speler: Jij (Wit) -->
        <footer class="player-header-bar user-bar">
            <div class="player-info-meta">
                <img src="../<?= htmlspecialchars($currentUser['avatar']) ?>" alt="Avatar van <?= htmlspecialchars($currentUser['username']) ?>" class="player-avatar-small">
                <span class="player-name"><?= htmlspecialchars($currentUser['username']) ?> <span class="player-elo">(<?= htmlspecialchars($currentUser['elo']) ?>)</span></span>
            </div>
            <div id="white-timer" class="clock-display active" aria-label="Jouw klok">
                <?= sprintf('%02d:%02d', floor($timeInSeconds / 60), $timeInSeconds % 60) ?>
            </div>
        </footer>
    </section>

    <!-- Kolom Rechts: Zetgeschiedenis & Gelijkspel -->
    <aside class="game-sidebar game-right-col">
        <section class="game-panel-card history-panel">
            <h2 class="panel-badge-title">Zetgeschiedenis</h2>
            <div id="move-history-list" class="move-history-table" role="region" aria-label="Lijst van zetten">
                <!-- Dynamisch gevuld met zetten (1. e4 e5 etc.) -->
                <div class="moves-placeholder">Nog geen zetten gedaan.</div>
            </div>
        </section>

        <div class="action-btn-row">
            <button type="button" id="btn-draw" class="btn btn-pill btn-draw btn-large">Gelijkspel</button>
        </div>
    </aside>

</div>

<!-- Game Over Modal -->
<div id="game-over-modal" class="modal-backdrop" hidden>
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <h2 id="modal-title" class="modal-heading">Partij afgelopen</h2>
        <p id="modal-desc" class="modal-text">Schaakmat! Wit heeft gewonnen.</p>
        <div id="modal-elo-box" class="modal-elo-change">
            <span>Rating aanpassing: </span><strong id="modal-elo-diff">+8</strong>
        </div>
        <div class="modal-buttons">
            <button type="button" id="btn-modal-rematch" class="btn btn-pill btn-primary">Nieuwe match</button>
            <a href="home.php" class="btn btn-pill btn-secondary">Overzicht</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
