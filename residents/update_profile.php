<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $form_type = $_POST['form_type']; 

    try {
        $pdo->beginTransaction();

        // --- 1. PERSONAL INFORMATION UPDATE ---
        if ($form_type === 'personal_info') {
            $f_name  = $_POST['first_name'];
            $l_name  = $_POST['last_name'];
            $bday    = $_POST['birthdate'];
            $contact = $_POST['contact_no'];
            $address = $_POST['full_address'] ?? '';
            $email   = $_POST['email'];

            // Update residents table
            $stmt1 = $pdo->prepare("UPDATE residents SET first_name = ?, last_name = ?, birthdate = ?, contact_no = ?, full_address = ? WHERE user_id = ?");
            $stmt1->execute([$f_name, $l_name, $bday, $contact, $address, $user_id]);

            // Update email in users table
            $stmt2 = $pdo->prepare("UPDATE users SET email = ? WHERE user_id = ?");
            $stmt2->execute([$email, $user_id]);

        // --- 2. PASSWORD CHANGE ---
        } elseif ($form_type === 'change_password') {
            $current_password = $_POST['current_password'];
            $new_password     = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            // Fetch current hash
            $stmtCheck = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
            $stmtCheck->execute([$user_id]);
            $user = $stmtCheck->fetch();

            // Verify current password
            if (!$user || !password_verify($current_password, $user['password'])) {
                throw new Exception("Incorrect current password. Password change failed.");
            }

            // Validate strength & match
            if (strlen($new_password) < 8 || !preg_match('/[A-Z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
                throw new Exception("New password does not meet requirements (8+ chars, Capital, Number).");
            }
            
            if ($new_password !== $confirm_password) {
                throw new Exception("New passwords do not match!");
            }

            // Save new hashed password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt3 = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt3->execute([$hashed_password, $user_id]);

// --- 3. PROFILE IMAGE UPLOAD ---
        } elseif ($form_type === 'profile_image') {
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                
                // 1. Get the current image name from the database before we update it
                $stmtOld = $pdo->prepare("SELECT profile_image FROM residents WHERE user_id = ?");
                $stmtOld->execute([$user_id]);
                $old_img = $stmtOld->fetchColumn();

                $img_name = $_FILES['image']['name'];
                $tmp_name = $_FILES['image']['tmp_name'];
                
                $img_ex = pathinfo($img_name, PATHINFO_EXTENSION);
                $img_ex_lc = strtolower($img_ex);
                $allowed_exs = array("jpg", "jpeg", "png");

                if (in_array($img_ex_lc, $allowed_exs)) {
                    $new_img_name = "USER_" . $user_id . "_" . uniqid() . "." . $img_ex_lc;
                    $img_upload_path = '../assets/uploads/residents/' . $new_img_name;

                    if (move_uploaded_file($tmp_name, $img_upload_path)) {
                        
                        // 2. DELETE THE OLD FILE (if it's not the default avatar)
                        if (!empty($old_img) && $old_img !== 'default_avatar.png') {
                            $old_file_path = '../assets/uploads/residents/' . $old_img;
                            if (file_exists($old_file_path)) {
                                unlink($old_file_path); // This deletes the actual file
                            }
                        }

                        // 3. Update database with the new name
                        $stmt = $pdo->prepare("UPDATE residents SET profile_image = ? WHERE user_id = ?");
                        $stmt->execute([$new_img_name, $user_id]);
                    } else {
                        throw new Exception("Failed to move uploaded file.");
                    }
                } else {
                    throw new Exception("Invalid file type. Only JPG, JPEG, and PNG allowed.");
                }
            } else {
                throw new Exception("No file selected or an error occurred.");
            }
        }
        $pdo->commit();
        header("Location: dashboard.php?update=success");

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header("Location: dashboard.php?update=error&msg=" . urlencode($e->getMessage()));
    }
} else {
    header("Location: dashboard.php");
    exit();
}