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

// Audit: page visit (optional)
$action = "Visited user management page";
require 'audit.php';

if (isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $username, $email, $password);
    $stmt->execute();
    $stmt->close();

    // Audit log
    $action = "Added new user: $username ($email)";
    require 'audit.php';

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if (isset($_POST['update_user'])) {
    $user_id_to_update = $_POST['user_id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "UPDATE users SET username=?, email=?, password=? WHERE user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $username, $email, $password, $user_id_to_update);
    $stmt->execute();
    $stmt->close();

    // Audit log
    $action = "Updated user ID $user_id_to_update: $username ($email)";
    require 'audit.php';

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if (isset($_GET['delete'])) {
    $user_id_to_delete = $_GET['delete'];
    $sql = "DELETE FROM users WHERE user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id_to_delete);
    $stmt->execute();
    $stmt->close();

    // Audit log
    $action = "Deleted user ID $user_id_to_delete";
    require 'audit.php';

    echo "<script>alert('User has been deleted successfully.'); window.location.href='" . $_SERVER['PHP_SELF'] . "';</script>";
    exit();
}

function getUsers($conn)
{
    $sql = "SELECT * FROM users";
    return $conn->query($sql);
}

$users = getUsers($conn);
$edit = false;
$editData = null;
if (isset($_GET['edit'])) {
    $edit = true;
    $user_id_to_edit = $_GET['edit'];
    $result = $conn->query("SELECT * FROM users WHERE user_id = $user_id_to_edit");
    $editData = $result->fetch_assoc();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            text-align: center;
            margin: 20px;
        }

        .container {
            width: 90%;
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

        table,
        th,
        td {
            border: 1px solid #ddd;
        }

        th,
        td {
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

        .back-button,
        .submit-btn {
            margin-top: 20px;
            padding: 10px 20px;
            background: #007BFF;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            font-size: 16px;
        }

        .back-button:hover,
        .submit-btn:hover {
            background: #0056b3;
        }

        form {
            margin-top: 20px;
            text-align: left;
        }

        form input[type="text"],
        form input[type="email"],
        form input[type="password"] {
            width: 100%;
            padding: 8px;
            margin: 5px 0;
        }
    </style>
</head>

<body>

    <div class="container">
        <h2>User Management</h2>
        <form method="POST">
            <h3>Add New User</h3>
            <label>Username:</label><input type="text" name="username" required>
            <label>Email:</label><input type="email" name="email" required>
            <label>Password:</label><input type="password" name="password" required>
            <button class="submit-btn" type="submit" name="add_user">Add User</button>
        </form>

        <?php if ($edit): ?>
            <form method="POST">
                <h3>Edit User</h3>
                <input type="hidden" name="user_id" value="<?php echo $editData['user_id']; ?>">
                <label>Username:</label><input type="text" name="username" required value="<?php echo $editData['username']; ?>">
                <label>Email:</label><input type="email" name="email" required value="<?php echo $editData['email']; ?>">
                <label>Password:</label><input type="password" name="password" required value="<?php echo $editData['password']; ?>">
                <button class="submit-btn" type="submit" name="update_user">Update User</button>
            </form>
        <?php endif; ?>

        <table>
            <tr>
                <th>User ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Password</th>
                <th>Action</th>
            </tr>
            <?php if ($users->num_rows > 0): ?>
                <?php while ($row = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row["user_id"]; ?></td>
                        <td><?php echo $row["username"]; ?></td>
                        <td><?php echo $row["email"]; ?></td>
                        <td><?php echo $row["password"]; ?></td>
                        <td>
                            <a href="?edit=<?php echo $row['user_id']; ?>">Edit</a> |
                            <a href="?delete=<?php echo $row['user_id']; ?>" onclick="return confirm('⚠️ Are you sure you want to permanently delete this user?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No users found</td>
                </tr>
            <?php endif; ?>
        </table>

        <br>
        <a href="management.php" class="back-button">← Back</a>
    </div>

</body>

</html>

<?php $conn->close(); ?>