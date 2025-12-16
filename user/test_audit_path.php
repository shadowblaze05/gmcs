<?php
// Start session
session_start();

// Temporary dummy variable for testing
$action = "Test audit path";

// Try including the audit.php file

require '../admin/admin_manage/audit.php';

echo "Audit path works!";
