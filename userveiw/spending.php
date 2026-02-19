<?php
// Start session only if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include 'database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// Get user info from database
$sql = "SELECT * FROM users WHERE userid = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if user exists
if ($result->num_rows !== 1) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

// Get filter parameters
$period = $_GET['period'] ?? 'month'; // month, year, all
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');
$category = $_GET['category'] ?? 'all'; // route, sacco, day

// Get spending summary for charts
$spending_data = [];

// 1. Monthly spending for current year
$sql = "
    SELECT 
        MONTH(p.createdat) as month,
        SUM(p.amount) as total,
        COUNT(*) as trip_count
    FROM payments p
    JOIN tripsessions ts ON p.sessionid = ts.sessionid
    WHERE ts.userid = ? 
        AND p.status = 'completed'
        AND YEAR(p.createdat) = ?
    GROUP BY MONTH(p.createdat)
    ORDER BY month ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $year);
$stmt->execute();
$monthly_data = $stmt->get_result();
$monthly_spending = [];
$monthly_trip_counts = [];

// Initialize all months with 0
for ($m = 1; $m <= 12; $m++) {
    $monthly_spending[$m] = 0;
    $monthly_trip_counts[$m] = 0;
}

while ($row = $monthly_data->fetch_assoc()) {
    $monthly_spending[$row['month']] = $row['total'];
    $monthly_trip_counts[$row['month']] = $row['trip_count'];
}
$stmt->close();

// 2. Spending by route (top 5)
$sql = "
    SELECT 
        r.routename,
        SUM(p.amount) as total,
        COUNT(*) as trip_count
    FROM payments p
    JOIN tripsessions ts ON p.sessionid = ts.sessionid
    JOIN trips t ON ts.tripid = t.tripid
    JOIN routes r ON t.routeid = r.routeid
    WHERE ts.userid = ? AND p.status = 'completed'
    GROUP BY r.routeid
    ORDER BY total DESC
    LIMIT 5
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$route_data = $stmt->get_result();
$route_spending = [];
while ($row = $route_data->fetch_assoc()) {
    $route_spending[] = $row;
}
$stmt->close();

// 3. Spending by SACCO
$sql = "
    SELECT 
        s.sacconame,
        SUM(p.amount) as total,
        COUNT(*) as trip_count
    FROM payments p
    JOIN tripsessions ts ON p.sessionid = ts.sessionid
    JOIN trips t ON ts.tripid = t.tripid
    JOIN buses b ON t.busid = b.busid
    JOIN saccos s ON b.saccoid = s.saccoid
    WHERE ts.userid = ? AND p.status = 'completed'
    GROUP BY s.saccoid
    ORDER BY total DESC
    LIMIT 5
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$sacco_data = $stmt->get_result();
$sacco_spending = [];
while ($row = $sacco_data->fetch_assoc()) {
    $sacco_spending[] = $row;
}
$stmt->close();

// 4. Spending by day of week
$sql = "
    SELECT 
        DAYOFWEEK(p.createdat) as day_num,
        CASE DAYOFWEEK(p.createdat)
            WHEN 1 THEN 'Sunday'
            WHEN 2 THEN 'Monday'
            WHEN 3 THEN 'Tuesday'
            WHEN 4 THEN 'Wednesday'
            WHEN 5 THEN 'Thursday'
            WHEN 6 THEN 'Friday'
            WHEN 7 THEN 'Saturday'
        END as day_name,
        SUM(p.amount) as total,
        COUNT(*) as trip_count
    FROM payments p
    JOIN tripsessions ts ON p.sessionid = ts.sessionid
    WHERE ts.userid = ? AND p.status = 'completed'
    GROUP BY DAYOFWEEK(p.createdat)
    ORDER BY day_num ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$day_data = $stmt->get_result();
$day_spending = [];
while ($row = $day_data->fetch_assoc()) {
    $day_spending[] = $row;
}
$stmt->close();

// 5. Get recent transactions for the table
$sql = "
    SELECT 
        p.createdat,
        r.routename,
        s1.stagename as from_stage,
        s2.stagename as to_stage,
        p.amount,
        p.status,
        s.sacconame
    FROM payments p
    JOIN tripsessions ts ON p.sessionid = ts.sessionid
    JOIN trips t ON ts.tripid = t.tripid
    JOIN routes r ON t.routeid = r.routeid
    JOIN stages s1 ON ts.fromstageid = s1.stageid
    JOIN stages s2 ON ts.tostageid = s2.stageid
    JOIN buses b ON t.busid = b.busid
    JOIN saccos s ON b.saccoid = s.saccoid
    WHERE ts.userid = ? AND p.status = 'completed'
    ORDER BY p.createdat DESC
    LIMIT 50
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$transactions = $stmt->get_result();
$stmt->close();

