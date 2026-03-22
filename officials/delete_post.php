<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_config.php';

// Access Control
if (!in_array($_SESSION['role'], ['secretary', 'admin', 'super_admin'])) {
    exit("Unauthorized");
}

if (isset($_GET['id'])) {
    $post_id = intval($_GET['id']);

    try {
        $stmt = $pdo->prepare("DELETE FROM posts WHERE post_id = ?");
        if ($stmt->execute([$post_id])) {
            echo "Deleted";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>