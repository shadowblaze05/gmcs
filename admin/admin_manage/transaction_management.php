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

$host = "127.0.0.1";
$username = "root";
$password = "";
$database = "gcms";
$port = 3307;

$conn = new mysqli($host, $username, $password, $database, $port);

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
    <style>
    body {
        font-family: 'Segoe UI', sans-serif;
        background-color: #f1f5f9;
        margin: 0;
        padding: 0;
    }

    .container {
        width: 90%;
        max-width: 1200px;
        margin: 40px auto;
        background-color: #ffffff;
        padding: 30px;
        border-radius: 16px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    }

    h2 {
        text-align: center;
        color: #1e293b;
        margin-bottom: 20px;
    }

    form {
        margin-bottom: 40px;
    }

    form label {
        display: block;
        margin-top: 15px;
        font-weight: 600;
        color: #1e293b;
    }

    form select,
    form input[type="number"],
    form input[type="date"] {
        width: 100%;
        padding: 10px 12px;
        margin-top: 6px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 15px;
        background-color: #f8fafc;
        transition: border-color 0.3s;
    }

    form select:focus,
    form input:focus {
        border-color: #090493;
        outline: none;
    }

    .submit-btn, .back-button {
        margin-top: 25px;
        background-color: #090493;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 16px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .submit-btn:hover, .back-button:hover {
        background-color: #0d0def;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 30px;
    }

    th, td {
        text-align: left;
        padding: 14px;
        border-bottom: 1px solid #e2e8f0;
    }

    th {
        background-color: #1e293b;
        color: white;
    }

    tr:nth-child(even) {
        background-color: #f8fafc;
    }

    tr:hover {
        background-color: #e0f2fe;
    }

    table a {
        color: #090493;
        text-decoration: none;
        font-weight: 600;
        margin-right: 8px;
    }

    table a:hover {
        text-decoration: underline;
    }

    h3 {
        color: #0f172a;
        margin-top: 40px;
    }

    .error-message {
        color: red;
        font-weight: bold;
        margin-top: 10px;
    }
    
</style>

</head>
<body>

<div class="container">
    <h2>Transaction Management</h2>

    <!-- Add New Transaction Form -->
    <form method="POST">
        <div>
            <label>Game Title:</label>
            <select name="game_id" required id="add_game_id">
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
            <label>Skin (optional):</label>
            <select name="skin_id" id="add_skin_id">
                <option value="">No Skin</option>
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

    <?php if ($edit): ?>
    <form method="POST">
        <h3>Edit Transaction</h3>
        <input type="hidden" name="transaction_id" value="<?php echo $editData['transaction_id']; ?>">

        <label>Game Title:</label>
        <select name="game_id" required id="edit_game_id">
            <?php
            $gameResult = $conn->query("SELECT game_id, title FROM games");
            while ($gameRow = $gameResult->fetch_assoc()) {
                $selected = ($editData['game_id'] == $gameRow['game_id']) ? "selected" : "";
                echo "<option value='" . $gameRow['game_id'] . "' $selected>" . $gameRow['title'] . "</option>";
            }
            ?>
        </select>

        <label>Skin (optional):</label>
        <select name="skin_id" id="edit_skin_id">
            <option value="">No Skin</option>
        </select>

        <label>Price (Real-Money):</label>
        <input type="number" name="amount" required value="<?php echo $editData['amount']; ?>">

        <label>Transaction Date:</label>
        <input type="date" name="transaction_date" required value="<?php echo $editData['transaction_date']; ?>">

        <button class="submit-btn" type="submit" name="update_transaction">Update Transaction</button>
    </form>

    <script>
    // Load skins for edit mode
    document.addEventListener('DOMContentLoaded', function () {
        const gameId = document.getElementById("edit_game_id").value;
        if (gameId) {
            fetch("getSkins.php?game_id=" + gameId)
                .then(response => response.json())
                .then(skins => {
                    const skinSelect = document.getElementById("edit_skin_id");
                    skinSelect.innerHTML = '<option value="">No Skin</option>';
                    skins.forEach(skin => {
                        const option = document.createElement("option");
                        option.value = skin.skin_id;
                        option.textContent = skin.skin_name;
                        if (skin.skin_id == <?php echo $editData['skin_id'] ?? 'null'; ?>) {
                            option.selected = true;
                        }
                        skinSelect.appendChild(option);
                    });
                });
        }
    });
    </script>
    <?php endif; ?>

    <script>
    // Load skins for add mode
    document.getElementById("add_game_id").addEventListener("change", function () {
        const gameId = this.value;
        if (gameId) {
            fetch("getSkins.php?game_id=" + gameId)
                .then(response => response.json())
                .then(skins => {
                    const skinSelect = document.getElementById("add_skin_id");
                    skinSelect.innerHTML = '<option value="">No Skin</option>';
                    skins.forEach(skin => {
                        const option = document.createElement("option");
                        option.value = skin.skin_id;
                        option.textContent = skin.skin_name;
                        skinSelect.appendChild(option);
                    });
                });
        }
    });
    </script>

<!-- Transaction Table -->
<h3>All Transactions</h3>
<div class="transaction-table">
    <table>
        <tr>
            <th>Transaction ID</th>
            <th>Game Title</th>
            <th>Skin</th>
            <th>Amount (Real Peso(₱))</th>
            <th>Transaction Date</th>
            <th>Action</th>
        </tr>
        <?php if ($transaction->num_rows > 0): ?>
            <?php while($row = $transaction->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row["transaction_id"]; ?></td>
                    <td><?php echo $row["game_title"]; ?></td>
                    <td><?php echo $row["skin_name"]; ?></td>
                    <td><?php echo $row["amount"]; ?></td>
                    <td><?php echo $row["transaction_date"]; ?></td>
                    <td>
                        <a href="?edit_transaction=<?php echo $row['transaction_id']; ?>">Edit</a> |
                        <a href="?delete_transaction=<?php echo $row['transaction_id']; ?>" onclick="return confirm('Are you sure you want to permanently delete this transaction?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">No transactions found</td></tr>
        <?php endif; ?>
    </table>
</div>

    <br>
    <a href="management.php" class="back-button">← Back</a>
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
