<?php 
require_once '../includes/auth_check.php'; 
require_once '../includes/db_config.php';

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin') {
    header("Location: ../residents/dashboard.php");
    exit();
}

// Simple logic to decide which section to show
$view = isset($_GET['view']) ? $_GET['view'] : 'feed';

$pendingCount = 0;
try {
    $stmtCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND status = 'pending'");
    $pendingCount = $stmtCount->fetchColumn();
} catch (PDOException $e) {
    // Silent fail or log error
    $pendingCount = 0;
}
$stmtResCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND status = 'pending'");
$pendingCount = $stmtResCount->fetchColumn();

// 2. Fetch pending requests count (UPDATED to match your table name)
try {
    // We use 'document_requests' because that is what your <tbody> query uses
    $stmtReqCount = $pdo->query("SELECT COUNT(*) FROM document_requests WHERE status = 'pending'");
    $requestCount = $stmtReqCount->fetchColumn();
} catch (PDOException $e) {
    $requestCount = 0; 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Barangay Connect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Your original styling kept exactly as is */
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Helvetica, Arial, sans-serif; margin: 0; }
        .admin-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: #ffffff; border-right: 1px solid #e4e6eb; padding: 20px; box-shadow: 2px 0 5px rgba(0,0,0,0.02); }
        .sidebar h2 { color: #1877f2; font-size: 1.3rem; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }
        .sidebar-nav { list-style: none; padding: 0; }
        .sidebar-nav li a { display: flex; align-items: center; gap: 10px; padding: 12px 15px; color: #050505; text-decoration: none; border-radius: 8px; margin-bottom: 5px; font-weight: 500; transition: 0.2s; }
        .sidebar-nav li a:hover { background: #f2f2f2; }
        .sidebar-nav li a i { color: #1877f2; font-size: 1.1rem; }
        .admin-main { flex: 1; padding: 30px; max-width: 1100px; margin: 0 auto; }
        .admin-main h1 { font-size: 1.7rem; color: #1c1e21; margin-bottom: 20px; }
        .card-section { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); margin-bottom: 25px; }
        .section-title { font-size: 1.1rem; font-weight: 700; color: #65676b; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
        .resident-table { width: 100%; border-collapse: collapse; }
        .resident-table th { text-align: left; padding: 12px; border-bottom: 1px solid #ebedf0; color: #65676b; font-size: 0.85rem; text-transform: uppercase; }
        .resident-table td { padding: 14px 12px; border-bottom: 1px solid #f0f2f5; font-size: 0.95rem; }
        .post-card { background: #fff; padding: 20px; border-radius: 12px; margin-bottom: 15px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); border-left: 5px solid #1877f2; }
        .official-badge { background: #e7f3ff; color: #1877f2; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: bold; }
        
   </style>
</head>
<body>
    <div class="admin-layout">
       <aside class="sidebar">
    <h2><i class="fa-solid fa-shield-halved"></i> Admin Portal</h2>
    <hr style="border: 0; border-top: 1px solid #eee; margin-bottom: 20px;">
    <ul class="sidebar-nav">
        <li><a href="dashboard.php?view=feed"><i class="fa-solid fa-stream"></i> Community Feed</a></li>
        
        <li>
            <a href="dashboard.php?view=requests" style="display: flex; justify-content: space-between; align-items: center;">
                <span><i class="fa-solid fa-file-invoice"></i> Manage Requests</span>
                <?php if ($requestCount > 0): ?>
                    <span style="background: #1877f2; color: white; font-size: 0.7rem; padding: 2px 7px; border-radius: 4px; font-weight: bold; min-width: 18px; text-align: center;">
                        <?php echo $requestCount; ?>
                    </span>
                <?php endif; ?>
            </a>
        </li>

        <li>
            <a href="dashboard.php?view=residents" style="display: flex; justify-content: space-between; align-items: center;">
                <span><i class="fa-solid fa-users-gear"></i> Manage Residents</span>
                <?php if ($pendingCount > 0): ?>
                    <span style="background: #e41e3f; color: white; font-size: 0.7rem; padding: 2px 7px; border-radius: 4px; font-weight: bold; min-width: 18px; text-align: center;">
                        <?php echo $pendingCount; ?>
                    </span>
                <?php endif; ?>
            </a>
        </li>

        <li><a href="dashboard.php?view=rejection_history"><i class="fa-solid fa-user-slash"></i> Rejection History</a></li>

        <li>
            <a href="analytics.php">
                <i class="fa-solid fa-chart-pie"></i> Community Analytics
            </a>
        </li>

        <li><a href="../auth/logout.php" style="color: #e41e3f;"><i class="fa-solid fa-power-off"></i> Logout</a></li>
    </ul>
</aside>

        <main class="admin-main">
            <h1>Welcome, Captain <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
            
            <?php if ($view === 'feed'): ?>
            <section id="community-feed-section" class="card-section" style="width: 100%; box-sizing: border-box;">
                <div class="section-title" style="color: #1877f2;">
                    <i class="fa-solid fa-bullhorn"></i> Create Official Announcement
                </div>
                <form id="officialPostForm" style="margin-bottom: 30px;">
                    <textarea id="announcementContent" name="content" placeholder="Write an official update for the community..." required 
                        style="width: 100%; height: 100px; padding: 15px; border-radius: 8px; border: 1px solid #dddfe2; resize: none; font-family: inherit; font-size: 1rem; background: #f0f2f5; box-sizing: border-box;"></textarea>
                    <div style="text-align: right; margin-top: 10px;">
                        <button type="button" onclick="publishAnnouncementAjax()" 
                            style="background: #1877f2; color: white; border: none; padding: 10px 25px; border-radius: 6px; cursor: pointer; font-weight: 600;">
                            Post to Feed
                        </button>
                    </div>
                </form>

                <hr style="border: 0; border-top: 1px solid #eee; margin-bottom: 25px;">

                <div class="section-title">
                    <i class="fa-solid fa-stream"></i> Community Activity
                </div>

                <div id="feedContainer">
                    <?php
                    try {
                        $stmt = $pdo->prepare("SELECT posts.*, residents.first_name, residents.last_name, residents.profile_image 
                                                FROM posts LEFT JOIN residents ON posts.user_id = residents.user_id 
                                                ORDER BY posts.created_at DESC");
                        $stmt->execute();
                        $posts = $stmt->fetchAll();

                        if ($posts):
                            foreach ($posts as $post): 
                                $post_id = $post['post_id'];
                                $isOfficial = ($post['type'] === 'official');
                                $displayName = !empty($post['first_name']) ? htmlspecialchars($post['first_name'] . " " . $post['last_name']) : "Barangay Official";
                                $dbImage = $post['profile_image'];
                                $userImage = (empty($dbImage) || $dbImage == 'default_avatar.png') ? "../assets/uploads/residents/profile_default.png" : "../assets/uploads/residents/" . $dbImage;

                                $likeCountStmt = $pdo->prepare("SELECT COUNT(*) FROM post_likes WHERE post_id = ?");
                                $likeCountStmt->execute([$post_id]);
                                $likeCount = $likeCountStmt->fetchColumn();

                                $hasLikedStmt = $pdo->prepare("SELECT 1 FROM post_likes WHERE post_id = ? AND user_id = ?");
                                $hasLikedStmt->execute([$post_id, $_SESSION['user_id']]);
                                $hasLiked = $hasLikedStmt->fetch();

                                $commentStmt = $pdo->prepare("SELECT post_comments.*, residents.first_name, residents.last_name, residents.profile_image 
                                                            FROM post_comments LEFT JOIN residents ON post_comments.user_id = residents.user_id 
                                                            WHERE post_id = ? ORDER BY created_at ASC");
                                $commentStmt->execute([$post_id]);
                                $comments = $commentStmt->fetchAll();
                                ?>
                                <div id="post-card-<?php echo $post_id; ?>" class="post-card" style="width: 100%; background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e4e6eb; box-sizing: border-box;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <img src="<?php echo $userImage; ?>" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 1px solid #ddd;">
                                            <div>
                                                <strong style="display: block; color: #1c1e21; font-size: 1rem;"><?php echo $displayName; ?></strong>
                                                <?php if ($isOfficial): ?>
                                                    <span style="background: #e7f3ff; color: #1877f2; font-size: 0.7rem; padding: 2px 8px; border-radius: 4px; font-weight: bold; text-transform: uppercase;">Official</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <small style="color: #65676b;"><i class="fa-regular fa-clock"></i> <?php echo date('M d, Y', strtotime($post['created_at'])); ?></small>
                                    </div>

                                    <p style="margin: 15px 0 15px 57px; color: #050505; line-height: 1.5; font-size: 0.95rem;">
                                        <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                                    </p>

                                    <hr style="border: 0; border-top: 1px solid #eee; margin: 10px 0;">

                                    <div style="display: flex; align-items: center; gap: 20px; margin-left: 57px; margin-bottom: 10px;">
                                        <a href="../residents/process_like.php?post_id=<?php echo $post_id; ?>&from=admin" style="text-decoration: none; color: <?php echo $hasLiked ? '#1877f2' : '#65676b'; ?>; font-weight: 600; font-size: 0.9rem;">
                                            <i class="<?php echo $hasLiked ? 'fa-solid' : 'fa-regular'; ?> fa-thumbs-up"></i> Like (<?php echo $likeCount; ?>)
                                        </a>
                                        <span style="color: #65676b; font-size: 0.9rem; font-weight: 600; cursor: pointer;" onclick="toggleComments(<?php echo $post_id; ?>)">
                                            <i class="fa-regular fa-comment"></i> Comments (<?php echo count($comments); ?>)
                                        </span>
                                        <button type="button" onclick="deletePostAjax(<?php echo $post_id; ?>)" style="background: none; border: none; color: #e41e3f; cursor: pointer; font-size: 0.9rem; font-weight: 600; padding: 0;">
                                            <i class="fa-solid fa-trash-can"></i> Remove
                                        </button>
                                    </div>

                                    <div id="comment-section-<?php echo $post_id; ?>" style="margin-left: 57px; background: #f0f2f5; border-radius: 8px; padding: 10px; display: none;">
                                        <?php foreach ($comments as $com): 
                                            $comImage = (empty($com['profile_image']) || $com['profile_image'] == 'default_avatar.png') ? "../assets/uploads/residents/profile_default.png" : "../assets/uploads/residents/" . $com['profile_image'];
                                        ?>
                                            <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                                                <img src="<?php echo $comImage; ?>" style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover;">
                                                <div style="background: white; padding: 8px 12px; border-radius: 15px; font-size: 0.85rem; border: 1px solid #e4e6eb; max-width: 85%;">
                                                    <strong style="font-size: 0.8rem;"><?php echo htmlspecialchars($com['first_name'] . " " . $com['last_name']); ?></strong>
                                                    <p style="margin: 2px 0;"><?php echo htmlspecialchars($com['comment_text']); ?></p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>

                                        <form action="../residents/process_comment.php" method="POST" style="display: flex; gap: 8px; margin-top: 10px;">
                                            <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                                            <input type="hidden" name="from" value="admin"> 
                                            <input type="text" name="comment_text" placeholder="Write a comment..." required style="flex: 1; border-radius: 20px; border: 1px solid #ddd; padding: 8px 15px; font-size: 0.85rem; outline: none;">
                                            <button type="submit" style="background: #1877f2; color: white; border: none; padding: 5px 15px; border-radius: 20px; cursor: pointer; font-weight: 600; font-size: 0.8rem;">Post</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach;
                        else: ?>
                            <div style="text-align: center; color: #888; padding: 50px; background: white; border-radius: 8px; border: 1px dashed #ccc;">No activity in the feed yet.</div>
                        <?php endif; 
                    } catch (PDOException $e) { 
                        echo "<div style='padding:20px; color:red;'>Error loading feed: " . $e->getMessage() . "</div>"; 
                    } ?>
                </div>
            </section>

            <?php elseif ($view === 'requests'): ?>
            <section id="manage-requests-section" class="card-section">
                <div class="section-title" style="color: #1877f2; display: flex; justify-content: space-between; align-items: center;">
                    <span><i class="fa-solid fa-file-invoice"></i> Document Request Management</span>
                    <a href="admin_dashboard.php?view=feed" style="background: #f0f2f5; color: #050505; text-decoration:none; padding: 5px 10px; border-radius: 6px; cursor: pointer; font-size: 0.8rem;">
                        <i class="fa-solid fa-xmark"></i> Close
                    </a>
                </div>

                <div style="overflow-x: auto; margin-top: 15px;">
                    <table class="resident-table">
                        <thead>
                            <tr>
                                <th>Ref Number</th>
                                <th>Resident</th>
                                <th>Document</th>
                                <th>Proof</th>
                                <th>Status</th>
                                <th style="text-align: center;">Process</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmtReq = $pdo->query("SELECT r.*, u.username FROM document_requests r JOIN users u ON r.user_id = u.user_id ORDER BY r.created_at DESC");
                            while($row = $stmtReq->fetch()):
                                $statusColor = ($row['status'] == 'approved') ? '#1877f2' : (($row['status'] == 'rejected') ? '#e41e3f' : '#f1c40f');
                            ?>
                            <tr>
                                <td><strong style="color: #1877f2;"><?php echo $row['reference_number']; ?></strong></td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['document_type']); ?></td>
                                
                                <td>
                                    <div style="display: flex; gap: 12px; align-items: center;">
                                        <button type="button" title="Front ID" onclick="openImageModal('../assets/uploads/requests/<?php echo $row['id_image']; ?>')" 
                                                style="background:none; border:none; color:#28a745; cursor:pointer; font-size:1.1rem; padding:0;">
                                            <i class="fa-solid fa-id-card"></i>
                                        </button>

                                        <?php if(!empty($row['id_image_back'])): ?>
                                        <button type="button" title="Back ID" onclick="openImageModal('../assets/uploads/requests/<?php echo $row['id_image_back']; ?>')" 
                                                style="background:none; border:none; color:#1877f2; cursor:pointer; font-size:1.1rem; padding:0;">
                                            <i class="fa-solid fa-id-card-clip"></i>
                                        </button>
                                        <?php endif; ?>

                                        <?php if(!empty($row['additional_doc'])): ?>
                                        <button type="button" title="Additional Document" onclick="openImageModal('../assets/uploads/requests/<?php echo $row['additional_doc']; ?>')" 
                                                style="background:none; border:none; color:#6c757d; cursor:pointer; font-size:1.1rem; padding:0;">
                                            <i class="fa-solid fa-file-circle-plus"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td><span style="color: <?php echo $statusColor; ?>; font-weight: bold;"><?php echo strtoupper($row['status']); ?></span></td>
                                
                                <td>
                                    <form action="update_request_status.php" method="POST" style="display: flex; flex-direction: column; gap: 5px;">
                                        <input type="hidden" name="request_id" value="<?php echo $row['request_id']; ?>">
                                        
                                        <div style="display: flex; gap: 5px;">
                                            <select name="new_status" style="padding: 4px; border-radius: 4px; border: 1px solid #ddd; font-size: 0.8rem;">
                                                <option value="pending" <?php if($row['status'] == 'pending') echo 'selected'; ?>>Pending</option>
                                                <option value="approved" <?php if($row['status'] == 'approved') echo 'selected'; ?>>Approve</option>
                                                <option value="rejected" <?php if($row['status'] == 'rejected') echo 'selected'; ?>>Reject</option>
                                            </select>
                                            <button type="submit" style="background: #1877f2; color: white; border: none; padding: 4px 10px; border-radius: 4px; cursor: pointer; font-weight: 600;">Save</button>
                                        </div>

                                        <input type="text" name="admin_remarks" 
                                            placeholder="Add a note (optional)..." 
                                            value="<?php echo htmlspecialchars($row['admin_remarks'] ?? ''); ?>"
                                            style="width: 100%; padding: 4px; font-size: 0.75rem; border: 1px solid #eee; border-radius: 4px;">
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>
           </section>

<?php elseif ($view === 'residents'): ?>
    <section id="manage-residents-section" class="card-section">
        <div class="section-title" style="color: #1877f2; display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fa-solid fa-users-gear"></i> Resident Account Management</span>
            <a href="dashboard.php?view=feed" style="background: #f0f2f5; color: #050505; text-decoration:none; padding: 5px 10px; border-radius: 6px; cursor: pointer; font-size: 0.8rem;">
                <i class="fa-solid fa-xmark"></i> Close
            </a>
        </div>

        <div style="overflow-x: auto; margin-top: 15px;">
            <table class="resident-table">
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Purok</th>
                    <th>Status</th>
                    <th>Voter</th> <th style="text-align: center;">Action</th>
                </tr>
            </thead>
<tbody>
    <?php
    try {
        // Updated query to include voter_status
        $stmtRes = $pdo->query("SELECT r.*, u.username, u.status, u.user_id, u.email 
                                FROM residents r 
                                JOIN users u ON r.user_id = u.user_id 
                                WHERE u.role = 'user'
                                ORDER BY CASE WHEN u.status = 'pending' THEN 1 ELSE 2 END, r.last_name ASC");
        
        while($res = $stmtRes->fetch()):
            $resStatusColor = ($res['status'] == 'active') ? '#1877f2' : (($res['status'] == 'inactive') ? '#e41e3f' : '#f1c40f');
            $fullName = htmlspecialchars($res['first_name'] . " " . $res['last_name']);
            $voterStatus = $res['voter_status'] ?? 'Non-Registered';
    ?>
    <tr style="<?php echo ($res['status'] == 'pending') ? 'background-color: #fff9e6;' : ''; ?>">
        <td><strong><?php echo $fullName; ?></strong></td>
        <td><?php echo htmlspecialchars($res['username']); ?></td>
        <td><?php echo htmlspecialchars($res['purok'] ?? 'N/A'); ?></td>
        <td>
            <span style="color: <?php echo $resStatusColor; ?>; font-weight: bold; font-size: 0.85rem;">
                <?php echo strtoupper($res['status']); ?>
            </span>
        </td>
        
        <td>
            <span style="padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; 
                         background: <?php echo ($voterStatus == 'Registered') ? '#e7f3ff' : '#f2f2f2'; ?>; 
                         color: <?php echo ($voterStatus == 'Registered') ? '#1877f2' : '#65676b'; ?>; font-weight: 600;">
                <?php echo $voterStatus; ?>
            </span>
        </td>

        <td>
            <div style="display: flex; gap: 8px; justify-content: center; align-items: center;">
                <?php if($res['status'] === 'pending'): ?>
                    <form action="process_resident_action.php" method="POST" style="margin:0;">
                        <input type="hidden" name="user_id" value="<?php echo $res['user_id']; ?>">
                        <button type="submit" name="action" value="activate" 
                            style="background: #28a745; color: white; border: none; padding: 5px 12px; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 0.75rem;">
                            Approve
                        </button>
                    </form>
                    <button type="button" 
                        onclick="confirmRejection('<?php echo $res['user_id']; ?>', '<?php echo $fullName; ?>', '<?php echo htmlspecialchars($res['username']); ?>', '<?php echo htmlspecialchars($res['email']); ?>')"
                        style="background-color: #ff4d4d; color: white; border: none; padding: 5px 12px; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 0.75rem;">
                        Reject
                    </button>
                <?php else: ?>
<button type="button" 
        onclick="openVoterModal('<?php echo $res['user_id']; ?>', '<?php echo addslashes($res['first_name'] . ' ' . $res['last_name']); ?>', '<?php echo $voterStatus; ?>')"
        title="Change Voter Status"
        style="background: #ffffff; color: #1877f2; border: 1px solid #1877f2; padding: 4px 8px; border-radius: 4px; cursor: pointer;">
    <i class="fa-solid fa-user-check"></i>
</button>
                <?php endif; ?>
                
                <a href="view_profile.php?id=<?php echo $res['user_id']; ?>" 
                   style="background: #f0f2f5; color: #050505; text-decoration: none; padding: 5px 12px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; border: 1px solid #ddd;">
                    Details
                </a>
            </div>
        </td>
    </tr>
    <?php endwhile; } catch (PDOException $e) { echo "Error: " . $e->getMessage(); } ?>
</tbody>
            </table>
        </div>
    </section>

    <!-- Rejection -->
     <?php elseif ($view === 'rejection_history'): ?>
    <section id="rejection-history-section" class="card-section">
        <div class="section-title" style="color: #e41e3f; display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fa-solid fa-clock-rotate-left"></i> Rejection History Log</span>
            <a href="dashboard.php?view=residents" style="background: #f0f2f5; color: #050505; text-decoration:none; padding: 5px 10px; border-radius: 6px; cursor: pointer; font-size: 0.8rem;">
                <i class="fa-solid fa-arrow-left"></i> Back to Residents
            </a>
        </div>

        <p style="font-size: 0.9rem; color: #65676b; margin-bottom: 15px;">
            This log tracks all account applications that were denied by the administration.
        </p>

        <div style="overflow-x: auto;">
            <table class="resident-table">
                <thead>
                    <tr style="background-color: #f8f9fa;">
                        <th>Full Name</th>
                        <th>Username / Email</th>
                        <th>Reason for Rejection</th>
                        <th>Rejected By</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        $stmtLogs = $pdo->query("SELECT * FROM rejection_logs ORDER BY rejected_at DESC");
                        $logs = $stmtLogs->fetchAll();

                        if (count($logs) > 0):
                            foreach ($logs as $log):
                    ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($log['fullname']); ?></strong></td>
                            <td style="font-size: 0.85rem;">
                                <div><?php echo htmlspecialchars($log['username']); ?></div>
                                <div style="color: #65676b;"><?php echo htmlspecialchars($log['email']); ?></div>
                            </td>
                            <td>
                                <div style="max-width: 250px; font-style: italic; color: #444;">
                                    "<?php echo htmlspecialchars($log['reason']); ?>"
                                </div>
                            </td>
                            <td><span class="badge" style="background: #eee; padding: 3px 8px; border-radius: 4px; font-size: 0.8rem;"><?php echo htmlspecialchars($log['rejected_by']); ?></span></td>
                            <td style="font-size: 0.85rem; color: #65676b;">
                                <?php echo date('M d, Y h:i A', strtotime($log['rejected_at'])); ?>
                            </td>
                        </tr>
                    <?php 
                            endforeach;
                        else:
                            echo "<tr><td colspan='5' style='text-align:center; padding: 20px; color: #65676b;'>No rejection records found.</td></tr>";
                        endif;
                    } catch (PDOException $e) {
                        echo "<tr><td colspan='5'>Error loading logs: " . $e->getMessage() . "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
   
        </main>
    </div>

    <div id="infoModal" class="modal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center;">
        <div class="modal-content" style="background: white; padding: 30px; border-radius: 12px; width: 90%; max-width: 500px; position: relative;">
            <span onclick="closeInfoModal()" style="position: absolute; right: 20px; top: 15px; font-size: 24px; cursor: pointer;">&times;</span>
            <h3 style="margin-top: 0; color: #1c1e21; border-bottom: 1px solid #eee; padding-bottom: 10px;">Resident Details</h3>
            <div id="residentDetailsBody" style="margin-top: 15px;"></div>
            <div style="margin-top: 25px; display: flex; justify-content: flex-end; gap: 10px;">
                <button id="editBtn" style="background: #f0f2f5; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer;">Edit</button>
                <button id="deactivateBtn" style="background: #ffebe9; color:#e41e3f; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer;">Deactivate</button>
                <button onclick="closeInfoModal()" style="padding: 10px 15px; border-radius: 6px; border: 1px solid #ddd; cursor: pointer;">Close</button>
            </div>
        </div>

    </div>
        <div id="imageModal" style="display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); align-items: center; justify-content: center;">
        <span onclick="closeImageModal()" style="position: absolute; top: 20px; right: 35px; color: #fff; font-size: 40px; font-weight: bold; cursor: pointer;">&times;</span>
        <img id="modalImg" style="max-width: 90%; max-height: 90%; border-radius: 8px; box-shadow: 0 0 20px rgba(0,0,0,0.5);">
    </div>

    <div id="voterModal" style="display:none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);">
    <div style="background: white; width: 350px; margin: 15% auto; padding: 20px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); text-align: center;">
        <h3 style="margin-top: 0; color: #1877f2;"><i class="fa-solid fa-id-card"></i> Voter Status</h3>
        <p id="voterModalText" style="font-size: 0.9rem; color: #444;"></p>
        
        <form id="voterForm" action="toggle_voter.php" method="GET">
            <input type="hidden" name="id" id="modalUserId">
            <input type="hidden" name="current" id="modalCurrentStatus">
            
            <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
                <button type="button" onclick="closeVoterModal()" style="padding: 8px 15px; border-radius: 5px; border: 1px solid #ddd; cursor: pointer;">Cancel</button>
                <button type="submit" style="padding: 8px 15px; border-radius: 5px; border: none; background: #1877f2; color: white; cursor: pointer; font-weight: bold;">Confirm Change</button>
            </div>
        </form>
    </div>
</div>
    <script>

                function confirmRejection(userId, fullname, username, email) {
            let reason = prompt("Enter reason for rejecting " + fullname + ":");
            
            if (reason !== null && reason.trim() !== "") {
                // Create a hidden form and submit it
                let form = document.createElement('form');
                form.method = 'POST';
                form.action = 'reject_user.php';

                let data = {
                    'user_id': userId,
                    'fullname': fullname,
                    'username': username,
                    'email': email,
                    'reason': reason
                };

                for (let key in data) {
                    let input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = data[key];
                    form.appendChild(input);
                }

                document.body.appendChild(form);
                form.submit();
            } else if (reason !== null) {
                alert("You must provide a reason for rejection.");
            }
        }
        function viewResident(residentId) {
            const modal = document.getElementById('infoModal');
            const body = document.getElementById('residentDetailsBody');
            modal.style.display = 'flex';
            body.innerHTML = '<div style="text-align:center; padding:20px;"><i class="fa-solid fa-spinner fa-spin"></i> Loading details...</div>';

            fetch(`get_resident_details.php?id=${residentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        body.innerHTML = `<p style="color:red; text-align:center;">${data.error}</p>`;
                    } else {
                        body.innerHTML = `
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; background: #f9fafb; padding: 15px; border-radius: 8px;">
                                <div><small style="color:#65676b; font-weight:600;">Full Name</small><br><strong style="color:#1c1e21;">${data.first_name} ${data.last_name}</strong></div>
                                <div><small style="color:#65676b; font-weight:600;">Username</small><br><strong>${data.username}</strong></div>
                                <div><small style="color:#65676b; font-weight:600;">Birthdate</small><br><strong>${data.birthdate}</strong></div>
                                <div><small style="color:#65676b; font-weight:600;">Contact No.</small><br><strong>${data.contact_no || 'Not Provided'}</strong></div>
                                <div style="grid-column: span 2;"><small style="color:#65676b; font-weight:600;">Email Address</small><br><strong>${data.email}</strong></div>
                                <div style="grid-column: span 2;"><small style="color:#65676b; font-weight:600;">Full Address</small><br><strong>${data.full_address}</strong></div>
                                <div><small style="color:#65676b; font-weight:600;">Status</small><br><span style="color:#42b72a; font-weight:bold;">${data.status.toUpperCase()}</span></div>
                                <div><small style="color:#65676b; font-weight:600;">Joined Date</small><br><strong>${new Date(data.created_at).toLocaleDateString()}</strong></div>
                            </div>`;
                        document.getElementById('editBtn').onclick = () => window.location.href = `edit_resident.php?id=${residentId}`;
                    }
                })
                .catch(err => { body.innerHTML = `<p style="color:red;">Error connecting to server.</p>`; });
        }

        function closeInfoModal() { document.getElementById('infoModal').style.display = 'none'; }

        function deletePostAjax(postId) {
            if (confirm('Are you sure you want to remove this post?')) {
                const xhr = new XMLHttpRequest();
                xhr.open("GET", "delete_post.php?id=" + postId, true);
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        const card = document.getElementById('post-card-' + postId);
                        card.style.opacity = "0";
                        setTimeout(() => card.remove(), 300);
                    }
                };
                xhr.send();
            }
        }

        function publishAnnouncementAjax() {
            const textarea = document.getElementById('announcementContent');
            const content = textarea.value.trim();
            if (content === "") return alert("Please enter content.");
            if (confirm("Publish this as an OFFICIAL ANNOUNCEMENT?")) {
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "process_official_post.php", true);
                xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        textarea.value = "";
                        location.reload(); 
                    }
                };
                xhr.send("content=" + encodeURIComponent(content));
            }
        }

            function toggleComments(postId) {
                const section = document.getElementById('comment-section-' + postId);
                section.style.display = (section.style.display === "none") ? "block" : "none";
            }

            function openImageModal(src, title = "Document Preview") {
                const modal = document.getElementById("imageModal");
                const img = document.getElementById("modalImg");
                
                // If you add a title element in your modal HTML, you can set it here:
                // document.getElementById("modalTitle").innerText = title;

                modal.style.display = "flex";
                img.src = src;
            }

            function closeImageModal() {
                document.getElementById("imageModal").style.display = "none";
            }

            // Close modal if user clicks outside the image
            window.onclick = function(event) {
                const modal = document.getElementById("imageModal");
                if (event.target == modal) {
                    closeImageModal();
                }
            }

        function openVoterModal(userId, name, currentStatus) {
            const modal = document.getElementById('voterModal');
            const text = document.getElementById('voterModalText');
            const inputId = document.getElementById('modalUserId');
            const inputStatus = document.getElementById('modalCurrentStatus');

            // Determine what the new status will be
            const nextStatus = (currentStatus === 'Registered') ? 'Non-Registered' : 'Registered';
            
            // Set the data
            inputId.value = userId;
            inputStatus.value = currentStatus;
            text.innerHTML = `Change <strong>${name}</strong> from <strong>${currentStatus}</strong> to <strong>${nextStatus}</strong>?`;
            
            // Show modal
            modal.style.display = 'block';
        }

        function closeVoterModal() {
            document.getElementById('voterModal').style.display = 'none';
        }

        // Close modal if user clicks outside the box
        window.onclick = function(event) {
            const modal = document.getElementById('voterModal');
            if (event.target == modal) {
                closeVoterModal();
            }
        }

    </script>
</body>
</html>