<?php
// ud_send_message.php
session_start();
require_once '../db.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'not_logged']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['type']) || empty($input['id']) || !isset($input['content'])) {
    echo json_encode(['error' => 'missing']);
    exit;
}

$me = (int)$_SESSION['user_id'];
$type = $input['type'];
$id = (int)$input['id'];
$content = trim($input['content']);
if ($content === '') {
    echo json_encode(['error' => 'empty']);
    exit;
}

if ($type === 'community') {
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, target_type, target_id, content) VALUES (?, 'community', ?, ?)");
    $stmt->bind_param('iis', $me, $id, $content);
} else {
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, target_type, target_id, content) VALUES (?, 'user', ?, ?)");
    $stmt->bind_param('iis', $me, $id, $content);
}
$ok = $stmt->execute();
echo json_encode(['success' => (bool)$ok]);
