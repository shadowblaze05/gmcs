<?php
session_start();
require "../db.php";

$user_id = $_SESSION['user_id'];
$action = "Deleted friend: User ID $user_id";
require '../admin/admin_manage/audit.php';

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$friend_id = intval($data['friend_id']);
$user = intval($_SESSION['user_id']);

$stmt = $conn->prepare("DELETE FROM friends WHERE (user_id=? AND friend_id=?) OR (user_id=? AND friend_id=?)");
$stmt->bind_param("iiii", $user, $friend_id, $friend_id, $user);
$stmt->execute();

$action = "Deleted friend ID: $friend_id by User ID: $user";
require '../admin/admin_manage/audit.php';

echo json_encode(["success" => true]);
$stmt->close();

?>