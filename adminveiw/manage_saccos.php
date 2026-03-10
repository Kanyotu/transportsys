<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
include 'database.php';

// Handle SACCO status updates (Approve/Deactivate)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    $status = ($action == 'approve' || $action == 'activate') ? 1 : ($action == 'deactivate' ? 0 : 2); // 2 could be pending
    
    $stmt = $conn->prepare("UPDATE saccos SET status = ? WHERE saccoid = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $status, $id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: manage_saccos.php");
    exit();
}

// Fetch SACCOs
$saccos = $conn->query("SELECT * FROM saccos ORDER BY createdat DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SACCO Management | SafiriPay Admin</title>
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
                <li><a href="manage_users.php"><i class="fas fa-users-cog"></i> <span>User Management</span></a></li>
                <li><a href="manage_saccos.php" class="active"><i class="fas fa-building"></i> <span>SACCO Management</span></a></li>
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
                    <h1>SACCO Management</h1>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Register, approve, and monitor transport cooperatives.</p>
                </div>
                <div class="header-actions">
                    <button class="theme-toggle" id="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                    <button class="btn btn-primary" style="padding: 0.6rem 1rem; font-size: 0.875rem;">
                        <i class="fas fa-plus"></i> Register SACCO
                    </button>
                </div>
            </header>

            <!-- SACCOs List -->
            <div class="data-card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>SACCO Name</th>
                                <th>Payment Details</th>
                                <th>QR Identifier</th>
                                <th>Status</th>
                                <th>Date Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($saccos && $saccos->num_rows > 0): ?>
                                <?php while($row = $saccos->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 700; color: var(--primary); font-size: 1rem;">
                                                <?php echo htmlspecialchars($row['sacconame']); ?>
                                            </div>
                                            <div style="font-size: 0.75rem; color: var(--text-muted);">ID: #<?php echo $row['saccoid']; ?></div>
                                        </td>
                                        <td>
                                            <div style="font-size: 0.875rem;"><i class="fas fa-money-check-alt" style="width: 20px;"></i> <?php echo htmlspecialchars($row['mpesashortcode']); ?></div>
                                            <div style="font-size: 0.75rem; color: var(--text-muted);">Passkey: ****<?php echo substr($row['mpesapasskey'], -4); ?></div>
                                        </td>
                                        <td>
                                            <code><?php echo htmlspecialchars($row['qr_identifier']); ?></code>
                                        </td>
                                        <td>
                                            <?php if ($row['status'] == 1): ?>
                                                <span class="status-badge status-active">
                                                    <i class="fas fa-check-circle"></i> Approved
                                                </span>
                                            <?php elseif ($row['status'] == 0): ?>
                                                <span class="status-badge status-deactivated">
                                                    <i class="fas fa-times-circle"></i> Deactivated
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge status-pending">
                                                    <i class="fas fa-clock"></i> Pending
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="color: var(--text-muted); font-size: 0.875rem;">
                                            <?php echo date('M d, Y', strtotime($row['createdat'])); ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 10px;">
                                                <button title="View Details" style="background: none; border: none; color: var(--primary); cursor: pointer;"><i class="fas fa-eye"></i></button>
                                                <?php if($row['status'] != 1): ?>
                                                    <a href="manage_saccos.php?action=approve&id=<?php echo $row['saccoid']; ?>" title="Approve" style="color: var(--success);"><i class="fas fa-check"></i></a>
                                                <?php endif; ?>
                                                <?php if($row['status'] == 1): ?>
                                                    <a href="manage_saccos.php?action=deactivate&id=<?php echo $row['saccoid']; ?>" title="Deactivate" style="color: var(--danger);"><i class="fas fa-ban"></i></a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align:center;">No SACCOs found.</td>
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
