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

$user_id = $_SESSION['user_id'];

$action = "Visited Review management page";
require 'audit.php';

$user_id = $_SESSION['user_id']; 
function getReviews($conn, $user_id) {
    $sql = "
        SELECT reviews.*, games.title AS game_title, users.username AS user_name
        FROM reviews
        JOIN games ON reviews.game_id = games.game_id
        JOIN users ON reviews.user_id = users.user_id
        WHERE reviews.user_id = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

if (isset($_POST['add_review'])) {
    $game_id = $_POST['game_id'];
    $rating = $_POST['rating'];
    $review_text = $_POST['review_text'];
    $review_date = $_POST['review_date'];

    $sql = "INSERT INTO reviews (game_id, user_id, rating, review_text, review_date) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiss", $game_id, $user_id, $rating, $review_text, $review_date);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    $action = "Added new review for game ID: $game_id";
    require 'audit.php';
    exit();
}

if (isset($_POST['update_review'])) {
    $review_id = $_POST['review_id'];
    $game_id = $_POST['game_id'];
    $rating = $_POST['rating'];
    $review_text = $_POST['review_text'];
    $review_date = $_POST['review_date'];

    $sql = "UPDATE reviews SET game_id=?, rating=?, review_text=?, review_date=? WHERE review_id=? AND user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissii", $game_id, $rating, $review_text, $review_date, $review_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    $action = "Updated review ID: $review_id";
    require 'audit.php';
    exit();
}

if (isset($_GET['delete_review'])) {
    $review_id = $_GET['delete_review'];
    $sql = "DELETE FROM reviews WHERE review_id=? AND user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $review_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    $action = "Deleted review ID: $review_id";
    require 'audit.php';
    exit();
}
$reviews = getReviews($conn, $user_id);
$edit = false;
$editData = null;
if (isset($_GET['edit_review'])) {
    $edit = true;
    $review_id = $_GET['edit_review'];
    $result = $conn->query("SELECT * FROM reviews WHERE review_id = $review_id AND user_id = $user_id");
    $editData = $result->fetch_assoc();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Management</title>
    <style>
        :root {
            --primary-color: #1e293b;
            --button-color: #090493;
            --button-hover: #0d0def;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f1f5f9;
            margin: 0;
            padding: 0;
        }

        .header {
            background-color: var(--primary-color);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .container {
            width: 90%;
            max-width: 1100px;
            margin: 30px auto;
            background-color: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        h2, h3 {
            color: #1e293b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }

        th, td {
            padding: 14px;
            border: 1px solid #ccc;
            text-align: left;
        }

        th {
            background-color: var(--primary-color);
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .form-section {
            margin-top: 30px;
        }

        form input[type="text"],
        form input[type="number"],
        form input[type="date"],
        form select,
        form textarea {
            width: 100%;
            padding: 10px;
            margin: 8px 0 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 15px;
        }

        .submit-btn,
        .back-button {
            background-color: var(--button-color);
            color: white;
            padding: 10px 22px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
            text-decoration: none;
            display: inline-block;
        }

        .submit-btn:hover,
        .back-button:hover {
            background-color: var(--button-hover);
        }

        .action-links a {
            margin-right: 10px;
            color: var(--button-color);
            text-decoration: none;
            font-weight: bold;
        }

        .action-links a:hover {
            color: var(--button-hover);
        }
    </style>
</head>
<body>

<div class="header">
    <h2>Review Management</h2>
</div>

<div class="container">
    <div class="form-section">
        <form method="POST">
            <h3>Add New Review</h3>
            <label>Game:</label>
            <select name="game_id" required>
                <?php
                $gameResult = $conn->query("SELECT game_id, title FROM games");
                while ($gameRow = $gameResult->fetch_assoc()) {
                    echo "<option value='" . $gameRow['game_id'] . "'>" . $gameRow['title'] . "</option>";
                }
                ?>
            </select>
            <label>Rating (1-5):</label>
            <input type="number" name="rating" min="1" max="5" required>
            <label>Review Text:</label>
            <textarea name="review_text" rows="4" required></textarea>
            <label>Review Date:</label>
            <input type="date" name="review_date" required>
            <button class="submit-btn" type="submit" name="add_review">Add Review</button>
        </form>
    </div>

    <?php if ($edit): ?>
    <div class="form-section">
        <form method="POST">
            <h3>Edit Review</h3>
            <input type="hidden" name="review_id" value="<?php echo $editData['review_id']; ?>">
            <label>Game:</label>
            <select name="game_id" required>
                <?php
                $gameResult = $conn->query("SELECT game_id, title FROM games");
                while ($gameRow = $gameResult->fetch_assoc()) {
                    $selected = ($editData['game_id'] == $gameRow['game_id']) ? "selected" : "";
                    echo "<option value='" . $gameRow['game_id'] . "' $selected>" . $gameRow['title'] . "</option>";
                }
                ?>
            </select>
            <label>Rating (1-5):</label>
            <input type="number" name="rating" min="1" max="5" required value="<?php echo $editData['rating']; ?>">
            <label>Review Text:</label>
            <textarea name="review_text" rows="4" required><?php echo $editData['review_text']; ?></textarea>
            <label>Review Date:</label>
            <input type="date" name="review_date" required value="<?php echo $editData['review_date']; ?>">
            <button class="submit-btn" type="submit" name="update_review">Update Review</button>
        </form>
    </div>
    <?php endif; ?>

    <table>
        <tr>
            <th>Review ID</th>
            <th>Game Title</th>
            <th>Rating</th>
            <th>Review Text</th>
            <th>Review Date</th>
            <th>Action</th>
        </tr>
        <?php if ($reviews->num_rows > 0): ?>
            <?php while($row = $reviews->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row["review_id"]; ?></td>
                    <td><?php echo $row["game_title"]; ?></td>
                    <td><?php echo $row["rating"]; ?></td>
                    <td><?php echo $row["review_text"]; ?></td>
                    <td><?php echo $row["review_date"]; ?></td>
                    <td class="action-links">
                        <a href="?edit_review=<?php echo $row['review_id']; ?>">Edit</a>
                        <a href="?delete_review=<?php echo $row['review_id']; ?>" onclick="return confirm('Are you sure you want to permanently delete this review?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">No reviews found</td></tr>
        <?php endif; ?>
    </table>

    <br>
    <a href="management.php" class="back-button">← Back</a>
</div>

</body>
</html>

<?php $conn->close(); ?>
