<?php
session_start();
session_unset();
session_destroy();

// Redirect to the correct login path
header("Location: ../index.html");
exit();
$action = "User logged out: User ID $user_id";
require '../admin/admin_manage/audit.php';
?>