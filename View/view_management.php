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


$username = $_SESSION['username'];

if (!isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $user_id = $user['user_id'];
    $_SESSION['user_id'] = $user_id;
} else {
    $user_id = $_SESSION['user_id'];
}

$searchResults = [];
$hasSearched = false;

if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["query"]) && isset($_GET["module"])) {
    $query = $_GET['query'];
    $module = $_GET['module'];
    $hasSearched = true;

    switch ($module) {
        case "games":
            $sql = "SELECT * FROM games WHERE title LIKE ? AND user_id = ?";
            break;
            case "skins":
                $sql = "SELECT skins.* 
                        FROM skins 
                        JOIN games ON skins.game_id = games.game_id 
                        WHERE skins.skin_name LIKE ? AND games.user_id = ?";
                break;
        case "transactions":
            $sql = "SELECT * FROM transaction WHERE transaction_id LIKE ? AND user_id = ?";
            break;
        case "reviews":
            $sql = "SELECT * FROM reviews WHERE review_id LIKE ? AND user_id = ?";
            break;
        case "collection":
            $sql = "SELECT * FROM user_game_collection WHERE collection_id LIKE ? AND user_id = ?";
            break;
        default:
            $sql = "";
    }

    if (!empty($sql)) {
        $stmt = $conn->prepare($sql);
        $likeQuery = "%" . $query . "%";
        $stmt->bind_param("si", $likeQuery, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $searchResults[] = $row;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GMCS - View Data</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            padding-bottom: 50px;
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
            font-size: 26px;
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
            margin-top: 30px;
            font-size: 20px;
            font-weight: bold;
        }

        .search-container {
            margin-top: 20px;
        }

        .search-container input[type="text"],
        .search-container select {
            padding: 10px;
            font-size: 16px;
            border-radius: 5px;
            border: 1px solid #ccc;
            margin: 5px;
        }

        .search-container button {
            padding: 10px 20px;
            font-size: 16px;
            background-color: rgb(9, 4, 147);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .search-container button:hover {
            background-color: rgb(13, 13, 239);
        }

        .option-buttons {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 20px;
            margin: 40px auto;
        }

        .option-buttons form {
            flex: 1 1 200px;
        }

        .option-buttons button {
            width: 100%;
            padding: 15px;
            font-size: 16px;
            background-color: #1e40af;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .option-buttons button:hover {
            background-color: #3b82f6;
        }

        .results-container {
            margin: 40px auto;
            max-width: 900px;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0px 4px 12px rgba(0,0,0,0.1);
            text-align: left;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ccc;
            text-align: left;
        }

        th {
            background-color: #1e293b;
            color: white;
        }

        .back-link {
            display: inline-block;
            margin: 20px auto;
            background-color: #dc3545;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 16px;
        }

        .back-link:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>

<header>
    <h1>GMCS - View Data</h1>
    <div class="user-info">
        <span class="username">Welcome, <?= htmlspecialchars($username); ?></span>
        <form action="../logout.php" method="POST" style="margin: 0;">
            <button type="submit" class="logout-btn">Logout</button>
        </form>
    </div>
</header>

<a href="../main.php" class="back-link">← Back to Dashboard</a>

<div class="subtitle">Search or Choose a View Option</div>

<div class="search-container">
    <form method="GET" action="">
        <input type="text" name="query" placeholder="Search..." required value="<?= htmlspecialchars($_GET['query'] ?? '') ?>">
        <select name="module">
            <option value="games" <?= (($_GET['module'] ?? '') === 'games') ? 'selected' : '' ?>>Games</option>
            <option value="skins" <?= (($_GET['module'] ?? '') === 'skins') ? 'selected' : '' ?>>Skins</option>
            <option value="transactions" <?= (($_GET['module'] ?? '') === 'transactions') ? 'selected' : '' ?>>Transactions</option>
            <option value="reviews" <?= (($_GET['module'] ?? '') === 'reviews') ? 'selected' : '' ?>>Reviews</option>
            <option value="collection" <?= (($_GET['module'] ?? '') === 'collection') ? 'selected' : '' ?>>Collection</option>
        </select>
        <button type="submit">Search</button>
    </form>
</div>

<div class="option-buttons">
    <form action="game_view.php" method="POST">
        <button type="submit">Games</button>
    </form>
    <form action="Skin_view.php" method="POST">
        <button type="submit">Skins</button>
    </form>
    <form action="Transaction_view.php" method="POST">
        <button type="submit">Transactions</button>
    </form>
    <form action="Review_view.php" method="POST">
        <button type="submit">Reviews</button>
    </form>
    <form action="collection_view.php" method="POST">
        <button type="submit">Collection</button>
    </form>
</div>

<?php if ($hasSearched): ?>
<div class="results-container">
    <h3>Search Results in <strong><?= ucfirst($module) ?></strong></h3>
    <?php if (count($searchResults) > 0): ?>
        <table>
            <tr>
                <?php foreach (array_keys($searchResults[0]) as $header): ?>
                    <th><?= htmlspecialchars(ucwords(str_replace('_', ' ', $header))) ?></th>
                <?php endforeach; ?>
            </tr>
            <?php foreach ($searchResults as $row): ?>
                <tr>
                    <?php foreach ($row as $cell): ?>
                        <td><?= htmlspecialchars($cell) ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No results found.</p>
    <?php endif; ?>
</div>
<?php endif; ?>

</body>
</html>
