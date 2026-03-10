<?php
session_start();
if(!isset($_SESSION['sacco_manager_id'])) {
    header("Location: login.php");
    exit();
}
include 'database.php';

$sacco_id = $_SESSION['sacco_id'];

// Total Revenue
$total_revenue = $conn->query("SELECT SUM(fareamount) as total FROM tripsessions WHERE saccoid = $sacco_id AND status = 'paid'")->fetch_assoc()['total'] ?? 0;

// Revenue by Route
$route_earnings = $conn->query("
    SELECT r.routename, SUM(ts.fareamount) as total, COUNT(ts.sessionid) as bookings
    FROM routes r
    JOIN trips t ON r.routeid = t.routeid
    JOIN tripsessions ts ON t.tripid = ts.tripid
    WHERE r.saccoid = $sacco_id AND ts.status = 'paid'
    GROUP BY r.routeid
    ORDER BY total DESC
");

// Daily Earnings (Last 7 Days)
$daily_earnings = $conn->query("
    SELECT DATE(createdat) as date, SUM(fareamount) as total
    FROM tripsessions
    WHERE saccoid = $sacco_id AND status = 'paid'
    GROUP BY DATE(createdat)
    ORDER BY date DESC
    LIMIT 7
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings | SafiriPay SACCO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="sacco.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <header class="header">
                <div class="header-title">
                    <h1>Earnings Analysis</h1>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Track your revenue and financial performance.</p>
                </div>
                <div class="header-actions">
                    <button class="theme-toggle" id="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                    <button class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print"></i> Download Report
                    </button>
                </div>
            </header>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Lifetime Revenue</h3>
                        <p>KSh <?php echo number_format($total_revenue, 2); ?></p>
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem;">
                <!-- Revenue by Route -->
                <div class="data-card">
                    <div class="data-card-header">
                        <h2>Revenue by Route</h2>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Route</th>
                                    <th>Bookings</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $route_earnings->fetch_assoc()): ?>
                                    <tr>
                                        <td style="font-weight: 600;"><?php echo htmlspecialchars($row['routename']); ?></td>
                                        <td><?php echo $row['bookings']; ?></td>
                                        <td style="font-weight: 700; color: var(--success);">KSh <?php echo number_format($row['total'], 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Daily Trends -->
                <div class="data-card">
                    <div class="data-card-header">
                        <h2>Daily Trends</h2>
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
                                <?php while($row = $daily_earnings->fetch_assoc()): ?>
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
        </main>
    </div>
    <script src="darkmode.js"></script>
</body>
</html>
