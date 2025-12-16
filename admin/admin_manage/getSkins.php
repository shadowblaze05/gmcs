<?php
include("../../db.php");


$action = "Get skins for game ID: " . (isset($_GET['game_id']) ? $_GET['game_id'] : 'N/A');
require_once 'audit.php';


if (isset($_GET['game_id'])) {
    $game_id = $_GET['game_id'];

    $sql = "SELECT skins.skin_id, skins.skin_name FROM skins
            JOIN game_skin ON skins.skin_id = game_skin.skin_id
            WHERE game_skin.game_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $game_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $skins = [];
    while ($row = $result->fetch_assoc()) {
        $skins[] = $row;
    }

    echo json_encode($skins);
} else {
    echo json_encode([]);
}

?>

