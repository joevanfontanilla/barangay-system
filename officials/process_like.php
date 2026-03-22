<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_config.php';

if (isset($_POST['post_id'])) {
    $post_id = intval($_POST['post_id']);
    $user_id = $_SESSION['user_id'];

    try {
        // Check if this secretary already liked the post
        $check = $pdo->prepare("SELECT * FROM post_likes WHERE post_id = ? AND user_id = ?");
        $check->execute([$post_id, $user_id]);

        if ($check->rowCount() > 0) {
            // If exists, UNLIKE (Remove record)
            $delete = $pdo->prepare("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?");
            $delete->execute([$post_id, $user_id]);
            echo "unliked";
        } else {
            // If doesn't exist, LIKE (Add record)
            $insert = $pdo->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)");
            $insert->execute([$post_id, $user_id]);
            echo "liked";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "No Post ID provided";
}
?>