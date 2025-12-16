<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Make sure user_id exists in session
$user_id = $_SESSION['user_id'] ?? 0;

// Define a default action if not set
$action = $action ?? "Visited " . basename($_SERVER['PHP_SELF']); // You can override $action before including

// Ensure $conn exists (should be defined in the including page)
if (isset($conn) && $user_id > 0) {
    $stmt = $conn->prepare("INSERT INTO audit_trail (user_id, action, created_at) VALUES (?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("is", $user_id, $action);
        $stmt->execute();
        $stmt->close();
    } else {
        // Optional: log errors if needed
        error_log("Audit insert failed: " . $conn->error);
    }
}
?>