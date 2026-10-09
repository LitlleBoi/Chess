-- Database schema voor Chess PWA (MySQL / MariaDB & SQLite compatibel)

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    elo INT DEFAULT 1200,
    avatar VARCHAR(255) DEFAULT 'image/Profielfoto.png',
    status VARCHAR(20) DEFAULT 'offline',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS friends (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    friend_id INT NOT NULL,
    status VARCHAR(20) DEFAULT 'accepted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    white_id INT NOT NULL,
    black_id INT NOT NULL,
    time_control INT DEFAULT 600,
    white_time_left INT DEFAULT 600,
    black_time_left INT DEFAULT 600,
    status VARCHAR(20) DEFAULT 'active',
    winner_id INT NULL,
    elo_change_white INT DEFAULT 0,
    elo_change_black INT DEFAULT 0,
    fen TEXT,
    turn VARCHAR(1) DEFAULT 'w',
    moves_history LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL,
    FOREIGN KEY (white_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (black_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
