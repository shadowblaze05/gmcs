<?php
session_start();
include("../../db.php");

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../index.html");
    exit();
}

$username = $_SESSION['username'];

// Fetch games from the database
$games = [];
$sql = "SELECT game_id, title, game_image FROM games";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $games[] = $row;
    }
} else {
    // Handle query failure
    die("Query failed: " . $conn->error);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - GMCS Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            text-align: center;
        }

        header {
            background-color: #1e293b;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header h1 {
            font-size: 28px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .username {
            font-weight: bold;
        }

        .logout-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .logout-btn:hover {
            background-color: #bb2d3b;
        }

        .subtitle {
            font-size: 20px;
            margin: 30px 0 10px;
        }

        .container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            padding: 40px;
        }

        .game-card {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: transform 0.3s ease;
        }

        .game-card:hover {
            transform: translateY(-5px);
        }

        .game-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .game-card h3 {
            font-size: 18px;
            margin-bottom: 10px;
        }

        .game-card form button {
            width: 100%;
            padding: 10px;
            background-color: rgb(9, 4, 147);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .game-card form button:hover {
            background-color: rgb(13, 13, 239);
        }
    </style>
</head>

<body>
    <header>
        <h1>Admin - Game Management Control System</h1>
        <div class="user-info">
            <span class="username">Welcome, <?php echo htmlspecialchars($username); ?></span>
            <form action="../logout.php" method="POST" style="margin: 0;">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>
    </header>

    <div class="subtitle">
        <h3>Select a Game</h3>
    </div>

    <div class="container">
        <?php foreach ($games as $game): ?>
            <div class="game-card">
            <img src="../admin_manage/uploads/<?php echo htmlspecialchars($game['game_image']); ?>" alt="<?php echo htmlspecialchars($game['title']); ?>">
                <h3><?php echo htmlspecialchars($game['title']); ?></h3>
                <form action="admin_manage/management.php" method="GET">
                    <input type="hidden" name="game_id" value="<?php echo $game['game_id']; ?>">
                    <button type="submit">Manage</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</body>

</html>
