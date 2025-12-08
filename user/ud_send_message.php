<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['type']) || empty($data['id']) || empty($data['content'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

$type = $data['type'];
$id = (int)$data['id'];
$content = trim($data['content']);

if ($type === 'private') {
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $id, $content);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to send private message']);
    }
    $stmt->close();
} elseif ($type === 'community') {
    $stmt = $conn->prepare("INSERT INTO community_messages (user_id, game_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $id, $content);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to send community message']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Unknown chat type']);
}
?>