<?php
// Prevent session_start() from being called twice
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // We send them back to the login with a message
    header("Location: ../auth/login.php?msg=Please login to access this page");
    exit();
}

function restrictToSuperAdmin() {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
        header("Location: ../residents/dashboard.php?msg=Unauthorized access denied.");
        exit();
    }
}
?>