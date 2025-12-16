<?php
include("db.php");


error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Safely collect form data
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm-password'] ?? '';

    // Basic validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        die("All fields are required.");
    }

    if ($password !== $confirm_password) {
        die("Passwords do not match.");
    }

    // Check if username or email already exists
    $check = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $check->bind_param("ss", $username, $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        die("Username or email already exists. Please choose another.");
    }
    $check->close();

    // Hash the password before storing
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert the new user
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
    if (!$stmt) {
        die("Database error: " . $conn->error);
    }

    $stmt->bind_param("sss", $username, $email, $hashed_password);

    if ($stmt->execute()) {
        // ✅ Get the new user ID
        $user_id = $stmt->insert_id;

        // ✅ Prepare audit info
        $action = "New user signup: $username (ID: $user_id)";
        require_once 'admin/admin_manage/audit.php';

        echo "Signup successful! Redirecting to login page...";
        echo "<script>setTimeout(function(){ window.location.href='index.html'; }, 2000);</script>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
