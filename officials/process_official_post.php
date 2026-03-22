<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_config.php';

// Security: Only allow Secretary, Admin, or Super Admin
if (!in_array($_SESSION['role'], ['secretary', 'admin', 'super_admin'])) {
    echo "Unauthorized";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $content = trim($_POST['content']);
    $user_id = $_SESSION['user_id']; // The ID of the official posting

    if (!empty($content)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, created_at) VALUES (?, ?, NOW())");
            if ($stmt->execute([$user_id, $content])) {
                echo "Success";
            } else {
                echo "Database Error";
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    } else {
        echo "Empty Content";
    }
}
?>