<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$action = "Deleted game from collection: User ID $user_id";
require '../admin/admin_manage/audit.php';

if (!isset($_POST['game_id'])) {
    die("Error: No game selected.");
}

$game_id = (int)$_POST['game_id'];

$stmt = $conn->prepare("DELETE FROM user_game_collection WHERE user_id = ? AND game_id = ?");
$stmt->bind_param("ii", $user_id, $game_id);

if ($stmt->execute()) {
    header("Location: user_dashboard.php");
    $action = "Deleted game ID: $game_id from user ID: $user_id";
    require '../admin/admin_manage/audit.php';
    exit();
} else {
    echo "Delete failed: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>