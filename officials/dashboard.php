<?php 
require_once '../includes/auth_check.php'; 
require_once '../includes/db_config.php';

// 1. Authorization Check
if (!in_array($_SESSION['role'], ['secretary', 'admin', 'super_admin'])) {
    header("Location: ../residents/dashboard.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$view = $_GET['view'] ?? 'feed';
$username = $_SESSION['username'] ?? 'User';

// 2. Fetch Data for the Feed
if ($view == 'feed') {
    $feed_query = "SELECT 
        p.*, r.first_name, r.last_name, r.profile_image,
        (SELECT COUNT(*) FROM post_likes WHERE post_id = p.post_id) AS like_count,
        (SELECT COUNT(*) FROM post_comments WHERE post_id = p.post_id) AS comment_count,
        (SELECT COUNT(*) FROM post_likes WHERE post_id = p.post_id AND user_id = ?) AS user_liked
    FROM posts p
    LEFT JOIN residents r ON p.user_id = r.user_id 
    ORDER BY p.created_at DESC LIMIT 15";
    
    $stmt = $pdo->prepare($feed_query);
    $stmt->execute([$current_user_id]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secretary Dashboard | Barangay Connect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root { --fb-blue: #1877f2; --fb-bg: #f0f2f5; --text-gray: #65676b; }
        body { background-color: var(--fb-bg); font-family: 'Segoe UI', Helvetica, Arial, sans-serif; margin: 0; color: #1c1e21; }
        .admin-layout { display: flex; min-height: 100vh; }
        
        /* Sidebar */
        .sidebar { width: 260px; background: #fff; border-right: 1px solid #e4e6eb; padding: 20px; position: sticky; top: 0; height: 100vh; box-sizing: border-box; }
        .sidebar h2 { color: var(--fb-blue); font-size: 1.3rem; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }
        .sidebar-nav { list-style: none; padding: 0; }
        .sidebar-nav li a { display: flex; align-items: center; gap: 10px; padding: 12px 15px; color: #050505; text-decoration: none; border-radius: 8px; margin-bottom: 5px; font-weight: 500; transition: 0.2s; }
        .sidebar-nav li a:hover, .sidebar-nav li a.active { background: #f2f2f2; color: var(--fb-blue); }

        /* Main Content */
        .admin-main { flex: 1; padding: 30px; max-width: 800px; margin: 0 auto; }
        .card-section { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); margin-bottom: 25px; }
        .section-title { font-size: 1.1rem; font-weight: 700; color: var(--text-gray); margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
        /* Make the dashboard container wider */
        .main-content {
            width: 98%; /* Or whatever your main wrapper class is */
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Expand the Request Section */
        #manage-requests-section {
            width: 100%;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Table text size adjustment for readability */
        .resident-table th, .resident-table td {
            font-size: 0.9rem;
            padding: 12px 8px;
        }
        /* Post Card Styling */
        .post-card { background: #fff; padding: 20px 20px 0 20px; border-radius: 12px; margin-bottom: 20px; border: 1px solid #e4e6eb; overflow: hidden; }
        .post-header { display: flex; justify-content: space-between; align-items: center; }
        .post-user { display: flex; align-items: center; gap: 10px; }
        .post-user img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 1px solid #ddd; }
        .post-content { margin: 15px 0; line-height: 1.5; font-size: 1rem; }
        
        .post-actions { display: flex; gap: 10px; padding: 5px 0; border-top: 1px solid #f0f2f5; border-bottom: 1px solid #f0f2f5; }
        .action-btn { background: none; border: none; color: var(--text-gray); font-size: 0.9rem; font-weight: 600; cursor: pointer; flex: 1; padding: 10px; border-radius: 6px; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .action-btn:hover { background-color: #f2f2f2; }

        /* Comments Bottom Styling */
        .comment-section-wrapper { display: none; background-color: #f8f9fa; margin: 0 -20px; padding: 15px 20px; border-top: 1px solid #eee; }
        .comment-list { max-height: 300px; overflow-y: auto; margin-bottom: 15px; }
        .single-comment { display: flex; gap: 10px; margin-bottom: 10px; }
        .comment-bubble { background: #ebedf0; padding: 8px 12px; border-radius: 18px; font-size: 0.85rem; max-width: 85%; }
        .comment-bubble strong { display: block; font-size: 0.8rem; color: #1c1e21; }

        .comment-input-group { display: flex; align-items: center; gap: 8px; padding-top: 10px; }
        .comment-input-group input { flex: 1; background: #f0f2f5; border: 1px solid #dddfe2; padding: 8px 15px; border-radius: 20px; outline: none; }
        
        /* Modal & Helpers */
        #imageModal { display: none; position: fixed; z-index: 999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); align-items: center; justify-content: center; }
        .btn-view-id { background: #f0f2f5; border: 1px solid #ddd; padding: 5px 10px; border-radius: 5px; cursor: pointer; color: var(--fb-blue); }
        .alert { padding: 12px; border-radius: 8px; background: #e7f3ff; color: var(--fb-blue); text-align: center; margin-bottom: 20px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <aside class="sidebar">
            <h2><i class="fa-solid fa-file-signature"></i> Secretary Portal</h2>
            <ul class="sidebar-nav">
                <li><a href="dashboard.php?view=feed" class="<?= $view == 'feed' ? 'active' : '' ?>"><i class="fa-solid fa-house"></i> Community Feed</a></li>
                <li><a href="dashboard.php?view=requests" class="<?= $view == 'requests' ? 'active' : '' ?>"><i class="fa-solid fa-file-invoice"></i> Manage Requests</a></li>
                <li><a href="../auth/logout.php" style="color: #e41e3f;"><i class="fa-solid fa-power-off"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="admin-main">
            <h1>Welcome, Secretary <?= htmlspecialchars($username); ?></h1>

            <?php if (isset($_GET['msg'])): ?><div class="alert">Action completed successfully!</div><?php endif; ?>

            <?php if ($view == 'feed'): ?>
                <section class="card-section">
                    <div class="section-title" style="color: var(--fb-blue);"><i class="fa-solid fa-bullhorn"></i> Create Official Announcement</div>
                    <textarea id="announcementContent" placeholder="Write an official update for the community..." 
                        style="width: 100%; height: 80px; padding: 12px; border-radius: 8px; border: 1px solid #dddfe2; resize: none; background: #f0f2f5; box-sizing: border-box;"></textarea>
                    <div style="text-align: right; margin-top: 10px;">
                        <button onclick="publishAnnouncementAjax()" style="background: var(--fb-blue); color: #fff; border: none; padding: 8px 20px; border-radius: 6px; cursor: pointer; font-weight: 600;">Post to Feed</button>
                    </div>
                </section>

                <div id="feedContainer">
                    <?php while ($post = $stmt->fetch()): 
                        $fullName = ($post['first_name'] || $post['last_name']) ? htmlspecialchars($post['first_name'] . " " . $post['last_name']) : "Official Account";
                        $userImg = (empty($post['profile_image']) || $post['profile_image'] == 'default_avatar.png') ? "../assets/uploads/residents/profile_default.png" : "../assets/uploads/residents/" . $post['profile_image'];
                    ?>
                    <div class="post-card" id="post-<?= $post['post_id']; ?>">
                        <div class="post-header">
                            <div class="post-user">
                                <img src="<?= $userImg; ?>">
                                <div>
                                    <strong><?= $fullName; ?></strong><br>
                                    <small style="color: var(--text-gray);"><?= date('M d, Y h:i A', strtotime($post['created_at'])); ?></small>
                                </div>
                            </div>
                            <button onclick="deletePostAjax(<?= $post['post_id']; ?>)" style="background:none; border:none; color:#e41e3f; cursor:pointer;"><i class="fa-solid fa-trash-can"></i></button>
                        </div>

                        <div class="post-content"><?= nl2br(htmlspecialchars($post['content'])); ?></div>

                        <div class="post-actions">
                            <button class="action-btn" id="like-btn-<?= $post['post_id']; ?>" onclick="toggleLike(<?= $post['post_id']; ?>)" style="<?= ($post['user_liked'] > 0) ? 'color: var(--fb-blue);' : ''; ?>">
                                <i class="<?= ($post['user_liked'] > 0) ? 'fa-solid' : 'fa-regular'; ?> fa-thumbs-up"></i> 
                                <span class="like-count"><?= $post['like_count']; ?></span> Likes
                            </button>
                            <button class="action-btn" onclick="toggleComments(<?= $post['post_id']; ?>)">
                                <i class="fa-regular fa-comment"></i> <?= $post['comment_count']; ?> Comments
                            </button>
                        </div>

                        <div id="comment-area-<?= $post['post_id']; ?>" class="comment-section-wrapper">
                            <div id="comment-list-<?= $post['post_id']; ?>" class="comment-list"></div>
                            <div class="comment-input-group">
                                <input type="text" id="comment-input-<?= $post['post_id']; ?>" placeholder="Write a comment..." onkeypress="if(event.key === 'Enter') submitComment(<?= $post['post_id']; ?>)">
                                <button onclick="submitComment(<?= $post['post_id']; ?>)" style="background: var(--fb-blue); color: white; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer;"><i class="fa-solid fa-paper-plane"></i></button>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>

<?php elseif ($view == 'requests'): ?>
    <section id="manage-requests-section" class="card-section">
        <div class="section-title" style="color: #1877f2; display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fa-solid fa-file-invoice"></i> Document Request Management</span>
            <a href="dashboard.php?view=feed" style="background: #f0f2f5; color: #050505; text-decoration:none; padding: 5px 10px; border-radius: 6px; cursor: pointer; font-size: 0.8rem;">
                <i class="fa-solid fa-xmark"></i> Close
            </a>
        </div>

        <div style="overflow-x: auto; margin-top: 15px;">
            <table class="resident-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; color: var(--text-gray); border-bottom: 1px solid #eee;">
                        <th style="padding: 10px;">Ref Number</th>
                        <th>Resident</th>
                        <th>Document</th>
                        <th>Proof (IDs)</th>
                        <th>Status</th>
                        <th style="text-align: center;">Process</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Query remains the same as your superadmin version
                    $stmtReq = $pdo->query("SELECT r.*, u.username FROM document_requests r JOIN users u ON r.user_id = u.user_id ORDER BY r.created_at DESC");
                    
                    while($row = $stmtReq->fetch()):
                        $statusColor = ($row['status'] == 'approved') ? '#1877f2' : (($row['status'] == 'rejected') ? '#e41e3f' : '#f1c40f');
                    ?>
                    <tr style="border-bottom: 1px solid #f9f9f9;">
                        <td style="padding: 12px;"><strong style="color: #1877f2;"><?php echo $row['reference_number']; ?></strong></td>
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
<form action="update_request_status.php" method="POST" style="display: flex; flex-direction: column; gap: 6px;">
    <input type="hidden" name="request_id" value="<?php echo $row['request_id']; ?>">
    
    <div style="display: flex; gap: 5px;">
        <select name="new_status" style="flex: 1; padding: 6px; border-radius: 4px; border: 1px solid #ddd; font-size: 0.85rem; cursor: pointer; background-color: #fcfcfc;">
            <option value="pending" <?php if($row['status'] == 'pending') echo 'selected'; ?>>Pending</option>
            <option value="approved" <?php if($row['status'] == 'approved') echo 'selected'; ?>>Approve</option>
            <option value="rejected" <?php if($row['status'] == 'rejected') echo 'selected'; ?>>Reject</option>
        </select>
        
        <button type="submit" name="update_btn" style="background: #1877f2; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: 600; transition: background 0.2s;">
            Save
        </button>
    </div>

    <input type="text" name="admin_remarks" 
        placeholder="Add reason for rejection or notes..." 
        value="<?php echo htmlspecialchars($row['admin_remarks'] ?? ''); ?>"
        style="width: 100%; padding: 6px; font-size: 0.8rem; border: 1px solid #f0f0f0; border-radius: 4px; background: #fafafa;"
        title="Admin Remarks">
</form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
        </main>
    </div>

<div id="imageModal" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.9); align-items: center; justify-content: center; flex-direction: column;">
    <span onclick="closeImageModal()" style="position: absolute; top: 20px; right: 35px; color: #f1f1f1; font-size: 40px; font-weight: bold; cursor: pointer;">&times;</span>
    <img id="modalImg" style="margin: auto; display: block; max-width: 80%; max-height: 80%; border-radius: 5px;">
    <div id="modalCaption" style="margin: auto; display: block; width: 80%; text-align: center; color: #ccc; padding: 10px 0; font-size: 1.2rem;"></div>
</div>

    <script>
        function toggleComments(postId) {
            const area = document.getElementById('comment-area-' + postId);
            area.style.display = (area.style.display === "none" || area.style.display === "") ? "block" : "none";
            if(area.style.display === "block") fetchComments(postId);
        }

        function fetchComments(postId) {
            const list = document.getElementById('comment-list-' + postId);
            const xhr = new XMLHttpRequest();
            xhr.open("GET", "process_comment.php?action=fetch&post_id=" + postId, true);
            xhr.onload = function() { list.innerHTML = this.responseText || "<small style='color:gray;'>No comments yet.</small>"; };
            xhr.send();
        }

        function submitComment(postId) {
            const input = document.getElementById('comment-input-' + postId);
            const text = input.value.trim();
            if (!text) return;
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "process_comment.php", true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                if (this.responseText.trim() === "success") {
                    input.value = "";
                    fetchComments(postId);
                }
            };
            xhr.send("action=add&post_id=" + postId + "&comment_text=" + encodeURIComponent(text));
        }

        function toggleLike(postId) {
            const btn = document.getElementById('like-btn-' + postId);
            const icon = btn.querySelector('i');
            const countSpan = btn.querySelector('.like-count');
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "process_like.php", true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                const res = this.responseText.trim();
                if (res === "liked") {
                    icon.classList.replace('fa-regular', 'fa-solid');
                    btn.style.color = '#1877f2';
                    countSpan.innerText = parseInt(countSpan.innerText) + 1;
                } else if (res === "unliked") {
                    icon.classList.replace('fa-solid', 'fa-regular');
                    btn.style.color = '#65676b';
                    countSpan.innerText = parseInt(countSpan.innerText) - 1;
                }
            };
            xhr.send("post_id=" + postId);
        }

        function publishAnnouncementAjax() {
            const content = document.getElementById('announcementContent').value.trim();
            if (!content) return;
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "process_official_post.php", true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.onload = function() { if (this.responseText.trim() === "Success") location.reload(); };
            xhr.send("content=" + encodeURIComponent(content));
        }

        function deletePostAjax(postId) {
            if (confirm('Delete this post?')) {
                const xhr = new XMLHttpRequest();
                xhr.open("GET", "delete_post.php?id=" + postId, true);
                xhr.onload = function() { if (this.responseText.trim() === "Deleted") document.getElementById('post-' + postId).remove(); };
                xhr.send();
            }
        }



        function openImageModal(src) {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImg');
    const modalCaption = document.getElementById('modalCaption');

    modal.style.display = "flex";
    modalImg.src = src;
    
    // Optional: Extract filename for the caption
    const fileName = src.split('/').pop();
    modalCaption.innerHTML = "Viewing: " + fileName;
}

function closeImageModal() {
    document.getElementById('imageModal').style.display = "none";
}

// Close modal if user clicks anywhere outside the image
window.onclick = function(event) {
    const modal = document.getElementById('imageModal');
    if (event.target == modal) {
        closeImageModal();
    }
}
    </script>
</body>
</html>