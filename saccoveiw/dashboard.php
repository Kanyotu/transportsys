<?php
session_start();
if(!isset($_SESSION['sacco_manager_id'])) {
    header("Location: login.php");
    exit();
}
include 'database.php';

$sacco_id = $_SESSION['sacco_id'];

// Stats calculations
$total_revenue = $conn->query("SELECT SUM(fareamount) as total FROM tripsessions WHERE saccoid = $sacco_id AND status = 'paid'")->fetch_assoc()['total'] ?? 0;
$active_buses = $conn->query("SELECT COUNT(*) as count FROM buses WHERE saccoid = $sacco_id AND status = 1")->fetch_assoc()['count'];
$today_bookings = $conn->query("SELECT COUNT(*) as count FROM tripsessions WHERE saccoid = $sacco_id AND DATE(createdat) = CURDATE()")->fetch_assoc()['count'];
$pending_trips = $conn->query("SELECT COUNT(*) as count FROM trips t JOIN routes r ON t.routeid = r.routeid WHERE r.saccoid = $sacco_id AND t.status = 'active'")->fetch_assoc()['count'];

// Recent Bookings for this SACCO
$recent_bookings = $conn->query("
    SELECT ts.*, u.username, r.routename 
    FROM tripsessions ts
    JOIN users u ON ts.userid = u.userid
    JOIN trips t ON ts.tripid = t.tripid
    JOIN routes r ON t.routeid = r.routeid
    WHERE ts.saccoid = $sacco_id
    ORDER BY ts.createdat DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SACCO Dashboard | SafiriPay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="sacco.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div class="header-title">
                    <p style="color: var(--text-muted); font-size: 0.875rem; font-weight: 600;">WELCOME BACK, <?php echo strtoupper($_SESSION['manager_username']); ?></p>
                    <h1>Fleet Overview</h1>
                </div>
                <div class="header-actions">
                    <button class="theme-toggle" id="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                    <div style="background: var(--card-bg); padding: 0.5rem 1rem; border-radius: 12px; border: 1px solid var(--border); font-weight: 700; color: var(--primary);">
                        <i class="fas fa-calendar-day"></i> <?php echo date('M d, Y'); ?>
                    </div>
                </div>
            </header>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #0ea5e9, #2563eb);">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Earnings</h3>
                        <p>KSh <?php echo number_format($total_revenue, 0); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                        <i class="fas fa-bus"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Active Fleet</h3>
                        <p><?php echo $active_buses; ?> Buses</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Today's Bookings</h3>
                        <p><?php echo $today_bookings; ?></p>
                    </div>
                </div>
            </div>

            <div class="data-card">
                <div class="data-card-header">
                    <h2>Recent Bookings</h2>
                    <a href="bookings.php" class="btn" style="background: var(--bg-main); color: var(--primary);">View All</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Passenger</th>
                                <th>Route</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $recent_bookings->fetch_assoc()): ?>
                                <tr>
                                    <td style="font-weight: 700;"><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['routename']); ?></td>
                                    <td style="font-weight: 700;">KSh <?php echo number_format($row['fareamount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td style="color: var(--text-muted);"><?php echo date('M d, H:i', strtotime($row['createdat'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script src="darkmode.js"></script>
</body>
</html>
