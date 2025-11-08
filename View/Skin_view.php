<?php
session_start();
include("../db.php");
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] !== 'user') {
    header("Location: ../index.html");
    exit();
} 

// Function to Get Skins with Game Title
function getskins($conn) {
    $sql = "SELECT skins.skin_id, skins.skin_name, skins.rarity, skins.price, games.title AS game_title
            FROM skins
            JOIN games ON skins.game_id = games.game_id";
    return $conn->query($sql);
}

// Fetch Data
$skins = getskins($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skin List</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            text-align: center;
            margin: 20px;
        }
        .container {
            width: 80%;
            margin: auto;
            background: white;
            padding: 20px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        h2 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background: #007BFF;
            color: white;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        .back-button {
            margin-top: 20px;
            padding: 10px 20px;
            background: #007BFF;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            font-size: 16px;
        }
        .back-button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Your Skins List</h2>
    <table>
        <tr>
            <th>Skin ID</th>
            <th>Game Title</th>
            <th>Skin Name</th>
            <th>Rarity</th>
            <th>Price (In-Game Money)</th>
        </tr>
        <?php if ($skins->num_rows > 0): ?>
            <?php while($row = $skins->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row["skin_id"]; ?></td>
                    <td><?php echo $row["game_title"]; ?></td>
                    <td><?php echo $row["skin_name"]; ?></td>
                    <td><?php echo $row["rarity"]; ?></td>
                    <td><?php echo $row["price"]; ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5">No skins found</td></tr>
        <?php endif; ?>
    </table>

    <br>
    <a href="view_management.php" class="back-button">← Back</a>
</div>

</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
