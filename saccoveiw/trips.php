<?php
session_start();
if(!isset($_SESSION['sacco_manager_id'])) {
    header("Location: login.php");
    exit();
}
include 'database.php';

$sacco_id = $_SESSION['sacco_id'];
$message = "";

// Handle Trip Addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_trip'])) {
    $routeid = intval($_POST['routeid']);
    $busid = intval($_POST['busid']);
    $driverid = intval($_POST['driverid']);
    $starttime = $_POST['starttime'];
    $trip_type = $_POST['trip_type'];
    
    $stmt = $conn->prepare("INSERT INTO trips (routeid, busid, driverid, starttime, status, trip_type) VALUES (?, ?, ?, ?, 'active', ?)");
    $stmt->bind_param("iiiss", $routeid, $busid, $driverid, $starttime, $trip_type);
    
    if($stmt->execute()) {
        $message = "Trip scheduled successfully!";
    } else {
        $message = "Error scheduling trip.";
    }
}

// Fetch Trips for this SACCO
$trips = $conn->query("
    SELECT t.*, r.routename, b.platenumber, d.dname 
    FROM trips t 
    JOIN routes r ON t.routeid = r.routeid 
    JOIN buses b ON t.busid = b.busid 
    JOIN drivers d ON t.driverid = d.driverid 
    WHERE r.saccoid = $sacco_id
    ORDER BY t.starttime DESC
");

// Fetch Routes, Buses, and Drivers for dropdowns
$routes_list = $conn->query("SELECT * FROM routes WHERE saccoid = $sacco_id");
$buses_list = $conn->query("SELECT * FROM buses WHERE saccoid = $sacco_id AND status = 1");
$drivers_list = $conn->query("SELECT * FROM drivers");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedules | SafiriPay SACCO</title>
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
                    <h1>Trip Schedules</h1>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Manage your departures and assignments.</p>
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

            <?php if($message): ?>
                <div style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 12px; margin-bottom: 2rem; font-weight: 600;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="data-card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Departure</th>
                                <th>Route</th>
                                <th>Vehicle & Driver</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $trips->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 700;"><?php echo date('H:i', strtotime($row['starttime'])); ?></div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo date('M d, Y', strtotime($row['starttime'])); ?></div>
                                    </td>
                                    <td style="font-weight: 600;"><?php echo htmlspecialchars($row['routename']); ?></td>
                                    <td>
                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($row['platenumber']); ?></div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($row['dname']); ?></div>
                                    </td>
                                    <td>
                                        <span class="status-badge" style="background: rgba(14, 165, 233, 0.1); color: var(--primary);">
                                            <?php echo strtoupper($row['trip_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn" style="padding: 0.5rem; background: var(--bg-main); color: var(--danger);"><i class="fas fa-ban"></i></button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Schedule Trip Modal -->
            <div id="addTripModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
                <div class="data-card" style="width:450px;">
                    <h2 style="margin-bottom:1.5rem;">Schedule New Trip</h2>
                    <form method="POST">
                        <div class="input-group">
                            <label>Select Route</label>
                            <select name="routeid" required>
                                <?php while($r = $routes_list->fetch_assoc()): ?>
                                    <option value="<?php echo $r['routeid']; ?>"><?php echo htmlspecialchars($r['routename']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="input-group">
                            <label>Assign Vehicle</label>
                            <select name="busid" required>
                                <?php while($b = $buses_list->fetch_assoc()): ?>
                                    <option value="<?php echo $b['busid']; ?>"><?php echo htmlspecialchars($b['platenumber']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="input-group">
                            <label>Assign Driver</label>
                            <select name="driverid" required>
                                <?php while($d = $drivers_list->fetch_assoc()): ?>
                                    <option value="<?php echo $d['driverid']; ?>"><?php echo htmlspecialchars($d['dname']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="input-group">
                            <label>Departure Time</label>
                            <input type="datetime-local" name="starttime" required>
                        </div>
                        <div class="input-group">
                            <label>Trip Type</label>
                            <select name="trip_type">
                                <option value="short">Short Distance</option>
                                <option value="long">Long Distance</option>
                            </select>
                        </div>
                        <div style="display:flex; gap:10px; margin-top:1rem;">
                            <button type="submit" name="add_trip" class="btn btn-primary" style="flex:1;">Schedule Trip</button>
                            <button type="button" onclick="document.getElementById('addTripModal').style.display='none'" class="btn" style="flex:1; background:var(--bg-main);">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    <script src="darkmode.js"></script>
</body>
</html>
