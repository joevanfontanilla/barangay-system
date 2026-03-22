<?php
require_once 'includes/db_config.php';

$password_plain = "Wasd1187!";
$hashed_password = password_hash($password_plain, PASSWORD_DEFAULT);

try {
    $pdo->beginTransaction();

    // 1. CLEAN UP: Remove old records to prevent duplicate errors
    $pdo->exec("DELETE FROM residents WHERE first_name IN ('Jonathan E.', 'Claudine A.')");
    $pdo->exec("DELETE FROM users WHERE username IN ('Jonathan', 'Claudine')");

    // 2. INSERT SUPER ADMIN (Jonathan)
    $stmt1 = $pdo->prepare("INSERT INTO users (username, password, email, role, status) VALUES (?, ?, ?, ?, ?)");
    $stmt1->execute(['Jonathan', $hashed_password, 'jonathan@barangay.gov', 'super_admin', 'active']);
    $user_id_1 = $pdo->lastInsertId();

    $stmt2 = $pdo->prepare("INSERT INTO residents (user_id, first_name, last_name, civil_status, purok, full_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt2->execute([$user_id_1, 'Jonathan E.', 'Marzan', 'Married', 'Purok 1', 'Barangay Diagyan, Dilasag, Aurora']);

    // 3. INSERT SECRETARY (Claudine)
    $stmt3 = $pdo->prepare("INSERT INTO users (username, password, email, role, status) VALUES (?, ?, ?, ?, ?)");
    $stmt3->execute(['Claudine', $hashed_password, 'claudine@barangay.gov', 'secretary', 'active']);
    $user_id_2 = $pdo->lastInsertId();

    $stmt4 = $pdo->prepare("INSERT INTO residents (user_id, first_name, last_name, civil_status, purok, full_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt4->execute([$user_id_2, 'Claudine A.', 'Bio', 'Single', 'Purok 2', 'Barangay Diagyan, Dilasag, Aurora']);

    $pdo->commit();
    echo "<h2 style='color:green;'>✅ Success! Officials have been created.</h2>";
    echo "<p><b>Username:</b> Jonathan | <b>Pass:</b> Wasd1187!</p>";
    echo "<p><b>Username:</b> Claudine | <b>Pass:</b> Wasd1187!</p>";
    echo "<hr><p style='color:red;'><b>SECURITY WARNING:</b> Delete this file (setup_officials.php) immediately after use!</p>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<h2 style='color:red;'>❌ Error: " . $e->getMessage() . "</h2>";
}
?>