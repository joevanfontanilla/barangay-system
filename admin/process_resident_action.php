<?php
session_start();
include '../includes/db_config.php'; 

// Changed 'admin' to 'super_admin' to match your database role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    $action = $_POST['action'];

    try {
        if ($action === 'activate') {
            // Approve: Set status to active
            $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE user_id = ?");
            $stmt->execute([$user_id]);
        } elseif ($action === 'reject') {
            // Reject: Delete the resident and the user account
            // Delete from residents first due to Foreign Key constraints
            $stmt1 = $pdo->prepare("DELETE FROM residents WHERE user_id = ?");
            $stmt1->execute([$user_id]);
            
            // Then delete from users table
            $stmt2 = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt2->execute([$user_id]);
        }

        header("Location: dashboard.php?view=residents&status=updated");
        exit();
    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
}