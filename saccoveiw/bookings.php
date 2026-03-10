<?php
session_start();
if(!isset($_SESSION['sacco_manager_id'])) {
    header("Location: login.php");
    exit();
}
include 'database.php';

$sacco_id = $_SESSION['sacco_id'];

// Fetch Bookings for this SACCO
$bookings = $conn->query("
    SELECT ts.*, u.username, u.phoneno, r.routename, b.platenumber, t.starttime
    FROM tripsessions ts
    JOIN users u ON ts.userid = u.userid
    JOIN trips t ON ts.tripid = t.tripid
    JOIN routes r ON t.routeid = r.routeid
    JOIN buses b ON t.busid = b.busid
    WHERE ts.saccoid = $sacco_id
    ORDER BY ts.createdat DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings | SafiriPay SACCO</title>
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
                    <h1>Booking Management</h1>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Monitor passenger reservations and seat availability.</p>
                </div>
                <div class="header-actions">
                    <button class="theme-toggle" id="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            </header>

            <div class="data-card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Passenger</th>
                                <th>Trip Details</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $bookings->fetch_assoc()): ?>
                                <tr>
                                    <td style="font-weight: 700;">#BK-<?php echo $row['sessionid']; ?></td>
                                    <td>
                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($row['username']); ?></div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($row['phoneno']); ?></div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($row['routename']); ?></div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($row['platenumber']); ?> @ <?php echo date('H:i', strtotime($row['starttime'])); ?></div>
                                    </td>
                                    <td style="font-weight: 700; color: var(--primary);">KSh <?php echo number_format($row['fareamount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td style="color: var(--text-muted); font-size: 0.8125rem;">
                                        <?php echo date('M d, Y', strtotime($row['createdat'])); ?>
                                    </td>
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
