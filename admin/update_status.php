<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_config.php';

if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = $_GET['id'];
    $status = $_GET['status'];

    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ?");
    if ($stmt->execute([$status, $id])) {
        echo "<script>alert('Account status updated!'); window.history.back();</script>";
    }
}
?>