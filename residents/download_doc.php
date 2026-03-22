<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db_config.php';

if (!isset($_GET['ref'])) {
    die("Error: Reference number is required.");
}

$ref = $_GET['ref'];
$user_id = $_SESSION['user_id'];

// 1. Fetch Request and Resident Data
$stmt = $pdo->prepare("
    SELECT 
        r.*, 
        res.first_name, 
        res.last_name, 
        res.civil_status, 
        res.purok, 
        res.full_address
    FROM document_requests r 
    JOIN users u ON r.user_id = u.user_id 
    JOIN residents res ON u.user_id = res.user_id
    WHERE r.reference_number = ? AND r.status = 'approved'
");
$stmt->execute([$ref]);
$data = $stmt->fetch();

if (!$data) {
    die("Document not found, not approved, or unauthorized access.");
}

// 2. DYNAMICALLY FETCH OFFICIAL NAMES
// Fetch Super Admin (Barangay Captain)
$stmtCaptain = $pdo->prepare("
    SELECT res.first_name, res.last_name 
    FROM residents res 
    JOIN users u ON res.user_id = u.user_id 
    WHERE u.role = 'super_admin' AND u.status = 'active' 
    LIMIT 1
");
$stmtCaptain->execute();
$capRow = $stmtCaptain->fetch();
$barangay_captain = $capRow ? "HON. " . strtoupper($capRow['first_name'] . " " . $capRow['last_name']) : "HON. BARANGAY CAPTAIN";

// Fetch Secretary
$stmtSec = $pdo->prepare("
    SELECT res.first_name, res.last_name 
    FROM residents res 
    JOIN users u ON res.user_id = u.user_id 
    WHERE u.role = 'secretary' AND u.status = 'active' 
    LIMIT 1
");
$stmtSec->execute();
$secRow = $stmtSec->fetch();
$secretary_name = $secRow ? strtoupper($secRow['first_name'] . " " . $secRow['last_name']) : "BARANGAY SECRETARY";


// 3. DOCUMENT CONTENT LOGIC
$full_name = htmlspecialchars($data['first_name'] . " " . $data['last_name']);
$doc_type = $data['document_type'];
$doc_title = strtoupper($doc_type);

$is_clearance = (stripos($doc_type, 'Clearance') !== false);
$is_indigency = (stripos($doc_type, 'Indigency') !== false);
$is_residency = (stripos($doc_type, 'Residency') !== false);
$is_business  = (stripos($doc_type, 'Business') !== false);

if ($is_clearance) {
    $body_text = "is known to be a person of good moral character and a law-abiding citizen in this community. <br><br> Based on the records available in this office, he/she has no pending case and no derogatory record filed in this barangay as of this date.";
} elseif ($is_residency) {
    $body_text = "is a bona fide resident of this barangay and is known to be of good moral character. This certification further confirms that he/she is currently residing at <strong>" . htmlspecialchars($data['purok']) . ", Barangay Diagyan</strong>.";
} elseif ($is_business) {
    $body_text = "is hereby authorized to operate his/her business establishment within the jurisdiction of this Barangay. This permit is issued after a review of the records and compliance with the existing barangay ordinances.";
} else {
    $body_text = "is a bona fide resident of this barangay and is known to be of good moral character.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $doc_title; ?> - <?php echo $full_name; ?></title>
    <style>
        @page { size: portrait; margin: 0; }
        body { font-family: 'Times New Roman', serif; line-height: 1.6; color: #000; margin: 0; padding: 0; background-color: #f0f2f5; }
        
        .no-print-nav { width: 8.5in; margin: 20px auto; display: flex; justify-content: space-between; padding: 0 20px; box-sizing: border-box; }
        .btn-print { background: #1877f2; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; cursor: pointer; border: none; }
        
        .page-container {
            width: 8.5in; height: 11in; padding: 1in; margin: auto;
            background: white; border: 1px solid #ccc; box-sizing: border-box;
            position: relative; overflow: hidden;
        }

        .watermark {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg);
            opacity: 0.05; width: 500px; z-index: 0; pointer-events: none;
        }

        .header { text-align: center; margin-bottom: 30px; text-transform: uppercase; position: relative; z-index: 1; }
        .header p { margin: 0; font-size: 14px; }
        .header h2 { margin: 2px 0; font-size: 20px; }

        .title-box { text-align: center; margin: 40px 0; position: relative; z-index: 1; }
        .title-box h1 { font-size: 28px; text-decoration: underline; font-weight: bold; }

        .content { font-size: 18px; text-align: justify; position: relative; z-index: 1; }
        .content p { margin-bottom: 20px; text-indent: 50px; }
        .salutation { font-weight: bold; text-indent: 0 !important; }

        .footer-section {
            position: absolute;
            bottom: 0.8in;
            left: 1in;
            right: 1in;
            z-index: 1;
        }

        .signatures-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            width: 100%;
            margin-bottom: 20px;
        }

        .sig-box {
            width: 45%;
            text-align: center;
        }

        .name-line { 
            font-weight: bold; 
            text-decoration: underline; 
            text-transform: uppercase; 
            font-size: 18px; 
            display: block; 
            margin-top: 40px;
        }

        .label-text { display: block; font-size: 15px; margin-top: 5px; }
        
        .ref-no { 
            margin-top: 30px; 
            font-size: 12px; 
            border-top: 1px solid #000; 
            padding-top: 5px;
            width: fit-content;
        }

        @media print {
            .no-print-nav { display: none; }
            body { background: white; padding: 0; }
            .page-container { border: none; margin: 0; width: 100%; height: 11in; }
        }
    </style>
</head>
<body>

    <div class="no-print-nav">
        <a href="javascript:history.back()" style="text-decoration: none; color: #666; font-weight: bold;">← BACK</a>
        <button class="btn-print" onclick="window.print()">PRINT / SAVE AS PDF</button>
    </div>

    <div class="page-container">
        <img src="../assets/img/barangay_logo.png" class="watermark">

        <div class="header">
            <p>REPUBLIC OF THE PHILIPPINES</p>
            <p>PROVINCE OF AURORA</p>
            <p>MUNICIPALITY OF DILASAG</p>
            <h2>BARANGAY DIAGYAN</h2>
        </div>

        <div class="title-box">
            <h1><?php echo $doc_title; ?></h1>
        </div>

        <div class="content">
            <p class="salutation">TO WHOM IT MAY CONCERN:</p>
            
            <p>
                This is to certify that <strong><?php echo strtoupper($full_name); ?></strong>, 
                <?php if(!$is_business): ?>
                    of legal age, Filipino, <?php echo htmlspecialchars($data['civil_status'] ?? 'Single'); ?>, 
                    and a resident of <?php echo htmlspecialchars($data['purok'] ?? 'Purok 3'); ?>, 
                    Barangay Diagyan, Municipality of Dilasag, Province of Aurora, 
                <?php endif; ?>
                <?php echo $body_text; ?>
            </p>

            <p>
                This <?php echo $is_business ? 'permit' : 'certification'; ?> is being issued upon the request of the above-named person for 
                <strong><?php echo strtoupper($data['purpose']); ?></strong>.
            </p>

            <?php if ($is_indigency): ?>
            <p>
                Based on the records of this office, he/she belongs to a low-income family and has insufficient financial resources to support his/her basic needs.
            </p>
            <?php endif; ?>

            <p>
                Issued this <strong><?php echo date('jS \d\a\y \o\f F, Y'); ?></strong> 
                at Barangay Diagyan, Municipality of Dilasag, Province of Aurora.
            </p>
        </div>

        <div class="footer-section">
            <div class="signatures-row">
                <div class="sig-row-item sig-box">
                    <p style="text-align: left; margin: 0;">Barangay Secretary:</p>
                    <span class="name-line"><?php echo $secretary_name; ?></span>
                    <span class="label-text">Barangay Secretary</span>
                </div>

                <div class="sig-row-item sig-box">
                    <p style="text-align: left; margin: 0;">Certified by:</p>
                    <span class="name-line"><?php echo $barangay_captain; ?></span>
                    <span class="label-text">Barangay Captain</span>
                </div>
            </div>

            <div class="ref-no">
                Reference No: <strong><?php echo $data['reference_number']; ?></strong>
            </div>
        </div>
    </div>

</body>
</html>