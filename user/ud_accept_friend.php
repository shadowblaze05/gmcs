<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

// Ensure logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Read JSON input
$data = json_decode(file_get_contents('php://input'), true);
$request_id = $data['request_id'] ?? 0;

if (!$request_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid request ID']);
    exit;
}

// Get sender_id from friend_requests
$stmt = $conn->prepare("SELECT sender_id FROM friend_requests WHERE id = ? AND receiver_id = ? AND status='pending'");
$stmt->bind_param("ii", $request_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $sender_id = $row['sender_id'];

    // Add to friends table (both directions)
    $stmt1 = $conn->prepare("INSERT INTO friends (user_id, friend_id) VALUES (?, ?), (?, ?)");
    $stmt1->bind_param("iiii", $user_id, $sender_id, $sender_id, $user_id);
    $stmt1->execute();
    $stmt1->close();

    // Update request as accepted
    $stmt2 = $conn->prepare("UPDATE friend_requests SET status='accepted' WHERE id = ?");
    $stmt2->bind_param("i", $request_id);
    $stmt2->execute();
    $stmt2->close();

    // Return success and sender info
    $stmt3 = $conn->prepare("SELECT user_id, username FROM users WHERE user_id = ?");
    $stmt3->bind_param("i", $sender_id);
    $stmt3->execute();
    $friend = $stmt3->get_result()->fetch_assoc();
    $stmt3->close();

    echo json_encode(['success' => true, 'friend' => $friend]);
} else {
    echo json_encode(['success' => false, 'error' => 'Friend request not found']);
}

$stmt->close();
?>