<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/auth_check.php';
require_once '../includes/db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'];
    $new_status = $_POST['new_status'];
    // Capture the remarks from the form
    $admin_remarks = isset($_POST['admin_remarks']) ? trim($_POST['admin_remarks']) : '';

    try {
        if ($new_status === 'approved') {
            // Update status, remarks, and set the 6-month timer start
            $sql = "UPDATE document_requests SET status = ?, admin_remarks = ?, approved_at = NOW() WHERE request_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$new_status, $admin_remarks, $request_id]);
        } else {
            // Update status and remarks only
            $sql = "UPDATE document_requests SET status = ?, admin_remarks = ? WHERE request_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$new_status, $admin_remarks, $request_id]);
        }

        // REDIRECT back to your dashboard with the success message
        header("Location: dashboard.php?status=success#manage-requests-section");
        exit();

    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
}