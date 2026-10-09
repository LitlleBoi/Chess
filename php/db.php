<?php
// Database verbinding & initialisatie voor Schaakapplicatie

// Optioneel: MySQL configuratie (indien gewenst)
$useMySQL = false;
$mysqlHost = '127.0.0.1';
$mysqlDb   = 'chess_db';
$mysqlUser = 'root';
$mysqlPass = '';

try {
    if ($useMySQL) {
        $dsn = "mysql:host=$mysqlHost;dbname=$mysqlDb;charset=utf8mb4";
        $pdo = new PDO($dsn, $mysqlUser, $mysqlPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } else {
        // SQLite database in projectmap
        $sqlitePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'chess.sqlite';
        $isNew = !file_exists($sqlitePath);
        $pdo = new PDO("sqlite:" . $sqlitePath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        if ($isNew || filesize($sqlitePath) === 0) {
            initDatabase($pdo);
        }
    }
} catch (PDOException $e) {
    die("Database fout: " . $e->getMessage());
}

function initDatabase($pdo) {
    // Tabellen aanmaken
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            elo INTEGER DEFAULT 1200,
            avatar TEXT DEFAULT 'image/Profielfoto.png',
            status TEXT DEFAULT 'offline',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS friends (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            friend_id INTEGER NOT NULL,
            status TEXT DEFAULT 'accepted',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS games (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            white_id INTEGER NOT NULL,
            black_id INTEGER NOT NULL,
            time_control INTEGER DEFAULT 600,
            white_time_left INTEGER DEFAULT 600,
            black_time_left INTEGER DEFAULT 600,
            status TEXT DEFAULT 'active',
            winner_id INTEGER NULL,
            elo_change_white INTEGER DEFAULT 0,
            elo_change_black INTEGER DEFAULT 0,
            fen TEXT DEFAULT 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1',
            turn TEXT DEFAULT 'w',
            moves_history TEXT DEFAULT '[]',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            ended_at DATETIME NULL
        );

        CREATE TABLE IF NOT EXISTS chat_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            game_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            message TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // Demo data toevoegen passend bij de Figma ontwerpen
    $defaultPass = password_hash('welkom123', PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT OR IGNORE INTO users (username, password, elo, avatar, status) VALUES (?, ?, ?, ?, ?)");
    $users = [
        ['GrootmeesterNL', $defaultPass, 1973, 'image/Profielfoto.png', 'online'],
        ['SchaakMeester',  $defaultPass, 2150, 'image/Profielfoto.png', 'online'],
        ['Naam Naam',      $defaultPass, 1928, 'image/Profielfoto.png', 'online'],
        ['MagnusFan',      $defaultPass, 2045, 'image/Profielfoto.png', 'offline'],
        ['PionPionier',    $defaultPass, 1540, 'image/Profielfoto.png', 'online'],
        ['AnnaChess',      $defaultPass, 1820, 'image/Profielfoto.png', 'offline']
    ];

    foreach ($users as $u) {
        $stmt->execute($u);
    }

    // Vrienden relaties aanmaken (bijv. GrootmeesterNL bevriend met Naam Naam en SchaakMeester)
    $stmtFriend = $pdo->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, ?)");
    $stmtFriend->execute([1, 3, 'accepted']); // GrootmeesterNL & Naam Naam
    $stmtFriend->execute([3, 1, 'accepted']);
    $stmtFriend->execute([1, 2, 'accepted']); // GrootmeesterNL & SchaakMeester
    $stmtFriend->execute([2, 1, 'accepted']);
    $stmtFriend->execute([1, 4, 'accepted']); // GrootmeesterNL & MagnusFan
    $stmtFriend->execute([4, 1, 'accepted']);

    // Game geschiedenis (exact passend bij Figma home.png: Naam Naam -8 en +8)
    $stmtGame = $pdo->prepare("
        INSERT INTO games (white_id, black_id, time_control, white_time_left, black_time_left, status, winner_id, elo_change_white, elo_change_black, fen, turn, moves_history, created_at, ended_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now', '-2 hours'), datetime('now', '-1 hours'))
    ");

    // Game 1: Verloren (-8)
    $sampleMoves1 = json_encode([
        ['white' => 'e4', 'black' => 'e5'],
        ['white' => 'Nf3', 'black' => 'Nc6'],
        ['white' => 'Bc4', 'black' => 'Bc5'],
        ['white' => 'd3', 'black' => 'd6']
    ]);
    $stmtGame->execute([1, 3, 600, 45, 120, 'completed', 3, -8, 8, 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1', 'w', $sampleMoves1]);

    // Game 2: Gewonnen (+8)
    $sampleMoves2 = json_encode([
        ['white' => 'd4', 'black' => 'd5'],
        ['white' => 'c4', 'black' => 'e6'],
        ['white' => 'Nc3', 'black' => 'Nf6'],
        ['white' => 'Bg5', 'black' => 'Be7']
    ]);
    $stmtGame->execute([3, 1, 300, 10, 85, 'completed', 1, -8, 8, 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1', 'w', $sampleMoves2]);
}

return $pdo;
