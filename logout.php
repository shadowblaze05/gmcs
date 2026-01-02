<?php
session_start();
$user_id = $_SESSION['user_id'];
$action = "User ID {$user_id} logged out";
require 'admin/admin_manage/audit.php';

session_unset();
session_destroy();
header("Location: index.html");

exit();
?>