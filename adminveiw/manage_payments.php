<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
include 'database.php';

// Fetch Statistics
$payment_stats = [
    'total_successful' => 0,
    'total_pending' => 0,
    'total_failed' => 0,
    'revenue_sum' => 0
];

$res = $conn->query("SELECT status, COUNT(*) as count, SUM(amount) as sum FROM payments GROUP BY status");
if ($res) {
    while($row = $res->fetch_assoc()) {
        if ($row['status'] == 'success') {
            $payment_stats['total_successful'] = $row['count'];
            $payment_stats['revenue_sum'] = $row['sum'];
        } elseif ($row['status'] == 'pending') {
            $payment_stats['total_pending'] = $row['count'];
        } elseif ($row['status'] == 'failed') {
            $payment_stats['total_failed'] = $row['count'];
        }
    }
}

// Filter Inputs
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : 'all';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

$where_conditions = ["1=1"];

if ($search != '') {
    $where_conditions[] = "(p.mpesareceipt LIKE '%$search%' OR u.username LIKE '%$search%' OR p.paymentid LIKE '%" . str_replace('#TX-', '', $search) . "%')";
}

if ($status != 'all') {
    $where_conditions[] = "p.status = '$status'";
}

if ($date_from != '') {
    $where_conditions[] = "p.createdat >= '$date_from 00:00:00'";
}

if ($date_to != '') {
    $where_conditions[] = "p.createdat <= '$date_to 23:59:59'";
}

$where_clause = implode(' AND ', $where_conditions);

// Fetch Payments with User and Booking details
$query = "
    SELECT p.*, u.username, u.phoneno, ts.sessionid 
    FROM payments p 
    JOIN tripsessions ts ON p.sessionid = ts.sessionid 
    JOIN users u ON ts.userid = u.userid 
    WHERE $where_clause
    ORDER BY p.createdat DESC
