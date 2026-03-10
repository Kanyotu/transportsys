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
$filter = isset($_GET['type']) ? $_GET['type'] : 'all';
$query = "SELECT * FROM users";
if ($filter != 'all') {
    $query .= " WHERE type = '" . $conn->real_escape_string($filter) . "'";
}
$query .= " ORDER BY datejoined DESC";
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
                <li><a href="manage_users.php" class="active"><i class="fas fa-users-cog"></i> <span>User Management</span></a></li>
                <li><a href="manage_saccos.php"><i class="fas fa-building"></i> <span>SACCO Management</span></a></li>
                <li><a href="manage_routes.php"><i class="fas fa-route"></i> <span>Routes & Stages</span></a></li>
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
            <div class="data-card" style="margin-bottom: 2rem; padding: 1rem 2rem;">
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <span style="font-weight: 600; font-size: 0.875rem; color: var(--text-muted);">FILTER BY ROLE:</span>
                    <a href="manage_users.php?type=all" class="status-badge <?php echo $filter == 'all' ? 'status-active' : ''; ?>" style="text-decoration: none; cursor: pointer;">All Users</a>
                    <a href="manage_users.php?type=user" class="status-badge <?php echo $filter == 'user' ? 'status-active' : ''; ?>" style="text-decoration: none; cursor: pointer;">Customers</a>
                    <a href="manage_users.php?type=sacco" class="status-badge <?php echo $filter == 'sacco' ? 'status-active' : ''; ?>" style="text-decoration: none; cursor: pointer;">SACCO Managers</a>
                    <a href="manage_users.php?type=admin" class="status-badge <?php echo $filter == 'admin' ? 'status-active' : ''; ?>" style="text-decoration: none; cursor: pointer;">Admins</a>
                </div>
            </div>

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
