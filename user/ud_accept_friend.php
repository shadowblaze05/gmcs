<?php
session_start();
require "../db.php";
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$request_id = intval($data['request_id']);

// Fetch request info
$q = $conn->prepare("SELECT sender_id, receiver_id FROM friend_requests WHERE id = ?");
$q->bind_param("i", $request_id);
$q->execute();
$row = $q->get_result()->fetch_assoc();

if (!$row) {
    echo json_encode(["error" => "Invalid request"]);
    exit;
}

$sender = intval($row['sender_id']);
$receiver = intval($row['receiver_id']);

// Only receiver can accept
if ($_SESSION['user_id'] !== $receiver) {
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

// Approve request
$update = $conn->prepare("UPDATE friend_requests SET status='accepted' WHERE id=?");
$update->bind_param("i", $request_id);
$update->execute();

// Add to friends table (bidirectional)
$add = $conn->prepare("INSERT INTO friends (user_id, friend_id) VALUES (?, ?), (?, ?)");
$add->bind_param("iiii", $sender, $receiver, $receiver, $sender);
$add->execute();

echo json_encode(["success" => true]);
$update->close();
$add->close();
$q->close();