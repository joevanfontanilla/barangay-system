<?php
session_start();
require_once '../includes/db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']); 
    $password = $_POST['password'];
    
    // 1. Catch the portal type from the hidden input
    $intended_role = $_POST['intended_role'] ?? 'user'; 

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user) {
            if (password_verify($password, $user['password'])) {
                
                $actual_role = $user['role'];
                $current_status = $user['status']; // Get the status (pending, active, inactive, unverified)

                // --- 2. THE STATUS GATE (UPDATED WITH UNVERIFIED) ---
                
                // ADDED: Check for email verification first
                if ($current_status === 'unverified') {
                    echo "<script>alert('Your email is not yet verified. Please check your inbox and click the verification link.'); window.history.back();</script>";
                    exit();
                }

                if ($current_status === 'pending') {
                    echo "<script>alert('Your account is still PENDING. Please wait for the Secretary or Super Admin to approve your registration.'); window.history.back();</script>";
                    exit();
                }

                if ($current_status === 'inactive') {
                    echo "<script>alert('Your account is INACTIVE. Please contact the Barangay Office for assistance.'); window.history.back();</script>";
                    exit();
                }

                // --- 3. THE ROLE SECURITY GATE ---
                
                // Block Residents from Captain/Official Portals
                if ($intended_role === 'super_admin' && $actual_role !== 'super_admin') {
                    echo "<script>alert('STOP! This portal is for the Barangay Captain only.'); window.history.back();</script>";
                    exit();
                }

                if ($intended_role === 'admin') {
                    $officials = ['secretary', 'treasurer', 'kagawad', 'admin'];
                    if (!in_array($actual_role, $officials)) {
                        echo "<script>alert('STOP! This portal is for Barangay Officials only.'); window.history.back();</script>";
                        exit();
                    }
                }

                // Block Captain/Officials from the Resident Portal
                if ($intended_role === 'user' && $actual_role !== 'user') {
                    echo "<script>alert('Please use the Official or Captain portal to log in.'); window.history.back();</script>";
                    exit();
                }

                // --- 4. SUCCESSFUL LOGIN ---
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['username'] = $user['username'];

                // Redirect logic based on role
                if ($actual_role === 'super_admin') {
                    header("Location: ../admin/dashboard.php");
                } elseif (in_array($actual_role, ['secretary', 'treasurer', 'kagawad', 'admin'])) {
                    header("Location: ../officials/dashboard.php");
                } else {
                    header("Location: ../residents/dashboard.php");
                }
                exit();
            } else {
                echo "<script>alert('Invalid password.'); window.history.back();</script>";
            }
        } else {
            echo "<script>alert('User not found.'); window.history.back();</script>";
        }
    } catch (PDOException $e) { 
        die("Database Error: " . $e->getMessage()); 
    }
}