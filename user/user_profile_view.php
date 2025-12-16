<?php
session_start();
require "../db.php";

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['user_id'])) {
    echo "Invalid user.";
    exit;
}

$view_user_id = (int)$_GET['user_id'];

$action = "Viewed profile of user ID: $view_user_id";
require '../admin/admin_manage/audit.php';


        // Fetch user info
        $stmt = $conn->prepare("SELECT username, email FROM users WHERE user_id = ?");
$stmt->bind_param("i", $view_user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "User not found.";
    exit;
}

// Fetch their game collection
$sql = "
    SELECT c.game_id, g.title, g.game_image, r.rank_name, r.rank_level, rv.review_text
    FROM user_game_collection c
    JOIN games g ON c.game_id = g.game_id
    LEFT JOIN ranks r ON c.rank_id = r.rank_id
    LEFT JOIN reviews rv ON c.review_id = rv.review_id
    WHERE c.user_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $view_user_id);
$stmt->execute();
$games_res = $stmt->get_result();
$games = [];

while ($row = $games_res->fetch_assoc()) {
    // Fetch skins for this user's game
    $skin_stmt = $conn->prepare("
        SELECT s.skin_name
        FROM game_skin gs
        JOIN skins s ON gs.skin_id = s.skin_id
        WHERE gs.game_id = ?
    ");
    $skin_stmt->bind_param("i", $row['game_id']);
    $skin_stmt->execute();
    $skin_result = $skin_stmt->get_result();
    $skins = [];
    while ($s = $skin_result->fetch_assoc()) {
        $skins[] = $s['skin_name'];
    }
    $skin_stmt->close();

    $row['skins'] = $skins;
    $games[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($user['username']) ?>'s Profile</title>
    <style>
        body {
            font-family: Arial;
            background: #f1f5f9;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 80%;
            margin: 30px auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        h2,
        h3 {
            margin-top: 0;
        }

        .game-card {
            display: flex;
            align-items: flex-start;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .game-card img {
            width: 100px;
            height: 100px;
            border-radius: 8px;
            object-fit: cover;
            margin-right: 20px;
            border: 2px solid #0d0def;
        }

        .skin-badge,
        .tag {
            background: #090493;
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            display: inline-block;
            margin-right: 5px;
            margin-top: 5px;
            font-size: 12px;
        }

        .tag {
            background: #0d0def;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 6px 12px;
            background: #555;
            color: white;
            border-radius: 6px;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="user_dashboard.php" class="back-btn">← Back</a>
        <h2><?= htmlspecialchars($user['username']) ?>'s Profile</h2>

        <h3>Game Collection</h3>
        <?php if (empty($games)): ?>
            <p>This user has no games in their collection.</p>
        <?php else: ?>
            <?php foreach ($games as $g): ?>
                <div class="game-card">
                    <img src="../<?= htmlspecialchars($g['game_image']) ?>" alt="Game Image">
                    <div>
                        <h4><?= htmlspecialchars($g['title']) ?></h4>
                        <div class="tag">Rank: <?= htmlspecialchars($g['rank_name'] ?? 'Unranked') ?> (Level <?= htmlspecialchars($g['rank_level'] ?? 0) ?>)</div>
                        <div class="tag">Review: <?= htmlspecialchars($g['review_text'] ?? 'No review') ?></div>
                        <p><strong>Skins:</strong></p>
                        <?php if (!empty($g['skins'])): ?>
                            <?php foreach ($g['skins'] as $skin): ?>
                                <span class="skin-badge"><?= htmlspecialchars($skin) ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span>No skins</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>

</html>