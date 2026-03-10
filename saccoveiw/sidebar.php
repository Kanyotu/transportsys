<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-bus"></i>
        <span>SafiriPay</span>
    </div>
    <ul class="sidebar-menu">
        <li>
            <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="profile.php" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-building"></i> <span>SACCO Profile</span>
            </a>
        </li>
        <li>
            <a href="manage_routes.php" class="<?php echo $current_page == 'manage_routes.php' ? 'active' : ''; ?>">
                <i class="fas fa-route"></i> <span>Routes & Stages</span>
            </a>
        </li>
        <li>
            <a href="manage_fares.php" class="<?php echo $current_page == 'manage_fares.php' ? 'active' : ''; ?>">
                <i class="fas fa-tag"></i> <span>Fare Prices</span>
            </a>
        </li>
        <li>
            <a href="vehicles.php" class="<?php echo $current_page == 'vehicles.php' ? 'active' : ''; ?>">
                <i class="fas fa-bus-alt"></i> <span>Vehicles</span>
            </a>
        </li>
        <li>
            <a href="trips.php" class="<?php echo $current_page == 'trips.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i> <span>Trip Schedules</span>
            </a>
        </li>
        <li>
            <a href="bookings.php" class="<?php echo $current_page == 'bookings.php' ? 'active' : ''; ?>">
                <i class="fas fa-ticket-alt"></i> <span>Bookings</span>
            </a>
        </li>
        <li>
            <a href="earnings.php" class="<?php echo $current_page == 'earnings.php' ? 'active' : ''; ?>">
                <i class="fas fa-wallet"></i> <span>Earnings</span>
            </a>
        </li>
        <li>
            <a href="reviews.php" class="<?php echo $current_page == 'reviews.php' ? 'active' : ''; ?>">
                <i class="fas fa-star"></i> <span>Reviews</span>
            </a>
        </li>
        <li>
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
            </a>
        </li>
    </ul>
</div>
