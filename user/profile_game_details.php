<?php
session_start();
require_once '../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'], $_GET['collection_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Missing collection ID']));
}

$collection_id = (int)$_GET['collection_id'];

$stmt = $conn->prepare("
    SELECT ugc.collection_id, g.title, r.rank_name, s.skin_name, t.transaction_id, rev.review
    FROM user_game_collection ugc
    JOIN games g ON ugc.game_id = g.game_id
    LEFT JOIN ranks r ON ugc.rank_id = r.rank_id
    LEFT JOIN skins s ON ugc.skin_id = s.skin_id
    LEFT JOIN transactions t ON ugc.transaction_id = t.transaction_id
    LEFT JOIN reviews rev ON ugc.review_id = rev.review_id
    WHERE ugc.collection_id=?
");
$stmt->bind_param("i", $collection_id);
$stmt->execute();
$res = $stmt->get_result();
$game = $res->fetch_assoc();
$stmt->close();

if (!$game) exit(json_encode(['success' => false, 'error' => 'Invalid game collection']));

echo json_encode([
    'success' => true,
    'title' => $game['title'],
    'rank' => $game['rank_name'],
    'skin' => $game['skin_name'],
    'transaction' => $game['transaction_id'],
    'review' => $game['review']
]);
?>