<?php
session_start();
require_once '../db.php';
header('Content-Type: application/json');

$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT collection_id, game_id, title
    FROM user_game_collection
    JOIN games USING(game_id)
    WHERE user_id=?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$games = [];
while ($row = $res->fetch_assoc()) {
    $games[] = $row;
}
$stmt->close();

echo json_encode($games);
?>