// 6. Get total statistics
$sql = "
    SELECT 
        COUNT(*) as total_trips,
        SUM(p.amount) as total_spent,
        AVG(p.amount) as avg_fare,
        MAX(p.amount) as max_fare,
        MIN(p.amount) as min_fare,
        COUNT(DISTINCT DATE(p.createdat)) as total_days
    FROM payments p
    JOIN tripsessions ts ON p.sessionid = ts.sessionid
    WHERE ts.userid = ? AND p.status = 'completed'
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 7. Get budget vs actual for current month
$current_month_spent = $monthly_spending[(int)date('m')] ?? 0;
$budget = $user['monthlybudget'] ?? 0;
$budget_percentage = $budget > 0 ? min(100, ($current_month_spent / $budget) * 100) : 0;
$remaining_budget = $budget - $current_month_spent;

// 8. Get spending forecast (simple linear projection)
$total_spent_30_days = 0;
$days_count = 0;

$sql = "
    SELECT 
        DATE(p.createdat) as trip_date,
        SUM(p.amount) as daily_total
    FROM payments p
    JOIN tripsessions ts ON p.sessionid = ts.sessionid
    WHERE ts.userid = ? 
        AND p.status = 'completed'
        AND p.createdat >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(p.createdat)
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$daily_data = $stmt->get_result();
while ($row = $daily_data->fetch_assoc()) {
    $total_spent_30_days += $row['daily_total'];
    $days_count++;
}
$stmt->close();

$avg_daily_spend = $days_count > 0 ? $total_spent_30_days / $days_count : 0;
$projected_monthly = $avg_daily_spend * 30;

