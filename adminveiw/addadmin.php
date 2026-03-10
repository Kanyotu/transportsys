<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
include 'database.php';

$message = "";
$message_type = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirmpassword = $_POST['confirmpassword'];
    $email = $_POST['email']?? "";
    $phone = $_POST['phone'];
    $type = "admin"; 

    if($password != $confirmpassword){
        $message = "Passwords do not match!";
        $message_type = "danger";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, hashedpassword, email, phoneno, type) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $hashed_password, $email, $phone, $type);
        
        if($stmt->execute()) {
            $message = "Admin added successfully!";
            $message_type = "success";
        } else {
            $message = "Error: " . $stmt->error;
            $message_type = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Administrators | SafiriPay Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-bus-alt"></i>
                <span>SafiriPay</span>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
                <li><a href="manage_users.php"><i class="fas fa-users-cog"></i> <span>User Management</span></a></li>
                <li><a href="manage_saccos.php"><i class="fas fa-building"></i> <span>SACCO Management</span></a></li>
                <li><a href="manage_routes.php"><i class="fas fa-route"></i> <span>Routes & Stages</span></a></li>
                <li><a href="manage_trips.php"><i class="fas fa-calendar-alt"></i> <span>Trips & Schedules</span></a></li>
                <li><a href="manage_bookings.php"><i class="fas fa-ticket-alt"></i> <span>Bookings</span></a></li>
                <li><a href="manage_payments.php"><i class="fas fa-file-invoice-dollar"></i> <span>Payments</span></a></li>
                <li><a href="manage_feedback.php"><i class="fas fa-comment-dots"></i> <span>Feedback & Complaints</span></a></li>
                <li><a href="reports.php"><i class="fas fa-chart-line"></i> <span>Reports</span></a></li>
                <li><a href="addadmin.php" class="active"><i class="fas fa-user-shield"></i> <span>Administrators</span></a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div class="header-title">
                    <h1>Administrators</h1>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Create and manage system administrator accounts.</p>
                </div>
                <div class="header-actions">
                    <button class="theme-toggle" id="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            </header>

            <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 2rem; align-items: start;">
                 <!-- Current Admins -->
                 <div class="data-card">
                    <div class="data-card-header">
                        <h2>System Admins</h2>
                    </div>
                    <?php 
                    $admins = $conn->query("SELECT * FROM users WHERE type = 'admin' ORDER BY datejoined DESC");
                    ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Admin</th>
                                    <th>Contact</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $admins->fetch_assoc()): ?>
                                    <tr>
                                        <td style="font-weight: 600;"><?php echo htmlspecialchars($row['username']); ?></td>
                                        <td style="font-size: 0.875rem; color: var(--text-muted);"><?php echo htmlspecialchars($row['phoneno']); ?></td>
                                        <td style="font-size: 0.8125rem; color: var(--text-muted);"><?php echo date('M d, Y', strtotime($row['datejoined'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add Admin Form -->
                <div class="card" style="max-width: 100%;">
                    <h2 style="margin-bottom: 2rem;">Add New Administrator</h2>
                    <?php if($message): ?>
                        <div class="status-badge status-<?php echo $message_type; ?>" style="width: 100%; margin-bottom: 1.5rem; justify-content: center; padding: 1rem;">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="input-group">
                            <label>Username</label>
                            <input type="text" name="username" required placeholder="e.g. JohnAdmin">
                        </div>
                        <div class="input-group">
                            <label>Phone Number</label>
                            <input type="text" name="phone" required placeholder="0712345678">
                        </div>
                        <div class="input-group">
                            <label>Email (Optional)</label>
                            <input type="email" name="email" placeholder="admin@safiripay.com">
                        </div>
                        <div class="input-group">
                            <label>Password</label>
                            <input type="password" name="password" required placeholder="••••••••">
                        </div>
                        <div class="input-group">
                            <label>Confirm Password</label>
                            <input type="password" name="confirmpassword" required placeholder="••••••••">
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Create Admin Account</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
    <script src="darkmode.js"></script>
</body>
</html>
