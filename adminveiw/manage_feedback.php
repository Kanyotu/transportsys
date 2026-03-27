<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
include 'database.php';

// Filter Inputs
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$filter_type = isset($_GET['type']) ? $_GET['type'] : 'all';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

$where_conditions = ["1=1"];

if ($search != '') {
    $where_conditions[] = "(u.username LIKE '%$search%' OR u.phoneno LIKE '%$search%' OR c.description LIKE '%$search%' OR c.platenumber LIKE '%$search%')";
}

if ($filter_type != 'all') {
    $where_conditions[] = "c.type = '$filter_type'";
}

if ($date_from != '') {
    $where_conditions[] = "c.date >= '$date_from 00:00:00'";
}

if ($date_to != '') {
    $where_conditions[] = "c.date <= '$date_to 23:59:59'";
}

$where_clause = implode(' AND ', $where_conditions);

// Fetch Complaints with User details
$query = "
    SELECT c.*, u.username, u.phoneno 
    FROM complaint c 
    JOIN users u ON c.userid = u.userid 
    WHERE $where_clause
    ORDER BY c.date DESC
";
$complaints = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback & Complaints | SafiriPay Admin</title>
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
                    <h1>Feedback & Complaints</h1>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Review and handle customer complaints regarding drivers and vehicles.</p>
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
                        <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem; display: block;">SEARCH FEEDBACK</label>
                        <div style="position: relative;">
                            <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="User, Phone, Content..." style="padding-left: 35px; width: 100%;">
                        </div>
                    </div>
                    <div class="input-group" style="margin-bottom: 0;">
                        <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem; display: block;">COMPLAINT TYPE</label>
                        <select name="type">
                            <option value="all" <?php echo $filter_type == 'all' ? 'selected' : ''; ?>>All Types</option>
                            <option value="car" <?php echo $filter_type == 'car' ? 'selected' : ''; ?>>Vehicle Issues</option>
                            <option value="driver" <?php echo $filter_type == 'driver' ? 'selected' : ''; ?>>Driver Issues</option>
                        </select>
                    </div>
                    <div class="input-group" style="margin-bottom: 0;">
                        <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem; display: block;">DATE FROM</label>
                        <input type="date" name="date_from" value="<?php echo $date_from; ?>">
                    </div>
                    <div style="display: flex; gap: 0.5rem; height: 42px;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="manage_feedback.php" class="btn" style="background: var(--border); color: var(--text-main); text-decoration: none; display: flex; align-items: center; justify-content: center; width: 42px;">
                            <i class="fas fa-undo"></i>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Feedback List -->
            <div class="data-card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Date Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($complaints && $complaints->num_rows > 0): ?>
                                <?php while($row = $complaints->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($row['username']); ?></div>
                                            <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($row['phoneno']); ?></div>
                                        </td>
                                        <td>
                                            <span class="status-badge" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; text-transform: uppercase;">
                                                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($row['type']); ?>
                                            </span>
                                            <?php if (!empty($row['platenumber'])): ?>
                                                <div style="font-size: 0.75rem; color: var(--primary); font-weight: 600; margin-top: 4px;">
                                                    <i class="fas fa-id-card"></i> <?php echo htmlspecialchars($row['platenumber']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="max-width: 400px; font-size: 0.875rem; color: var(--text-main); line-height: 1.4;">
                                                <?php echo htmlspecialchars($row['description']); ?>
                                            </div>
                                        </td>
                                        <td style="color: var(--text-muted); font-size: 0.8125rem;">
                                            <?php echo date('M d, Y', strtotime($row['date'])); ?><br>
                                            <?php echo date('H:i', strtotime($row['date'])); ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 8px;">
                                                <button title="Mark as Resolved" style="background: none; border: none; color: var(--success); cursor: pointer;"><i class="fas fa-check-double"></i></button>
                                                <button title="Delete" style="background: none; border: none; color: var(--danger); cursor: pointer;"><i class="fas fa-trash-alt"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align:center;">No feedback/complaints found.</td>
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