";
$payments = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Monitoring | SafiriPay Admin</title>
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
                <li><a href="manage_users.php"><i class="fas fa-users-cog"></i> <span>People</span></a></li>
                <li><a href="manage_saccos.php"><i class="fas fa-building"></i> <span>Sacco List</span></a></li>
                <li><a href="manage_routes.php"><i class="fas fa-route"></i> <span>Routes & Stages</span></a></li>
                <li><a href="manage_trips.php"><i class="fas fa-calendar-alt"></i> <span>Trips & Schedules</span></a></li>
                <li><a href="manage_bookings.php"><i class="fas fa-ticket-alt"></i> <span>Bookings</span></a></li>
                <li><a href="manage_payments.php" class="active"><i class="fas fa-file-invoice-dollar"></i> <span>Money</span></a></li>
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
                    <h1>Payment Monitoring</h1>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Track all system transactions and financial records.</p>
                </div>
                <div class="header-actions">
                    <button class="theme-toggle" id="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                    <button class="btn btn-primary" style="padding: 0.6rem 1rem; font-size: 0.875rem;">
                        <i class="fas fa-download"></i> Export CSV
                    </button>
                </div>
            </header>

            <!-- Payment Stats -->
            <div class="stats-grid animate__animated animate__fadeIn">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, var(--success), #059669);">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Successful</h3>
                        <p><?php echo number_format($payment_stats['total_successful']); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, var(--warning), #d97706);">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Pending</h3>
                        <p><?php echo number_format($payment_stats['total_pending']); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, var(--primary), var(--primary-alt));">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Revenue</h3>
                        <p>KSh <?php echo number_format($payment_stats['revenue_sum'], 0); ?></p>
                    </div>
                </div>
            </div>
            <!-- Filters -->
            <div class="data-card animate__animated animate__fadeInUp" style="margin-bottom: 2rem; padding: 2rem;">
                <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; align-items: end;">
                    <div class="input-group" style="margin-bottom: 0;">
                        <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.75rem; display: block; letter-spacing: 0.05em;">SEARCH TRANSACTIONS</label>
                        <div style="position: relative;">
                            <i class="fas fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.9rem;"></i>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="ID, Name, Receipt..." style="padding-left: 42px; width: 100%; height: 48px; border-radius: 14px; border: 1px solid var(--border); background: var(--bg-main); transition: all 0.3s ease;">
                        </div>
                    </div>
                    <div class="input-group" style="margin-bottom: 0;">
                        <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.75rem; display: block; letter-spacing: 0.05em;">STATUS</label>
                        <select name="status" style="height: 48px; border-radius: 14px; border: 1px solid var(--border); background: var(--bg-main); transition: all 0.3s ease;">
                            <option value="all" <?php echo $status == 'all' ? 'selected' : ''; ?>>All Transactions</option>
                            <option value="success" <?php echo $status == 'success' ? 'selected' : ''; ?>>Success</option>
                            <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="failed" <?php echo $status == 'failed' ? 'selected' : ''; ?>>Failed</option>
                        </select>
                    </div>
                    <div class="input-group" style="margin-bottom: 0;">
                        <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.75rem; display: block; letter-spacing: 0.05em;">FROM DATE</label>
                        <input type="date" name="date_from" value="<?php echo $date_from; ?>" style="height: 48px; border-radius: 14px; border: 1px solid var(--border); background: var(--bg-main);">
                    </div>
                    <div class="input-group" style="margin-bottom: 0;">
                        <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.75rem; display: block; letter-spacing: 0.05em;">TO DATE</label>
                        <input type="date" name="date_to" value="<?php echo $date_to; ?>" style="height: 48px; border-radius: 14px; border: 1px solid var(--border); background: var(--bg-main);">
                    </div>
                    <div style="display: flex; gap: 0.75rem; height: 48px;">
                        <button type="submit" class="btn btn-primary" style="flex: 1; border-radius: 14px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="manage_payments.php" class="btn" title="Reset Filters" style="background: var(--border); color: var(--text-main); text-decoration: none; display: flex; align-items: center; justify-content: center; width: 48px; border-radius: 14px; transition: all 0.3s ease;">
                            <i class="fas fa-undo"></i>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Payments Table -->
            <div class="data-card animate__animated animate__fadeInUp animate__delay-1s">
                <div class="table-responsive">
                    <table style="border-spacing: 0 16px;">
                        <thead>
                            <tr>
                                <th style="padding-left: 2rem;">Transaction ID</th>
                                <th>Customer</th>
                                <th>Booking Ref</th>
                                <th>Amount</th>
                                <th>M-Pesa Receipt</th>
                                <th>Status</th>
                                <th style="padding-right: 2rem;">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($payments && $payments->num_rows > 0): ?>
                                <?php while($row = $payments->fetch_assoc()): ?>
                                    <tr style="transition: transform 0.2s ease;">
                                        <td style="font-weight: 800; color: var(--primary); padding-left: 2rem;">
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <i class="fas fa-receipt" style="opacity: 0.5;"></i>
                                                #TX-<?php echo $row['paymentid']; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 12px;">
                                                <div style="width: 36px; height: 36px; border-radius: 10px; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.875rem;">
                                                    <?php echo strtoupper(substr($row['username'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($row['username']); ?></div>
                                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($row['phoneno']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="manage_bookings.php" class="ref-link" style="color: var(--text-main); font-weight: 600; text-decoration: none; background: var(--bg-main); padding: 4px 10px; border-radius: 8px; border: 1px solid var(--border); font-size: 0.8125rem;">
                                                BK-<?php echo $row['sessionid']; ?>
                                            </a>
                                        </td>
                                        <td style="font-weight: 800; font-size: 1rem;">KSh <?php echo number_format($row['amount'], 2); ?></td>
                                        <td>
                                            <code style="background: var(--bg-main); padding: 4px 10px; border-radius: 8px; font-weight: 600; font-size: 0.75rem; border: 1px dashed var(--border);"><?php echo htmlspecialchars($row['mpesareceipt'] ?: '---'); ?></code>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                                <i class="fas <?php 
                                                    echo $row['status'] == 'success' ? 'fa-check-circle' : 
                                                        ($row['status'] == 'pending' ? 'fa-clock' : 'fa-times-circle'); 
                                                ?>" style="font-size: 0.8rem;"></i>
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td style="color: var(--text-muted); font-size: 0.8125rem; font-weight: 500; padding-right: 2rem;">
                                            <?php echo date('M d, Y', strtotime($row['createdat'])); ?><br>
                                            <span style="font-size: 0.7rem; opacity: 0.7;"><?php echo date('H:i A', strtotime($row['createdat'])); ?></span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align:center; padding: 4rem 2rem;">
                                        <div style="opacity: 0.5; margin-bottom: 1.5rem;">
                                            <i class="fas fa-search-dollar" style="font-size: 4rem;"></i>
                                        </div>
                                        <h3 style="color: var(--text-muted);">No Transactions Found</h3>
                                        <p style="color: var(--text-muted); font-size: 0.875rem;">Try adjusting your filters or search terms.</p>
                                    </td>
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
