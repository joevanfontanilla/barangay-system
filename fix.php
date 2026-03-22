<?php
$plain = "Wasd1187!";
// This is the new, verified hash
$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

if (password_verify($plain, $hash)) {
    echo "✅ THE HASH IS NOW CORRECT! You can now log in with Wasd1187!";
} else {
    echo "❌ Still not matching. Check for hidden spaces when copying.";
}
?>