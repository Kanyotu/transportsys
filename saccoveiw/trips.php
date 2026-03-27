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

// Filter Inputs
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$filter_route = isset($_GET['route_id']) && $_GET['route_id'] !== '' ? intval($_GET['route_id']) : null;
$filter_bus = isset($_GET['bus_id']) && $_GET['bus_id'] !== '' ? intval($_GET['bus_id']) : null;
$filter_driver = isset($_GET['driver_id']) && $_GET['driver_id'] !== '' ? intval($_GET['driver_id']) : null;
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

$where_conditions = ["r.saccoid = $sacco_id"];

if ($search != '') {
    $where_conditions[] = "(r.routename LIKE '%$search%' OR b.platenumber LIKE '%$search%' OR d.dname LIKE '%$search%')";
}

if ($filter_route) $where_conditions[] = "t.routeid = $filter_route";
if ($filter_bus) $where_conditions[] = "t.busid = $filter_bus";
if ($filter_driver) $where_conditions[] = "t.driverid = $filter_driver";

if ($filter_status != 'all') {
    $where_conditions[] = "t.status = '$filter_status'";
}

if ($date_from != '') {
    $where_conditions[] = "t.starttime >= '$date_from 00:00:00'";
}

if ($date_to != '') {
    $where_conditions[] = "t.starttime <= '$date_to 23:59:59'";
}

$where_clause = implode(' AND ', $where_conditions);

// Fetch Trips for this SACCO
$query = "
    SELECT t.*, r.routename, b.platenumber, d.dname 
    FROM trips t 
    JOIN routes r ON t.routeid = r.routeid 
    JOIN buses b ON t.busid = b.busid 
    JOIN drivers d ON t.driverid = d.driverid 
    WHERE $where_clause
    ORDER BY t.starttime DESC
";
$trips = $conn->query($query);

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
            <!-- Filter Section -->
            <div class="data-card" style="margin-bottom: 2rem; padding: 1.5rem 2rem;">
                <form method="GET" style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; align-items: end;">
                        <div class="input-group" style="margin-bottom: 0;">
                            <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem; display: block;">SEARCH</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Route, Plate, Driver...">
                        </div>
                        <div class="input-group" style="margin-bottom: 0;">
                            <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem; display: block;">ROUTE</label>
                            <select name="route_id">
                                <option value="">All Routes</option>
                                <?php $routes_list->data_seek(0); while($r = $routes_list->fetch_assoc()): ?>
                                    <option value="<?php echo $r['routeid']; ?>" <?php echo $filter_route == $r['routeid'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($r['routename']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="input-group" style="margin-bottom: 0;">
                            <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem; display: block;">STATUS</label>
                            <select name="status">
                                <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="active" <?php echo $filter_status == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="completed" <?php echo $filter_status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $filter_status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="input-group" style="margin-bottom: 0;">
                            <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem; display: block;">DATE</label>
                            <input type="date" name="date_from" value="<?php echo $date_from; ?>">
                        </div>
                        <div style="display: flex; gap: 0.5rem; height: 42px;">
                            <button type="submit" class="btn btn-primary" style="flex: 1;">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <a href="trips.php" class="btn" style="background: var(--bg-main); color: var(--text-main); text-decoration: none; display: flex; align-items: center; justify-content: center; width: 42px;">
                                <i class="fas fa-undo"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>

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
