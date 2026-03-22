<?php 
require_once '../includes/auth_check.php';
require_once '../includes/db_config.php';

// Fetch all pending residents
$stmt = $pdo->prepare("SELECT users.user_id, users.username, residents.first_name, residents.last_name, residents.full_address 
                       FROM users 
                       JOIN residents ON users.user_id = residents.user_id 
                       WHERE users.status = 'pending' AND users.role = 'user'");
$stmt->execute();
$pendingUsers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Residents</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <h2>Pending Resident Approvals</h2>
    <table border="1" style="width:100%; border-collapse: collapse;">
        <tr>
            <th>Name</th>
            <th>Address</th>
            <th>Action</th>
        </tr>
        <?php foreach ($pendingUsers as $user): ?>
        <tr>
            <td><?php echo $user['first_name'] . " " . $user['last_name']; ?></td>
            <td><?php echo $user['full_address']; ?></td>
            <td>
                <a href="update_status.php?id=<?php echo $user['user_id']; ?>&status=active" style="color: green;">Approve</a> | 
                <a href="update_status.php?id=<?php echo $user['user_id']; ?>&status=inactive" style="color: red;">Reject</a>
            </td>
        </tr>
        <?php endforeach; ?>
        
    </table>
    <br>
    <a href="dashboard.php">Back to Dashboard</a>
</body>
</html>