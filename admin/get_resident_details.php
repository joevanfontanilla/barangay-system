<?php
ob_start();
require_once '../includes/auth_check.php';
require_once '../includes/db_config.php';
ob_clean();
header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception('No ID provided');
    }

    $id = $_GET['id'];

    // JOIN residents (r) with users (u) using the user_id link
    $sql = "SELECT 
                r.resident_id, 
                r.first_name, 
                r.last_name, 
                r.full_address, 
                r.birthdate,
                r.contact_no,
                u.username,
                u.email, 
                u.created_at,
                u.status
            FROM residents r
            INNER JOIN users u ON r.user_id = u.user_id 
            WHERE r.resident_id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        echo json_encode($data);
    } else {
        echo json_encode(['error' => 'Resident account link not found.']);
    }

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
exit();