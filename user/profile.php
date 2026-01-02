<?php
require "../db.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "error" => "Not logged in"]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action = "Viewed profile for user ID: $user_id";
require '../admin/admin_manage/audit.php';

/* ===============================
   FETCH USER INFO
================================ */
$user_stmt = $conn->prepare("SELECT username, email FROM users WHERE user_id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();

/* ===============================
   FETCH COLLECTION
================================ */
$sql = "SELECT * FROM vw_user_game_profile WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$collections_res = $stmt->get_result();
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

        .game-card {
            display: flex;
            align-items: center;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .game-card img {
            width: 120px;
            height: 120px;
            border-radius: 10px;
            object-fit: cover;
            margin-right: 20px;
            border: 2px solid #0d0def;
        }

        .tag {
            background: #0d0def;
            color: white;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            display: inline-block;
            margin-top: 4px;
        }

        .skin-badge {
            background: #090493;
            color: white;
            padding: 6px 10px;
            border-radius: 8px;
            display: inline-block;
            margin-top: 6px;
        }

        .back-btn {
            background: transparent;
            border: none;
            color: blue;
            font-size: 14px;
            cursor: pointer;
            margin-bottom: 10px;
        }

        .back-btn:hover {
            color: #ffffff;
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="profile-container">

        <button class="back-btn" onclick="window.history.back()">← Back</button>

        <h2><?= htmlspecialchars($user_data['username']) ?>'s Profile</h2>

        <h3>Your Game Collection</h3>

        <?php while ($g = $collections_res->fetch_assoc()): ?>

            <div class="game-card">
                <img src="../<?= htmlspecialchars($g['game_image']) ?>" alt="Game Image">

                <div>
                    <h3><?= htmlspecialchars($g['game_title']) ?></h3>

                    <div class="tag">
                        Rank: <?= htmlspecialchars($g['rank_name'] ?? 'Unranked') ?>
                        <!--(Level <?= htmlspecialchars($g['rank_level'] ?? 0) ?>)-->
                    </div>

                    <div class="tag">
                        Review: <?= htmlspecialchars($g['review_text'] ?? 'No Review') ?>
                    </div>

                    <p><strong>Owned Skins:</strong></p>
                    <?php
                    $skins = explode(',', $g['skins'] ?? '');
                    foreach ($skins as $skin):
                    ?>
                        <span class="skin-badge"><?= htmlspecialchars(trim($skin)) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php endwhile; ?>

    </div>

</body>

</html>