<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_config.php';

if (!isset($_GET['post_id']) || !is_numeric($_GET['post_id'])) {
    header("Location: dashboard.php");
    exit();
}

$post_id = $_GET['post_id'];
$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT 1 FROM post_likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post_id, $user_id]);

    if ($stmt->fetch()) {
        $pdo->prepare("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?")->execute([$post_id, $user_id]);
    } else {
        $pdo->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)")->execute([$post_id, $user_id]);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
}

// Logic: If coming from Admin, go back to Admin Dashboard. Otherwise, go to Resident Dashboard.
$redirect = (isset($_GET['from']) && $_GET['from'] === 'admin') ? '../admin/dashboard.php' : 'dashboard.php';
header("Location: " . $redirect);
exit();