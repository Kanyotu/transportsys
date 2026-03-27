<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
include 'database.php';

// Handle Cancellation
if (isset($_GET['action']) && $_GET['action'] == 'cancel' && isset($_GET['id'])) {
    $sessionid = intval($_GET['id']);
    $stmt = $conn->prepare("UPDATE tripsessions SET status = 'cancelled' WHERE sessionid = ?");
    if ($stmt) {
        $stmt->bind_param("i", $sessionid);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: manage_bookings.php");
    exit();
}

// Get Filter Inputs
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$filter_driver = isset($_GET['driver_id']) && $_GET['driver_id'] !== '' ? intval($_GET['driver_id']) : null;
$filter_plate = isset($_GET['plate']) && $_GET['plate'] !== '' ? $conn->real_escape_string($_GET['plate']) : null;
$filter_min_seats = isset($_GET['min_seats']) && $_GET['min_seats'] !== '' ? intval($_GET['min_seats']) : null;
$filter_status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : 'all';

// Base conditions for filtering
$where_conditions = ["1=1"];
if ($search != '') {
    $where_conditions[] = "(u.username LIKE '%$search%' OR u.phoneno LIKE '%$search%' OR r.routename LIKE '%$search%')";
}
if ($filter_driver) $where_conditions[] = "t.driverid = $filter_driver";
if ($filter_plate) $where_conditions[] = "b.platenumber = '$filter_plate'";
if ($filter_status != 'all') {
    $where_conditions[] = "ts.status = '$filter_status'";
}
$where_clause = implode(' AND ', $where_conditions);

// Fetch Bookings with Full Details and Occupancy
$query = "
    SELECT ts.*, u.username, u.phoneno, r.routename, b.platenumber, s.seatnumber, b.capacity, d.dname,
           (SELECT COUNT(*) FROM tripsessions WHERE tripid = ts.tripid AND status != 'cancelled') as booked_seats
    FROM tripsessions ts
    JOIN users u ON ts.userid = u.userid
    JOIN trips t ON ts.tripid = t.tripid
    JOIN routes r ON t.routeid = r.routeid
    LEFT JOIN buses b ON ts.busid = b.busid
    LEFT JOIN drivers d ON t.driverid = d.driverid
    LEFT JOIN seats s ON ts.seatid = s.seatid
    WHERE $where_clause
";

if ($filter_min_seats !== null) {
    $query .= " HAVING (capacity - booked_seats) >= $filter_min_seats";
}

$query .= " ORDER BY ts.createdat DESC";
$bookings = $conn->query($query);

// Data for filters
$all_drivers = $conn->query("SELECT driverid, dname FROM drivers ORDER BY dname");
$all_plates = $conn->query("SELECT DISTINCT platenumber FROM buses WHERE platenumber IS NOT NULL ORDER BY platenumber");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Management | SafiriPay Admin</title>
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
                <li><a href="manage_users.php"><i class="fas fa-users-cog"></i> <span>People</span></a></li>
                <li><a href="manage_saccos.php"><i class="fas fa-building"></i> <span>Sacco List</span></a></li>
                <li><a href="manage_routes.php"><i class="fas fa-route"></i> <span>Routes & Stages</span></a></li>
                <li><a href="manage_trips.php"><i class="fas fa-calendar-alt"></i> <span>Trips & Schedules</span></a></li>
                <li><a href="manage_bookings.php" class="active"><i class="fas fa-ticket-alt"></i> <span>Bookings</span></a></li>
                <li><a href="manage_payments.php"><i class="fas fa-file-invoice-dollar"></i> <span>Money</span></a></li>
                <li><a href="manage_feedback.php"><i class="fas fa-comment-dots"></i> <span>Talk</span></a></li>
                <li><a href="reports.php"><i class="fas fa-chart-line"></i> <span>Reports</span></a></li>
                <li><a href="addadmin.php"><i class="fas fa-user-shield"></i> <span>Administrators</span></a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div class="header-title">
                    <h1>Booking Management</h1>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Monitor trip sessions, seat reservations, and booking statuses.</p>
                </div>
                <div class="header-actions">
                    <button class="theme-toggle" id="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            </header>

            <!-- Filter Section -->
            <div class="data-card" style="margin-bottom: 2rem; padding: 1.5rem 2rem;">
                <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.5rem; align-items: end;">
                    <div class="input-group" style="margin-bottom: 0;">
                        <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem; display: block;">SEARCH</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="User, Phone, Route...">
                    </div>
                    <div class="input-group" style="margin-bottom: 0;">
                        <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem; display: block;">DRIVER</label>
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
                        <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem; display: block;">STATUS</label>
                        <select name="status">
                            <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="confirmed" <?php echo $filter_status == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="cancelled" <?php echo $filter_status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="completed" <?php echo $filter_status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    <div style="display: flex; gap: 0.5rem; height: 42px;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="manage_bookings.php" class="btn" style="background: var(--border); color: var(--text-main); text-decoration: none; display: flex; align-items: center; justify-content: center; width: 42px;">
                            <i class="fas fa-undo"></i>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Bookings Table -->
            <div class="data-card">
                <div class="data-card-header">
                    <h2>All Bookings</h2>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Trip & Bus</th>
                                <th>Seat/Fare</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($bookings && $bookings->num_rows > 0): ?>
                                <?php while($row = $bookings->fetch_assoc()): ?>
                                    <tr>
                                        <td style="font-weight: 700;">#BK-<?php echo $row['sessionid']; ?></td>
                                        <td>
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($row['username']); ?></div>
                                            <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($row['phoneno']); ?></div>
                                        </td>
                                        <td>
                                            <div style="font-size: 0.875rem; font-weight: 600;"><?php echo htmlspecialchars($row['routename']); ?></div>
                                            <div style="font-size: 0.75rem; color: var(--text-muted);"><i class="fas fa-bus"></i> <?php echo htmlspecialchars($row['platenumber'] ?: 'N/A'); ?></div>
                                            <div style="font-size: 0.75rem; color: var(--text-muted);"><i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($row['dname'] ?: 'N/A'); ?></div>
                                            <div style="font-size: 0.75rem; font-weight: 600; color: var(--primary);">
                                                <i class="fas fa-users"></i> Booked: <?php echo $row['booked_seats']; ?>/<?php echo $row['capacity'] ?: '14'; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-weight: 700; color: var(--primary);">KSh <?php echo number_format($row['fareamount'], 2); ?></div>
                                            <div style="font-size: 0.75rem; color: var(--text-muted);">Seat: <?php echo htmlspecialchars($row['seatnumber'] ?: 'TBD'); ?></div>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                                <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td style="font-size: 0.8125rem; color: var(--text-muted);">
                                            <?php echo date('M d, Y', strtotime($row['createdat'])); ?><br>
                                            <?php echo date('H:i', strtotime($row['createdat'])); ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 8px;">
                                                <button title="View Full Details" style="background: none; border: none; color: var(--primary); cursor: pointer;"><i class="fas fa-info-circle"></i></button>
                                                <?php if($row['status'] != 'cancelled'): ?>
                                                    <a href="manage_bookings.php?action=cancel&id=<?php echo $row['sessionid']; ?>" title="Cancel Booking" style="color: var(--danger);" onclick="return confirm('Are you sure you want to cancel this booking?')"><i class="fas fa-window-close"></i></a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align:center;">No bookings found.</td>
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
