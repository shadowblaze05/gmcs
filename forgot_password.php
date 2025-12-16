<?php
session_start();

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

require_once 'audit.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email format.";
        exit();
    }
    $to_email = "shadowblaze269@gmail.com";  
    $subject = "Password Reset Request - GMCS";
    $message = "A user with the email address $email has requested to reset their password.";
    $headers = "From: no-reply@yourdomain.com"; 
    if (mail($to_email, $subject, $message, $headers)) {
        echo "A reset link request has been sent to your email address.";
    } else {
        echo "Error sending email.";
    }
}
?>
