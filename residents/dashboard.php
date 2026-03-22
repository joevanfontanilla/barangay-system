<?php 
require_once '../includes/auth_check.php'; 
require_once '../includes/db_config.php';

$user_id = $_SESSION['user_id'];

// Fetch profile
$stmtUser = $pdo->prepare("SELECT r.*, u.email, u.username FROM users u LEFT JOIN residents r ON u.user_id = r.user_id WHERE u.user_id = ?");
$stmtUser->execute([$user_id]);
$profile = $stmtUser->fetch();

if (!$profile) {
    $profile = [
        'first_name' => 'System',
        'last_name' => 'User',
        'email' => $_SESSION['username'],
        'contact_no' => 'N/A',
        'full_address' => 'N/A',
        'profile_image' => 'default_avatar.png'
    ];
}

// Fetch posts - Updated to include official posts and handle potential missing resident profiles
$stmtPosts = $pdo->prepare("
    SELECT p.*, r.first_name, r.last_name, r.profile_image,
    (SELECT COUNT(*) FROM post_likes WHERE post_id = p.post_id) as like_count,
    (SELECT COUNT(*) FROM post_comments WHERE post_id = p.post_id) as comment_count,
    (SELECT COUNT(*) FROM post_likes WHERE post_id = p.post_id AND user_id = ?) as user_liked
    FROM posts p 
    LEFT JOIN residents r ON p.user_id = r.user_id 
    WHERE p.status IN ('active', 'pinned')
    ORDER BY p.status = 'pinned' DESC, p.created_at DESC
");
$stmtPosts->execute([$user_id]);
$posts = $stmtPosts->fetchAll();
?>
<?php if (isset($_GET['request']) && $_GET['request'] == 'sent'): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Request Sent Successfully',
            text: 'Please wait for admin confirmation.',
            confirmButtonColor: '#28a745'
        });
    </script>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resident Portal | Barangay Connect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/resident_dashboard.css">
    <style>
        .sidebar-img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 10px; border: 3px solid #28a745; }
        .alert-box { position: fixed; top: 20px; right: 20px; z-index: 1000; min-width: 300px; }
        .post-action-btn { cursor: pointer; transition: 0.2s; text-decoration: none; color: #666; font-weight: bold; font-size: 0.9rem; border: none; background: none; padding: 5px 10px; border-radius: 5px; }
        .post-action-btn:hover { background: #f0f0f0; }
        .liked { color: #28a745 !important; }
        .comment-item { border-bottom: 1px solid #eee; padding: 8px 0; font-size: 0.85rem; }
        .comment-item:last-child { border-bottom: none; }
    </style>
</head>
<body>
    
    <div class="alert-box">
        <?php if (isset($_GET['msg'])): ?>
            <div id="alert-box" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; border: 1px solid #f5c6cb; transition: opacity 0.5s ease;">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($_GET['msg']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['update']) && $_GET['update'] === 'success'): ?>
            <div id="alert-box" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; border: 1px solid #c3e6cb; transition: opacity 0.5s ease;">
                <i class="fa-solid fa-circle-check"></i> Profile updated successfully!
            </div>
        <?php endif; ?>
    </div>

    <div class="grid-layout">
        <aside class="sidebar">
            <div class="profile-box">
                <?php if (!empty($profile['profile_image']) && $profile['profile_image'] !== 'default_avatar.png'): ?>
                    <img src="../assets/uploads/residents/<?php echo htmlspecialchars($profile['profile_image']); ?>" class="sidebar-img">
                <?php else: ?>
                    <div class="avatar"><?php echo strtoupper($profile['first_name'][0] . $profile['last_name'][0]); ?></div>
                <?php endif; ?>
                
                <h3><?php echo htmlspecialchars($profile['first_name'] ?? 'User'); ?></h3>
                <small><?php echo htmlspecialchars($profile['email'] ?? $_SESSION['username']); ?></small>
            </div>
            
            <ul class="nav-menu">
                <li><a href="#" onclick="openTab('feed')" class="active" id="feed-link"><i class="fa-solid fa-comments"></i> Community Feed</a></li>
                <li><a href="#" onclick="openTab('requests')" id="requests-link"><i class="fa-solid fa-file-invoice"></i> Document Requests</a></li>
                <li><a href="#" onclick="openTab('settings')" id="settings-link"><i class="fa-solid fa-user-gear"></i> Account Settings</a></li>
                <li style="margin-top: auto;"><a href="../auth/logout.php" class="logout-link"><i class="fa-solid fa-power-off"></i> Logout</a></li>
                
            </ul>
        </aside>

 <main class="content-area">

    <div id="feed" class="tab-content active">
        <h2 class="section-title">Community Feed</h2>
        
        <div class="card">
            <h4 style="margin-bottom: 15px;"><i class="fa-solid fa-pen-to-square"></i> Share a suggestion</h4>
            <form action="process_post.php" method="POST">
                <textarea name="content" placeholder="What's on your mind? Suggestions for the barangay are welcome!" required style="width: 100%; min-height: 80px; padding: 12px; border-radius: 8px; border: 1px solid #ddd; font-family: inherit; resize: vertical;"></textarea>
                <button type="submit" class="btn-primary" style="margin-top: 10px;">Post Suggestion</button>
            </form>
        </div>

        <div class="feed-list" style="margin-top: 20px;">
            <?php if (empty($posts)): ?>
                <div class="card" style="text-align: center; color: #888;">
                    <p>No suggestions yet. Be the first to start a conversation!</p>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="card" style="margin-bottom: 15px; border-left: 5px solid <?php echo ($post['status'] === 'pinned') ? '#ffc107' : '#28a745'; ?>;">
                        
                        <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
                                <img src="../assets/uploads/residents/<?php echo !empty($post['profile_image']) ? htmlspecialchars($post['profile_image']) : 'default_avatar.png'; ?>" 
                                     style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 1px solid #eee;">
                                
                                <div>
                                    <strong style="display: block; font-size: 1rem;">
                                        <?php echo htmlspecialchars(($post['first_name'] ?? 'Barangay') . ' ' . ($post['last_name'] ?? 'Official')); ?>
                                        <?php if ($post['type'] === 'official'): ?>
                                            <span style="background: #007bff; color: white; font-size: 0.65rem; padding: 2px 8px; border-radius: 20px; margin-left: 5px; text-transform: uppercase;">Official</span>
                                        <?php endif; ?>
                                    </strong>
                                    <small style="color: #999;">
                                        <i class="fa-regular fa-clock"></i> <?php echo date('M d, Y | h:i A', strtotime($post['created_at'])); ?>
                                    </small>
                                </div>
                            </div>

                            <?php if ($post['user_id'] == $user_id): ?>
                                <a href="delete_post.php?id=<?php echo $post['post_id']; ?>" 
                                   onclick="return confirm('Are you sure you want to delete this post?')" 
                                   style="color: #dc3545; text-decoration: none; font-size: 0.9rem;" title="Delete Post">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <div style="padding-left: 57px;">
                            <p style="color: #333; line-height: 1.6; white-space: pre-wrap; margin: 0;"><?php echo htmlspecialchars($post['content']); ?></p>
                            
                            <div style="margin-top: 15px; display: flex; gap: 15px; border-top: 1px solid #eee; padding-top: 10px;">
                                <a href="process_like.php?post_id=<?php echo $post['post_id']; ?>" class="post-action-btn <?php echo $post['user_liked'] ? 'liked' : ''; ?>">
                                    <i class="<?php echo $post['user_liked'] ? 'fa-solid' : 'fa-regular'; ?> fa-thumbs-up"></i> <?php echo $post['like_count']; ?>
                                </a>
                                
                                <button class="post-action-btn" onclick="toggleComments(<?php echo $post['post_id']; ?>)">
                                    <i class="fa-regular fa-comment"></i> <?php echo $post['comment_count']; ?>
                                </button>
                            </div>

                            <div id="comment-section-<?php echo $post['post_id']; ?>" style="display: none; margin-top: 10px; background: #fdfdfd; padding: 15px; border-radius: 8px; border: 1px solid #eee;">
                                <div class="comments-list" style="margin-bottom: 15px; max-height: 200px; overflow-y: auto;">
                                    <?php
                                    $stmtComments = $pdo->prepare("SELECT c.*, r.first_name, r.last_name FROM post_comments c LEFT JOIN residents r ON c.user_id = r.user_id WHERE c.post_id = ? ORDER BY c.created_at ASC");
                                    $stmtComments->execute([$post['post_id']]);
                                    $comments = $stmtComments->fetchAll();
                                    
                                    if (empty($comments)): ?>
                                        <p style="font-size: 0.8rem; color: #999;">No comments yet.</p>
                                    <?php else: foreach ($comments as $comment): ?>
                                        <div class="comment-item">
                                            <strong><?php echo htmlspecialchars($comment['first_name'] ?? 'User'); ?>:</strong> 
                                            <span><?php echo htmlspecialchars($comment['comment_text']); ?></span>
                                        </div>
                                    <?php endforeach; endif; ?>
                                </div>

                                <form action="process_comment.php" method="POST" style="display: flex; gap: 8px;">
                                    <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                    <input type="text" name="comment_text" placeholder="Write a comment..." required 
                                           style="flex: 1; padding: 8px 12px; border-radius: 20px; border: 1px solid #ddd; outline: none; font-size: 0.85rem;">
                                    <button type="submit" style="background: #28a745; color: white; border: none; padding: 5px 12px; border-radius: 20px; cursor: pointer;">
                                        <i class="fa-solid fa-paper-plane"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

<!-- Request Document -->
    <div id="requests" class="tab-content">
        <h2 class="section-title"><i class="fa-solid fa-file-shield"></i> Barangay Diagyan e-Services</h2>

        <div class="grid-layout" style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px; align-items: start;">
            <div class="card">
                <h4 style="margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:10px;">New Application</h4>
                <form action="./process_request.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label style="font-size: 0.85rem; font-weight: 600;">Document Type</label>
                        <select name="document_type" required style="width:100%; padding:10px; border-radius:6px; border:1px solid #ccc;">
                            <option value="" disabled selected>Select document...</option>
                            <option value="Barangay Clearance">Barangay Clearance</option>
                            <option value="Indigency">Certificate of Indigency</option>
                            <option value="Residency">Certificate of Residency</option>
                            <option value="Business Permit">Business Permit</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label style="font-size: 0.85rem; font-weight: 600;">Purpose</label>
                        <textarea name="purpose" placeholder="e.g. For Scholarship Application" required 
                                style="width:100%; padding:10px; border-radius:6px; border:1px solid #ccc; height:80px;"></textarea>
                    </div>
                
                  <!--  ID upload-->
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="font-size: 0.85rem; font-weight: 600;">Front of ID <small>(Required)</small></label>
                        <div style="position: relative; border: 2px dashed #28a745; border-radius: 8px; padding: 15px; text-align: center; background: #f8fff9;">
                            <input type="file" name="id_image" id="resident_id_front" accept="image/*" required 
                                onchange="previewFile(this, 'id-preview-front', 'placeholder-front')" 
                                style="position: absolute; width:100%; height:100%; top:0; left:0; opacity:0; cursor:pointer;">
                            <div id="placeholder-front">
                                <i class="fa-solid fa-id-card"></i>
                                <p>Upload Front ID</p>
                            </div>
                            <img id="id-preview-front" style="display:none; width:100%; border-radius:5px;">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="font-size: 0.85rem; font-weight: 600;">Back of ID <small>(Required)</small></label>
                        <div style="position: relative; border: 2px dashed #1877f2; border-radius: 8px; padding: 15px; text-align: center; background: #f0f7ff;">
                            <input type="file" name="id_image_back" id="resident_id_back" accept="image/*" required 
                                onchange="previewFile(this, 'id-preview-back', 'placeholder-back')" 
                                style="position: absolute; width:100%; height:100%; top:0; left:0; opacity:0; cursor:pointer;">
                            <div id="placeholder-back">
                                <i class="fa-solid fa-id-card-clip"></i>
                                <p>Upload Back ID</p>
                            </div>
                            <img id="id-preview-back" style="display:none; width:100%; border-radius:5px;">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="font-size: 0.85rem; font-weight: 600;">Additional Document <small>(If Applicable)</small></label>
                        <div style="position: relative; border: 2px dashed #6c757d; border-radius: 8px; padding: 15px; text-align: center; background: #f8f9fa;">
                            <input type="file" name="additional_doc" id="resident_additional" accept="image/*,application/pdf" 
                                onchange="previewFile(this, 'id-preview-add', 'placeholder-add')" 
                                style="position: absolute; width:100%; height:100%; top:0; left:0; opacity:0; cursor:pointer;">
                            <div id="placeholder-add">
                                <i class="fa-solid fa-file-circle-plus"></i>
                                <p>Upload Additional Proof</p>
                            </div>
                            <img id="id-preview-add" style="display:none; width:100%; border-radius:5px;">
                        </div>
                    </div>  

                    <button type="submit" class="btn-primary" style="width:100%; font-weight:bold;">Submit Application</button>
                </form>
            </div>

                        <div class="card">
                <h4 style="margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:10px;">My Applications</h4>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; font-size: 0.85rem; border-collapse: collapse;">
                        <thead>
                            <tr style="background:#f4f4f4; text-align:left;">
                                <th style="padding:12px;">Document Details</th>
                                <th style="padding:12px;">Status</th>
                                <th style="padding:12px;">Remarks/Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Query remains the same
                            $stmtReq = $pdo->prepare("SELECT * FROM document_requests WHERE user_id = ? ORDER BY created_at DESC");
                            $stmtReq->execute([$user_id]);
                            
                            while($req = $stmtReq->fetch()):
                                $badgeColor = ($req['status'] == 'approved') ? '#28a745' : (($req['status'] == 'rejected') ? '#dc3545' : '#ffc107');
                            ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding:12px;">
                                    <strong style="color: #333; font-size: 0.9rem;"><?php echo $req['document_type']; ?></strong><br>
                                    
                                    <div style="margin: 4px 0;">
                                        <span style="color: #28a745; font-weight: 700; font-family: monospace; background: #e8f5e9; padding: 2px 5px; border-radius: 3px; border: 1px solid #c8e6c9;">
                                            <i class="fa-solid fa-hashtag" style="font-size: 0.7rem;"></i> <?php echo htmlspecialchars($req['reference_number']); ?>
                                        </span>
                                    </div>

                                    <small style="color:#888;">
                                        <i class="fa-regular fa-clock"></i> <?php echo date('M d, Y', strtotime($req['created_at'])); ?>
                                    </small>
                                </td>
                                
                                <td style="padding:12px;">
                                    <span style="background:<?php echo $badgeColor; ?>; color:white; padding:4px 10px; border-radius:50px; font-weight:bold; font-size:0.65rem; letter-spacing: 0.5px;">
                                        <?php echo strtoupper($req['status']); ?>
                                    </span>
                                </td>
                                
                                <td style="padding:12px;">
                                    <?php if($req['status'] == 'approved'): ?>
                                        <a href="download_doc.php?ref=<?php echo $req['reference_number']; ?>" class="btn-download" style="color:#007bff; text-decoration:none; font-weight: 600;">
                                            <i class="fa-solid fa-file-pdf"></i> Get Document
                                        </a>
                                    <?php elseif($req['admin_remarks']): ?>
                                        <div style="max-width: 200px;">
                                            <small style="color:#dc3545; line-height: 1.2; display: block;">
                                                <strong>Note:</strong> "<?php echo htmlspecialchars($req['admin_remarks']); ?>"
                                            </small>
                                        </div>
                                    <?php else: ?>
                                        <small style="color:#999; font-style: italic;">Processing in progress...</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            
                            <?php if($stmtReq->rowCount() == 0): ?>
                            <tr>
                                <td colspan="3" style="padding: 30px; text-align: center; color: #999;">
                                    <i class="fa-solid fa-folder-open" style="font-size: 2rem; display: block; margin-bottom: 10px; opacity: 0.3;"></i>
                                    No requests found yet.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="settings" class="tab-content">
        <h2 class="section-title">Profile Settings</h2>

        <div class="card">
            <h4 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                <i class="fa-solid fa-user"></i> Personal Information
            </h4>
            <form action="update_profile.php" method="POST">
                <input type="hidden" name="form_type" value="personal_info">
                <div class="form-grid">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($profile['first_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($profile['last_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Birthdate</label>
                        <input type="date" name="birthdate" value="<?php echo htmlspecialchars($profile['birthdate']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" name="contact_no" value="<?php echo htmlspecialchars($profile['contact_no'] ?? ''); ?>">
                    </div>
                    <div style="grid-column: span 2;">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($profile['email']); ?>" required>
                    </div>
                    <div style="grid-column: span 2;">
                        <label>Full Address</label>
                        <textarea name="full_address" rows="2" style="width:100%; padding:10px; border-radius:6px; border:1px solid #ccc;"><?php echo htmlspecialchars($profile['full_address']); ?></textarea>
                    </div>
                </div>
                <button type="submit" class="btn-primary" style="margin-top: 15px;">Update Profile</button>
            </form>
        </div>

        <div class="card">
            <h4 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                <i class="fa-solid fa-camera"></i> Profile Picture
            </h4>
            <form action="update_profile.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="form_type" value="profile_image">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <img id="preview-img" src="../assets/uploads/residents/<?php echo !empty($profile['profile_image']) ? $profile['profile_image'] : 'default_avatar.png'; ?>" 
                        style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 2px solid #28a745;">
                    <div style="flex-grow: 1;">
                        <label>Select New Image (JPG, PNG)</label>
                        <input type="file" name="image" accept="image/*" onchange="previewFile(event)" required style="margin-top: 5px;">
                    </div>
                </div>
                <button type="submit" class="btn-primary" style="margin-top: 15px;">Save Photo Permanently</button>
            </form>
        </div>

        <div class="card">
            <h4 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                <i class="fa-solid fa-lock"></i> Change Password
            </h4>
            <form action="update_profile.php" method="POST">
                <input type="hidden" name="form_type" value="change_password">
                <div class="form-grid">
                    <div class="form-group" style="grid-column: span 2; position: relative;">
                        <label>Current Password</label>
                        <input type="password" name="current_password" id="curr_p" required>
                        <i class="fa-solid fa-eye" onclick="togglePass('curr_p', this)" style="position: absolute; right: 15px; top: 40px; cursor: pointer; color: #888;"></i>
                    </div>
                    <div class="form-group" style="position: relative;">
                        <label>New Password</label>
                        <input type="password" name="new_password" id="new_p" onkeyup="checkPasswordStrength(this.value)" required>
                        <i class="fa-solid fa-eye" onclick="togglePass('new_p', this)" style="position: absolute; right: 15px; top: 40px; cursor: pointer; color: #888;"></i>
                        <ul id="password-requirements" style="font-size: 0.75rem; color: #888; list-style: none; padding: 5px 0 0 0;">
                            <li id="req-length"><i class="fa-solid fa-circle-xmark"></i> 8+ characters</li>
                            <li id="req-upper"><i class="fa-solid fa-circle-xmark"></i> Capital letter</li>
                            <li id="req-number"><i class="fa-solid fa-circle-xmark"></i> One number</li>
                        </ul>
                    </div>
                    <div class="form-group" style="position: relative;">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" id="conf_p" required>
                        <i class="fa-solid fa-eye" onclick="togglePass('conf_p', this)" style="position: absolute; right: 15px; top: 40px; cursor: pointer; color: #888;"></i>
                    </div>
                </div>
                <button type="submit" class="btn-primary" style="background: #dc3545; margin-top: 15px;">Change Password</button>
            </form>
        </div>
    </div>
</main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function openTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.nav-menu a').forEach(l => l.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
            document.getElementById(tabName + '-link').classList.add('active');
        }

        function toggleComments(postId) {
            const section = document.getElementById('comment-section-' + postId);
            section.style.display = (section.style.display === 'none') ? 'block' : 'none';
        }

        function togglePass(inputId, icon) {
            const input = document.getElementById(inputId);
            const isPass = input.type === "password";
            input.type = isPass ? "text" : "password";
            icon.classList.toggle("fa-eye");
            icon.classList.toggle("fa-eye-slash");
        }

        function checkPasswordStrength(password) {
            const requirements = {
                length: password.length >= 8,
                upper: /[A-Z]/.test(password),
                number: /[0-9]/.test(password)
            };
            updateRequirementUI("req-length", requirements.length);
            updateRequirementUI("req-upper", requirements.upper);
            updateRequirementUI("req-number", requirements.number);
        }

        function updateRequirementUI(elementId, isMet) {
            const el = document.getElementById(elementId);
            if(!el) return;
            const icon = el.querySelector('i');
            el.style.color = isMet ? "#28a745" : "#888";
            icon.className = isMet ? "fa-solid fa-circle-check" : "fa-solid fa-circle-xmark";
        }

        window.onload = function() {
            const alertBox = document.getElementById('alert-box');
            if (alertBox) {
                setTimeout(() => {
                    alertBox.style.opacity = '0';
                    setTimeout(() => { alertBox.style.display = 'none'; }, 500);
                }, 2000);
            }
        };

        function previewFile(input, previewId, placeholderId) {
            const preview = document.getElementById(previewId);
            const placeholder = document.getElementById(placeholderId);
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    placeholder.style.display = 'none';
                }

                reader.readAsDataURL(input.files[0]);
            }
        }
        function previewID(input) {
            const preview = document.getElementById('id-preview');
            const placeholder = document.getElementById('id-placeholder');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    placeholder.style.display = 'none';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

// Check the URL for parameters
const urlParams = new URLSearchParams(window.location.search);

// Helper function to remove parameters without refreshing the page
function removeUrlParams() {
    const newUrl = window.location.pathname + window.location.hash;
    window.history.replaceState({}, document.title, newUrl);
}

// 1. Success Alert
if (urlParams.get('request') === 'sent') {
    Swal.fire({
        icon: 'success',
        title: 'Request Submitted!',
        text: 'Your document request has been sent to the Barangay Admin.',
        confirmButtonColor: '#28a745'
    }).then(() => {
        removeUrlParams(); // Clean URL after clicking OK
    });
}

// 2. Spam Prevention Alert (Pending)
if (urlParams.get('msg') === 'pending_exists') {
    Swal.fire({
        icon: 'warning',
        title: 'Duplicate Request',
        text: 'You already have a pending request for this document.',
        confirmButtonColor: '#ffc107'
    }).then(() => {
        removeUrlParams(); // Clean URL after clicking OK
    });
}

// 3. 6-Month Validity Alert (Don't forget this one!)
if (urlParams.get('msg') === 'already_approved') {
    const expiry = urlParams.get('expiry');
    Swal.fire({
        icon: 'info',
        title: 'Document Still Valid',
        html: `You have an approved request.<br>It expires on: <b>${expiry}</b>`,
        confirmButtonColor: '#1877f2'
    }).then(() => {
        removeUrlParams();
    });
}


    

    </script>
</body>
</html>