<?php
session_start();
require_once '../includes/db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    $reason = htmlspecialchars($_POST['reason']);
    $fullname = $_POST['fullname'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $admin_name = $_SESSION['username'] ?? 'Admin';

    try {
        $pdo->beginTransaction();

        // 1. Log the rejection details
        $logSql = "INSERT INTO rejection_logs (username, email, fullname, reason, rejected_by) VALUES (?, ?, ?, ?, ?)";
        $logStmt = $pdo->prepare($logSql);
        $logStmt->execute([$username, $email, $fullname, $reason, $admin_name]);

        // 2. Delete the user (This triggers CASCADE to delete the resident too)
        $deleteSql = "DELETE FROM users WHERE user_id = ?";
        $deleteStmt = $pdo->prepare($deleteSql);
        $deleteStmt->execute([$user_id]);

        $pdo->commit();

        echo "<script>alert('User rejected and logged successfully.'); window.location.href='dashboard.php?view=residents';</script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}
?>