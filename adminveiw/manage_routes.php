<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
include 'database.php';

// Handle Route Addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_route'])) {
    $routename = $_POST['routename'];
    $saccoid = intval($_POST['saccoid']);
    
    $stmt = $conn->prepare("INSERT INTO routes (routename, saccoid) VALUES (?, ?)");
    if ($stmt) {
        $stmt->bind_param("si", $routename, $saccoid);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: manage_routes.php");
    exit();
}

// Fetch Routes with SACCO info
$routes = $conn->query("
    SELECT r.*, s.sacconame 
    FROM routes r 
    LEFT JOIN saccos s ON r.saccoid = s.saccoid 
    ORDER BY r.createdat DESC
");

// Fetch SACCOs for the selection dropdown
$saccos_list = $conn->query("SELECT saccoid, sacconame FROM saccos WHERE status = 1");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Route Management | SafiriPay Admin</title>
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
                <li><a href="manage_routes.php" class="active"><i class="fas fa-route"></i> <span>Routes & Stages</span></a></li>
                <li><a href="manage_trips.php"><i class="fas fa-calendar-alt"></i> <span>Trips & Schedules</span></a></li>
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
                    <h1>Routes & Stages</h1>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Define transport paths and intermediate pickup points.</p>
                </div>
                <div class="header-actions">
                    <button class="theme-toggle" id="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                    <button class="btn btn-primary" onclick="document.getElementById('addRouteModal').style.display='flex'">
                        <i class="fas fa-plus"></i> New Route
                    </button>
                </div>
            </header>

            <!-- Routes List -->
            <div class="data-card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Route Name</th>
                                <th>Assigned SACCO</th>
                                <th>Total Stages</th>
                                <th>Date Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($routes && $routes->num_rows > 0): ?>
                                <?php while($row = $routes->fetch_assoc()): 
                                    $route_id = $row['routeid'];
                                    $stage_count_res = $conn->query("SELECT COUNT(*) as count FROM stages WHERE routeid = $route_id");
                                    $stage_count = $stage_count_res ? $stage_count_res->fetch_assoc()['count'] : 0;
                                ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 700; color: var(--text-main);"><?php echo htmlspecialchars($row['routename']); ?></div>
                                            <div style="font-size: 0.75rem; color: var(--text-muted);">ID: #RT-<?php echo $row['routeid']; ?></div>
                                        </td>
                                        <td>
                                            <span style="font-weight: 500;"><?php echo htmlspecialchars($row['sacconame'] ?: 'Unassigned'); ?></span>
                                        </td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <i class="fas fa-map-marker-alt" style="color: var(--primary);"></i>
                                                <span style="font-weight: 600;"><?php echo $stage_count; ?> Stages</span>
                                            </div>
                                        </td>
                                        <td style="color: var(--text-muted); font-size: 0.875rem;">
                                            <?php echo date('M d, Y', strtotime($row['createdat'])); ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 10px;">
                                                <button title="Edit Route" style="background: none; border: none; color: var(--primary); cursor: pointer;"><i class="fas fa-edit"></i></button>
                                                <button title="Manage Stages" style="background: none; border: none; color: var(--success); cursor: pointer;"><i class="fas fa-layer-group"></i></button>
                                                <button title="Delete" style="background: none; border: none; color: var(--danger); cursor: pointer;"><i class="fas fa-trash-alt"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align:center;">No routes found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Simple "Modal" for Adding Route (CSS-only toggle for basic demo) -->
            <div id="addRouteModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
                <div class="card" style="width:400px; padding:2rem;">
                    <h2 style="margin-bottom:1.5rem;">Add New Route</h2>
                    <form method="POST">
                        <div class="input-group">
                            <label>Route Name (e.g. Nairobi - Murang'a)</label>
                            <input type="text" name="routename" required>
                        </div>
                        <div class="input-group">
                            <label>Assign SACCO</label>
                            <select name="saccoid" required>
                                <option value="">Select SACCO</option>
                                <?php while($s = $saccos_list->fetch_assoc()): ?>
                                    <option value="<?php echo $s['saccoid']; ?>"><?php echo htmlspecialchars($s['sacconame']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div style="display:flex; gap:10px; margin-top:1rem;">
                            <button type="submit" name="add_route" class="btn btn-primary" style="flex:1;">Create Route</button>
                            <button type="button" onclick="document.getElementById('addRouteModal').style.display='none'" class="btn" style="flex:1; background:var(--border);">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    <script src="darkmode.js"></script>
</body>
</html>
