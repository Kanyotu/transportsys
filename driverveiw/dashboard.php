<?php
session_start();
if(!isset($_SESSION['driver_id'])) {
    header("Location: login.php");
    exit();
}
include 'database.php';

$driver_id = $_SESSION['driver_id'];
$driver_name = $_SESSION['driver_name'];

// Fetch Driver's assigned Bus
$bus_sql = "SELECT b.*, s.sacconame FROM buses b JOIN saccos s ON b.saccoid = s.saccoid WHERE b.driverid = $driver_id LIMIT 1";
$bus_res = $conn->query($bus_sql);
$bus = $bus_res->fetch_assoc();

// Fetch Current/Active Trip
$active_trip_sql = "SELECT t.*, r.routename 
                    FROM trips t 
                    JOIN routes r ON t.routeid = r.routeid 
                    WHERE t.driverid = $driver_id AND t.status IN ('active', 'started')
                    ORDER BY t.starttime ASC LIMIT 1";
$active_trip_res = $conn->query($active_trip_sql);
$active_trip = $active_trip_res->fetch_assoc();

// Trip Counts
$stats = $conn->query("SELECT 
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
    COUNT(CASE WHEN status = 'active' OR status = 'started' THEN 1 END) as pending
    FROM trips WHERE driverid = $driver_id")->fetch_assoc();

$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_email'])) {
    $new_email = mysqli_real_escape_string($conn, $_POST['email']);
    $stmt = $conn->prepare("UPDATE drivers SET email = ? WHERE driverid = ?");
    $stmt->bind_param("si", $new_email, $driver_id);
    if($stmt->execute()) {
        $message = "Email successfully updated!";
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard | SafiriPay</title>
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
                <li><a href="dashboard.php" class="active"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
                <li><a href="trips.php"><i class="fas fa-map-marked-alt"></i> <span>My Trips</span></a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>

        <main class="main-content">
            <header class="header">
                <div class="header-title">
                    <div class="driver-profile-header">
                        <div class="profile-avatar">
                            <?php echo strtoupper(substr($driver_name, 0, 1)); ?>
                        </div>
                        <div>
                            <h1>Hello, <?php echo htmlspecialchars($driver_name); ?></h1>
                            <p style="color: var(--text-muted); font-size: 0.95rem;">Operational status: <span style="color: var(--success); font-weight: 700;">Ready for Service</span></p>
                        </div>
                    </div>
                </div>
                <div class="header-actions">
                    <button class="theme-toggle" id="theme-toggle"><i class="fas fa-moon"></i></button>
                </div>
            </header>

            <?php if ($message): ?>
                <div style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1.25rem; border-radius: var(--radius-md); margin-bottom: 2rem; font-weight: 700; border: 1px solid rgba(16, 185, 129, 0.2);">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: var(--primary);">
                        <i class="fas fa-bus"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Your Vehicle</h3>
                        <p><?php echo $bus ? htmlspecialchars($bus['platenumber']) : 'Not Assigned'; ?></p>
                        <span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo $bus ? htmlspecialchars($bus['sacconame']) : 'Please contact SACCO'; ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: var(--success);">
                        <i class="fas fa-route"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Trips Today</h3>
                        <p><?php echo $stats['pending'] + $stats['completed']; ?></p>
                        <span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo $stats['completed']; ?> completed</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: var(--sidebar-bg);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Next Trip</h3>
                        <p><?php echo $active_trip ? date('H:i', strtotime($active_trip['starttime'])) : '--:--'; ?></p>
                        <span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo $active_trip ? htmlspecialchars($active_trip['routename']) : 'No upcoming trips'; ?></span>
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 2.5rem;">
                <div class="data-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                        <h2 style="font-size: 1.5rem;">Current Activity</h2>
                        <a href="trips.php" style="color: var(--primary); text-decoration: none; font-weight: 700; font-size: 0.9rem;">View All Trips <i class="fas fa-arrow-right"></i></a>
                    </div>

                    <?php if($active_trip): ?>
                        <div style="background: var(--bg-main); padding: 2.5rem; border-radius: var(--radius-lg); border: 2px dashed var(--primary); text-align: center;">
                            <i class="fas fa-map-marker-alt" style="font-size: 3rem; color: var(--primary); margin-bottom: 1.5rem; display: block;"></i>
                            <h3 style="font-size: 1.75rem; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($active_trip['routename']); ?></h3>
                            <p style="color: var(--text-muted); margin-bottom: 2rem;">Status: <span style="color: var(--primary); font-weight: 800; text-transform: uppercase;"><?php echo $active_trip['status']; ?></span></p>
                            
                            <a href="trip_details.php?id=<?php echo $active_trip['tripid']; ?>" class="btn btn-primary" style="padding: 1rem 2.5rem; font-size: 1.1rem;">
                                Start Operations <i class="fas fa-play-circle" style="margin-left: 10px;"></i>
                            </a>
                        </div>
                    <?php else: ?>
                        <div style="padding: 4rem; text-align: center; color: var(--text-muted);">
                            <i class="fas fa-coffee" style="font-size: 4rem; margin-bottom: 1.5rem; display: block; opacity: 0.1;"></i>
                            <p style="font-size: 1.1rem;">No active trips assigned. Take a break!</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="data-card">
                    <h2 style="font-size: 1.25rem; margin-bottom: 1.5rem;">Quick Operations</h2>
                    <ul style="list-style: none; display: flex; flex-direction: column; gap: 1rem;">
                        <li>
                            <button class="btn btn-outline" style="width: 100%; justify-content: flex-start; border-color: var(--border); color: var(--text-main);">
                                <i class="fas fa-qrcode" style="color: var(--primary);"></i> Scan Passenger Ticket
                            </button>
                        </li>
                        <li>
                            <button class="btn btn-outline" style="width: 100%; justify-content: flex-start; border-color: var(--border); color: var(--text-main);">
                                <i class="fas fa-gas-pump" style="color: var(--warning);"></i> Log Fuel Consumption
                            </button>
                        </li>
                    </ul>

                    <div style="margin-top: 2rem; padding: 1.5rem; background: var(--primary-light); border-radius: var(--radius-md); border-left: 4px solid var(--primary);">
                        <h4 style="color: var(--primary); margin-bottom: 0.5rem;"><i class="fas fa-lightbulb"></i> Driver Tip</h4>
                        <p style="font-size: 0.85rem; color: var(--text-main);">Confirm boarding for every passenger to ensure accurate manifests.</p>
                    </div>

                    <!-- Email Update Feature -->
                    <div style="margin-top: 2.5rem; border-top: 1px solid var(--border); padding-top: 2rem;">
                        <h2 style="font-size: 1.25rem; margin-bottom: 1rem;">Profile Settings</h2>
                        <form method="POST">
                            <input type="hidden" name="update_email" value="1">
                            <div class="input-group">
                                <label>Email Address</label>
                                <input type="email" name="email" value="<?php 
                                    $email_check = $conn->query("SELECT email FROM drivers WHERE driverid = $driver_id")->fetch_assoc();
                                    echo htmlspecialchars($email_check['email'] ?: ''); 
                                ?>" placeholder="Add your email" required style="font-size: 0.85rem;">
                            </div>
                            <button type="submit" class="btn btn-outline" style="width: 100%; font-size: 0.8rem;">Update Email</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="darkmode.js"></script>
</body>
</html>
