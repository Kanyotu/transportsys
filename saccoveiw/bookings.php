<?php
session_start();
if(!isset($_SESSION['sacco_manager_id'])) {
    header("Location: login.php");
    exit();
}
include 'database.php';

$sacco_id = $_SESSION['sacco_id'];

// Get Filter Inputs
$filter_driver = isset($_GET['driver_id']) && $_GET['driver_id'] !== '' ? intval($_GET['driver_id']) : null;
$filter_plate = isset($_GET['plate']) && $_GET['plate'] !== '' ? $conn->real_escape_string($_GET['plate']) : null;
$filter_min_seats = isset($_GET['min_seats']) && $_GET['min_seats'] !== '' ? intval($_GET['min_seats']) : null;

// Base conditions for filtering
$where_conditions = ["ts.saccoid = $sacco_id"];
if ($filter_driver) $where_conditions[] = "t.driverid = $filter_driver";
if ($filter_plate) $where_conditions[] = "b.platenumber = '$filter_plate'";
$where_clause = implode(' AND ', $where_conditions);

// Fetch Bookings for this SACCO with Occupancy and Filters
$query = "
    SELECT ts.*, u.username, u.phoneno, r.routename, b.platenumber, t.starttime, b.capacity, d.dname,
           (SELECT COUNT(*) FROM tripsessions WHERE tripid = ts.tripid AND status != 'cancelled') as booked_seats
    FROM tripsessions ts
    JOIN users u ON ts.userid = u.userid
    JOIN trips t ON ts.tripid = t.tripid
    JOIN routes r ON t.routeid = r.routeid
    JOIN buses b ON t.busid = b.busid
    LEFT JOIN drivers d ON t.driverid = d.driverid
    WHERE $where_clause
";

if ($filter_min_seats !== null) {
    $query .= " HAVING (capacity - booked_seats) >= $filter_min_seats";
}

$query .= " ORDER BY ts.createdat DESC";
$bookings = $conn->query($query);

// Data for filters
$all_drivers = $conn->query("SELECT driverid, dname FROM drivers WHERE saccoid = $sacco_id ORDER BY dname");
$all_plates = $conn->query("SELECT platenumber FROM buses WHERE saccoid = $sacco_id AND platenumber IS NOT NULL ORDER BY platenumber");
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

            <!-- Filter Section -->
            <div class="data-card" style="margin-bottom: 2rem;">
                <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.5rem; align-items: end;">
                    <div class="input-group" style="margin-bottom: 0;">
                        <label>Driver</label>
                        <select name="driver_id">
                            <option value="">All Drivers</option>
                            <?php while($d = $all_drivers->fetch_assoc()): ?>
                                <option value="<?php echo $d['driverid']; ?>" <?php echo $filter_driver == $d['driverid'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($d['dname']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="input-group" style="margin-bottom: 0;">
                        <label>Vehicle (Plate)</label>
                        <select name="plate">
                            <option value="">All Vehicles</option>
                            <?php while($p = $all_plates->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($p['platenumber']); ?>" <?php echo $filter_plate == $p['platenumber'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p['platenumber']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="input-group" style="margin-bottom: 0;">
                        <label>Min Available Seats</label>
                        <input type="number" name="min_seats" value="<?php echo $filter_min_seats; ?>" min="0" placeholder="e.g. 5">
                    </div>
                    <div style="display: flex; gap: 0.5rem; height: 48px;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="bookings.php" class="btn" style="background: var(--border); color: var(--text-main); text-decoration: none; display: flex; align-items: center; justify-content: center; width: 48px;">
                            <i class="fas fa-undo"></i>
                        </a>
                    </div>
                </form>
            </div>

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
                                        <div style="font-size: 0.75rem; color: var(--text-muted);"><i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($row['dname'] ?: 'N/A'); ?></div>
                                        <div style="font-size: 0.75rem; font-weight: 600; color: var(--primary);">
                                            <i class="fas fa-users"></i> Booked: <?php echo $row['booked_seats']; ?>/<?php echo $row['capacity'] ?: '14'; ?>
                                        </div>
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
