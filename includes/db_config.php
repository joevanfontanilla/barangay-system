<?php
// Determine if we are on Localhost (XAMPP) or Online (InfinityFree)
if ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['REMOTE_ADDR'] == '127.0.0.1' || $_SERVER['REMOTE_ADDR'] == '::1') {
    // LOCALHOST SETTINGS (XAMPP)
    $host = 'localhost';
    $dbname = 'barangay_db';
    $username = 'root'; 
    $password = ''; 
} else {
    // ONLINE SETTINGS (InfinityFree - Update these from your Account Dashboard!)
    $host = 'sqlXXX.infinityfree.com'; // <--- Change to your MySQL Host
    $dbname = 'if0_XXXXXX_db';         // <--- Change to your DB Name
    $username = 'if0_XXXXXX';           // <--- Change to your DB Username
    $password = 'Your_FTP_Password';    // <--- Change to your Account Password
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>