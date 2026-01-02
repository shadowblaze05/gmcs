<?php
session_start();
require_once '../../db.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// --- Audit: log page visit ---
$action = "Visited Audit Log page";
require_once 'audit.php';

// Optional: search
$search = '';
$searchAction = '';
$searchUser = '';
if (isset($_GET['search']) && $_GET['search'] !== '') {
    $search = htmlspecialchars($_GET['search'], ENT_QUOTES, 'UTF-8');
    // Use prefix search to utilize indexes
    $searchAction = $search . '%';
    $searchUser   = $search . '%';
}

// Base query
$sql = "SELECT a.id, u.username, a.action, a.created_at AS timestamp
        FROM audit_trail a
        LEFT JOIN users u ON a.user_id = u.user_id";

// Use index-friendly search
if ($search) {
    $sql .= " WHERE a.action LIKE ? OR u.username LIKE ?";
}

$sql .= " ORDER BY a.created_at DESC";

$stmt = $conn->prepare($sql);

if ($search) {
    $stmt->bind_param("ss", $searchAction, $searchUser);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Audit Trail — GMCS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 90%;
            margin: 30px auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
        }

        h2 {
            margin-top: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th,
        table td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }

        table th {
            background: #090493;
            color: #fff;
        }

        .search-box {
            margin-bottom: 15px;
        }

        .search-box input[type="text"] {
            padding: 6px;
            width: 200px;
        }

        .search-box button {
            padding: 6px 12px;
            background: #0d0def;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Audit Trail</h2>

        <!-- Back Button -->
        <div style="margin-bottom: 15px;">
            <a href="management.php" style="text-decoration:none;">
                <button style="padding: 6px 12px; background:#090493; color:white; border:none; border-radius:6px; cursor:pointer;">
                    &larr; Back
                </button>
            </a>
        </div>

        <form method="GET" class="search-box">
            <input type="text" name="search" placeholder="Search by action or user" value="<?= $search ?>">
            <button type="submit">Search</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User/Admin</th>
                    <th>Action</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows === 0): ?>
                    <tr>
                        <td colspan="4" style="text-align:center;">No audit logs found.</td>
                    </tr>
                <?php else: ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['username'] ?? 'System') ?></td>
                            <td><?= htmlspecialchars($row['action']) ?></td>
                            <td><?= $row['timestamp'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>


</html>
<?php $stmt->close(); ?>