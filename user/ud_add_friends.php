<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$sender_id = (int)$_SESSION['user_id'];

// Get POSTed JSON
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['username']) || empty(trim($data['username']))) {
    echo json_encode(['success' => false, 'error' => 'Username required']);
    exit;
}

$friend_username = trim($data['username']);

// Check if user exists
$stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    echo json_encode(['success' => false, 'error' => 'Database prepare failed']);
    exit;
}
$stmt->bind_param("s", $friend_username);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

$receiver = $res->fetch_assoc();
$receiver_id = (int)$receiver['user_id'];

// Check if already friends or request exists
$stmt = $conn->prepare("SELECT * FROM friend_requests WHERE sender_id=? AND receiver_id=? AND status='pending'");
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    echo json_encode(['success' => false, 'error' => 'Database prepare failed']);
    exit;
}
$stmt->bind_param("ii", $sender_id, $receiver_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'Friend request already sent']);
    exit;
}

// Insert friend request
$stmt = $conn->prepare("INSERT INTO friend_requests (sender_id, receiver_id, status) VALUES (?, ?, 'pending')");
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    echo json_encode(['success' => false, 'error' => 'Database prepare failed']);
    exit;
}
$stmt->bind_param("ii", $sender_id, $receiver_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'friend' => ['user_id' => $receiver_id, 'username' => $friend_username]]);
} else {
    error_log("Insert failed: " . $stmt->error);
    echo json_encode(['success' => false, 'error' => 'Database insert failed: ' . $stmt->error]);
}
$stmt->close();
?>