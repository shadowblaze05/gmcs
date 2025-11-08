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

function getreview($conn, $user_id) {
    $sql = "SELECT reviews.review_id, reviews.user_id, reviews.game_id, reviews.rating, reviews.review_text, reviews.review_date, games.title AS game_title, users.username AS username
            FROM reviews
            JOIN users ON reviews.user_id = users.user_id
            JOIN games ON reviews.game_id = games.game_id
            WHERE reviews.user_id = ?"; 

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);  
        $stmt->execute();
        return $stmt->get_result(); 
    } else {
        die("Error preparing the statement: " . $conn->error);
    }
}

$review = getreview($conn, $_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Reviews List</title>
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
    <h2>Your Reviews List</h2>
    <table>
        <tr>
            <th>Review ID</th>
            <th>User</th>
            <th>Game</th>
            <th>Rating (1-5)</th>
            <th>Review</th>
            <th>Review Date</th>
        </tr>
        <?php if ($review->num_rows > 0): ?>
            <?php while($row = $review->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row["review_id"]; ?></td>
                    <td><?php echo $row["username"]; ?></td>
                    <td><?php echo $row["game_title"]; ?></td>
                    <td><?php echo $row["rating"]; ?></td>
                    <td><?php echo $row["review_text"]; ?></td>
                    <td><?php echo $row["review_date"]; ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">No reviews found</td></tr>
        <?php endif; ?>
    </table>

    <br>
    <a href="view_management.php" class="back-button">← Back</a>
</div>

</body>
</html>

<?php
$conn->close();
?>
