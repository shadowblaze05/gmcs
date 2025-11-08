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

$searchResults = [];
$hasSearched = false;

if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["query"], $_GET["module"])) {
    $query = $_GET['query'];
    $module = $_GET['module'];
    $hasSearched = true;

    $sql = match ($module) {
        "games" => "SELECT * FROM games WHERE title LIKE ?",
        "skins" => "SELECT * FROM skins WHERE skin_name LIKE ?",
        "transactions" => "SELECT * FROM transactions WHERE transaction_id LIKE ?",
        "reviews" => "SELECT * FROM reviews WHERE review_text LIKE ?",
        "collection" => "SELECT * FROM collection WHERE item_name LIKE ?",
        default => ""
    };

    if ($sql) {
        $stmt = $conn->prepare($sql);
        $likeQuery = "%" . $query . "%";
        $stmt->bind_param("s", $likeQuery);
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
    <title>GMCS - View Management</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f4;
            color: #333;
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
            font-size: 24px;
        }
        .back-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            font-size: 14px;
            text-decoration: none;
        }
        .back-btn:hover {
            background-color: #bb2d3b;
        }
        .container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }
        h2 {
            margin-bottom: 20px;
        }
        .options {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            margin-bottom: 30px;
        }
        .option-button {
            padding: 14px 24px;
            font-size: 16px;
            background-color: #090493;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .option-button:hover {
            background-color: #0d0def;
        }
        .results {
            margin-top: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #090493;
            color: white;
        }
        .search-bar {
            text-align: center;
            margin-bottom: 30px;
        }
        .search-bar input,
        .search-bar select {
            padding: 10px;
            margin-right: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }
        .search-bar button {
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
        }
        .search-bar button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>

<header>
    <h1>GMCS - View Management</h1>
    <a href="../main.php" class="back-btn">← Back</a>
</header>

<div class="container">
    <h2>What Would You Like to Manage?</h2>

    <div class="options">
        <form action="user_management.php" method="POST"><button class="option-button" type="submit">Users</button></form>
        <form action="game_management.php" method="POST"><button class="option-button" type="submit">Games</button></form>
        <form action="Skin_management.php" method="POST"><button class="option-button" type="submit">Skins</button></form>
        <form action="transaction_management.php" method="POST"><button class="option-button" type="submit">Transactions</button></form>
        <form action="review_management.php" method="POST"><button class="option-button" type="submit">Reviews</button></form>
        <form action="collections_management.php" method="POST"><button class="option-button" type="submit">Collections</button></form>
    </div>

</body>
</html>
