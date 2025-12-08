<?php
session_start();
require "../db.php";

// HARD-CODE A TEST until we fix your JS
$_SESSION['user_id'] = 1;   // CHANGE THIS to your real user id
$username = "testuser";     // CHANGE THIS to a real existing username

echo "<h2>Testing Friend Request Insert</h2>";

echo "Sender (session user): " . $_SESSION['user_id'] . "<br>";
echo "Receiver username: $username<br><br>";

// Get receiver_id from username
$q = $conn->prepare("SELECT id FROM users WHERE username = ?");
$q->bind_param("s", $username);
$q->execute();
$res = $q->get_result();

if ($res->num_rows == 0) {
    echo "❌ Username not found.";
    exit;
}

$row = $res->fetch_assoc();
$receiver = $row['id'];

echo "Receiver ID: $receiver <br>";

// Insert into friend_requests
$insert = $conn->prepare("INSERT INTO friend_requests (sender_id, receiver_id, status) VALUES (?, ?, 'pending')");
$insert->bind_param("ii", $_SESSION['user_id'], $receiver);

if ($insert->execute()) {
    echo "✔ SUCCESS — friend request added!";
} else {
    echo "❌ FAILED: " . $conn->error;
}
$insert->close();