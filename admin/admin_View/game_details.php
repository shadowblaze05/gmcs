<?php
session_start();
include("../../db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../index.html");
    exit();
}

$game_id = isset($_GET['game_id']) ? intval($_GET['game_id']) : 0;

$sql = "SELECT games.*, user_game_collection.*, ranks.rank_name
        FROM user_game_collection
        LEFT JOIN games ON user_game_collection.game_id = games.game_id
        LEFT JOIN ranks ON user_game_collection.rank_id = ranks.rank_id
        WHERE user_game_collection.game_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $game_id);
$stmt->execute();
$result = $stmt->get_result();
$game = $result->fetch_assoc();

if (!$game) {
    echo "Game not found.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Game Details</title>
    <style>
        body {
            background-color: #f4f4f4;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            text-align: center;
        }
        .container {
            width: 90%;
            max-width: 900px;
            margin: 40px auto;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h2 {
            color: #1e293b;
            margin-bottom: 20px;
        }
        .game-image {
            max-width: 300px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .details {
            text-align: left;
            margin-top: 25px;
        }
        .details p {
            margin: 10px 0;
            font-size: 16px;
        }
        .details label {
            font-weight: bold;
            color: #333;
        }
        .back-button {
            margin-top: 30px;
            padding: 10px 20px;
            background-color: #090493;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            text-decoration: none;
            transition: background 0.3s ease;
        }
        .back-button:hover {
            background-color: #0d0def;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Game Details</h2>
    <img class="game-image" src="images/<?php echo htmlspecialchars($game['game_image']); ?>" alt="<?php echo htmlspecialchars($game['title']); ?>">

    <div class="details">
        <p><label>Title:</label> <?php echo htmlspecialchars($game['title']); ?></p>
        <p><label>Description:</label> <?php echo htmlspecialchars($game['description']); ?></p>
        <p><label>Release Date:</label> <?php echo htmlspecialchars($game['release_date']); ?></p>
        <p><label>Price:</label> ₱<?php echo number_format($game['price'], 2); ?></p>
        <p><label>Rating:</label> <?php echo $game['rating'] ?? 'N/A'; ?>/5</p>
        <p><label>Rank:</label> <?php echo htmlspecialchars($game['rank_name']); ?></p>
    </div>

    <a class="back-button" href="games.php">← Back to Games</a>
</div>

</body>
</html>

<?php $conn->close(); ?>
