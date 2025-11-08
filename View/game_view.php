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

function getGames($conn, $user_id) {
    $sql = "SELECT * FROM games WHERE user_id = ?";  
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id); 
    $stmt->execute();
    return $stmt->get_result(); 
}

$games = getGames($conn, $_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Game List</title>
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
    <h2>Your Game List</h2>
    <table>
        <tr>
            <th>Game ID</th>
            <th>Title</th>
            <th>Release Date</th>
            <th>Developer</th>
            <th>Publisher</th>
            <th>Genre</th>
            <th>Price ($)</th>
        </tr>
        <?php if ($games->num_rows > 0): ?>
            <?php while($row = $games->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row["game_id"]; ?></td>
                    <td><?php echo $row["title"]; ?></td>
                    <td><?php echo $row["release_date"]; ?></td>
                    <td><?php echo $row["developer"]; ?></td>
                    <td><?php echo $row["publisher"]; ?></td>
                    <td><?php echo $row["genre"]; ?></td>
                    <td><?php echo $row["price"]; ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7">You haven't added any games yet.</td></tr>
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
