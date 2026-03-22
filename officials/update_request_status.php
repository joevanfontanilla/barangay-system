<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_config.php';

// Access Control
if (!in_array($_SESSION['role'], ['secretary', 'admin', 'super_admin'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'];
    $new_status = $_POST['new_status'];
    $admin_remarks = $_POST['admin_remarks'] ?? ''; // Capture the remarks from your form

    // Validate status values
    $allowed_statuses = ['pending', 'approved', 'rejected'];
    
    if (in_array($new_status, $allowed_statuses)) {
        try {
            // Determine the SQL based on whether it's being approved
            if ($new_status === 'approved') {
                // Update status, remarks, AND the approved_at timestamp
                $sql = "UPDATE document_requests 
                        SET status = ?, 
                            admin_remarks = ?, 
                            approved_at = NOW() 
                        WHERE request_id = ?";
                $params = [$new_status, $admin_remarks, $request_id];
            } else {
                // Update status and remarks only (don't touch approved_at)
                $sql = "UPDATE document_requests 
                        SET status = ?, 
                            admin_remarks = ? 
                        WHERE request_id = ?";
                $params = [$new_status, $admin_remarks, $request_id];
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            // Redirect back with success message
            header("Location: dashboard.php?view=requests&msg=updated");
            exit();
        } catch (PDOException $e) {
            // This will no longer throw the 'updated_at' error!
            die("Error updating status: " . $e->getMessage());
        }
    } else {
        header("Location: dashboard.php?view=requests&error=invalid_status");
        exit();
    }
}
?>