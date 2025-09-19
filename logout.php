<?php
require_once 'config/config.php';

// Destroy session
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to homepage
header('Location: ' . APP_URL . '/index.php?logged_out=1');
exit();
?>
