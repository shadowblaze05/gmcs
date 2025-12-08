<?php
// ud_add_game.php
session_start();
require_once '../db.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'not_logged']);
    exit;
}
$me = (int)$_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['title'])) {
    echo json_encode(['error' => 'title required']);
    exit;
}
$title = trim($input['title']);
$skins = isset($input['skins']) ? trim($input['skins']) : '';

// check game exists
$stmt = $conn->prepare("SELECT game_id FROM games WHERE title = ? LIMIT 1");
$stmt->bind_param('s', $title);
$stmt->execute();
$r = $stmt->get_result();
if ($r->num_rows > 0) {
    $game = $r->fetch_assoc();
    $game_id = (int)$game['game_id'];
} else {
    $stmt = $conn->prepare("INSERT INTO games (title) VALUES (?)");
    $stmt->bind_param('s', $title);
    $stmt->execute();
    $game_id = (int)$stmt->insert_id;
}

// upsert user_games
$stmt = $conn->prepare("SELECT id FROM user_games WHERE user_id = ? AND game_id = ? LIMIT 1");
$stmt->bind_param('ii', $me, $game_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $stmt = $conn->prepare("UPDATE user_games SET skins = ? WHERE user_id = ? AND game_id = ?");
    $stmt->bind_param('sii', $skins, $me, $game_id);
    $ok = $stmt->execute();
} else {
    $stmt = $conn->prepare("INSERT INTO user_games (user_id, game_id, skins) VALUES (?, ?, ?)");
    $stmt->bind_param('iis', $me, $game_id, $skins);
    $ok = $stmt->execute();
}
echo json_encode(['success' => (bool)$ok]);
