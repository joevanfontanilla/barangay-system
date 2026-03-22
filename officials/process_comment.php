<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_config.php';

$user_id = $_SESSION['user_id'];

// ACTION: Save New Comment
if (isset($_POST['action']) && $_POST['action'] == 'add') {
    $post_id = intval($_POST['post_id']);
    $text = trim($_POST['comment_text']);

    if (!empty($text)) {
        $stmt = $pdo->prepare("INSERT INTO post_comments (post_id, user_id, comment_text) VALUES (?, ?, ?)");
        $stmt->execute([$post_id, $user_id, $text]);
        echo "success";
    }
    exit;
}

// ACTION: Fetch Comments
if (isset($_GET['action']) && $_GET['action'] == 'fetch') {
    $post_id = intval($_GET['post_id']);
    $stmt = $pdo->prepare("SELECT c.*, r.first_name, r.last_name 
                           FROM post_comments c 
                           LEFT JOIN residents r ON c.user_id = r.user_id 
                           WHERE c.post_id = ? ORDER BY c.created_at ASC");
    $stmt->execute([$post_id]);
    $comments = $stmt->fetchAll();

    foreach ($comments as $c) {
        $name = ($c['first_name']) ? $c['first_name'] . " " . $c['last_name'] : "Official";
        echo "<div style='margin-bottom: 8px; font-size: 0.9rem;'>
                <strong>$name:</strong> " . htmlspecialchars($c['comment_text']) . "
              </div>";
    }
    exit;
}
?>