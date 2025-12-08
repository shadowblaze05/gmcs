<?php
session_start();
require "../db.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$user = intval($_SESSION['user_id']);

$q = $conn->prepare("
SELECT fr.id AS request_id, fr.sender_id, u.username
FROM friend_requests fr
JOIN users u ON u.user_id = fr.sender_id
WHERE fr.receiver_id = ? AND fr.status = 'pending'
");
$q->bind_param("i", $user);
$q->execute();

$res = $q->get_result();
$requests = [];

while ($row = $res->fetch_assoc()) {
    $requests[] = $row;
}

echo json_encode($requests);
$q->close();
?>