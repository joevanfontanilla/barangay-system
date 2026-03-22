<?php 
require_once '../includes/auth_check.php';
require_once '../includes/db_config.php';
restrictToSuperAdmin(); // Safety Lock

// Fetch all Admins (Officials)
$stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'admin'");
$stmt->execute();
$admins = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Officials</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <h2 style="color: #d9534f;">🛡️ Super Admin: Official Management</h2>
    <table border="1" style="width:100%;">
        <tr>
            <th>Username</th>
            <th>Email</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php foreach ($admins as $admin): ?>
        <tr>
            <td><?php echo $admin['username']; ?></td>
            <td><?php echo $admin['email']; ?></td>
            <td><?php echo $admin['status']; ?></td>
            <td>
                <a href="update_status.php?id=<?php echo $admin['user_id']; ?>&status=inactive">Deactivate</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>