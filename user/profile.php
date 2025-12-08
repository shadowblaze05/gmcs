<?php
require "../db.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "error" => "Not logged in"]);
    exit;
}

$user_id = $_SESSION['user_id'];

/////////////////////////
// FETCH USER DATA
/////////////////////////
$user_stmt = $conn->prepare("SELECT username, email FROM users WHERE user_id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();

/////////////////////////
// FETCH GAME COLLECTIONS
/////////////////////////
$sql = "
    SELECT 
        c.collection_id,
        g.game_id,
        g.title,
        g.game_image,
        
        r.rank_name,
        r.rank_level,

        t.transaction_id,
        rv.review_text

    FROM user_game_collection c
    JOIN games g ON c.game_id = g.game_id
    LEFT JOIN ranks r ON c.rank_id = r.rank_id
    LEFT JOIN transaction t ON c.transaction_id = t.transaction_id
    LEFT JOIN reviews rv ON c.review_id = rv.review_id
    WHERE c.user_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$collections_res = $stmt->get_result();

$collections = [];

while ($row = $collections_res->fetch_assoc()) {

    // Fetch skins for this game
    $skin_sql = "
        SELECT s.skin_name 
        FROM game_skin gs
        JOIN skins s ON gs.skin_id = s.skin_id
        WHERE gs.game_id = ?
    ";
    $skin_stmt = $conn->prepare($skin_sql);
    $skin_stmt->bind_param("i", $row['game_id']);
    $skin_stmt->execute();
    $skin_result = $skin_stmt->get_result();

    $skins = [];
    while ($s = $skin_result->fetch_assoc()) {
        $skins[] = $s['skin_name'];
    }

    $collections[] = [
        "game_id" => $row['game_id'],
        "title" => $row['title'],
        "game_image" => $row['game_image'],
        "rank_name" => $row['rank_name'] ?? "Unranked",
        "rank_level" => $row['rank_level'] ?? "0",
        "transaction_id" => $row['transaction_id'] ?? "None",
        "review_text" => $row['review_text'] ?? "No Review",
        "skins" => $skins
    ];
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>User Profile</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            margin: 0;
            padding: 0;
        }

        .profile-container {
            width: 80%;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .profile-header h2 {
            margin: 0;
            font-size: 28px;
        }

        .game-card {
            display: flex;
            align-items: center;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        }

        .game-card img {
            width: 120px;
            height: 120px;
            border-radius: 10px;
            object-fit: cover;
            margin-right: 20px;
            border: 2px solid #0d0def;
        }

        .game-details h3 {
            margin: 0;
            font-size: 22px;
        }

        .tag {
            background: #0d0def;
            display: inline-block;
            color: white;
            padding: 4px 10px;
            border-radius: 6px;
            margin-top: 4px;
            font-size: 12px;
        }

        .skin-badge {
            background: #090493;
            color: white;
            padding: 5px 8px;
            border-radius: 8px;
            display: inline-block;
            margin-right: 5px;
            margin-top: 5px;
        }

        .section-title {
            font-size: 22px;
            margin-top: 30px;
        }
    </style>

</head>

<body>

    <div class="profile-container">
        <div class="profile-header">
            <button onclick="window.history.back()"
                style="padding:6px 12px; margin-bottom:10px; background:#0d0def; color:white; border:none; border-radius:6px; cursor:pointer;">
                ← Back
            </button>

            <h2><?= htmlspecialchars($user_data['username']) ?>'s Profile</h2>
        </div>

        <h3 class="section-title">Your Game Collections</h3>

        <?php if (empty($collections)) : ?>
            <p>No games in your collection.</p>

        <?php else : ?>
            <?php foreach ($collections as $g): ?>
                <div class="game-card">

                    <!-- GAME IMAGE -->
                    <img src="../<?= htmlspecialchars($g['game_image']) ?>" alt="Game Image">


                    <div class="game-details">
                        <h3><?= htmlspecialchars($g['title']) ?></h3>

                        <div class="tag">Rank: <?= htmlspecialchars($g['rank_name']) ?> (Level <?= htmlspecialchars($g['rank_level']) ?>)</div>
                        <div class="tag">Review: <?= htmlspecialchars($g['review_text']) ?></div>

                        <p><strong>Skins:</strong></p>
                        <?php foreach ($g['skins'] as $skin): ?>
                            <span class="skin-badge"><?= htmlspecialchars($skin) ?></span>
                        <?php endforeach; ?>
                    </div>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>

</body>

</html>