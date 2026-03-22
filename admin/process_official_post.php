<?php
session_start();
require_once '../includes/db_config.php';

if ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $content = trim($_POST['content']);
    $user_id = $_SESSION['user_id'];

    if (!empty($content)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, type) VALUES (?, ?, 'official')");
            $stmt->execute([$user_id, $content]);
            
            http_response_code(200); // Success!
            exit();
        } catch (PDOException $e) {
            http_response_code(500);
            exit();
        }
    }
}
?>