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

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gcms";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

function getTransaction($conn, $user_id) {
    $sql = "SELECT transaction.*, users.username AS user_name, games.title AS game_title, skins.skin_name
            FROM transaction
            JOIN users ON transaction.user_id = users.user_id
            JOIN games ON transaction.game_id = games.game_id
            JOIN skins ON transaction.skin_id = skins.skin_id
            WHERE transaction.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

function getSkinsByGame($conn, $game_id) {
    $sql = "SELECT skin_id, skin_name FROM skins WHERE skin_id IN (SELECT skin_id FROM game_skins WHERE game_id = ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $game_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Handle adding new transaction
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_transaction'])) {
    $game_id = $_POST['game_id'];
    $skin_id = $_POST['skin_id'];
    $amount = $_POST['amount'];
    $transaction_date = $_POST['transaction_date'];

    $stmt = $conn->prepare("INSERT INTO transaction (user_id, game_id, skin_id, amount, transaction_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiids", $user_id, $game_id, $skin_id, $amount, $transaction_date);

    if ($stmt->execute()) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "<p class='error-message'>Error adding transaction: " . $stmt->error . "</p>";
    }
    $stmt->close();
}

// Handle updating transaction
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_transaction'])) {
    $transaction_id = $_POST['transaction_id'];
    $game_id = $_POST['game_id'];
    $skin_id = $_POST['skin_id'];
    $amount = $_POST['amount'];
    $transaction_date = $_POST['transaction_date'];

    $stmt = $conn->prepare("UPDATE transaction SET game_id = ?, skin_id = ?, amount = ?, transaction_date = ? WHERE transaction_id = ? AND user_id = ?");
    $stmt->bind_param("iidssi", $game_id, $skin_id, $amount, $transaction_date, $transaction_id, $user_id);

    if ($stmt->execute()) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "<p class='error-message'>Error updating transaction: " . $stmt->error . "</p>";
    }
    $stmt->close();
}

// Handle deleting transaction
if (isset($_GET['delete_transaction'])) {
    $transaction_id = $_GET['delete_transaction'];
    $stmt = $conn->prepare("DELETE FROM transaction WHERE transaction_id=? AND user_id=?");
    $stmt->bind_param("ii", $transaction_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch transactions for display
$transaction = getTransaction($conn, $user_id);
$edit = false;
$editData = null;

// Fetch transaction data for edit
if (isset($_GET['edit_transaction'])) {
    $edit = true;
    $transaction_id = intval($_GET['edit_transaction']);
    $stmt = $conn->prepare("SELECT * FROM transaction WHERE transaction_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $transaction_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $editData = $result->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
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
    <h2>Transaction Management</h2>

    <!-- Add New Transaction Form -->
    <form method="POST">
        <div>
            <label>Game Title:</label>
            <select name="game_id" required onchange="fetchSkinsByGame(this.value, this.form)">
                <option value="">Select Game</option>
                <?php
                $gameResult = $conn->query("SELECT game_id, title FROM games");
                while ($gameRow = $gameResult->fetch_assoc()) {
                    echo "<option value='" . $gameRow['game_id'] . "'>" . $gameRow['title'] . "</option>";
                }
                ?>
            </select>
        </div>

        <div>
            <label>Skin:</label>
            <select name="skin_id" class="skin-dropdown" required>
                <option value="">Select a game first</option>
            </select>
        </div>

        <div>
            <label>Price (Real-Money):</label>
            <input type="number" name="amount" required step="0.01">
        </div>

        <div>
            <label>Transaction Date:</label>
            <input type="date" name="transaction_date" required>
        </div>

        <button type="submit" name="add_transaction" class="submit-btn">Add Transaction</button>
    </form>

    <!-- Edit Transaction Form -->
    <?php if ($edit): ?>
        <form method="POST">
            <h3>Edit Transaction</h3>
            <input type="hidden" name="transaction_id" value="<?php echo $editData['transaction_id']; ?>">

            <div>
                <label>Game Title:</label>
                <select name="game_id" required onchange="fetchSkinsByGame(this.value, this.form)">
                    <?php
                    $gameResult = $conn->query("SELECT game_id, title FROM games");
                    while ($gameRow = $gameResult->fetch_assoc()) {
                        $selected = ($editData['game_id'] == $gameRow['game_id']) ? "selected" : "";
                        echo "<option value='" . $gameRow['game_id'] . "' $selected>" . $gameRow['title'] . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div>
                <label>Skin:</label>
                <select name="skin_id" class="skin-dropdown" required>
                    <option value="">Select a skin</option>
                    <?php
                    $skinResult = getSkinsByGame($conn, $editData['game_id']);
                    while ($skinRow = $skinResult->fetch_assoc()) {
                        $selected = ($editData['skin_id'] == $skinRow['skin_id']) ? "selected" : "";
                        echo "<option value='" . $skinRow['skin_id'] . "' $selected>" . $skinRow['skin_name'] . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div>
                <label>Price (Real-Money):</label>
                <input type="number" name="amount" step="0.01" required value="<?php echo htmlspecialchars($editData['amount']); ?>">
            </div>

            <div>
                <label>Transaction Date:</label>
                <input type="date" name="transaction_date" required value="<?php echo date('Y-m-d', strtotime($editData['transaction_date'])); ?>">
            </div>

            <button type="submit" name="update_transaction" class="submit-btn">Update Transaction</button>
        </form>
    <?php endif; ?>

    <!-- Transactions Table -->
    <table>
        <tr>
            <th>Transaction ID</th>
            <th>Game Title</th>
            <th>Skin</th>
            <th>Amount</th>
            <th>Transaction Date</th>
            <th>Actions</th>
        </tr>
        <?php if ($transaction->num_rows > 0): ?>
            <?php while($row = $transaction->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row["transaction_id"]; ?></td>
                    <td><?php echo $row["game_title"]; ?></td>
                    <td><?php echo $row["skin_name"]; ?></td>
                    <td><?php echo $row["amount"]; ?></td>
                    <td><?php echo $row["transaction_date"]; ?></td>
                    <td class="action-btns">
                        <a href="?edit_transaction=<?php echo $row['transaction_id']; ?>">Edit</a>
                        <a href="?delete_transaction=<?php echo $row['transaction_id']; ?>" onclick="return confirm('Are you sure you want to delete this transaction?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">No transactions found</td></tr>
        <?php endif; ?>
    </table>

    <br>
    <a href="management.php" class="back-button">&#8592; Back</a>
</div>

<script>
function fetchSkinsByGame(game_id, form) {
    var xhr = new XMLHttpRequest();
    xhr.open("GET", "getSkins.php?game_id=" + game_id, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            var skins = JSON.parse(xhr.responseText);
            var skinDropdown = form.querySelector(".skin-dropdown");
            skinDropdown.innerHTML = "<option value=''>Select a skin</option>";
            skins.forEach(function(skin) {
                var option = document.createElement("option");
                option.value = skin.skin_id;
                option.textContent = skin.skin_name;
                skinDropdown.appendChild(option);
            });
        }
    };
    xhr.send();
}
</script>

</body>
</html>

<?php $conn->close(); ?>
