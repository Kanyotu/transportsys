<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
include 'database.php';

// Handle Trip Addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_trip'])) {
    $routeid = intval($_POST['routeid']);
    $busid = intval($_POST['busid']);
    $driverid = intval($_POST['driverid']);
    $starttime = $_POST['starttime'];
    $trip_type = $_POST['trip_type'];
    
    $stmt = $conn->prepare("INSERT INTO trips (routeid, busid, driverid, starttime, trip_type, status) VALUES (?, ?, ?, ?, ?, 'active')");
    if ($stmt) {
        $stmt->bind_param("iiiss", $routeid, $busid, $driverid, $starttime, $trip_type);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: manage_trips.php");
    exit();
}

// Fetch Trips
$trips = $conn->query("
    SELECT t.*, r.routename, b.platenumber, d.dname 
    FROM trips t 
    JOIN routes r ON t.routeid = r.routeid 
    LEFT JOIN buses b ON t.busid = b.busid 
    LEFT JOIN drivers d ON t.driverid = d.driverid 
    ORDER BY t.starttime DESC
");

// Fetch dropdown data
$routes_list = $conn->query("SELECT routeid, routename FROM routes");
$buses_list = $conn->query("SELECT busid, platenumber FROM buses WHERE status = 1");
$drivers_list = $conn->query("SELECT driverid, dname FROM drivers");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip Management | SafiriPay Admin</title>
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
                <li><a href="manage_trips.php" class="active"><i class="fas fa-calendar-alt"></i> <span>Trips & Schedules</span></a></li>
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
                    <h1>Trips & Schedules</h1>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Coordinate vehicle assignments and departure schedules.</p>
                </div>
                <div class="header-actions">
                    <button class="theme-toggle" id="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                    <button class="btn btn-primary" onclick="document.getElementById('addTripModal').style.display='flex'">
                        <i class="fas fa-calendar-plus"></i> Schedule Trip
                    </button>
                </div>
            </header>

            <!-- Trips List -->
            <div class="data-card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Trip Info</th>
                                <th>Route</th>
                                <th>Vehicle & Driver</th>
                                <th>Status</th>
                                <th>Departure</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($trips && $trips->num_rows > 0): ?>
                                <?php while($row = $trips->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 700; color: var(--text-main);">TRP-<?php echo $row['tripid']; ?></div>
                                            <span class="status-badge" style="background:rgba(59, 130, 246, 0.1); color:var(--primary); font-size:0.7rem;">
                                                <?php echo strtoupper($row['trip_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($row['routename']); ?></div>
                                        </td>
                                        <td>
                                            <div style="display: flex; flex-direction: column; gap: 2px;">
                                                <span style="font-size: 0.875rem; font-weight: 600;"><i class="fas fa-bus"></i> <?php echo htmlspecialchars($row['platenumber'] ?: 'N/A'); ?></span>
                                                <span style="font-size: 0.75rem; color: var(--text-muted);"><i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($row['dname'] ?: 'No Driver'); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                                <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600;"><?php echo date('H:i', strtotime($row['starttime'])); ?></div>
                                            <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo date('M d, Y', strtotime($row['starttime'])); ?></div>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 8px;">
                                                <button title="Edit" style="background: none; border: none; color: var(--primary); cursor: pointer;"><i class="fas fa-edit"></i></button>
                                                <button title="Cancel Trip" style="background: none; border: none; color: var(--danger); cursor: pointer;"><i class="fas fa-ban"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align:center;">No trips scheduled.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Schedule Trip Modal -->
            <div id="addTripModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
                <div class="card" style="width:450px; padding:2rem;">
                    <h2 style="margin-bottom:1.5rem;">Schedule New Trip</h2>
                    <form method="POST">
                        <div class="input-group">
                            <label>Route</label>
                            <select name="routeid" required>
                                <option value="">Select Route</option>
                                <?php $routes_list->data_seek(0); while($r = $routes_list->fetch_assoc()): ?>
                                    <option value="<?php echo $r['routeid']; ?>"><?php echo htmlspecialchars($r['routename']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                            <div class="input-group">
                                <label>Bus</label>
                                <select name="busid" required>
                                    <option value="">Select Bus</option>
                                    <?php while($b = $buses_list->fetch_assoc()): ?>
                                        <option value="<?php echo $b['busid']; ?>"><?php echo htmlspecialchars($b['platenumber']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="input-group">
                                <label>Driver</label>
                                <select name="driverid" required>
                                    <option value="">Select Driver</option>
                                    <?php while($d = $drivers_list->fetch_assoc()): ?>
                                        <option value="<?php echo $d['driverid']; ?>"><?php echo htmlspecialchars($d['dname']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="input-group">
                            <label>Departure Time</label>
                            <input type="datetime-local" name="starttime" required>
                        </div>
                        <div class="input-group">
                            <label>Trip Type</label>
                            <select name="trip_type" required>
                                <option value="short">Short Distance</option>
                                <option value="long">Long Distance</option>
                            </select>
                        </div>
                        <div style="display:flex; gap:10px; margin-top:1rem;">
                            <button type="submit" name="add_trip" class="btn btn-primary" style="flex:1;">Schedule</button>
                            <button type="button" onclick="document.getElementById('addTripModal').style.display='none'" class="btn" style="flex:1; background:var(--border);">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    <script src="darkmode.js"></script>
</body>
</html>
