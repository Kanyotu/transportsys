<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
include 'database.php';

// Get Filter Inputs
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$filter_sacco = isset($_GET['sacco_id']) && $_GET['sacco_id'] !== '' ? intval($_GET['sacco_id']) : null;
$filter_bus = isset($_GET['bus_id']) && $_GET['bus_id'] !== '' ? intval($_GET['bus_id']) : null;

// Base conditions for filtering
$conditions = ["ts.status = 'paid'", "DATE(ts.createdat) BETWEEN '$start_date' AND '$end_date'"];
if ($filter_sacco) $conditions[] = "ts.saccoid = $filter_sacco";
if ($filter_bus) $conditions[] = "ts.busid = $filter_bus";
$where_clause = implode(' AND ', $conditions);

// Revenue by SACCO
$sacco_revenue_query = "
    SELECT s.saccoid, s.sacconame, SUM(ts.fareamount) as total_revenue, COUNT(ts.sessionid) as total_bookings
    FROM saccos s
    LEFT JOIN tripsessions ts ON s.saccoid = ts.saccoid AND $where_clause
    GROUP BY s.saccoid
    ORDER BY total_revenue DESC
";
$sacco_revenue = $conn->query($sacco_revenue_query);

// Daily Revenue
$daily_revenue_query = "
    SELECT DATE(ts.createdat) as date, SUM(ts.fareamount) as total 
    FROM tripsessions ts
    WHERE $where_clause
    GROUP BY DATE(ts.createdat) 
    ORDER BY date DESC
";
$daily_revenue = $conn->query($daily_revenue_query);

// Top Routes
$top_routes_query = "
    SELECT r.routename, COUNT(ts.sessionid) as bookings
    FROM routes r
    JOIN trips t ON r.routeid = t.routeid
    JOIN tripsessions ts ON t.tripid = ts.tripid
    WHERE $where_clause
    GROUP BY r.routeid
    ORDER BY bookings DESC
    LIMIT 5
";
$top_routes = $conn->query($top_routes_query);

// Data for filters
$all_saccos = $conn->query("SELECT saccoid, sacconame FROM saccos ORDER BY sacconame");
$all_buses = $conn->query("SELECT busid, platenumber FROM buses ORDER BY platenumber");

// Fetch Vehicle Performance if SACCO is selected
$vehicle_performance = null;
if ($filter_sacco) {
    $vehicle_perf_query = "
        SELECT b.platenumber, b.busid, 
               (SELECT COUNT(*) FROM tripsessions ts WHERE ts.busid = b.busid AND ts.status = 'paid' AND DATE(ts.createdat) BETWEEN '$start_date' AND '$end_date') as bookings,
               (SELECT IFNULL(SUM(fareamount), 0) FROM tripsessions ts WHERE ts.busid = b.busid AND ts.status = 'paid' AND DATE(ts.createdat) BETWEEN '$start_date' AND '$end_date') as revenue,
               (SELECT COUNT(*) FROM complaint c WHERE c.platenumber = b.platenumber) as review_count
        FROM buses b
        WHERE b.saccoid = $filter_sacco
        ORDER BY revenue DESC
    ";
    $vehicle_performance = $conn->query($vehicle_perf_query);
}

