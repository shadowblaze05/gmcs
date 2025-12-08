<?php
// ud_get_messages.php
session_start();
require_once '../db.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}
$me = (int)$_SESSION['user_id'];

$type = $_GET['type'] ?? null;
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$type || !$id) {
    echo json_encode([]);
    exit;
}

if ($type === 'community') {
    $stmt = $conn->prepare("SELECT m.*, u.username FROM messages m JOIN users u ON u.id = m.sender_id WHERE m.target_type = 'community' AND m.target_id = ? ORDER BY m.created_at ASC");
    $stmt->bind_param('i', $id);
} else {
    $other = $id;
    $stmt = $conn->prepare("SELECT m.*, u.username FROM messages m JOIN users u ON u.id = m.sender_id WHERE m.target_type = 'user' AND ((m.sender_id = ? AND m.target_id = ?) OR (m.sender_id = ? AND m.target_id = ?)) ORDER BY m.created_at ASC");
    $stmt->bind_param('iiii', $me, $other, $other, $me);
}
$stmt->execute();
$res = $stmt->get_result();
$out = [];
while ($r = $res->fetch_assoc()) $out[] = $r;
echo json_encode($out);
