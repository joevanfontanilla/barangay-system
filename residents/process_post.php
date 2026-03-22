<?php
session_start();
require_once '../includes/db_config.php';

// 1. Security check: Only logged-in users can post
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $content = trim($_POST['content']);
    $user_id = $_SESSION['user_id'];
    // Dynamically detect type if stored in session, otherwise default to resident
    $user_type = isset($_SESSION['role']) ? $_SESSION['role'] : 'resident'; 

    // 2. FORBIDDEN WORD FILTER
    $forbidden = ['gago', 'tanga', 'bobo', 'puta', 'nonsense', 'scam']; 
    foreach ($forbidden as $word) {
        if (stripos($content, $word) !== false) {
            echo "<script>alert('Inappropriate language detected. Please keep your suggestions respectful.'); window.history.back();</script>";
            exit();
        }
    }

    // 3. CHECK: Content length (Min 10 characters)
    if (empty($content) || strlen($content) < 10) {
        echo "<script>alert('Please provide a meaningful suggestion (at least 10 characters).'); window.history.back();</script>";
        exit();
    }

    try {
        // 4. RATE LIMITING: Check cooldown (60 seconds)
        $stmt = $pdo->prepare("SELECT created_at, content FROM posts WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$user_id]);
        $lastPost = $stmt->fetch();

        if ($lastPost) {
            $lastTime = strtotime($lastPost['created_at']);
            $currentTime = time();
            $secondsPassed = $currentTime - $lastTime;
            $cooldown = 60; 

            if ($secondsPassed < $cooldown) {
                $wait = $cooldown - $secondsPassed;
                echo "<script>alert('Anti-Spam: Please wait $wait more seconds before posting again.'); window.history.back();</script>";
                exit();
            }

            // 5. DUPLICATE CHECK: Prevent double-posting
            if ($lastPost['content'] === $content) {
                echo "<script>alert('You already posted this exact suggestion.'); window.history.back();</script>";
                exit();
            }
        }

        // 6. SUCCESS: Save the post
        // We use $user_type to distinguish between 'official' and 'resident' posts
        $post_type = ($user_type === 'admin' || $user_type === 'superadmin') ? 'official' : 'resident';
        
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, type, status) VALUES (?, ?, ?, 'active')");
        $stmt->execute([$user_id, $content, $post_type]);
        
        // Redirect back to the dashboard with a success message
        header("Location: dashboard.php?msg=Post shared successfully!");
        exit();

    } catch (PDOException $e) {
        // Log the error and show a user-friendly message
        error_log("Post Error: " . $e->getMessage());
        echo "<script>alert('Something went wrong. Please try again later.'); window.history.back();</script>";
        exit();
    }
}
?>