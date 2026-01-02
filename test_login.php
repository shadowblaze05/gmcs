<?php
$servername = "localhost";
$username = "root";
$password = ""; // or your root password if set
$dbname = "gcms";
$port = 3307; // your custom MySQL port

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$username = "Shadow";

$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

print_r($row);

$stmt->close();
$conn->close();
?>