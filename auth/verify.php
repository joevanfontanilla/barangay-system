<?php
session_start();
require_once '../includes/db_config.php';

if (isset($_GET['email']) && isset($_GET['token'])) {
    $email = trim($_GET['email']);
    $token = trim($_GET['token']);

    try {
        // 1. First, check if there is a user matching this email and token who is still 'unverified'
        $stmt = $pdo->prepare("SELECT user_id, username FROM users WHERE email = ? AND verification_token = ? AND status = 'unverified'");
        $stmt->execute([$email, $token]);
        $user = $stmt->fetch();

        if ($user) {
            // 2. Found them! Move them to 'pending' so the Admin/Secretary can approve them
            $update = $pdo->prepare("UPDATE users SET status = 'pending', verification_token = NULL WHERE user_id = ?");
            $update->execute([$user['user_id']]);

            echo "<script>
                    alert('Email verified successfully! Your account is now PENDING approval. Please wait for the Secretary or Captain to activate your account.');
                    window.location.href = 'login.php';
                  </script>";
            exit();
        } else {
            // 3. If no 'unverified' user found, check if they are already further along in the process
            $checkStatus = $pdo->prepare("SELECT status FROM users WHERE email = ?");
            $checkStatus->execute([$email]);
            $statusCheck = $checkStatus->fetch();

            if ($statusCheck) {
                if ($statusCheck['status'] === 'pending') {
                    echo "<script>
                            alert('This account is already verified and is currently awaiting Admin approval.');
                            window.location.href = 'login.php';
                          </script>";
                } elseif ($statusCheck['status'] === 'active') {
                    echo "<script>
                            alert('This account is already active. Please log in.');
                            window.location.href = 'login.php';
                          </script>";
                } else {
                    echo "<script>
                            alert('Invalid or expired verification link.');
                            window.location.href = '../index.php';
                          </script>";
                }
            } else {
                echo "<script>
                        alert('No account associated with this email was found.');
                        window.location.href = '../index.php';
                      </script>";
            }
            exit();
        }
    } catch (PDOException $e) {
        error_log("Verification Error: " . $e->getMessage());
        die("An internal error occurred. Please try again later.");
    }
} else {
    header("Location: ../index.php");
    exit();
}
?>