// Fetch individual review descriptions if a specific bus is selected OR for the whole SACCO
$selected_bus_reviews = null;
if ($filter_sacco) {
    $bus_detail_where = "b.saccoid = $filter_sacco";
    if ($filter_bus) $bus_detail_where .= " AND b.busid = $filter_bus";
    
    $bus_reviews_query = "
        SELECT c.*, u.username, b.platenumber
        FROM complaint c
        JOIN buses b ON c.platenumber = b.platenumber
        JOIN users u ON c.userid = u.userid
        WHERE $bus_detail_where
        ORDER BY c.date DESC
        LIMIT 20
    ";
    $selected_bus_reviews = $conn->query($bus_reviews_query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Reports | SafiriPay Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
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
                <li><a href="manage_trips.php"><i class="fas fa-calendar-alt"></i> <span>Trips & Schedules</span></a></li>
                <li><a href="manage_bookings.php"><i class="fas fa-ticket-alt"></i> <span>Bookings</span></a></li>
                <li><a href="manage_payments.php"><i class="fas fa-file-invoice-dollar"></i> <span>Payments</span></a></li>
                <li><a href="manage_feedback.php"><i class="fas fa-comment-dots"></i> <span>Feedback & Complaints</span></a></li>
                <li><a href="reports.php" class="active"><i class="fas fa-chart-line"></i> <span>Reports</span></a></li>
                <li><a href="addadmin.php"><i class="fas fa-user-shield"></i> <span>Administrators</span></a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div class="header-title">
                    <h1>Analytics & Reports</h1>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Revenue analysis, SACCO performance, and booking trends.</p>
                </div>
                <div class="header-actions">
                    <button class="theme-toggle" id="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                    <button class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>
            </header>

            <!-- Filter Section -->
            <div class="data-card animate__animated animate__fadeInDown" style="margin-bottom: 2rem;">
                <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; align-items: end;">
                    <div class="input-group" style="margin-bottom: 0;">
                        <label>Start Date</label>
                        <input type="date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="input-group" style="margin-bottom: 0;">
                        <label>End Date</label>
                        <input type="date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="input-group" style="margin-bottom: 0;">
                        <label>SACCO</label>
                        <select name="sacco_id">
                            <option value="">All SACCOs</option>
                            <?php 
                            $all_saccos->data_seek(0);
                            while($s = $all_saccos->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $s['saccoid']; ?>" <?php echo $filter_sacco == $s['saccoid'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($s['sacconame']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="input-group" style="margin-bottom: 0;">
                        <label>Vehicle</label>
                        <select name="bus_id">
                            <option value="">All Vehicles</option>
                            <?php 
                            $all_buses->data_seek(0);
                            while($b = $all_buses->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $b['busid']; ?>" <?php echo $filter_bus == $b['busid'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($b['platenumber']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div style="display: flex; gap: 0.5rem; height: 48px;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <i class="fas fa-filter"></i> Apply
                        </button>
                        <a href="reports.php" class="btn" style="background: var(--border); color: var(--text-main); text-decoration: none; display: flex; align-items: center; justify-content: center; width: 48px;">
                            <i class="fas fa-undo"></i>
                        </a>
                    </div>
                </form>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- SACCO Performance -->
                <div class="data-card animate__animated animate__fadeInLeft">
                    <div class="data-card-header">
                        <h2>SACCO Performance</h2>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>SACCO Name</th>
                                    <th>Bookings</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $sacco_revenue->data_seek(0);
                                while($row = $sacco_revenue->fetch_assoc()): 
                                ?>
                                    <tr style="cursor: pointer; transition: all 0.2s;" onclick="window.location.href='reports.php?sacco_id=<?php echo $row['saccoid']; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>'">
                                        <td style="font-weight: 700; color: var(--primary);">
                                            <i class="fas fa-arrow-right" style="font-size: 0.7rem; margin-right: 8px; opacity: 0.5;"></i>
                                            <?php echo htmlspecialchars($row['sacconame']); ?>
                                        </td>
                                        <td><?php echo number_format($row['total_bookings']); ?></td>
                                        <td style="font-weight: 700; color: var(--success);">KSh <?php echo number_format($row['total_revenue'], 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Daily Revenue -->
                <div class="data-card animate__animated animate__fadeInRight">
                    <div class="data-card-header">
                        <h2>Revenue Trends</h2>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $daily_revenue->data_seek(0);
                                while($row = $daily_revenue->fetch_assoc()): 
                                ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                        <td style="font-weight: 700; color: var(--primary);">KSh <?php echo number_format($row['total'], 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Vehicle Drill-down Section (New) -->
            <?php if ($filter_sacco && $vehicle_performance): ?>
                <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 2rem; margin-top: 2rem;">
                    <div class="data-card animate__animated animate__fadeInUp">
                        <div class="data-card-header">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 48px; height: 48px; border-radius: 12px; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                                    <i class="fas fa-bus"></i>
                                </div>
                                <div>
                                    <h2 style="margin: 0;">Vehicle Performance</h2>
                                    <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">Detailed reports for individual units.</p>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table style="border-spacing: 0 10px;">
                                <thead>
                                    <tr>
                                        <th>Bus Plate</th>
                                        <th>Bookings</th>
                                        <th>Revenue</th>
                                        <th>Reviews</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($v = $vehicle_performance->fetch_assoc()): ?>
                                        <tr style="background: var(--bg-main); transition: all 0.2s;">
                                            <td style="font-weight: 800; color: var(--text-main);">
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <i class="fas fa-hashtag" style="font-size: 0.7rem; opacity: 0.4;"></i>
                                                    <code><?php echo htmlspecialchars($v['platenumber']); ?></code>
                                                </div>
                                            </td>
                                            <td style="font-weight: 700;"><?php echo number_format($v['bookings']); ?></td>
                                            <td style="font-weight: 800; color: var(--success);">KSh <?php echo number_format($v['revenue'], 0); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $v['review_count'] > 0 ? 'status-pending' : 'status-active'; ?>" style="font-size: 0.7rem;">
                                                    <i class="fas fa-comment-alt"></i> <?php echo $v['review_count']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="vehicle_analytics.php?sacco_id=<?php echo $filter_sacco; ?>&bus_id=<?php echo $v['busid']; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" target="_blank" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.75rem; border-radius: 10px;">
                                                    <i class="fas fa-chart-bar"></i> Analytics
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="data-card animate__animated animate__fadeInUp animate__delay-1s">
                        <div class="data-card-header">
                            <h2>Complaint Feed</h2>
                            <p style="font-size: 0.8rem; color: var(--text-muted);">
                                <?php echo $filter_bus ? "Filtered by vehicle: " . $filter_bus : "Recent reports for this SACCO"; ?>
                            </p>
                        </div>
                        <div style="max-height: 480px; overflow-y: auto; padding-right: 10px;">
                            <?php if ($selected_bus_reviews && $selected_bus_reviews->num_rows > 0): ?>
                                <?php while($rev = $selected_bus_reviews->fetch_assoc()): ?>
                                    <div style="background: var(--bg-main); padding: 1.25rem; border-radius: 14px; border: 1px solid var(--border); margin-bottom: 1rem; transition: transform 0.2s;" onmouseover="this.style.transform='translateX(5px)'" onmouseout="this.style.transform='translateX(0)'">
                                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                            <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-main);"><?php echo htmlspecialchars($rev['username']); ?></div>
                                            <div style="font-size: 0.7rem; color: var(--text-muted);"><?php echo date('M d, H:i', strtotime($rev['date'])); ?></div>
                                        </div>
                                        <div style="font-size: 0.85rem; line-height: 1.5; color: var(--text-muted); margin-bottom: 10px;">
                                            "<?php echo htmlspecialchars($rev['description']); ?>"
                                        </div>
                                        <div style="display: flex; gap: 10px; align-items: center;">
                                            <span style="font-size: 0.7rem; font-weight: 700; color: var(--primary); text-transform: uppercase;">
                                                <i class="fas fa-tag"></i> <?php echo $rev['type']; ?>
                                            </span>
                                            <span style="font-size: 0.7rem; color: var(--text-muted); background: var(--border); padding: 2px 6px; border-radius: 4px;">
                                                <?php echo $rev['platenumber']; ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div style="text-align: center; padding: 3rem 1rem; opacity: 0.5;">
                                    <i class="fas fa-check-circle" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                                    <p>No reviews found for this selection.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Top Routes -->
            <div class="data-card animate__animated animate__fadeInUp" style="margin-top: 2rem;">
                <div class="data-card-header">
                    <h2>Most Popular Routes</h2>
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 1.5rem; padding-top: 1rem;">
                    <?php 
                    $top_routes->data_seek(0);
                    while($row = $top_routes->fetch_assoc()): 
                    ?>
                        <div style="flex: 1; min-width: 250px; background: var(--bg-main); padding: 1.5rem; border-radius: 20px; border: 1px solid var(--border); transition: all 0.3s ease;" onmouseover="this.style.borderColor='var(--primary)'; this.style.transform='translateY(-5px)'" onmouseout="this.style.borderColor='var(--border)'; this.style.transform='translateY(0)'">
                            <div style="color: var(--text-muted); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 8px;">Route Performance</div>
                            <div style="font-weight: 800; font-size: 1.25rem; margin-bottom: 12px; color: var(--text-main);"><?php echo htmlspecialchars($row['routename']); ?></div>
                            <div style="display: flex; align-items: center; justify-content: space-between; background: var(--card-bg); padding: 10px 15px; border-radius: 12px; border: 1px solid var(--border);">
                                <span style="font-size: 0.875rem; color: var(--text-muted); font-weight: 600;">Total Bookings</span>
                                <span style="font-weight: 800; color: var(--primary); font-size: 1.125rem;"><?php echo number_format($row['bookings']); ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="darkmode.js"></script>
</body>
</html>
