<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$type = $_GET['type'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if ($type === 'private') {
    $stmt = $conn->prepare("
        SELECT m.sender_id, m.content, m.created_at, u.username
        FROM messages m
        JOIN users u ON m.sender_id = u.user_id
        WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC
    ");
    $stmt->bind_param("iiii", $user_id, $id, $id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $messages = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    echo json_encode($messages);
} elseif ($type === 'community') {
    $stmt = $conn->prepare("
        SELECT cm.user_id AS sender_id, cm.content, cm.created_at, u.username
        FROM community_messages cm
        JOIN users u ON cm.user_id = u.user_id
        WHERE cm.game_id = ?
        ORDER BY cm.created_at ASC
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $messages = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    echo json_encode($messages);
} else {
    echo json_encode([]);
}
?>