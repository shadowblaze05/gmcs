<?php
// ud_get_games.php
session_start();
require_once '../db.php';

$user_id = (int)$_SESSION['user_id'];

$action = "Viewed games list: User ID $user_id";
require '../admin/admin_manage/audit.php';


header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}
$me = (int)$_SESSION['user_id'];

$sql = "SELECT ug.id, g.game_id, g.title, ug.skins
        FROM user_games ug
        JOIN games g ON g.game_id = ug.game_id
        WHERE ug.user_id = ?
        ORDER BY ug.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $me);
$stmt->execute();
$res = $stmt->get_result();
$out = [];
while ($r = $res->fetch_assoc()) $out[] = $r;
echo json_encode($out);

?>
