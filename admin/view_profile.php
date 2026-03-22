<?php
session_start();
include '../includes/db_config.php'; 

// Security check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    die("Error: No resident ID provided.");
}

try {
    // Fetching all resident details + user account status (excluding password)
    $stmt = $pdo->prepare("SELECT r.*, u.username, u.email, u.status as account_status, u.created_at as date_joined 
                            FROM residents r 
                            JOIN users u ON r.user_id = u.user_id 
                            WHERE u.user_id = ?");
    $stmt->execute([$user_id]);
    $res = $stmt->fetch();

    if (!$res) {
        die("Resident not found.");
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// Image Paths
$profile_img = (empty($res['profile_image']) || $res['profile_image'] == 'profile_default.png') 
                ? "../assets/uploads/residents/profile_default.png" 
                : "../assets/uploads/residents/" . $res['profile_image'];

$user_id = $_GET['id']; // Make sure this is sanitized!

// Fetch Request History
$stmtHistory = $pdo->prepare("SELECT * FROM document_requests WHERE user_id = ? ORDER BY created_at DESC");
$stmtHistory->execute([$user_id]);
$requestHistory = $stmtHistory->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Verification - <?php echo htmlspecialchars($res['last_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #1877f2; --bg: #f0f2f5; --text: #1c1e21; --secondary: #65676b; --success: #28a745; --danger: #dc3545; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 30px; }
        
        /* Widescreen Container */
        .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); overflow: hidden; }
        
        /* Professional Header */
        .profile-header { background: linear-gradient(135deg, #1877f2, #0056b3); padding: 40px; text-align: left; color: white; display: flex; align-items: center; gap: 30px; }
        .profile-header img { width: 120px; height: 120px; border-radius: 50%; border: 4px solid rgba(255,255,255,0.3); object-fit: cover; background: white; }
        .header-text h1 { margin: 0; font-size: 2.2rem; letter-spacing: -0.5px; }
        .status-badge { display: inline-block; margin-top: 10px; padding: 5px 15px; border-radius: 20px; font-size: 0.85rem; font-weight: bold; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); }

        /* Dashboard Grid Layout */
        .profile-grid { display: grid; grid-template-columns: 1fr 380px; min-height: 500px; }
        .main-content { padding: 40px; border-right: 1px solid #eee; }
        .side-content { background: #fafbfc; padding: 40px; }

        .section-title { font-size: 0.9rem; font-weight: 800; color: var(--primary); margin-bottom: 25px; display: flex; align-items: center; gap: 10px; text-transform: uppercase; letter-spacing: 1.2px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        
        /* 2-Column Info Grid */
        .info-columns { display: grid; grid-template-columns: 1fr 1fr; gap: 0 40px; margin-bottom: 40px; }
        .info-box { margin-bottom: 20px; }
        .info-box label { display: block; font-size: 0.75rem; color: var(--secondary); text-transform: uppercase; font-weight: bold; margin-bottom: 5px; }
        .info-box span { font-size: 1.1rem; font-weight: 500; color: #333; }

        /* History Table */
        .history-card { background: white; border: 1px solid #eef0f2; border-radius: 8px; overflow: hidden; }
        .history-table { width: 100%; border-collapse: collapse; }
        .history-table th { background: #f8f9fa; text-align: left; font-size: 0.75rem; color: var(--secondary); padding: 15px; text-transform: uppercase; }
        .history-table td { padding: 15px; border-top: 1px solid #eee; font-size: 0.95rem; }
        .history-table tr:hover { background-color: #fcfcfc; }

        /* ID Styles */
        .id-image { width: 100%; border-radius: 8px; border: 1px solid #ddd; box-shadow: 0 4px 12px rgba(0,0,0,0.05); transition: 0.3s; }
        .id-image:hover { transform: scale(1.02); cursor: pointer; }

        /* Footer Actions */
        .actions { background: white; padding: 25px 40px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #eee; }
        .btn { padding: 12px 25px; border-radius: 8px; font-weight: 600; text-decoration: none; cursor: pointer; transition: 0.2s; border: none; font-size: 1rem; display: inline-flex; align-items: center; gap: 8px; }
        .btn-back { background: #f0f2f5; color: #050505; }
        .btn-approve { background: var(--success); color: white; }
        .btn-reject { background: var(--danger); color: white; }
        .btn:hover { filter: brightness(0.9); transform: translateY(-1px); }
    </style>
</head>
<body>

<div class="container">
    <div class="profile-header">
        <img src="<?php echo $profile_img; ?>" alt="Profile">
        <div class="header-text">
            <h1><?php echo htmlspecialchars($res['first_name'] . ' ' . $res['last_name']); ?></h1>
            <div class="status-badge">
                <i class="fa-solid fa-id-card-clip"></i> <?php echo strtoupper($res['account_status']); ?>
            </div>
        </div>
    </div>

    <div class="profile-grid">
        <div class="main-content">
            <div class="section-title"><i class="fa-solid fa-user"></i> Resident Profile Information</div>
            <div class="info-columns">
                <div class="col">
                    <div class="info-box"><label>Full Name</label><span><?php echo htmlspecialchars($res['first_name'] . " " . $res['last_name']); ?></span></div>
                    <div class="info-box"><label>Birthdate</label><span><?php echo date('F d, Y', strtotime($res['birthdate'])); ?></span></div>
                    <div class="info-box"><label>Gender</label><span><?php echo htmlspecialchars($res['gender'] ?? 'Not Specified'); ?></span></div>
                </div>
                <div class="col">
                    <div class="info-box"><label>Address / Purok</label><span><?php echo htmlspecialchars($res['purok'] ?? 'N/A'); ?></span></div>
                    <div class="info-box"><label>Contact No.</label><span><?php echo htmlspecialchars($res['contact_no']); ?></span></div>
                    <div class="info-box"><label>Email Address</label><span><?php echo htmlspecialchars($res['email']); ?></span></div>
                </div>
            </div>

            <div class="section-title"><i class="fa-solid fa-clock-rotate-left"></i> Document Request History</div>
            <div class="history-card">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Document Type</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($requestHistory) > 0): ?>
                            <?php foreach ($requestHistory as $history): 
                                $hStatusColor = ($history['status'] == 'approved') ? '#28a745' : (($history['status'] == 'rejected') ? '#e41e3f' : '#f1c40f');
                            ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($history['created_at'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($history['document_type']); ?></strong></td>
                                <td><span style="color: <?php echo $hStatusColor; ?>; font-weight: bold;"><?php echo strtoupper($history['status']); ?></span></td>
                                <td style="color: var(--secondary); font-style: italic; font-size: 0.85rem;"><?php echo htmlspecialchars($history['admin_remarks'] ?? 'No remarks'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align: center; padding: 30px; color: #999;">No previous requests found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="side-content">
            <div class="section-title"><i class="fa-solid fa-shield-halved"></i> Proof of Identity</div>
            <div class="id-container">
                <?php if(!empty($res['id_image'])): ?>
                    <img src="../assets/uploads/requests/<?php echo $res['id_image']; ?>" class="id-image" alt="Resident ID" onclick="window.open(this.src)">
                    <p style="text-align: center; color: var(--secondary); font-size: 0.8rem; margin-top: 15px;">
                        <i class="fa-solid fa-magnifying-glass"></i> Click image to view full size
                    </p>
                <?php else: ?>
                    <div style="padding: 60px 20px; text-align: center; color: #ccc; background: white; border-radius: 8px; border: 2px dashed #ddd;">
                        <i class="fa-solid fa-image-slash fa-3x"></i><br><br>
                        No ID Document Uploaded
                    </div>
                <?php endif; ?>
            </div>
            
            <div style="margin-top: 40px; padding: 20px; background: #eef4ff; border-radius: 8px; color: #385898; font-size: 0.85rem; line-height: 1.4;">
                <strong>Admin Tip:</strong><br>
                Cross-reference the birthdate on the ID with the profile data provided by the resident.
            </div>
        </div>
    </div>

    <div class="actions">
        <a href="dashboard.php?view=residents" class="btn btn-back"><i class="fa-solid fa-arrow-left"></i> Back to List</a>
        
        <?php if($res['account_status'] === 'pending'): ?>
            <form action="process_resident_action.php" method="POST" style="display: flex; gap: 15px;">
                <input type="hidden" name="user_id" value="<?php echo $res['user_id']; ?>">
                <button type="submit" name="action" value="reject" class="btn btn-reject" onclick="return confirm('Reject this resident?')">
                    <i class="fa-solid fa-user-xmark"></i> Reject
                </button>
                <button type="submit" name="action" value="activate" class="btn btn-approve">
                    <i class="fa-solid fa-user-check"></i> Approve Resident
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>