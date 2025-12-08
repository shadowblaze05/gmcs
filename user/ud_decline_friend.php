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

// Ensure only receiver can decline
$q = $conn->prepare("SELECT receiver_id FROM friend_requests WHERE id=?");
$q->bind_param("i", $request_id);
$q->execute();
$row = $q->get_result()->fetch_assoc();

if (!$row) {
    echo json_encode(["error" => "Invalid request"]);
    exit;
}

if ($row['receiver_id'] != $_SESSION['user_id']) {
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

// Decline request
$stmt = $conn->prepare("UPDATE friend_requests SET status='declined' WHERE id=?");
$stmt->bind_param("i", $request_id);
$stmt->execute();

echo json_encode(["success" => true]);
$stmt->close();
$q->close();