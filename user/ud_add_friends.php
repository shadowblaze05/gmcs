<?php
session_start();
require_once "../db.php";
header("Content-Type: application/json");

// DEBUG: Check session user ID
var_dump($_SESSION['user_id']); // <-- Add this line

// 1. Check login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['username']) || empty($data['username'])) {
    echo json_encode(["error" => "No username provided"]);
    exit;
}

$sender = intval($_SESSION['user_id']);
$username = trim($data['username']);

// 2. Get receiver_id
$q = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
$q->bind_param("s", $username);
$q->execute();
$res = $q->get_result();

if ($res->num_rows === 0) {
    echo json_encode(["error" => "User not found"]);
    exit;
}

$row = $res->fetch_assoc();
$receiver = intval($row['user_id']);

// 3. Prevent adding yourself
if ($sender === $receiver) {
    echo json_encode(["error" => "You cannot add yourself"]);
    exit;
}

// 4. Check if request exists
$check = $conn->prepare("
    SELECT id FROM friend_requests 
    WHERE (sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?)
");
$check->bind_param("iiii", $sender, $receiver, $receiver, $sender);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    echo json_encode(["error" => "Request already exists"]);
    exit;
}

// 5. Insert friend request
$add = $conn->prepare("
    INSERT INTO friend_requests (sender_id, receiver_id, status)
    VALUES (?, ?, 'pending')
");
$add->bind_param("ii", $sender, $receiver);

if ($add->execute()) {
    echo json_encode(["success" => true, "message" => "Friend request sent"]);
} else {
    echo json_encode(["success" => false, "error" => $conn->error]);
}
$add->close();
$q->close();