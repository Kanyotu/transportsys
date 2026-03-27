<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
include 'database.php';

// Handle User Deactivation/Activation
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    $status = ($action == 'activate') ? 1 : 0;
    
    // Check if status column exists or just run it (gracefully handling error for now)
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE userid = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $status, $id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: manage_users.php");
    exit();
}

// Fetch Users with Filters
$filter_role = isset($_GET['type']) ? $_GET['type'] : 'all';
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

$where_conditions = ["1=1"];

if ($filter_role != 'all') {
    $where_conditions[] = "type = '" . $conn->real_escape_string($filter_role) . "'";
}

if ($filter_status != 'all') {
    $status_val = ($filter_status == 'active') ? 1 : 0;
    $where_conditions[] = "status = $status_val";
}

if ($search != '') {
    $where_conditions[] = "(username LIKE '%$search%' OR email LIKE '%$search%' OR phoneno LIKE '%$search%')";
}

if ($date_from != '') {
    $where_conditions[] = "datejoined >= '$date_from 00:00:00'";
}

if ($date_to != '') {
    $where_conditions[] = "datejoined <= '$date_to 23:59:59'";
}

$where_clause = implode(' AND ', $where_conditions);
$query = "SELECT * FROM users WHERE $where_clause ORDER BY datejoined DESC";
$users = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | SafiriPay Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar (Reused) -->
        <div class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-bus-alt"></i>
                <span>SafiriPay</span>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
                <li><a href="manage_users.php" class="active"><i class="fas fa-users-cog"></i> <span>People</span></a></li>
                <li><a href="manage_saccos.php"><i class="fas fa-building"></i> <span>Sacco List</span></a></li>
                <li><a href="manage_routes.php"><i class="fas fa-route"></i> <span>Routes & Stages</span></a></li>
                <li><a href="manage_trips.php"><i class="fas fa-calendar-alt"></i> <span>Trips & Schedules</span></a></li>
                <li><a href="manage_bookings.php"><i class="fas fa-ticket-alt"></i> <span>Bookings</span></a></li>
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
                    <h1>User Management</h1>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Manage customers, SACCO managers, and administrators.</p>
                </div>
                <div class="header-actions">
                    <button class="theme-toggle" id="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                    <a href="addadmin.php" class="btn btn-primary" style="text-decoration: none; padding: 0.6rem 1rem; font-size: 0.875rem;">
                        <i class="fas fa-user-plus"></i> Add Admin
                    </a>
                </div>
            </header>

            <!-- Filters -->
            <div class="data-card" style="margin-bottom: 2rem; padding: 1.5rem 2rem;">
                <form method="GET" style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                        <div class="input-group" style="margin-bottom: 0;">
                            <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem; display: block;">SEARCH USERS</label>
                            <div style="position: relative;">
                                <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, Email or Phone..." style="padding-left: 35px; width: 100%;">
                            </div>
                        </div>
                        <div class="input-group" style="margin-bottom: 0;">
                            <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem; display: block;">STATUS</label>
                            <select name="status">
                                <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="active" <?php echo $filter_status == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $filter_status == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="input-group" style="margin-bottom: 0;">
                            <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem; display: block;">FROM DATE</label>
                            <input type="date" name="date_from" value="<?php echo $date_from; ?>">
                        </div>
                        <div class="input-group" style="margin-bottom: 0;">
                            <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem; display: block;">TO DATE</label>
                            <input type="date" name="date_to" value="<?php echo $date_to; ?>">
                        </div>
                        <div style="display: flex; gap: 0.5rem;">
                            <button type="submit" class="btn btn-primary" style="flex: 1; height: 42px;">
                                <i class="fas fa-filter"></i> Apply
                            </button>
                            <a href="manage_users.php" class="btn" style="background: var(--border); color: var(--text-main); text-decoration: none; display: flex; align-items: center; justify-content: center; width: 42px; height: 42px;">
                                <i class="fas fa-undo"></i>
                            </a>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; align-items: center; border-top: 1px solid var(--border); pt: 1rem; padding-top: 1rem;">
                        <span style="font-weight: 600; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Role:</span>
                        <input type="hidden" name="type" id="role-filter" value="<?php echo $filter_role; ?>">
                        <div style="display: flex; gap: 0.5rem;">
                            <button type="button" onclick="setRole('all')" class="status-badge <?php echo $filter_role == 'all' ? 'status-active' : ''; ?>" style="border: none; cursor: pointer; background: <?php echo $filter_role == 'all' ? 'var(--primary)' : 'var(--bg-main)'; ?>; color: <?php echo $filter_role == 'all' ? '#fff' : 'var(--text-main)'; ?>;">All</button>
                            <button type="button" onclick="setRole('user')" class="status-badge <?php echo $filter_role == 'user' ? 'status-active' : ''; ?>" style="border: none; cursor: pointer; background: <?php echo $filter_role == 'user' ? 'var(--primary)' : 'var(--bg-main)'; ?>; color: <?php echo $filter_role == 'user' ? '#fff' : 'var(--text-main)'; ?>;">Customers</button>
                            <button type="button" onclick="setRole('sacco')" class="status-badge <?php echo $filter_role == 'sacco' ? 'status-active' : ''; ?>" style="border: none; cursor: pointer; background: <?php echo $filter_role == 'sacco' ? 'var(--primary)' : 'var(--bg-main)'; ?>; color: <?php echo $filter_role == 'sacco' ? '#fff' : 'var(--text-main)'; ?>;">SACCOs</button>
                            <button type="button" onclick="setRole('admin')" class="status-badge <?php echo $filter_role == 'admin' ? 'status-active' : ''; ?>" style="border: none; cursor: pointer; background: <?php echo $filter_role == 'admin' ? 'var(--primary)' : 'var(--bg-main)'; ?>; color: <?php echo $filter_role == 'admin' ? '#fff' : 'var(--text-main)'; ?>;">Admins</button>
                        </div>
                    </div>
                </form>
            </div>

            <script>
            function setRole(role) {
                document.getElementById('role-filter').value = role;
                document.querySelector('form').submit();
            }
            </script>

            <!-- Users Table -->
            <div class="data-card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Contact Info</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Date Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($users && $users->num_rows > 0): ?>
                                <?php while($row = $users->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 12px;">
                                                <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--sidebar-hover); display: flex; align-items: center; justify-content: center; font-weight: 700; color: #fff;">
                                                    <?php echo strtoupper(substr($row['username'], 0, 1)); ?>
                                                </div>
                                                <div style="font-weight: 600;"><?php echo htmlspecialchars($row['username']); ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-size: 0.875rem;"><?php echo htmlspecialchars($row['phoneno']); ?></div>
                                            <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($row['email'] ?: 'No email'); ?></div>
                                        </td>
                                        <td>
                                            <span style="text-transform: capitalize; font-weight: 500; font-size: 0.875rem;">
                                                <?php echo $row['type'] ?: 'User'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $is_active = !isset($row['status']) || $row['status'] == 1;
                                            ?>
                                            <span class="status-badge <?php echo $is_active ? 'status-active' : 'status-deactivated'; ?>">
                                                <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                                                <?php echo $is_active ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td style="color: var(--text-muted); font-size: 0.875rem;">
                                            <?php echo date('M d, Y', strtotime($row['datejoined'])); ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 8px;">
                                                <button title="Edit User" style="background: none; border: none; color: var(--primary); cursor: pointer;"><i class="fas fa-edit"></i></button>
                                                <?php if($is_active): ?>
                                                    <a href="manage_users.php?action=deactivate&id=<?php echo $row['userid']; ?>" title="Deactivate" style="color: var(--danger);"><i class="fas fa-user-slash"></i></a>
                                                <?php else: ?>
                                                    <a href="manage_users.php?action=activate&id=<?php echo $row['userid']; ?>" title="Activate" style="color: var(--success);"><i class="fas fa-user-check"></i></a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align:center;">No users found.</td>
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
