<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
include 'database.php';

// Fetch Statistics
$stats = [
    'users' => 0,
    'saccos' => 0,
    'bookings' => 0,
    'revenue' => 0,
    'trips' => 0,
    'complaints' => 0
];

// Total Users (Customers)
$res = $conn->query("SELECT COUNT(*) as count FROM users WHERE type = 'user'");
if ($res) $stats['users'] = $res->fetch_assoc()['count'];

// Total SACCOs
$res = $conn->query("SELECT COUNT(*) as count FROM saccos");
if ($res) $stats['saccos'] = $res->fetch_assoc()['count'];

// Total Bookings
$res = $conn->query("SELECT COUNT(*) as count FROM tripsessions");
if ($res) $stats['bookings'] = $res->fetch_assoc()['count'];

// Total Revenue
$res = $conn->query("SELECT SUM(fareamount) as total FROM tripsessions WHERE status = 'paid'");
if ($res) $stats['revenue'] = $res->fetch_assoc()['total'] ?? 0;

// Active Trips
$res = $conn->query("SELECT COUNT(*) as count FROM trips WHERE status = 'active'");
if ($res) $stats['trips'] = $res->fetch_assoc()['count'];

// Pending Complaints (assuming a 'complaint' table exists)
$res = $conn->query("SELECT COUNT(*) as count FROM complaint");
if ($res) $stats['complaints'] = $res->fetch_assoc()['count'];

// Recent Bookings
$recent_bookings = $conn->query("
    SELECT ts.*, u.username, b.platenumber 
    FROM tripsessions ts 
    JOIN users u ON ts.userid = u.userid 
    LEFT JOIN buses b ON ts.busid = b.busid 
    ORDER BY ts.createdat DESC 
    LIMIT 10
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | SafiriPay</title>
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
                <li><a href="dashboard.php" class="active"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
                <li><a href="manage_users.php"><i class="fas fa-users-cog"></i> <span>User Management</span></a></li>
                <li><a href="manage_saccos.php"><i class="fas fa-building"></i> <span>SACCO Management</span></a></li>
                <li><a href="manage_routes.php"><i class="fas fa-route"></i> <span>Routes & Stages</span></a></li>
                <li><a href="manage_trips.php"><i class="fas fa-calendar-alt"></i> <span>Trips & Schedules</span></a></li>
                <li><a href="manage_bookings.php"><i class="fas fa-ticket-alt"></i> <span>Bookings</span></a></li>
                <li><a href="manage_payments.php"><i class="fas fa-file-invoice-dollar"></i> <span>Payments</span></a></li>
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
                    <h1>Dashboard Overview</h1>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Welcome back, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></p>
                </div>
                <div class="header-actions">
                    <button class="theme-toggle" id="theme-toggle" title="Toggle Dark Mode">
                        <i class="fas fa-moon"></i>
                    </button>
                    <div class="user-info" style="display: flex; align-items: center; gap: 10px;">
                        <span>Admin</span>
                        <i class="fas fa-user-circle fa-2x"></i>
                    </div>
                </div>
            </header>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Users</h3>
                        <p><?php echo number_format($stats['users']); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bookings">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Bookings</h3>
                        <p><?php echo number_format($stats['bookings']); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon users" style="background: linear-gradient(135deg, #fbbf24, #f59e0b);">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total SACCOs</h3>
                        <p><?php echo number_format($stats['saccos']); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon revenue">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Revenue</h3>
                        <p>KSh <?php echo number_format($stats['revenue'], 0); ?></p>
                    </div>
                </div>
            </div>

            <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">
                 <div class="stat-card">
                    <div class="stat-icon buses">
                        <i class="fas fa-bus"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Active Trips</h3>
                        <p><?php echo number_format($stats['trips']); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon revenue" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Complaints</h3>
                        <p><?php echo number_format($stats['complaints']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="data-card">
                <div class="data-card-header">
                    <h2>Recent Bookings</h2>
                    <a href="manage_bookings.php" style="color: var(--primary); font-size: 0.875rem; font-weight: 600; text-decoration: none;">View All</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Bus</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_bookings && $recent_bookings->num_rows > 0): ?>
                                <?php while($row = $recent_bookings->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                                        <td><?php echo htmlspecialchars($row['platenumber'] ?? 'N/A'); ?></td>
                                        <td style="font-weight: 600;">KSh <?php echo number_format($row['fareamount'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                                <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td style="color: var(--text-muted);"><?php echo date('M d, H:i', strtotime($row['createdat'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align:center;">No recent bookings found.</td>
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
