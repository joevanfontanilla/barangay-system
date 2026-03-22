<?php
// Detect the role from the URL (?role=...)
$display_role = "User";
$role_class = "resident-green-text";
$btn_class = "btn-secondary"; // Default green for residents

if (isset($_GET['role'])) {
    if ($_GET['role'] == 'super_admin') {
        $display_role = "Barangay Captain";
        $role_class = "admin-gold-text";
        $btn_class = ""; 
    } elseif ($_GET['role'] == 'admin') {
        $display_role = "Barangay Official";
        $role_class = "official-blue-text";
        $btn_class = ""; 
    } else {
        $display_role = "Resident";
        $role_class = "resident-green-text";
        $btn_class = "btn-secondary";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Barangay Connect - <?php echo $display_role; ?> Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Specific header colors based on role */
        .admin-gold-text { color: #b8860b; }
        .official-blue-text { color: #0056b3; }
        .resident-green-text { color: #1e7e34; }
        .login-header { margin-bottom: 20px; }
    </style>
</head>
<body class="auth-page">
    
    <div class="register-container"> 
        <div class="login-header" style="text-align: center;">
            <h2 class="<?php echo $role_class; ?>"><?php echo $display_role; ?> Portal</h2>
            <p style="color: #65676b; font-size: 14px;">Please enter your credentials to continue</p>
        </div>

<form action="process_login.php" method="POST">
    <input type="hidden" name="intended_role" value="<?php echo htmlspecialchars($_GET['role'] ?? 'user'); ?>">
    
    <input type="text" name="username" placeholder="Username" required>
    
    <div class="password-wrapper">
        <input type="password" id="login_password" name="password" placeholder="Password" required>
        <i class="fa-solid fa-eye toggle-eye" id="eye-icon" onclick="togglePassword('login_password', 'eye-icon')"></i>
    </div>

    <button type="submit" class="<?php echo $btn_class; ?>" style="margin-top: 10px;">Login</button>
</form>

        <div style="text-align:center; font-size:0.9em; margin-top: 15px; line-height: 1.8;">
            <p style="margin: 0;">New here? <a href="register.php" style="color: #1877f2; text-decoration: none; font-weight: 600;">Create an account</a></p>
            <p style="margin: 0;"><a href="../index.php" style="color: #65676b; text-decoration: none;">← Back to Home Feed</a></p>
        </div>
    </div>

    <script>
        // Updated Toggle Function for Font Awesome
        function togglePassword(inputId, eyeId) {
            const input = document.getElementById(inputId);
            const eyeIcon = document.getElementById(eyeId);
            
            if (input.type === "password") {
                input.type = "text";
                // Swap to slashed eye
                eyeIcon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = "password";
                // Swap back to normal eye
                eyeIcon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>
</html>