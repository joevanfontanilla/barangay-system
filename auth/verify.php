<?php
// Start session in case you want to track verification status
session_start();
require_once '../includes/db_config.php';

// We use trim() to ensure no accidental whitespace breaks the token check
if (isset($_GET['email']) && isset($_GET['token'])) {
    $email = trim($_GET['email']);
    $token = trim($_GET['token']);

    try {
        // 1. Check if a user exists with this email, token, and 'unverified' status
        $stmt = $pdo->prepare("SELECT user_id, username FROM users WHERE email = ? AND verification_token = ? AND status = 'unverified'");
        $stmt->execute([$email, $token]);
        $user = $stmt->fetch();

        if ($user) {
            // 2. Update user to 'active' and clear the token so it can't be used again
            // NOTE: If you want them to go to 'pending' for Admin approval, change 'active' to 'pending'
            $update = $pdo->prepare("UPDATE users SET status = 'active', verification_token = NULL WHERE user_id = ?");
            $update->execute([$user['user_id']]);

            echo "<script>
                    alert('Email verified successfully! Hello " . htmlspecialchars($user['username']) . ", you can now log in.');
                    window.location.href = 'login.php';
                  </script>";
            exit();
        } else {
            // Check if they are already active (maybe they clicked the link twice?)
            $checkActive = $pdo->prepare("SELECT status FROM users WHERE email = ?");
            $checkActive->execute([$email]);
            $statusCheck = $checkActive->fetch();

            if ($statusCheck && $statusCheck['status'] === 'active') {
                echo "<script>
                        alert('This account is already verified. Please log in.');
                        window.location.href = 'login.php';
                      </script>";
            } else {
                echo "<script>
                        alert('Invalid or expired verification link.');
                        window.location.href = '../index.php';
                      </script>";
            }
            exit();
        }
    } catch (PDOException $e) {
        // On a live site like InfinityFree, it's safer to log errors rather than die()
        error_log("Verification Error: " . $e->getMessage());
        die("An internal error occurred. Please try again later.");
    }
} else {
    // Redirect if they try to access the file directly without parameters
    header("Location: ../index.php");
    exit();
}
?>