<?php
session_start();
session_unset();
session_destroy();

// Redirect to the correct login path
header("Location: ../index.html");
exit();
