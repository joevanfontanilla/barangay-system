<?php
session_start();
require_once '../includes/db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $document_type = $_POST['document_type'];
    $purpose = trim($_POST['purpose']);

    // --- FEATURE #1: GENERATE UNIQUE REF NUMBER ---
    $ref_no = "DIAG-" . date("Y") . "-" . strtoupper(bin2hex(random_bytes(3)));

    // --- FEATURE #2: SECURITY & VALIDITY CHECK (6 MONTHS) ---
    $stmtCheck = $pdo->prepare("
        SELECT approved_at, status 
        FROM document_requests 
        WHERE user_id = ? 
        AND document_type = ? 
        AND (
            status = 'pending' 
            OR (status = 'approved' AND approved_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH))
        )
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmtCheck->execute([$user_id, $document_type]);
    $existingRequest = $stmtCheck->fetch();

    if ($existingRequest) {
        if ($existingRequest['status'] == 'pending') {
            header("Location: dashboard.php?msg=pending_exists#requests");
            exit();
        } else {
            $expiryDate = date('M d, Y', strtotime($existingRequest['approved_at'] . ' + 6 months'));
            header("Location: dashboard.php?msg=already_approved&expiry=" . urlencode($expiryDate) . "#requests");
            exit();
        }
    }

    // --- FILE UPLOAD LOGIC ---
    $uploadDir = '../assets/uploads/requests/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    /**
     * Helper Function to process individual file uploads
     */
    function processUpload($fileArray, $prefix, $user_id, $uploadDir) {
        if (isset($fileArray) && $fileArray['error'] === 0) {
            $fileExt = pathinfo($fileArray['name'], PATHINFO_EXTENSION);
            $newFileName = $prefix . "_" . $user_id . "_" . time() . "_" . rand(100, 999) . "." . $fileExt;
            if (move_uploaded_file($fileArray['tmp_name'], $uploadDir . $newFileName)) {
                return $newFileName;
            }
        }
        return null;
    }

    // Attempt to upload all three
    $frontID = processUpload($_FILES['id_image'], "FRONT", $user_id, $uploadDir);
    $backID  = processUpload($_FILES['id_image_back'], "BACK", $user_id, $uploadDir);
    $addDoc  = processUpload($_FILES['additional_doc'], "ADD", $user_id, $uploadDir);

    // Validate that the REQUIRED files (Front and Back) are present
    if ($frontID && $backID) {
        
        // --- INSERT NEW REQUEST (Including the 2 new columns) ---
        $sql = "INSERT INTO document_requests 
                (user_id, reference_number, document_type, purpose, id_image, id_image_back, additional_doc, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
        
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$user_id, $ref_no, $document_type, $purpose, $frontID, $backID, $addDoc])) {
            header("Location: dashboard.php?request=sent#requests");
            exit();
        } else {
            header("Location: dashboard.php?msg=Database error#requests");
            exit();
        }
    } else {
        header("Location: dashboard.php?msg=Please upload both Front and Back ID images#requests");
        exit();
    }
}