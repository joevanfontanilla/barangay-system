<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_config.php';

// Only Admins/Super Admins can delete
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin') {
    http_response_code(403);
    exit("Unauthorized");
}

if (isset($_GET['id'])) {
    $post_id = $_GET['id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM posts WHERE post_id = ?");
        $stmt->execute([$post_id]);
        
        // Just send a 200 OK status back to the AJAX script
        http_response_code(200);
        exit(); 
    } catch (PDOException $e) {
        http_response_code(500);
        exit();
    }
}
?>