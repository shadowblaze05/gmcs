<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

if (!isset($_POST['game_id'])) {
    die("Error: No game selected.");
}

$game_id = (int)$_POST['game_id'];

/* Call stored function */
$stmt = $conn->prepare("SELECT fn_delete_user_game(?, ?) AS deleted");
$stmt->bind_param("ii", $user_id, $game_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if ($result['deleted'] == 1) {
    $action = "Deleted game ID $game_id from collection (User ID $user_id)";
    require '../admin/admin_manage/audit.php';
    header("Location: user_dashboard.php");
    exit();
} else {
    echo "Delete failed or game not found in collection.";
}

$stmt->close();
$conn->close();
