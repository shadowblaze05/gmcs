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


function getSkins($conn) {
    $sql = "
        SELECT skins.*, games.title AS game_title
        FROM skins
        JOIN games ON skins.game_id = games.game_id
    ";
    return $conn->query($sql);
}

if (isset($_POST['add_skin'])) {
    $skin_name = $_POST['skin_name'];
    $rarity = $_POST['rarity'];
    $price = $_POST['price']; 
    $game_id = $_POST['game_id'];

    $sql = "INSERT INTO skins (skin_name, rarity, price, game_id) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $skin_name, $rarity, $price, $game_id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if (isset($_POST['update_skin'])) {
    $skin_id = $_POST['skin_id'];
    $skin_name = $_POST['skin_name'];
    $rarity = $_POST['rarity'];
    $price = $_POST['price']; 
    $game_id = $_POST['game_id'];

    $sql = "UPDATE skins SET skin_name=?, rarity=?, price=?, game_id=? WHERE skin_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssii", $skin_name, $rarity, $price, $game_id, $skin_id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if (isset($_GET['delete_skin'])) {
    $skin_id = $_GET['delete_skin'];

    $sql = "DELETE FROM skins WHERE skin_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $skin_id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$skins = getSkins($conn);
$edit = false;
$editData = null;
if (isset($_GET['edit_skin'])) {
    $edit = true;
    $skin_id = $_GET['edit_skin'];
    $result = $conn->query("SELECT * FROM skins WHERE skin_id = $skin_id");
    $editData = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skin Management</title>
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
        form input[type="text"], form input[type="number"], form select { width: 100%; padding: 8px; margin: 5px 0; }
    </style>
</head>
<body>

<div class="container">
    <h2>Skin Management</h2>
    <form method="POST">
        <h3>Add New Skin</h3>
        <label>Skin Name:</label><input type="text" name="skin_name" required>
        <label>Rarity:</label><input type="text" name="rarity" required>
        <label>Price (In-Game Money):</label><input type="text" name="price" required> 
        <label>Game Title:</label>
        <select name="game_id" required>
            <?php
            $gameResult = $conn->query("SELECT game_id, title FROM games");
            while ($gameRow = $gameResult->fetch_assoc()) {
                echo "<option value='" . $gameRow['game_id'] . "'>" . $gameRow['title'] . "</option>";
            }
            ?>
        </select>
        <button class="submit-btn" type="submit" name="add_skin">Add Skin</button>
    </form>

    <?php if ($edit): ?>
    <form method="POST">
        <h3>Edit Skin</h3>
        <input type="hidden" name="skin_id" value="<?php echo $editData['skin_id']; ?>">
        <label>Skin Name:</label><input type="text" name="skin_name" required value="<?php echo $editData['skin_name']; ?>">
        <label>Rarity:</label><input type="text" name="rarity" required value="<?php echo $editData['rarity']; ?>">
        <label>Price (In-Game Money):</label><input type="text" name="price" required value="<?php echo $editData['price']; ?>">
        <label>Game Title:</label>
        <select name="game_id" required>
            <?php
            $gameResult = $conn->query("SELECT game_id, title FROM games");
            while ($gameRow = $gameResult->fetch_assoc()) {
                $selected = ($editData['game_id'] == $gameRow['game_id']) ? "selected" : "";
                echo "<option value='" . $gameRow['game_id'] . "' $selected>" . $gameRow['title'] . "</option>";
            }
            ?>
        </select>
        <button class="submit-btn" type="submit" name="update_skin">Update Skin</button>
    </form>
    <?php endif; ?>

    <table>
        <tr>
            <th>Skin ID</th>
            <th>Skin Name</th>
            <th>Rarity</th>
            <th>Price (In-game money)</th> 
            <th>Game Title</th>
            <th>Action</th>
        </tr>
        <?php if ($skins->num_rows > 0): ?>
            <?php while($row = $skins->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row["skin_id"]; ?></td>
                    <td><?php echo $row["skin_name"]; ?></td>
                    <td><?php echo $row["rarity"]; ?></td>
                    <td><?php echo $row["price"]; ?></td>
                    <td><?php echo $row["game_title"]; ?></td>
                    <td>
                        <a href="?edit_skin=<?php echo $row['skin_id']; ?>">Edit</a> |
                        <a href="?delete_skin=<?php echo $row['skin_id']; ?>" onclick="return confirm('Are you sure you want to permanently delete this skin?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">No skins found</td></tr>
        <?php endif; ?>
    </table>

    <br>
    <a href="management.php" class="back-button">← Back</a>
</div>

</body>
</html>

<?php $conn->close(); ?>
