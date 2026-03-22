<?php
session_start();
require_once '../includes/db_config.php';

// Security check (only officials)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'secretary', 'admin'])) {
    die("Unauthorized");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $new_status = $_POST['new_status'];

    try {
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ?");
        $stmt->execute([$new_status, $user_id]);
        
        // Redirect back to the residents view
        header("Location: dashboard.php?view=residents");
        exit();
    } catch (PDOException $e) {
        die("Error updating status: " . $e->getMessage());
    }
}