<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-bus-alt"></i>
        <span>SafiriPay User</span>
    </div>
    <ul class="sidebar-menu">
        <li>
            <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="scan_qr.php" class="<?php echo $current_page == 'scan_qr.php' ? 'active' : ''; ?>">
                <i class="fas fa-qrcode"></i> <span>Scan & Ride</span>
            </a>
        </li>
        <li>
            <a href="book.php" class="<?php echo $current_page == 'book.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i> <span>Book Trip</span>
            </a>
        </li>
        <li>
            <a href="trip_history.php" class="<?php echo $current_page == 'trip_history.php' ? 'active' : ''; ?>">
                <i class="fas fa-history"></i> <span>My Trips</span>
            </a>
        </li>
        <li>
            <a href="budget_setting.php" class="<?php echo $current_page == 'budget_setting.php' ? 'active' : ''; ?>">
                <i class="fas fa-wallet"></i> <span>Budget</span>
            </a>
        </li>
        <li>
            <a href="spending.php" class="<?php echo $current_page == 'spending.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i> <span>Spending</span>
            </a>
        </li>
        <li>
            <a href="my_complaints.php" class="<?php echo $current_page == 'my_complaints.php' ? 'active' : ''; ?>">
                <i class="fas fa-comment-alt"></i> <span>Complaints</span>
            </a>
        </li>
        <li>
            <a href="profile.php" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-circle"></i> <span>Profile</span>
            </a>
        </li>
        <li>
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
            </a>
        </li>
    </ul>
</div>
