<?php
session_start();
if(!isset($_SESSION['driver_id'])) {
    header("Location: login.php");
    exit();
}
include 'database.php';

$driver_id = $_SESSION['driver_id'];
$trip_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = "";

if ($trip_id == 0) {
    header("Location: trips.php");
    exit();
}

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    $stmt = $conn->prepare("UPDATE trips SET status = ? WHERE tripid = ? AND driverid = ?");
    $stmt->bind_param("sii", $new_status, $trip_id, $driver_id);
    if($stmt->execute()) {
        $message = "Trip status updated to " . ucfirst($new_status);
    }
}

// Handle Boarding Update
if (isset($_GET['board_session'])) {
    $session_id = intval($_GET['board_session']);
    $stmt = $conn->prepare("UPDATE tripsessions SET boarding_status = 'boarded' WHERE sessionid = ? AND tripid = ?");
    $stmt->bind_param("ii", $session_id, $trip_id);
    if($stmt->execute()) {
        header("Location: trip_details.php?id=$trip_id&msg=Passenger boarded");
        exit();
    }
}

// Handle Issue Report
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['report_issue'])) {
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $stmt = $conn->prepare("INSERT INTO trip_issues (tripid, driverid, description) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $trip_id, $driver_id, $desc);
    if($stmt->execute()) {
        $message = "Issue reported successfully.";
    }
}

if(isset($_GET['msg'])) $message = $_GET['msg'];

// Fetch Trip Details
$trip_sql = "SELECT t.*, r.routename, b.platenumber, b.capacity 
             FROM trips t 
             JOIN routes r ON t.routeid = r.routeid 
             JOIN buses b ON t.busid = b.busid
             WHERE t.tripid = $trip_id AND t.driverid = $driver_id";
$trip_res = $conn->query($trip_sql);
$trip = $trip_res->fetch_assoc();

if (!$trip) {
    echo "Trip not found or access denied.";
    exit();
}

// Fetch Bookings / Passengers
$bookings_sql = "SELECT ts.*, u.username, u.phoneno, s.seatnumber 
                 FROM tripsessions ts 
                 JOIN users u ON ts.userid = u.userid 
                 LEFT JOIN seats s ON ts.seatid = s.seatid
                 WHERE ts.tripid = $trip_id AND ts.status = 'paid'";
$bookings_res = $conn->query($bookings_sql);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip Details | SafiriPay Driver</title>
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
                <li><a href="dashboard.php"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
                <li><a href="trips.php" class="active"><i class="fas fa-map-marked-alt"></i> <span>My Trips</span></a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>

        <main class="main-content">
            <header class="header">
                <div class="header-title">
                    <a href="trips.php" style="color: var(--primary); text-decoration: none; font-weight: 700; margin-bottom: 0.5rem; display: inline-block;">
                        <i class="fas fa-arrow-left"></i> Back to Schedule
                    </a>
                    <h1>Trip Operations</h1>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Manage status and passenger boarding for <?php echo htmlspecialchars($trip['routename']); ?>.</p>
                </div>
                <div class="header-actions">
                    <button class="theme-toggle" id="theme-toggle"><i class="fas fa-moon"></i></button>
                </div>
            </header>

            <?php if($message): ?>
                <div style="background: var(--primary-light); color: var(--primary); padding: 1.25rem; border-radius: var(--radius-md); margin-bottom: 2rem; font-weight: 700; border: 1px solid var(--primary);">
                    <i class="fas fa-info-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: var(--primary);">
                        <i class="fas fa-bus-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Vehicle</h3>
                        <p><?php echo htmlspecialchars($trip['platenumber']); ?></p>
                        <span style="font-size: 0.75rem; color: var(--text-muted);">Capacity: <?php echo $trip['capacity']; ?> seats</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: var(--warning);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Bookings</h3>
                        <p><?php echo $bookings_res->num_rows; ?></p>
                        <span style="font-size: 0.75rem; color: var(--text-muted);">Confirmed Passengers</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: var(--success);">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Trip Status</h3>
                        <p style="text-transform: capitalize;"><?php echo $trip['status']; ?></p>
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                <!-- Passenger List -->
                <div class="data-card">
                    <h2 style="margin-bottom: 2rem;">Passenger List</h2>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Passenger</th>
                                    <th>Seat</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($bookings_res->num_rows > 0): ?>
                                    <?php while($b = $bookings_res->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div style="font-weight: 700;"><?php echo htmlspecialchars($b['username']); ?></div>
                                                <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($b['phoneno']); ?></div>
                                            </td>
                                            <td><span class="status-badge" style="background: var(--primary-light); color: var(--primary);"><?php echo $b['seatnumber'] ?: 'Any'; ?></span></td>
                                            <td>
                                                <?php if($b['boarding_status'] == 'boarded'): ?>
                                                    <span class="status-badge" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">Boarded</span>
                                                <?php else: ?>
                                                    <span class="status-badge" style="background: var(--bg-main); color: var(--text-muted);">Waiting</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($b['boarding_status'] != 'boarded'): ?>
                                                    <a href="?id=<?php echo $trip_id; ?>&board_session=<?php echo $b['sessionid']; ?>" class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.75rem;">
                                                        Confirm Boarding
                                                    </a>
                                                <?php else: ?>
                                                    <i class="fas fa-check-double" style="color: var(--success);"></i>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" style="text-align: center; padding: 2rem; color: var(--text-muted);">No confirmed bookings for this trip.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Controls -->
                <div style="display: flex; flex-direction: column; gap: 2rem;">
                    <!-- Status Controls -->
                    <div class="data-card">
                        <h2 style="margin-bottom: 1.5rem; font-size: 1.25rem;">Trip Control</h2>
                        <form method="POST">
                            <input type="hidden" name="update_status" value="1">
                            <div class="input-group">
                                <label>Change Status</label>
                                <select name="status">
                                    <option value="active" <?php echo $trip['status'] == 'active' ? 'selected' : ''; ?>>Active / Scheduled</option>
                                    <option value="started" <?php echo $trip['status'] == 'started' ? 'selected' : ''; ?>>Trip Started</option>
                                    <option value="completed" <?php echo $trip['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $trip['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%;">Update Status</button>
                        </form>
                    </div>

                    <!-- Issue Reporting -->
                    <div class="data-card">
                        <h2 style="margin-bottom: 1.5rem; font-size: 1.25rem;">Report Issue</h2>
                        <form method="POST">
                            <input type="hidden" name="report_issue" value="1">
                            <div class="input-group">
                                <label>Description</label>
                                <textarea name="description" placeholder="Report delays, mechanical issues, etc." rows="3" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-outline" style="width: 100%; border-color: var(--danger); color: var(--danger);">Report Issue</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="darkmode.js"></script>
</body>
</html>
