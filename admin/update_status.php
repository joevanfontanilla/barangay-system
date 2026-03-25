<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db_config.php';

// 1. Security Check: Only allow Officials/Admins to run this script
if (!in_array($_SESSION['role'], ['super_admin', 'secretary', 'admin'])) {
    die("Unauthorized access.");
}

if (isset($_GET['id']) && isset($_GET['status'])) {
    $user_id = (int)$_GET['id'];
    $new_status = $_GET['status'];

    // 2. Only allow specific status changes for security
    $allowed_statuses = ['active', 'inactive'];
    
    if (in_array($new_status, $allowed_statuses)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ?");
            $stmt->execute([$new_status, $user_id]);

            $action = ($new_status === 'active') ? 'Approved' : 'Rejected';
            
            echo "<script>
                    alert('Resident has been $action successfully!');
                    window.location.href = 'manage_residents.php';
                  </script>";
        } catch (PDOException $e) {
            die("Error updating status: " . $e->getMessage());
        }
    } else {
        echo "<script>alert('Invalid status request.'); window.location.href = 'manage_residents.php';</script>";
    }
} else {
    header("Location: manage_residents.php");
}
exit();