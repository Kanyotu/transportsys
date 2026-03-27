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

            <!-- Simplified Action Grid -->
            <div class="action-grid">
                <a href="earnings.php" class="action-card money">
                    <i class="fas fa-wallet"></i>
                    <h2>My Money</h2>
                    <p>See how much you earned</p>
                </a>
                
                <a href="bookings.php" class="action-card">
                    <i class="fas fa-ticket-alt"></i>
                    <h2>Bookings</h2>
                    <p>People who want to travel</p>
                </a>

                <a href="trips.php" class="action-card">
                    <i class="fas fa-road"></i>
                    <h2>Today's Work</h2>
                    <p>Manage trips and buses</p>
                </a>

                <a href="manage_drivers.php" class="action-card">
                    <i class="fas fa-users"></i>
                    <h2>Drivers</h2>
                    <p>Add or remove drivers</p>
                </a>

                <a href="vehicles.php" class="action-card">
                    <i class="fas fa-bus"></i>
                    <h2>Buses</h2>
                    <p>Manage your fleet</p>
                </a>

                <a href="reviews.php" class="action-card">
                    <i class="fas fa-comment-dots"></i>
                    <h2>Feedback</h2>
                    <p>What people are saying</p>
                </a>
            </div>

            <!-- Quick Stats in a simple row below -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Money Today</h3>
                        <p>KSh <?php echo number_format($total_revenue, 0); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Active Buses</h3>
                        <p><?php echo $active_buses; ?></p>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="darkmode.js"></script>
</body>
</html>
