<?php
session_start();
include("../db.php");

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$action = "Added game to collection: User ID $user_id";
require '../admin/admin_manage/audit.php';

// Check if game_id was sent from the form
if (!isset($_POST['game_id']) || empty($_POST['game_id'])) {
    die("Error: No game selected.");
}

$game_id = $_POST['game_id'];

// Insert into user_game_collection
$stmt = $conn->prepare("
    INSERT INTO user_game_collection (user_id, game_id) 
    VALUES (?, ?)
");
if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("ii", $user_id, $game_id);

if ($stmt->execute()) {
    header("Location: user_dashboard.php");
    $action = "Added game ID: $game_id to user ID: $user_id";
    require '../admin/admin_manage/audit.php';
    exit();
} else {
    echo "Insert failed: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
