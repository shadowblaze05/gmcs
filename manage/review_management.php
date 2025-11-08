<?php
session_start();
include("../db.php");

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] !== 'user') {
    header("Location: ../index.html");
    exit();
} 

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
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; text-align: center; margin: 20px; }
        .container { width: 90%; margin: auto; background: white; padding: 20px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); border-radius: 8px; }
        h2 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 12px; text-align: left; }
        th { background: #007BFF; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .back-button, .submit-btn { margin-top: 20px; padding: 10px 20px; background: #007BFF; color: white; border: none; cursor: pointer; border-radius: 5px; font-size: 16px; }
        .back-button:hover, .submit-btn:hover { background: #0056b3; }
        form { margin-top: 20px; text-align: left; }
        form input[type="text"], form input[type="number"], form select, form textarea { width: 100%; padding: 8px; margin: 5px 0; }
    </style>
</head>
<body>

<div class="container">
    <h2>Review Management</h2>
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
        <label>Rating:</label><input type="number" name="rating" min="1" max="5" required>
        <label>Review Text:</label><textarea name="review_text" required></textarea>
        <label>Review Date:</label><input type="date" name="review_date" required>
        <br>
        <button class="submit-btn" type="submit" name="add_review">Add Review</button>
    </form>
    <?php if ($edit): ?>
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
        <label>Rating:</label><input type="number" name="rating" min="1" max="5" required value="<?php echo $editData['rating']; ?>">
        <label>Review Text:</label><textarea name="review_text" required><?php echo $editData['review_text']; ?></textarea>
        <label>Review Date:</label><input type="date" name="review_date" required value="<?php echo $editData['review_date']; ?>">
        <button class="submit-btn" type="submit" name="update_review">Update Review</button>
    </form>
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
                    <td>
                        <a href="?edit_review=<?php echo $row['review_id']; ?>">Edit</a> |
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
