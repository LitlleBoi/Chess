<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$user = currentUser($pdo);

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Niet ingelogd']);
    exit;
}

$userId = $user['id'];

switch ($action) {
    case 'save_game':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            $data = $_POST;
        }

        $opponentId = (int)($data['opponent_id'] ?? 2);
        $winner = $data['winner'] ?? 'draw'; // 'white', 'black', 'draw'
        $movesHistory = is_array($data['moves'] ?? null) ? json_encode($data['moves']) : ($data['moves'] ?? '[]');
        $timeControl = (int)($data['time_control'] ?? 600);
        $reason = $data['reason'] ?? 'checkmate'; // 'checkmate', 'draw', 'resigned', 'timeout'

        $whiteId = $userId;
        $blackId = $opponentId;

        $eloChangeWhite = 0;
        $eloChangeBlack = 0;
        $winnerId = null;
        $status = 'completed';

        if ($winner === 'white') {
            $winnerId = $whiteId;
            $eloChangeWhite = 8;
            $eloChangeBlack = -8;
            $status = ($reason === 'resigned') ? 'resigned' : 'completed';
        } elseif ($winner === 'black') {
            $winnerId = $blackId;
            $eloChangeWhite = -8;
            $eloChangeBlack = 8;
            $status = ($reason === 'resigned') ? 'resigned' : 'completed';
        } else {
            $status = 'draw';
            $eloChangeWhite = 0;
            $eloChangeBlack = 0;
        }

        // Game invoegen
        $stmtGame = $pdo->prepare("
            INSERT INTO games (white_id, black_id, time_control, white_time_left, black_time_left, status, winner_id, elo_change_white, elo_change_black, moves_history, ended_at)
            VALUES (?, ?, ?, 0, 0, ?, ?, ?, ?, ?, datetime('now'))
        ");
        $stmtGame->execute([$whiteId, $blackId, $timeControl, $status, $winnerId, $eloChangeWhite, $eloChangeBlack, $movesHistory]);
        $gameId = $pdo->lastInsertId();

        // ELO ratings bijwerken
        if ($eloChangeWhite !== 0) {
            $pdo->prepare("UPDATE users SET elo = MAX(100, elo + ?) WHERE id = ?")->execute([$eloChangeWhite, $whiteId]);
        }
        if ($eloChangeBlack !== 0) {
            $pdo->prepare("UPDATE users SET elo = MAX(100, elo + ?) WHERE id = ?")->execute([$eloChangeBlack, $blackId]);
        }

        // Nieuwe ELO ophalen
        $newEloStmt = $pdo->prepare("SELECT elo FROM users WHERE id = ?");
        $newEloStmt->execute([$userId]);
        $newElo = $newEloStmt->fetchColumn();

        echo json_encode([
            'success' => true,
            'game_id' => $gameId,
            'elo_change' => $eloChangeWhite,
            'new_elo' => $newElo
        ]);
        break;

    case 'friend_request':
        $data = json_decode(file_get_contents('php://input'), true);
        $targetId = (int)($data['target_user_id'] ?? 0);

        if ($targetId > 0 && $targetId !== $userId) {
            $stmtCheck = $pdo->prepare("SELECT id FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
            $stmtCheck->execute([$userId, $targetId, $targetId, $userId]);
            if (!$stmtCheck->fetch()) {
                $pdo->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 'accepted')")->execute([$userId, $targetId]);
            }
            echo json_encode(['success' => true, 'message' => 'Vriendverzoek succesvol verwerkt']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Ongeldige doelspeler']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Onbekende actie']);
        break;
}
