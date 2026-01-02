<?php
session_start();
include("../db.php");
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.html");
    exit();
}
$username = $_SESSION['username'];

$user_id = $_SESSION['user_id'];

$action = "Visited main admin dashboard";
require_once 'admin_manage/audit.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GMCS - Dashboard</title>
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
            font-size: 28px;
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
            font-size: 20px;
            margin: 30px 0 10px;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 40px;
            gap: 40px;
        }

        .option-box {
            width: 280px;
            height: 200px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
            padding: 30px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .option-box:hover {
            transform: translateY(-5px);
        }

        .option-box h2 {
            margin-bottom: 25px;
            font-size: 22px;
        }

        .option-box button {
            width: 100%;
            padding: 14px;
            font-size: 16px;
            background-color:rgb(9, 4, 147);
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 6px;
            transition: background-color 0.3s ease;
        }

        .option-box button:hover {
            background-color:rgb(13, 13, 239);
        }

        footer {
            margin-top: 60px;
            font-size: 14px;
            color: #888;
        }
    </style>
</head>
<body>

<header>
    <h1>Admin - Game Management Control System</h1>
    <div class="user-info">
        <span class="username">Welcome, <?php echo htmlspecialchars($username); ?></span>
        <form action="../logout.php" method="POST" style="margin: 0;">
            <button type="submit" class="logout-btn">Logout</button>
        </form>
    </div>
</header>

<div class="container">
    <div class="option-box">
        <h2>Manage</h2>
        <form action="admin_manage/management.php" method="POST">
            <button type="submit">Go to Manage</button>
        </form>
    </div>

    <!--<div class="option-box">
        <h2>View</h2>
        <form action="admin_view/profile.php" method="POST">
            <button type="submit">Go to View</button>
        </form>
    </div> -->
</div>


</body>
</html>


