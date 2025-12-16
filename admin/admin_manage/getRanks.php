<?php
include("../../db.php");

$user_id = $_SESSION['user_id'];

$action = "Get ranks for game ID: " . (isset($_GET['game_id']) ? $_GET['game_id'] : 'N/A');
require_once 'audit.php';

if (isset($_GET['game_id'])) {
    $game_id = $_GET['game_id'];

    // SQL to get ranks for the selected game
    $sql = "
        SELECT ranks.rank_id, ranks.rank_name
        FROM game_ranks
        JOIN ranks ON game_ranks.rank_id = ranks.rank_id
        WHERE game_ranks.game_id = '$game_id'
    ";

    $result = $conn->query($sql);

    $ranks = [];
    while ($row = $result->fetch_assoc()) {
        $ranks[] = $row;
    }

    echo json_encode($ranks);
}

?>
