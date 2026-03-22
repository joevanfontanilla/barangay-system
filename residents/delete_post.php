<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_config.php';

if (isset($_GET['id'])) {
    $post_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    // Verify ownership before deleting
    $stmt = $pdo->prepare("DELETE FROM posts WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post_id, $user_id]);

    header("Location: dashboard.php?msg=Post deleted");
} else {
    header("Location: dashboard.php");
}