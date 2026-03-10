<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
include 'database.php';

// Revenue by SACCO
$sacco_revenue = $conn->query("
    SELECT s.sacconame, SUM(ts.fareamount) as total_revenue, COUNT(ts.sessionid) as total_bookings
    FROM saccos s
    LEFT JOIN tripsessions ts ON s.saccoid = ts.saccoid AND ts.status = 'paid'
    GROUP BY s.saccoid
    ORDER BY total_revenue DESC
");

// Daily Revenue (Last 7 Days)
$daily_revenue = $conn->query("
    SELECT DATE(createdat) as date, SUM(amount) as total 
    FROM payments 
    WHERE status = 'success' 
    GROUP BY DATE(createdat) 
    ORDER BY date DESC 
    LIMIT 7
");

// Top Routes
$top_routes = $conn->query("
    SELECT r.routename, COUNT(ts.sessionid) as bookings
    FROM routes r
    JOIN trips t ON r.routeid = t.routeid
    JOIN tripsessions ts ON t.tripid = ts.tripid
    WHERE ts.status = 'paid'
    GROUP BY r.routeid
    ORDER BY bookings DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Reports | SafiriPay Admin</title>
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
                <li><a href="reports.php" class="active"><i class="fas fa-chart-line"></i> <span>Reports</span></a></li>
                <li><a href="addadmin.php"><i class="fas fa-user-shield"></i> <span>Administrators</span></a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div class="header-title">
                    <h1>Analytics & Reports</h1>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Revenue analysis, SACCO performance, and booking trends.</p>
                </div>
                <div class="header-actions">
                    <button class="theme-toggle" id="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                    <button class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>
            </header>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- SACCO Performance -->
                <div class="data-card">
                    <div class="data-card-header">
                        <h2>SACCO Performance</h2>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>SACCO Name</th>
                                    <th>Bookings</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $sacco_revenue->fetch_assoc()): ?>
                                    <tr>
                                        <td style="font-weight: 600;"><?php echo htmlspecialchars($row['sacconame']); ?></td>
                                        <td><?php echo $row['total_bookings']; ?></td>
                                        <td style="font-weight: 700; color: var(--success);">KSh <?php echo number_format($row['total_revenue'], 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Daily Revenue -->
                <div class="data-card">
                    <div class="data-card-header">
                        <h2>Revenue Trends (Last 7 Days)</h2>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $daily_revenue->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                        <td style="font-weight: 700; color: var(--primary);">KSh <?php echo number_format($row['total'], 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Top Routes -->
            <div class="data-card" style="margin-top: 2rem;">
                <div class="data-card-header">
                    <h2>Most Popular Routes</h2>
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 1.5rem; padding-top: 1rem;">
                    <?php while($row = $top_routes->fetch_assoc()): ?>
                        <div style="flex: 1; min-width: 200px; background: var(--bg-main); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border);">
                            <div style="color: var(--text-muted); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 8px;">Route</div>
                            <div style="font-weight: 700; font-size: 1.125rem; margin-bottom: 12px;"><?php echo htmlspecialchars($row['routename']); ?></div>
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <span style="font-size: 0.875rem; color: var(--text-muted);">Total Bookings</span>
                                <span style="font-weight: 800; color: var(--primary);"><?php echo $row['bookings']; ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="darkmode.js"></script>
</body>
</html>
