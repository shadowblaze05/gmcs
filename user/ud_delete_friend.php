<?php
session_start();
require "../db.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "error" => "Not logged in"]);
    exit;
}

$user = (int)$_SESSION['user_id'];
$friend_id = isset($_POST['friend_id']) ? (int)$_POST['friend_id'] : 0;

if (!$friend_id) {
    echo json_encode(["success" => false, "error" => "Invalid friend ID"]);
    exit;
}

$stmt = $conn->prepare("
    DELETE FROM friends 
    WHERE (user_id=? AND friend_id=?) 
       OR (user_id=? AND friend_id=?)
");
$stmt->bind_param("iiii", $user, $friend_id, $friend_id, $user);
$stmt->execute();

require '../admin/admin_manage/audit.php';

echo json_encode(["success" => true]);
$stmt->close();

?>