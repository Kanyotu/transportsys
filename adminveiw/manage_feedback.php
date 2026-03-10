<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
include 'database.php';

// Fetch Complaints with User details
$complaints = $conn->query("
    SELECT c.*, u.username, u.phoneno 
    FROM complaint c 
    JOIN users u ON c.userid = u.userid 
    ORDER BY c.date DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback & Complaints | SafiriPay Admin</title>
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
                <li><a href="manage_feedback.php" class="active"><i class="fas fa-comment-dots"></i> <span>Feedback & Complaints</span></a></li>
                <li><a href="reports.php"><i class="fas fa-chart-line"></i> <span>Reports</span></a></li>
                <li><a href="addadmin.php"><i class="fas fa-user-shield"></i> <span>Administrators</span></a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div class="header-title">
                    <h1>Feedback & Complaints</h1>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Review and handle customer complaints regarding drivers and vehicles.</p>
                </div>
                <div class="header-actions">
                    <button class="theme-toggle" id="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            </header>

            <!-- Feedback List -->
            <div class="data-card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Date Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($complaints && $complaints->num_rows > 0): ?>
                                <?php while($row = $complaints->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($row['username']); ?></div>
                                            <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($row['phoneno']); ?></div>
                                        </td>
                                        <td>
                                            <span class="status-badge" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; text-transform: uppercase;">
                                                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($row['type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="max-width: 400px; font-size: 0.875rem; color: var(--text-main); line-height: 1.4;">
                                                <?php echo htmlspecialchars($row['description']); ?>
                                            </div>
                                        </td>
                                        <td style="color: var(--text-muted); font-size: 0.8125rem;">
                                            <?php echo date('M d, Y', strtotime($row['date'])); ?><br>
                                            <?php echo date('H:i', strtotime($row['date'])); ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 8px;">
                                                <button title="Mark as Resolved" style="background: none; border: none; color: var(--success); cursor: pointer;"><i class="fas fa-check-double"></i></button>
                                                <button title="Delete" style="background: none; border: none; color: var(--danger); cursor: pointer;"><i class="fas fa-trash-alt"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align:center;">No feedback/complaints found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script src="darkmode.js"></script>
</body>
</html>
