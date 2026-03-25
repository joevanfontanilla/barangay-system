<?php
// 1. Load PHPMailer classes
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// This tells PHP: "Go out of 'auth', then into 'vendor'"
require '../vendor/PHPMailer/src/Exception.php';
require '../vendor/PHPMailer/src/PHPMailer.php';
require '../vendor/PHPMailer/src/SMTP.php';

require_once '../includes/db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = htmlspecialchars(trim($_POST['username']));
    $email = htmlspecialchars(trim($_POST['email']));
    $first_name = htmlspecialchars(trim($_POST['first_name']));
    $last_name = htmlspecialchars(trim($_POST['last_name']));
    $gender = $_POST['gender'] ?? ''; 
    $contact_no = $_POST['contact_no'] ?? ''; 
    $birthdate = $_POST['birthdate'];
    $civil_status = $_POST['civil_status'];
    $purok = htmlspecialchars(trim($_POST['purok']));

    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        die("<script>alert('Error: Passwords do not match.'); window.history.back();</script>");
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $verification_token = bin2hex(random_bytes(16)); 
    $full_address = $purok . ", " . $_POST['barangay'] . ", " . $_POST['city'] . ", " . $_POST['province'];

    try {
        $pdo->beginTransaction();

        // 2. Insert User (Status: unverified)
       // Inside process_register.php
$sql1 = "INSERT INTO users (username, password, email, role, status, verification_token) 
         VALUES (?, ?, ?, 'user', 'unverified', ?)";
        $stmt1 = $pdo->prepare($sql1);
        $stmt1->execute([$username, $hashed_password, $email, $verification_token]);
        
        $user_id = $pdo->lastInsertId();

        // 3. Insert Resident Data
        $sql2 = "INSERT INTO residents (user_id, first_name, last_name, birthdate, gender, contact_no, civil_status, purok, full_address) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute([$user_id, $first_name, $last_name, $birthdate, $gender, $contact_no, $civil_status, $purok, $full_address]);

        // --- 4. START EMAIL SENDING ---
        $mail = new PHPMailer(true);

        // Server settings
$mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'joevanfontanilla@gmail.com';
        $mail->Password   = 'imvwghieuguiplpm'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Change this
        $mail->Port       = 465;                         // Change this

        // Recipients
        $mail->setFrom('joevanfontanilla@gmail.com', 'Barangay Connect');
        $mail->addAddress($email, "$first_name $last_name");

        // CLEANED LINK: Points directly to your auth folder
        $base_url = "https://barangay-connect-project.infinityfreeapp.com/auth/verify.php";
        $verify_link = $base_url . "?email=" . urlencode($email) . "&token=" . $verification_token;
        
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Barangay Account';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                <h3 style='color: #1877f2;'>Welcome to Barangay Connect, $first_name!</h3>
                <p>Thank you for registering. Please click the button below to verify your email address and activate your account:</p>
                <div style='margin: 30px 0;'>
                    <a href='$verify_link' style='background:#1877f2; color:white; padding:12px 25px; text-decoration:none; border-radius:5px; font-weight: bold;'>Verify Account</a>
                </div>
                <p style='font-size: 0.8em; color: #666;'>If the button doesn't work, copy and paste this link into your browser:<br>
                <a href='$verify_link'>$verify_link</a></p>
            </div>
        ";

        $mail->send();
        
        $pdo->commit();
        echo "<script>alert('Registration Successful! A verification email has been sent to $email.'); window.location.href='login.php';</script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        if ($e->getCode() == 23000) {
            die("<script>alert('Error: Username or Email already exists.'); window.history.back();</script>");
        }
        die("Registration/Email Error: " . $mail->ErrorInfo);
    }
}
?>