<?php
session_start();
require "../db.php";
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$me = intval($_SESSION['user_id']);

$sql = "
    SELECT u.user_id, u.username 
    FROM friends f
    JOIN users u ON u.user_id = f.friend_id
    WHERE f.user_id = ?

    UNION

    SELECT u2.user_id, u2.username 
    FROM friends f2
    JOIN users u2 ON u2.user_id = f2.user_id
    WHERE f2.friend_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $me, $me);
$stmt->execute();

$res = $stmt->get_result();
$out = [];

while ($row = $res->fetch_assoc()) {
    $out[] = $row;
}

echo json_encode($out);
$stmt->close();
?>