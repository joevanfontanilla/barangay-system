<?php
require_once '../includes/db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Collect and sanitize basic input
    $username = htmlspecialchars(trim($_POST['username']));
    $email = htmlspecialchars(trim($_POST['email']));
    $first_name = htmlspecialchars(trim($_POST['first_name']));
    $last_name = htmlspecialchars(trim($_POST['last_name']));
    $gender = $_POST['gender'] ?? ''; 
    
    // IMPORTANT: Catch the 'contact_no' from the hidden field
    $contact_no = $_POST['contact_no'] ?? ''; 
    
    $birthdate = $_POST['birthdate'];
    $civil_status = $_POST['civil_status'];
    $purok = htmlspecialchars(trim($_POST['purok']));

    // 2. Password Validation
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        die("<script>alert('Error: Passwords do not match.'); window.history.back();</script>");
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 3. Address Handling
    $full_address = $purok . ", " . $_POST['barangay'] . ", " . $_POST['city'] . ", " . $_POST['province'];

    try {
        $pdo->beginTransaction();

        // 4. Insert into 'users' table
        $sql1 = "INSERT INTO users (username, password, email, role, status) VALUES (?, ?, ?, 'user', 'pending')";
        $stmt1 = $pdo->prepare($sql1);
        $stmt1->execute([$username, $hashed_password, $email]);
        
        $user_id = $pdo->lastInsertId();

        // 5. Insert into 'residents' table
        // Double-check: Make sure 'contact_no' exists in your DB table!
        $sql2 = "INSERT INTO residents (user_id, first_name, last_name, birthdate, gender, contact_no, civil_status, purok, full_address) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt2 = $pdo->prepare($sql2);
        
        $stmt2->execute([
            $user_id, 
            $first_name, 
            $last_name, 
            $birthdate,
            $gender, 
            $contact_no, // Ensuring this value is passed
            $civil_status, 
            $purok, 
            $full_address
        ]);

        $pdo->commit();
        echo "<script>alert('Registration Successful!'); window.location.href='login.php';</script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        if ($e->getCode() == 23000) {
            die("<script>alert('Error: Username or Email already exists.'); window.history.back();</script>");
        }
        // This will tell you exactly which column is the problem
        die("Registration Error: " . $e->getMessage());
    }
}
?>