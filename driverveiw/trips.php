<?php
session_start();
if(!isset($_SESSION['driver_id'])) {
    header("Location: login.php");
    exit();
}
include 'database.php';

$driver_id = $_SESSION['driver_id'];
$driver_name = $_SESSION['driver_name'];

// Fetch All Trips for this driver
$trips_sql = "SELECT t.*, r.routename, b.platenumber 
              FROM trips t 
              JOIN routes r ON t.routeid = r.routeid 
              JOIN buses b ON t.busid = b.busid
              WHERE t.driverid = $driver_id 
              ORDER BY t.starttime DESC";
$trips_res = $conn->query($trips_sql);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Trips | SafiriPay Driver</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="driver.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-id-card"></i>
                <span>SafiriPay Driver</span>
            </div>
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php">
                        <i class="fas fa-th-large"></i> <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="trips.php" class="active">
                        <i class="fas fa-map-marked-alt"></i> <span>My Trips</span>
                    </a>
                </li>
                <li>
                    <a href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>

        <main class="main-content">
            <header class="header">
                <div class="header-title">
                    <h1>My Trips</h1>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">View your full schedule and trip history.</p>
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
                                <th>Route</th>
                                <th>Vehicle</th>
                                <th>Departure Time</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($trips_res->num_rows > 0): ?>
                                <?php while($trip = $trips_res->fetch_assoc()): ?>
                                    <tr>
                                        <td style="font-weight: 700;"><?php echo htmlspecialchars($trip['routename']); ?></td>
                                        <td><span style="font-weight: 600;"><?php echo htmlspecialchars($trip['platenumber']); ?></span></td>
                                        <td><?php echo date('M d, Y - H:i', strtotime($trip['starttime'])); ?></td>
                                        <td><span style="text-transform: capitalize;"><?php echo $trip['trip_type']; ?></span></td>
                                        <td>
                                            <?php 
                                            $status_class = '';
                                            if($trip['status'] == 'active') $status_class = 'var(--success)';
                                            else if($trip['status'] == 'completed') $status_class = 'var(--primary)';
                                            else $status_class = 'var(--text-muted)';
                                            ?>
                                            <span class="status-badge" style="background: <?php echo $status_class; ?>22; color: <?php echo $status_class; ?>;">
                                                <?php echo ucfirst($trip['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="trip_details.php?id=<?php echo $trip['tripid']; ?>" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.8rem;">
                                                Manage Trip
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 4rem; color: var(--text-muted);">
                                        <i class="fas fa-calendar-times" style="font-size: 3rem; margin-bottom: 1rem; display: block; opacity: 0.2;"></i>
                                        No trips assigned to you yet.
                                    </td>
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
