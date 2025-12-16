<?php
// Start session
session_start();

// Include DB connection
include("db.php");

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get username and password from form
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare and execute query
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Database error: " . $conn->error);
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if user exists
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            $action = "User {$user['username']} logged in";
            require_once 'admin/admin_manage/audit.php';

            if ($user['role'] === 'admin') {
                header("Location: admin/main.php");
            } else {
                header("Location: user/user_dashboard.php");
            }
            exit();
        } else {
            echo "Invalid password.";
        }
    } else {
        echo "Username not found.";
    }

    $stmt->close();
}
$conn->close();
?>



