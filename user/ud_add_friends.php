<?php
session_start();

// 1. Start Output Buffering (Prevents "headers already sent" and random text output)
ob_start();

require_once '../db.php';

// Set JSON header
header('Content-Type: application/json');

// 2. Check Login
if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$sender_id = (int)$_SESSION['user_id'];

// 3. Get and Validate Input
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!isset($data['username']) || empty(trim($data['username']))) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Username required']);
    exit;
}

$friend_username = trim($data['username']);

// 4. Check if User Exists
$stmt = $conn->prepare("SELECT user_id, username FROM users WHERE username = ?");
$stmt->bind_param("s", $friend_username);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

$receiver = $res->fetch_assoc();
$receiver_id = (int)$receiver['user_id'];

// 5. Prevent Adding Yourself
if ($sender_id === $receiver_id) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'You cannot add yourself']);
    exit;
}

// 6. Check if Already Friends or Request Pending
// Check Friends table
$checkFriend = $conn->prepare("SELECT * FROM friends WHERE (user_id=? AND friend_id=?) OR (user_id=? AND friend_id=?)");
$checkFriend->bind_param("iiii", $sender_id, $receiver_id, $receiver_id, $sender_id);
$checkFriend->execute();
if ($checkFriend->get_result()->num_rows > 0) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'You are already friends']);
    exit;
}

// Check Friend Requests table
$checkReq = $conn->prepare("SELECT * FROM friend_requests WHERE sender_id=? AND receiver_id=? AND status='pending'");
$checkReq->bind_param("ii", $sender_id, $receiver_id);
$checkReq->execute();
if ($checkReq->get_result()->num_rows > 0) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Friend request already sent']);
    exit;
}

// 7. Insert Friend Request
$stmt = $conn->prepare("INSERT INTO friend_requests (sender_id, receiver_id, status) VALUES (?, ?, 'pending')");
$stmt->bind_param("ii", $sender_id, $receiver_id);

if ($stmt->execute()) {
    // 8. Audit Log (Only on success)
    $action = "Sent friend request from User ID: $sender_id to User ID: $receiver_id";
    if (file_exists('../admin/admin_manage/audit.php')) {
        include '../admin/admin_manage/audit.php';
    }

    ob_end_clean();
    // Return the friend data so JS can use it (though usually it stays in requests until accepted)
    echo json_encode([
        'success' => true,
        'friend' => [
            'user_id' => $receiver_id,
            'username' => $receiver['username'] // Use real username from DB
        ]
    ]);
} else {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Database insert failed']);
}

$stmt->close();
