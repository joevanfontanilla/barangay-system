<?php 
session_start(); 
require_once 'includes/db_config.php'; 

$current_user_id = $_SESSION['user_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Connect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/index.css">
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="nav-brand">🏠 BarangayConnect</a>
        
        <div class="nav-auth">
            <?php if ($current_user_id): ?>
                <a href="residents/dashboard.php" class="btn-header login-link" style="text-decoration:none;">
                    <i class="fa-solid fa-gauge"></i> <span>Dashboard</span>
                </a>
            <?php else: ?>
                <button onclick="toggleModal(true)" class="btn-header login-link" style="border:none; cursor:pointer; background: #f0f2f5; padding: 8px 15px; border-radius: 6px;">
                    <i class="fa-solid fa-right-to-bracket"></i> Login
                </button>
                <a href="auth/register.php" class="btn-header register-link" style="background: #1877f2; color: white; padding: 8px 15px; border-radius: 6px; text-decoration: none; margin-left: 10px;">
                    <i class="fa-solid fa-user-plus"></i> Join
                </a>
            <?php endif; ?>
        </div>
    </nav>

    <div id="roleModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="toggleModal(false)" style="position:absolute; right:15px; top:10px; cursor:pointer; font-size:20px;">&times;</span>
            <h3>Welcome Back!</h3>
            <p style="color: #65676b; font-size: 14px; margin-bottom: 20px;">Select your portal to continue</p>
            <div class="role-list" style="display: flex; flex-direction: column; gap: 10px;">
                <a href="auth/login.php?role=super_admin" class="role-item" style="text-decoration:none; display:flex; align-items:center; gap:10px; padding:12px; border:1px solid #ddd; border-radius:8px; color:#333;">
                    <i class="fa-solid fa-star" style="color: #ffc107;"></i>
                    <span>Barangay Captain</span>
                </a>
                <a href="auth/login.php?role=admin" class="role-item" style="text-decoration:none; display:flex; align-items:center; gap:10px; padding:12px; border:1px solid #ddd; border-radius:8px; color:#333;">
                    <i class="fa-solid fa-user-shield" style="color: #0056b3;"></i>
                    <span>Barangay Official</span>
                </a>
                <a href="auth/login.php?role=user" class="role-item" style="text-decoration:none; display:flex; align-items:center; gap:10px; padding:12px; border:1px solid #ddd; border-radius:8px; color:#333;">
                    <i class="fa-solid fa-house-user" style="color: #1e7e34;"></i>
                    <span>Resident</span>
                </a>
            </div>
        </div>
    </div>

    <div class="main-wrapper">
        <main class="feed-column">
            <h3 style="color: #65676b; margin-left: 10px;">Public Feed</h3>

            <?php
            try {
                $stmt = $pdo->prepare("
                    SELECT p.*, r.first_name, r.last_name, r.profile_image,
                    (SELECT COUNT(*) FROM post_likes WHERE post_id = p.post_id) as like_count,
                    (SELECT COUNT(*) FROM post_comments WHERE post_id = p.post_id) as comment_count,
                    (SELECT COUNT(*) FROM post_likes WHERE post_id = p.post_id AND user_id = ?) as user_liked
                    FROM posts p 
                    LEFT JOIN residents r ON p.user_id = r.user_id 
                    WHERE p.status = 'active' OR p.status = 'pinned'
                    ORDER BY p.status = 'pinned' DESC, p.created_at DESC
                ");
                $stmt->execute([$current_user_id]);
                $posts = $stmt->fetchAll();

                foreach ($posts as $post): 
                    $isOfficial = ($post['type'] === 'official');
                    $name = ($post['first_name']) ? htmlspecialchars($post['first_name']." ".$post['last_name']) : "Barangay Official";
                    $photo = !empty($post['profile_image']) ? 'assets/uploads/residents/'.$post['profile_image'] : 'assets/uploads/residents/profile_default.png';
                    ?>
                    <div class="post-card">
                        <div style="display: flex; align-items: center; margin-bottom: 12px;">
                            <img src="<?php echo $photo; ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; margin-right: 12px; border: 1px solid #eee;">
                            <div>
                                <div style="font-weight: bold;"><?php echo $name; ?> <?php if($isOfficial) echo "📢"; ?></div>
                                <small style="color: #65676b;"><?php echo date('M d, g:i A', strtotime($post['created_at'])); ?></small>
                            </div>
                        </div>
                        <div style="font-size: 15px; color: #1c1e21; line-height: 1.5;"><?php echo nl2br(htmlspecialchars($post['content'])); ?></div>

                        <div class="post-action-bar">
                            <?php if ($current_user_id): ?>
                                <a href="residents/process_like.php?post_id=<?php echo $post['post_id']; ?>&from=index" class="action-btn <?php echo $post['user_liked'] ? 'liked' : ''; ?>">
                                    <i class="<?php echo $post['user_liked'] ? 'fa-solid' : 'fa-regular'; ?> fa-thumbs-up"></i> 
                                    <span><?php echo $post['like_count'] > 0 ? $post['like_count'] : ''; ?></span>
                                </a>
                            <?php else: ?>
                                <button onclick="toggleModal(true)" class="action-btn">
                                    <i class="fa-regular fa-thumbs-up"></i> 
                                    <span><?php echo $post['like_count'] > 0 ? $post['like_count'] : ''; ?></span>
                                </button>
                            <?php endif; ?>

                            <button class="action-btn" onclick="toggleComments(<?php echo $post['post_id']; ?>)">
                                <i class="fa-regular fa-comment"></i> 
                                <span><?php echo $post['comment_count'] > 0 ? $post['comment_count'] : ''; ?></span>
                            </button>
                        </div>

                        <div id="comment-box-<?php echo $post['post_id']; ?>" class="comment-box">
                            <div class="comment-list">
                                <?php
                                $cStmt = $pdo->prepare("SELECT c.*, r.first_name FROM post_comments c JOIN residents r ON c.user_id = r.user_id WHERE c.post_id = ? ORDER BY c.created_at ASC");
                                $cStmt->execute([$post['post_id']]);
                                $comments = $cStmt->fetchAll();
                                if(empty($comments)) echo "<small style='color:#888;'>No comments yet.</small>";
                                foreach($comments as $comm): ?>
                                    <div class="comment-item">
                                        <strong><?php echo htmlspecialchars($comm['first_name']); ?>:</strong> <?php echo htmlspecialchars($comm['comment_text']); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if ($current_user_id): ?>
                                <form action="residents/process_comment.php" method="POST" style="display:flex; gap:5px; margin-top:10px;">
                                    <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                    <input type="hidden" name="from" value="index">
                                    <input type="text" name="comment_text" placeholder="Write a comment..." required style="flex:1; border-radius:20px; border:1px solid #ddd; padding:8px 15px; font-size:0.85rem; outline:none;">
                                    <button type="submit" style="background:#1877f2; color:white; border:none; border-radius:50%; width:32px; height:32px; cursor:pointer;"><i class="fa-solid fa-paper-plane"></i></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; 
            } catch (PDOException $e) { echo "<p>Unable to load feed.</p>"; } ?>
        </main>
    </div>

    <script>
        const modal = document.getElementById('roleModal');
        function toggleModal(show) { modal.style.display = show ? 'flex' : 'none'; }
        window.onclick = function(e) { if (e.target == modal) toggleModal(false); }

        function toggleComments(postId) {
            const box = document.getElementById('comment-box-' + postId);
            box.style.display = (box.style.display === 'block') ? 'none' : 'block';
        }
    </script>
</body>
</html>