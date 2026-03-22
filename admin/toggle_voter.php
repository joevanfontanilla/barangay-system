<?php
// 1. Security Checks
require_once '../includes/auth_check.php'; // Adjust path if needed
require_once '../includes/db_config.php';   // Adjust path if needed

if (isset($_GET['id']) && isset($_GET['current'])) {
    $userId = $_GET['id'];
    $currentStatus = $_GET['current'];
    
    // Determine the opposite status
    $newStatus = ($currentStatus === 'Registered') ? 'Non-Registered' : 'Registered';

    try {
        // 2. Update the Database
        $stmt = $pdo->prepare("UPDATE residents SET voter_status = ? WHERE user_id = ?");
        $stmt->execute([$newStatus, $userId]);

        // 3. Redirect back to the Residents view on the Dashboard
        header("Location: dashboard.php?view=residents&msg=voter_updated");
        exit();
    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
} else {
    // If someone tries to load this page directly without IDs
    header("Location: dashboard.php?view=residents");
    exit();
}
?>