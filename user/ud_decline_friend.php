<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

$user_id = (int)$_SESSION['user_id'];
$action = "Declined friend request: User ID $user_id";
require '../admin/admin_manage/audit.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
$request_id = $data['request_id'] ?? 0;

if (!$request_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid request ID']);
    exit;
}

// Update request as declined
$stmt = $conn->prepare("UPDATE friend_requests SET status='declined' WHERE id = ? AND receiver_id = ?");
$stmt->bind_param("ii", $request_id, $user_id);
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

$action = "Declined friend request ID: $request_id by User ID: $user_id";
require '../admin/admin_manage/audit.php';

if ($affected) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Request not found or already handled']);
}
?>