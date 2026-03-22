<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $post_id = $_POST['post_id'] ?? null;
    $comment_text = trim($_POST['comment_text'] ?? '');
    
    // Check if the request came from Admin or Resident
    // We check both the hidden 'from' input AND the actual URL path
    $is_admin = (isset($_POST['from']) && $_POST['from'] === 'admin');
    
    // UNIVERSAL REDIRECT LOGIC
    // If Admin: go back to admin dashboard
    // If Resident: stay in resident dashboard
    $dest = $is_admin ? '../admin/dashboard.php' : 'dashboard.php';

    // 1. Forbidden Word Filter
    $forbidden = ['gago', 'tanga', 'bobo', 'puta', 'nonsense', 'scam']; 
    foreach ($forbidden as $word) {
        if (stripos($comment_text, $word) !== false) {
            echo "<script>alert('Inappropriate language in comments is not allowed.'); window.history.back();</script>";
            exit();
        }
    }

    if (!empty($comment_text) && !empty($post_id)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO post_comments (post_id, user_id, comment_text) VALUES (?, ?, ?)");
            $stmt->execute([$post_id, $user_id, $comment_text]);
            
            // Redirect back with success message
            header("Location: " . $dest . "?comment=success");
            exit();
        } catch (Exception $e) {
            error_log($e->getMessage());
            header("Location: " . $dest . "?msg=" . urlencode("Error saving comment."));
            exit();
        }
    } else {
        header("Location: " . $dest);
        exit();
    }
} else {
    header("Location: ../index.php");
    exit();
}