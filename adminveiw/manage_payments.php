<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
include 'database.php';

// Fetch Statistics
$payment_stats = [
    'total_successful' => 0,
    'total_pending' => 0,
    'total_failed' => 0,
    'revenue_sum' => 0
];

$res = $conn->query("SELECT status, COUNT(*) as count, SUM(amount) as sum FROM payments GROUP BY status");
if ($res) {
    while($row = $res->fetch_assoc()) {
        if ($row['status'] == 'success') {
            $payment_stats['total_successful'] = $row['count'];
            $payment_stats['revenue_sum'] = $row['sum'];
        } elseif ($row['status'] == 'pending') {
            $payment_stats['total_pending'] = $row['count'];
        } elseif ($row['status'] == 'failed') {
            $payment_stats['total_failed'] = $row['count'];
        }
    }
}

// Fetch Payments with User and Booking details
$payments = $conn->query("
    SELECT p.*, u.username, ts.sessionid 
    FROM payments p 
    JOIN tripsessions ts ON p.sessionid = ts.sessionid 
    JOIN users u ON ts.userid = u.userid 
    ORDER BY p.createdat DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Monitoring | SafiriPay Admin</title>
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
                <li><a href="manage_payments.php" class="active"><i class="fas fa-file-invoice-dollar"></i> <span>Payments</span></a></li>
                <li><a href="manage_feedback.php"><i class="fas fa-comment-dots"></i> <span>Feedback & Complaints</span></a></li>
                <li><a href="reports.php"><i class="fas fa-chart-line"></i> <span>Reports</span></a></li>
                <li><a href="addadmin.php"><i class="fas fa-user-shield"></i> <span>Administrators</span></a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div class="header-title">
                    <h1>Payment Monitoring</h1>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Track all system transactions and financial records.</p>
                </div>
                <div class="header-actions">
                    <button class="theme-toggle" id="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                    <button class="btn btn-primary" style="padding: 0.6rem 1rem; font-size: 0.875rem;">
                        <i class="fas fa-download"></i> Export CSV
                    </button>
                </div>
            </header>

            <!-- Payment Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon revenue" style="background: var(--success);">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Successful</h3>
                        <p><?php echo $payment_stats['total_successful']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon revenue" style="background: var(--warning);">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Pending</h3>
                        <p><?php echo $payment_stats['total_pending']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon revenue" style="background: var(--primary);">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Revenue</h3>
                        <p>KSh <?php echo number_format($payment_stats['revenue_sum'], 0); ?></p>
                    </div>
                </div>
            </div>

            <!-- Payments Table -->
            <div class="data-card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Customer</th>
                                <th>Booking Ref</th>
                                <th>Amount</th>
                                <th>M-Pesa Receipt</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($payments && $payments->num_rows > 0): ?>
                                <?php while($row = $payments->fetch_assoc()): ?>
                                    <tr>
                                        <td style="font-weight: 700;">#TX-<?php echo $row['paymentid']; ?></td>
                                        <td>
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($row['username']); ?></div>
                                            <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($row['phoneno']); ?></div>
                                        </td>
                                        <td>
                                            <a href="manage_bookings.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">BK-<?php echo $row['sessionid']; ?></a>
                                        </td>
                                        <td style="font-weight: 700;">KSh <?php echo number_format($row['amount'], 2); ?></td>
                                        <td>
                                            <code style="background: var(--bg-main); padding: 4px 8px; border-radius: 4px;"><?php echo htmlspecialchars($row['mpesareceipt'] ?: 'N/A'); ?></code>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                                <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td style="color: var(--text-muted); font-size: 0.8125rem;">
                                            <?php echo date('M d, Y', strtotime($row['createdat'])); ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align:center;">No transactions found.</td>
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
