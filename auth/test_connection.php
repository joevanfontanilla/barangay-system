<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing paths...<br>";

if (file_exists('../includes/db_config.php')) {
    echo "✅ db_config.php found!<br>";
    require_once '../includes/db_config.php';
    echo "✅ Database connected!<br>";
} else {
    echo "❌ db_config.php NOT found!<br>";
}

if (file_exists('../vendor/PHPMailer/src/PHPMailer.php')) {
    echo "✅ PHPMailer files found!<br>";
} else {
    echo "❌ PHPMailer files MISSING in vendor folder!<br>";
}
?>