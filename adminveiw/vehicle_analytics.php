<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
include 'database.php';

$bus_id = isset($_GET['bus_id']) ? intval($_GET['bus_id']) : 0;
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

if ($bus_id <= 0) {
    die("Invalid Vehicle ID");
}

// Fetch Bus Info
$bus_query = "SELECT b.*, s.sacconame FROM buses b JOIN saccos s ON b.saccoid = s.saccoid WHERE b.busid = $bus_id";
$bus_res = $conn->query($bus_query);
$bus = $bus_res->fetch_assoc();

if (!$bus) {
    die("Vehicle not found");
}

// Fetch Trip & Passenger Details
$analytics_query = "
    SELECT ts.*, u.username, u.phoneno, r.routename, t.departuretime, t.tripdate
    FROM tripsessions ts
    JOIN users u ON ts.userid = u.userid
    JOIN trips t ON ts.tripid = t.tripid
    JOIN routes r ON t.routeid = r.routeid
    WHERE ts.busid = $bus_id AND ts.status = 'paid' 
    AND DATE(ts.createdat) BETWEEN '$start_date' AND '$end_date'
    ORDER BY ts.createdat DESC
";
$analytics = $conn->query($analytics_query);

// Summary Stats
$stats = [
    'total_passengers' => 0,
    'total_revenue' => 0,
    'unique_trips' => 0
];
$trips_seen = [];

if ($analytics && $analytics->num_rows > 0) {
    while($row = $analytics->fetch_assoc()) {
        $stats['total_passengers']++;
        $stats['total_revenue'] += $row['fareamount'];
        if (!in_array($row['tripid'], $trips_seen)) {
            $trips_seen[] = $row['tripid'];
            $stats['unique_trips']++;
        }
    }
    $analytics->data_seek(0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Analytics <?php echo isset($bus) ? '| ' . $bus['platenumber'] : ''; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .analytics-header {
            background: linear-gradient(135deg, var(--sidebar-bg), #1e293b);
            color: white;
            padding: 3rem 2.5rem;
            border-radius: 30px;
            margin-bottom: 2.5rem;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }
        .analytics-header::after {
            content: '\f207';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: -20px;
            bottom: -20px;
            font-size: 15rem;
            opacity: 0.05;
            transform: rotate(-15deg);
        }
        .badge-plate {
            background: var(--primary);
            color: white;
            padding: 0.5rem 1.25rem;
            border-radius: 12px;
            font-weight: 800;
            font-family: 'Outfit', sans-serif;
            letter-spacing: 0.05em;
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3);
        }
        @media print {
            .btn, .no-print {
                display: none !important;
            }
            body {
                padding: 0 !important;
                background: white !important;
            }
            .analytics-header {
                background: white !important;
                color: black !important;
                padding: 1rem 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                border-bottom: 2px solid #eee !important;
                margin-bottom: 2rem !important;
            }
            .analytics-header::after {
                display: none;
            }
            .stat-card {
                padding: 1rem !important;
                border: 1px solid #eee !important;
                box-shadow: none !important;
                background: transparent !important;
            }
            .stat-icon {
                display: none !important;
            }
            .data-card {
                box-shadow: none !important;
                border: 1px solid #eee !important;
                background: transparent !important;
            }
            .badge-plate {
                background: #eee !important;
                color: black !important;
                border: 1px solid #ccc !important;
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body style="padding: 2.5rem; max-width: 1400px; margin: 0 auto;">
    <?php if (isset($error_msg)): ?>
        <div class="data-card animate__animated animate__shakeX" style="border-left: 5px solid var(--danger); padding: 2rem; text-align: center;">
            <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: var(--danger); margin-bottom: 1.5rem;"></i>
            <h2 style="color: var(--text-main);">Oops! Something went wrong.</h2>
            <p style="color: var(--text-muted); margin-bottom: 2rem;"><?php echo htmlspecialchars($error_msg); ?></p>
            <button class="btn btn-primary" onclick="window.location.reload()">
                <i class="fas fa-sync"></i> Try Again
            </button>
        </div>
    <?php else: ?>
    <div class="animate__animated animate__fadeIn">
        <header class="analytics-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 1rem;">
                        <span class="badge-plate"><?php echo $bus['platenumber']; ?></span>
                        <span style="opacity: 0.7; font-weight: 600;">• <?php echo htmlspecialchars($bus['sacconame']); ?></span>
                    </div>
                    <h1 style="font-size: 2.5rem; margin-bottom: 0.5rem;">Vehicle Trip Analytics</h1>
                    <p style="opacity: 0.8;">Detailed performance report from <strong><?php echo date('M d, Y', strtotime($start_date)); ?></strong> to <strong><?php echo date('M d, Y', strtotime($end_date)); ?></strong></p>
                </div>
                <div class="no-print" style="text-align: right;">
                    <button class="btn btn-primary" onclick="window.print()" style="padding: 0.8rem 1.5rem; font-weight: 700;">
                        <i class="fas fa-print" style="margin-right: 8px;"></i> Print Report
                    </button>
                </div>
            </div>
        </header>

        <!-- Stats Grid -->
        <div class="stats-grid animate__animated animate__fadeInUp" style="margin-bottom: 2.5rem;">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                    <i class="fas fa-route"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Trips</h3>
                    <p><?php echo $stats['unique_trips']; ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3>Passengers</h3>
                    <p><?php echo number_format($stats['total_passengers']); ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--primary), var(--primary-alt));">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Revenue</h3>
                    <p>KSh <?php echo number_format($stats['total_revenue'], 0); ?></p>
                </div>
            </div>
        </div>

        <!-- Detailed Table -->
        <div class="data-card animate__animated animate__fadeInUp animate__delay-1s">
            <div class="data-card-header">
                <h2>Journey & Passenger Log</h2>
                <div style="font-size: 0.875rem; color: var(--text-muted);">
                    Showing <?php echo $analytics ? $analytics->num_rows : 0; ?> total boardings
                </div>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Customer Name</th>
                            <th>Contact</th>
                            <th>Route Taken</th>
                            <th>Trip Time</th>
                            <th>Amount Paid</th>
                            <th>Transaction Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($analytics && $analytics->num_rows > 0): ?>
                            <?php while($row = $analytics->fetch_assoc()): ?>
                                <tr>
                                    <td style="font-weight: 700; color: var(--text-main);">
                                        <?php echo htmlspecialchars($row['username']); ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 8px; color: var(--text-muted); font-size: 0.85rem;">
                                            <i class="fas fa-phone-alt" style="font-size: 0.7rem;"></i>
                                            <?php echo htmlspecialchars($row['phoneno']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: var(--primary); font-size: 0.9rem;">
                                            <?php echo htmlspecialchars($row['routename']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.85rem; font-weight: 600;">
                                            <?php echo date('M d, Y', strtotime($row['tripdate'])); ?>
                                            <span style="opacity: 0.6; font-weight: 400;">@ <?php echo date('H:i', strtotime($row['departuretime'])); ?></span>
                                        </div>
                                    </td>
                                    <td style="font-weight: 800; font-size: 1.1rem; color: var(--success);">
                                        KSh <?php echo number_format($row['fareamount'], 2); ?>
                                    </td>
                                    <td style="color: var(--text-muted); font-size: 0.8rem;">
                                        <?php echo date('M d, H:i', strtotime($row['createdat'])); ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 5rem;">
                                    <i class="fas fa-history" style="font-size: 4rem; opacity: 0.1; margin-bottom: 1.5rem;"></i>
                                    <h3 style="color: var(--text-muted);">No activity recorded for this period</h3>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <script src="darkmode.js"></script>
</body>
</html>