// Close database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spending Analytics | SafiriPay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary: #0f5132;
            --primary-dark: #0c4128;
            --secondary: #20c997;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --danger: #dc3545;
            --warning: #ffc107;
            --success: #28a745;
            --info: #17a2b8;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            background-color: #f5f7fb;
            color: var(--dark);
            line-height: 1.6;
        }

        .spending-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        /* Header */
        .spending-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .spending-header h1 {
            font-size: 2rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-bar {
            display: flex;
            gap: 1rem;
            align-items: center;
            background: white;
            padding: 0.5rem;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
        }

        .filter-select {
            padding: 0.5rem 1rem;
            border: 1px solid var(--light-gray);
            border-radius: 6px;
            font-size: 0.9rem;
            color: var(--dark);
            background: white;
            cursor: pointer;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--secondary);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
        }

        .stat-icon.total {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
        }

        .stat-icon.trips {
            background: linear-gradient(135deg, #6f42c1, #e83e8c);
        }

        .stat-icon.avg {
            background: linear-gradient(135deg, #fd7e14, #ffc107);
        }

        .stat-icon.days {
            background: linear-gradient(135deg, #17a2b8, #20c997);
        }

        .stat-info h3 {
            font-size: 0.9rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
        }

        .stat-info .value {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--dark);
        }

        .stat-info .subtext {
            font-size: 0.8rem;
            color: var(--gray);
            margin-top: 0.25rem;
        }

        /* Budget Card */
        .budget-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .budget-info h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .budget-amount {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .budget-progress {
            margin: 1.5rem 0;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .progress-bar {
            height: 12px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: var(--secondary);
            border-radius: 6px;
            transition: width 1s ease;
        }

        .budget-status {
            background: rgba(255, 255, 255, 0.1);
            padding: 1.5rem;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .status-label {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 0.5rem;
        }

        .status-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .status-sub {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        /* Chart Grid */
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .chart-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
        }

        .chart-card.full-width {
            grid-column: span 2;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .chart-header h3 {
            font-size: 1.1rem;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-header h3 i {
            color: var(--primary);
        }

        .chart-container {
            height: 300px;
            position: relative;
        }

        /* Transactions Table */
        .transactions-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
        }

        .transactions-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .transactions-header h3 {
            font-size: 1.2rem;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 12px 15px;
            background: var(--light-gray);
            color: var(--dark);
            font-weight: 600;
            font-size: 0.9rem;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--light-gray);
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-success {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }

        .amount {
            font-weight: 600;
            color: var(--primary);
        }

        /* Insights Section */
        .insights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .insight-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            border-left: 4px solid var(--secondary);
        }

        .insight-card h4 {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .insight-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .insight-trend {
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .trend-up {
            color: #28a745;
        }

        .trend-down {
            color: #dc3545;
        }

        /* Download Button */
        .download-btn {
            background: white;
            border: 1px solid var(--primary);
            color: var(--primary);
            padding: 8px 16px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: var(--transition);
        }

        .download-btn:hover {
            background: var(--primary);
            color: white;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .chart-grid {
                grid-template-columns: 1fr;
            }
            
            .chart-card.full-width {
                grid-column: span 1;
            }
            
            .budget-card {
                grid-template-columns: 1fr;
            }
            
            .spending-container {
                padding: 0 1rem;
            }
        }

        @media (max-width: 768px) {
            .spending-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter-bar {
                width: 100%;
                flex-wrap: wrap;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .stat-card {
                padding: 1rem;
            }
            
            .stat-icon {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }
            
            .stat-info .value {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .budget-amount {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="spending-container">
        <!-- Header with Filters -->
        <div class="spending-header">
            <h1>
                <i class="fas fa-chart-line"></i>
                Spending Analytics
            </h1>
            
            <div class="filter-bar">
                <select class="filter-select" id="period-filter" onchange="applyFilters()">
                    <option value="month" <?php echo $period == 'month' ? 'selected' : ''; ?>>This Month</option>
                    <option value="year" <?php echo $period == 'year' ? 'selected' : ''; ?>>This Year</option>
                    <option value="all" <?php echo $period == 'all' ? 'selected' : ''; ?>>All Time</option>
                </select>
                
                <select class="filter-select" id="year-filter" onchange="applyFilters()">
                    <?php for($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endfor; ?>
                </select>
                
                <button class="download-btn" onclick="exportData()">
                    <i class="fas fa-download"></i>
                    Export
                </button>
            </div>
        </div>

        <?php if ($stats['total_trips'] > 0): ?>
            <!-- Quick Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Spent</h3>
                        <div class="value">Ksh <?php echo number_format($stats['total_spent'] ?? 0, 0); ?></div>
                        <div class="subtext">Lifetime spend</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon trips">
                        <i class="fas fa-route"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Trips</h3>
                        <div class="value"><?php echo number_format($stats['total_trips'] ?? 0); ?></div>
                        <div class="subtext">Journeys taken</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon avg">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Average Fare</h3>
                        <div class="value">Ksh <?php echo number_format($stats['avg_fare'] ?? 0, 0); ?></div>
                        <div class="subtext">Per trip</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon days">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Travel Days</h3>
                        <div class="value"><?php echo number_format($stats['total_days'] ?? 0); ?></div>
                        <div class="subtext">Unique days</div>
                    </div>
                </div>
            </div>

            <!-- Budget Card -->
            <div class="budget-card">
                <div class="budget-info">
                    <h2><i class="fas fa-wallet"></i> Monthly Budget</h2>
                    <div class="budget-amount">Ksh <?php echo number_format($budget, 0); ?></div>
                    
                    <div class="budget-progress">
                        <div class="progress-label">
                            <span>Spent this month</span>
                            <span><?php echo round($budget_percentage); ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $budget_percentage; ?>%;"></div>
                        </div>
                    </div>
                    
                    <?php if ($budget > 0): ?>
                        <p>
                            <?php if ($remaining_budget > 0): ?>
                                <i class="fas fa-check-circle"></i> You have Ksh <?php echo number_format($remaining_budget, 0); ?> left for this month
                            <?php else: ?>
                                <i class="fas fa-exclamation-triangle"></i> You've exceeded your monthly budget
                            <?php endif; ?>
                        </p>
                    <?php else: ?>
                        <p>
                            <i class="fas fa-info-circle"></i> 
                            <a href="budget_setting.php" style="color: white; text-decoration: underline;">Set a monthly budget</a> to track your spending
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="budget-status">
                    <span class="status-label">Average Daily Spend</span>
                    <span class="status-value">Ksh <?php echo number_format($avg_daily_spend, 0); ?></span>
                    <span class="status-sub">Based on last 30 days</span>
                    
                    <span class="status-label" style="margin-top: 1.5rem;">Projected Monthly</span>
                    <span class="status-value">Ksh <?php echo number_format($projected_monthly, 0); ?></span>
                    <span class="status-sub">At current spending rate</span>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="chart-grid">
                <!-- Monthly Spending Chart -->
                <div class="chart-card full-width">
                    <div class="chart-header">
                        <h3><i class="fas fa-calendar-alt"></i> Monthly Spending (<?php echo $year; ?>)</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
                
                <!-- Spending by Route -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-route"></i> Top Routes</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="routeChart"></canvas>
                    </div>
                </div>
                
                <!-- Spending by SACCO -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-building"></i> Top SACCOs</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="saccoChart"></canvas>
                    </div>
                </div>
                
                <!-- Spending by Day -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-sun"></i> Spending by Day</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="dayChart"></canvas>
                    </div>
                </div>
                
                <!-- Fare Distribution -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-pie"></i> Fare Distribution</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="fareChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Insights Section -->
            <div class="insights-grid">
                <div class="insight-card">
                    <h4><i class="fas fa-trophy"></i> Most Used Route</h4>
                    <div class="insight-value">
                        <?php echo !empty($route_spending) ? htmlspecialchars($route_spending[0]['routename']) : 'N/A'; ?>
                    </div>
                    <div class="insight-trend">
                        <?php if (!empty($route_spending)): ?>
                            <?php echo number_format($route_spending[0]['trip_count']); ?> trips
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="insight-card">
                    <h4><i class="fas fa-building"></i> Favorite SACCO</h4>
                    <div class="insight-value">
                        <?php echo !empty($sacco_spending) ? htmlspecialchars($sacco_spending[0]['sacconame']) : 'N/A'; ?>
                    </div>
                    <div class="insight-trend">
                        <?php if (!empty($sacco_spending)): ?>
                            Ksh <?php echo number_format($sacco_spending[0]['total'], 0); ?> total
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="insight-card">
                    <h4><i class="fas fa-clock"></i> Busiest Day</h4>
                    <div class="insight-value">
                        <?php 
                        $busiest_day = !empty($day_spending) ? $day_spending[array_search(max(array_column($day_spending, 'total')), array_column($day_spending, 'total'))] : null;
                        echo $busiest_day ? $busiest_day['day_name'] : 'N/A';
                        ?>
                    </div>
                    <div class="insight-trend">
                        <?php if ($busiest_day): ?>
                            Ksh <?php echo number_format($busiest_day['total'], 0); ?> total
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="insight-card">
                    <h4><i class="fas fa-chart-line"></i> Highest Fare</h4>
                    <div class="insight-value">
                        Ksh <?php echo number_format($stats['max_fare'] ?? 0, 0); ?>
                    </div>
                    <div class="insight-trend">
                        Lowest: Ksh <?php echo number_format($stats['min_fare'] ?? 0, 0); ?>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="transactions-card">
                <div class="transactions-header">
                    <h3><i class="fas fa-history"></i> Recent Transactions</h3>
                    <a href="trip_history.php" style="color: var(--primary); text-decoration: none;">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>SACCO</th>
                                <th>Route</th>
                                <th>From → To</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $count = 0;
                            while($row = $transactions->fetch_assoc()): 
                                if($count >= 10) break;
                                $count++;
                            ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($row['createdat'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['sacconame']); ?></td>
                                    <td><?php echo htmlspecialchars($row['routename']); ?></td>
                                    <td><?php echo htmlspecialchars($row['from_stage']); ?> → <?php echo htmlspecialchars($row['to_stage']); ?></td>
                                    <td class="amount">Ksh <?php echo number_format($row['amount'], 0); ?></td>
                                    <td>
                                        <span class="badge badge-success">Completed</span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state">
                <i class="fas fa-chart-bar"></i>
                <h2>No spending data yet</h2>
                <p>Your spending analytics will appear here after you take your first trip.</p>
                <a href="scan_qr.php" style="display: inline-block; margin-top: 1.5rem; padding: 12px 24px; background: var(--primary); color: white; text-decoration: none; border-radius: 8px;">
                    <i class="fas fa-qrcode"></i> Scan QR to Start
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Initialize charts when page loads
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($stats['total_trips'] > 0): ?>
                // Monthly Spending Chart
                new Chart(document.getElementById('monthlyChart'), {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                        datasets: [{
                            label: 'Spending (Ksh)',
                            data: [
                                <?php 
                                for($i = 1; $i <= 12; $i++) {
                                    echo $monthly_spending[$i] . ',';
                                }
                                ?>
                            ],
                            borderColor: '#0f5132',
                            backgroundColor: 'rgba(15, 81, 50, 0.1)',
                            borderWidth: 3,
                            pointBackgroundColor: '#20c997',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return `Ksh ${context.raw.toLocaleString()}`;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'Ksh ' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });

                // Route Chart
                new Chart(document.getElementById('routeChart'), {
                    type: 'doughnut',
                    data: {
                        labels: [
                            <?php 
                            foreach($route_spending as $route) {
                                echo "'" . addslashes($route['routename']) . "',";
                            }
                            ?>
                        ],
                        datasets: [{
                            data: [
                                <?php 
                                foreach($route_spending as $route) {
                                    echo $route['total'] . ',';
                                }
                                ?>
                            ],
                            backgroundColor: [
                                '#0f5132',
                                '#20c997',
                                '#6f42c1',
                                '#fd7e14',
                                '#e83e8c'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.label || '';
                                        let value = context.raw || 0;
                                        let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        let percentage = ((value / total) * 100).toFixed(1);
                                        return `${label}: Ksh ${value.toLocaleString()} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });

                // SACCO Chart
                new Chart(document.getElementById('saccoChart'), {
                    type: 'bar',
                    data: {
                        labels: [
                            <?php 
                            foreach($sacco_spending as $sacco) {
                                echo "'" . addslashes($sacco['sacconame']) . "',";
                            }
                            ?>
                        ],
                        datasets: [{
                            label: 'Total Spent',
                            data: [
                                <?php 
                                foreach($sacco_spending as $sacco) {
                                    echo $sacco['total'] . ',';
                                }
                                ?>
                            ],
                            backgroundColor: '#20c997',
                            borderRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return `Ksh ${context.raw.toLocaleString()}`;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'Ksh ' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });

                // Day of Week Chart
                new Chart(document.getElementById('dayChart'), {
                    type: 'line',
                    data: {
                        labels: [
                            <?php 
                            foreach($day_spending as $day) {
                                echo "'" . $day['day_name'] . "',";
                            }
                            ?>
                        ],
                        datasets: [{
                            label: 'Spending',
                            data: [
                                <?php 
                                foreach($day_spending as $day) {
                                    echo $day['total'] . ',';
                                }
                                ?>
                            ],
                            borderColor: '#6f42c1',
                            backgroundColor: 'rgba(111, 66, 193, 0.1)',
                            borderWidth: 2,
                            pointBackgroundColor: '#20c997',
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let value = context.raw;
                                        let dayData = <?php echo json_encode($day_spending); ?>;
                                        let tripCount = dayData[context.dataIndex]?.trip_count || 0;
                                        return [
                                            `Ksh ${value.toLocaleString()}`,
                                            `${tripCount} trips`
                                        ];
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'Ksh ' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });

                // Fare Distribution Chart
                new Chart(document.getElementById('fareChart'), {
                    type: 'pie',
                    data: {
                        labels: ['Under Ksh 50', 'Ksh 50-100', 'Ksh 100-200', 'Over Ksh 200'],
                        datasets: [{
                            data: [
                                <?php
                                // This would need a separate query in production
                                echo "0, 0, 0, 0";
                                ?>
                            ],
                            backgroundColor: [
                                '#0f5132',
                                '#20c997',
                                '#6f42c1',
                                '#fd7e14'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.label || '';
                                        let value = context.raw || 0;
                                        return `${label}: ${value} trips`;
                                    }
                                }
                            }
                        }
                    }
                });
            <?php endif; ?>
        });

        // Apply filters
        function applyFilters() {
            const period = document.getElementById('period-filter').value;
            const year = document.getElementById('year-filter').value;
            window.location.href = `spending.php?period=${period}&year=${year}`;
        }

        // Export data as CSV
        function exportData() {
            <?php if ($stats['total_trips'] > 0): ?>
            // Prepare CSV data
            let csv = 'Date,SACCO,Route,From,To,Amount,Status\n';
            
            <?php 
            // Reset transaction pointer
            $transactions->data_seek(0);
            while($row = $transactions->fetch_assoc()): 
            ?>
                csv += `'<?php echo date('Y-m-d', strtotime($row['createdat'])); ?>',`;
                csv += `'<?php echo addslashes($row['sacconame']); ?>',`;
                csv += `'<?php echo addslashes($row['routename']); ?>',`;
                csv += `'<?php echo addslashes($row['from_stage']); ?>',`;
                csv += `'<?php echo addslashes($row['to_stage']); ?>',`;
                csv += `<?php echo $row['amount']; ?>,`;
                csv += `'<?php echo $row['status']; ?>'\n`;
            <?php endwhile; ?>
            
            // Download CSV
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'safiripay_spending_<?php echo date('Y-m-d'); ?>.csv';
            a.click();
            <?php endif; ?>
        }

        // Set budget
        function setBudget() {
            const budget = prompt('Enter your monthly transport budget (Ksh):');
            if (budget && !isNaN(budget) && budget > 0) {
                fetch('update_budget.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `budget=${budget}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Failed to update budget. Please try again.');
                    }
                });
            }
        }
    </script>
</body>
</html>