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

// Handle budget update
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'set_budget') {
        $new_budget = floatval($_POST['monthly_budget'] ?? 0);
        
        if ($new_budget >= 0) {
            $sql = "UPDATE users SET monthlybudget = ? WHERE userid = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("di", $new_budget, $user_id);
            
            if ($stmt->execute()) {
                $message = "Budget updated successfully!";
                $message_type = "success";
                $user['monthlybudget'] = $new_budget;
            } else {
                $message = "Failed to update budget. Please try again.";
                $message_type = "error";
            }
            $stmt->close();
        } else {
            $message = "Please enter a valid budget amount.";
            $message_type = "error";
        }
    } elseif ($action == 'set_notification') {
        $notification_threshold = floatval($_POST['notification_threshold'] ?? 80);
        $enable_notifications = isset($_POST['enable_notifications']) ? 1 : 0;
        
        // Save notification settings (you may want to add these columns to users table)
        // For now, we'll store in session or you can add columns later
        $_SESSION['budget_settings'] = [
            'threshold' => $notification_threshold,
            'enabled' => $enable_notifications
        ];
        
        $message = "Notification settings saved!";
        $message_type = "success";
    }
}

// Get current budget
$current_budget = $user['monthlybudget'] ?? 0;

// Get notification settings from session (or database if you add columns)
$notification_threshold = $_SESSION['budget_settings']['threshold'] ?? 80;
$notifications_enabled = $_SESSION['budget_settings']['enabled'] ?? true;

// Get current month spending
$current_month = date('m');
$current_year = date('Y');

$sql = "
    SELECT SUM(p.amount) as monthly_spent,
           COUNT(*) as trip_count
    FROM payments p
    JOIN tripsessions ts ON p.sessionid = ts.sessionid
    WHERE ts.userid = ? 
    AND p.status = 'completed'
    AND MONTH(p.createdat) = ? 
    AND YEAR(p.createdat) = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $current_month, $current_year);
$stmt->execute();
$monthly_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

$monthly_spent = $monthly_data['monthly_spent'] ?? 0;
$trip_count = $monthly_data['trip_count'] ?? 0;

// Calculate budget metrics
$budget_percentage = $current_budget > 0 ? min(100, ($monthly_spent / $current_budget) * 100) : 0;
$remaining_budget = $current_budget - $monthly_spent;
$avg_daily_spend = date('t') > 0 ? $monthly_spent / date('t') : 0;
$projected_monthly = $avg_daily_spend * date('t');

// Get spending history for trend analysis
$sql = "
    SELECT 
        MONTH(p.createdat) as month,
        YEAR(p.createdat) as year,
        SUM(p.amount) as total
    FROM payments p
    JOIN tripsessions ts ON p.sessionid = ts.sessionid
    WHERE ts.userid = ? AND p.status = 'completed'
    GROUP BY YEAR(p.createdat), MONTH(p.createdat)
    ORDER BY year DESC, month DESC
    LIMIT 6
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$history_result = $stmt->get_result();
$history = [];
while ($row = $history_result->fetch_assoc()) {
    $history[] = $row;
}
$stmt->close();

// Get budget alerts if any
$alerts = [];

if ($current_budget > 0) {
    if ($budget_percentage >= 100) {
        $alerts[] = [
            'type' => 'danger',
            'icon' => 'exclamation-triangle',
            'title' => 'Budget Exceeded!',
            'message' => "You've exceeded your monthly budget by Ksh " . number_format(abs($remaining_budget), 0)
        ];
    } elseif ($budget_percentage >= 90) {
        $alerts[] = [
            'type' => 'warning',
            'icon' => 'exclamation-circle',
            'title' => 'Budget Alert!',
            'message' => "You've used {$budget_percentage}% of your monthly budget"
        ];
    } elseif ($budget_percentage >= 75) {
        $alerts[] = [
            'type' => 'info',
            'icon' => 'info-circle',
            'title' => 'Budget Notice',
            'message' => "You've used {$budget_percentage}% of your monthly budget"
        ];
    }
}

// Check if projected spending will exceed budget
if ($current_budget > 0 && $projected_monthly > $current_budget) {
    $alerts[] = [
        'type' => 'warning',
        'icon' => 'chart-line',
        'title' => 'Projected Overspend',
        'message' => "At your current rate, you'll exceed your budget by Ksh " . number_format($projected_monthly - $current_budget, 0)
    ];
}

// Get budget tips based on spending patterns
$tips = [];

if ($trip_count > 0) {
    $avg_fare = $monthly_spent / $trip_count;
    
    if ($avg_fare > 150) {
        $tips[] = [
            'icon' => 'fa-route',
            'title' => 'High Average Fare',
            'message' => 'Consider using shorter routes or alternative SACCOs to reduce costs'
        ];
    }
    
    if ($trip_count > 30) {
        $tips[] = [
            'icon' => 'fa-clock',
            'title' => 'Frequent Traveler',
            'message' => 'Look into weekly or monthly travel passes for better rates'
        ];
    }
    
    if ($current_budget > 0 && $monthly_spent > $current_budget * 0.8) {
        $tips[] = [
            'icon' => 'fa-wallet',
            'title' => 'Near Budget Limit',
            'message' => 'Try carpooling or alternative routes for the rest of the month'
        ];
    }
}

if (empty($tips)) {
    $tips[] = [
        'icon' => 'fa-smile',
        'title' => 'Doing Great!',
        'message' => 'Your spending is well within budget. Keep it up!'
    ];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Settings | SafiriPay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .budget-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        /* Header */
        .budget-header {
            margin-bottom: 2rem;
        }

        .budget-header h1 {
            font-size: 2rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 0.5rem;
        }

        .budget-header p {
            color: var(--gray);
            font-size: 1.1rem;
        }

        /* Message Alerts */
        .message {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.3s ease;
        }

        .message.success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            color: #155724;
        }

        .message.error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            color: #721c24;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Budget Overview Card */
        .budget-overview {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            color: white;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            box-shadow: var(--card-shadow);
        }

        .overview-left h2 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
            opacity: 0.9;
        }

        .current-budget {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .budget-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .budget-progress {
            margin: 1.5rem 0;
        }

        .progress-header {
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

        .budget-stats {
            display: flex;
            gap: 2rem;
        }

        .stat-item {
            flex: 1;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.85rem;
            opacity: 0.8;
        }

        .overview-right {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 1.5rem;
        }

        .overview-right h3 {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .metric-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .metric-label {
            opacity: 0.9;
        }

        .metric-value {
            font-weight: bold;
        }

        /* Budget Setting Card */
        .budget-setting-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .setting-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1.5rem;
        }

        .setting-header i {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .setting-header h2 {
            font-size: 1.3rem;
            color: var(--dark);
        }

        .budget-form {
            max-width: 500px;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark);
            font-weight: 500;
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }

        .input-with-icon input {
            width: 100%;
            padding: 14px 14px 14px 45px;
            border: 2px solid var(--light-gray);
            border-radius: 10px;
            font-size: 1rem;
            transition: var(--transition);
        }

        .input-with-icon input:focus {
            outline: none;
            border-color: var(--secondary);
        }

        .suggested-budgets {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
            flex-wrap: wrap;
        }

        .suggested-budget {
            background: var(--light-gray);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .suggested-budget:hover {
            background: var(--secondary);
            color: white;
        }

        /* Notification Settings */
        .notification-settings {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .toggle-switch {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .toggle-label {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--primary);
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .threshold-slider {
            margin: 2rem 0;
        }

        .threshold-value {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        input[type=range] {
            width: 100%;
            height: 8px;
            border-radius: 4px;
            background: var(--light-gray);
            outline: none;
        }

        input[type=range]::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            background: var(--primary);
            border-radius: 50%;
            cursor: pointer;
        }

        /* Alerts Section */
        .alerts-section {
            margin-bottom: 2rem;
        }

        .alert-card {
            background: white;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 15px;
            border-left: 4px solid;
            box-shadow: var(--card-shadow);
        }

        .alert-card.danger {
            border-left-color: var(--danger);
        }

        .alert-card.warning {
            border-left-color: var(--warning);
        }

        .alert-card.info {
            border-left-color: var(--info);
        }

        .alert-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .alert-icon.danger {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }

        .alert-icon.warning {
            background: rgba(255, 193, 7, 0.1);
            color: var(--warning);
        }

        .alert-icon.info {
            background: rgba(23, 162, 184, 0.1);
            color: var(--info);
        }

        .alert-content {
            flex: 1;
        }

        .alert-title {
            font-weight: bold;
            margin-bottom: 0.25rem;
        }

        .alert-message {
            font-size: 0.9rem;
            color: var(--gray);
        }

        /* Tips Section */
        .tips-section {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .tips-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1.5rem;
        }

        .tips-header i {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .tips-header h2 {
            font-size: 1.3rem;
            color: var(--dark);
        }

        .tips-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .tip-card {
            background: var(--light-gray);
            border-radius: 12px;
            padding: 1.5rem;
            transition: var(--transition);
        }

        .tip-card:hover {
            transform: translateY(-5px);
        }

        .tip-icon {
            width: 50px;
            height: 50px;
            background: var(--primary);
            color: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin-bottom: 1rem;
        }

        .tip-title {
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        .tip-message {
            font-size: 0.9rem;
            color: var(--gray);
        }

        /* Spending History */
        .history-section {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .history-header h2 {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3rem;
        }

        .history-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .history-item {
            text-align: center;
            padding: 1rem;
            background: var(--light-gray);
            border-radius: 10px;
        }

        .history-month {
            font-size: 0.9rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
        }

        .history-amount {
            font-weight: bold;
            color: var(--primary);
        }

        /* Buttons */
        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(15, 81, 50, 0.2);
        }

        .btn-secondary {
            background: var(--secondary);
            color: white;
        }

        .btn-secondary:hover {
            background: #1ba87e;
            transform: translateY(-2px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .budget-container {
                padding: 0 1rem;
            }
            
            .budget-overview {
                grid-template-columns: 1fr;
            }
            
            .budget-stats {
                flex-direction: column;
                gap: 1rem;
            }
            
            .tips-grid {
                grid-template-columns: 1fr;
            }
            
            .history-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 480px) {
            .history-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .current-budget {
                font-size: 2rem;
            }
            
            .budget-overview {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="budget-container">
        <!-- Header -->
        <div class="budget-header">
            <h1>
                <i class="fas fa-wallet"></i>
                Budget Settings
            </h1>
            <p>Manage your transport budget and track your spending habits</p>
        </div>

        <!-- Success/Error Message -->
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Budget Overview Card -->
        <div class="budget-overview">
            <div class="overview-left">
                <h2>
                    <i class="fas fa-chart-pie"></i>
                    Current Budget Status
                </h2>
                
                <div class="current-budget">
                    Ksh <?php echo number_format($current_budget, 0); ?>
                </div>
                <div class="budget-label">Monthly Transport Budget</div>
                
                <div class="budget-progress">
                    <div class="progress-header">
                        <span>This Month's Spending</span>
                        <span>Ksh <?php echo number_format($monthly_spent, 0); ?> (<?php echo round($budget_percentage); ?>%)</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $budget_percentage; ?>%;"></div>
                    </div>
                </div>
                
                <div class="budget-stats">
                    <div class="stat-item">
                        <div class="stat-value">Ksh <?php echo number_format($remaining_budget, 0); ?></div>
                        <div class="stat-label">Remaining</div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $trip_count; ?></div>
                        <div class="stat-label">Trips This Month</div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-value">Ksh <?php echo number_format($avg_daily_spend, 0); ?></div>
                        <div class="stat-label">Daily Average</div>
                    </div>
                </div>
            </div>
            
            <div class="overview-right">
                <h3>
                    <i class="fas fa-calculator"></i>
                    Budget Insights
                </h3>
                
                <div class="metric-item">
                    <span class="metric-label">Average Fare</span>
                    <span class="metric-value">
                        Ksh <?php echo $trip_count > 0 ? number_format($monthly_spent / $trip_count, 0) : 0; ?>
                    </span>
                </div>
                
                <div class="metric-item">
                    <span class="metric-label">Projected Monthly</span>
                    <span class="metric-value">Ksh <?php echo number_format($projected_monthly, 0); ?></span>
                </div>
                
                <div class="metric-item">
                    <span class="metric-label">Days Remaining</span>
                    <span class="metric-value"><?php echo date('t') - date('j'); ?> days</span>
                </div>
                
                <div class="metric-item">
                    <span class="metric-label">Budget Status</span>
                    <span class="metric-value">
                        <?php if ($budget_percentage >= 100): ?>
                            <span style="color: var(--danger);">Exceeded</span>
                        <?php elseif ($budget_percentage >= 80): ?>
                            <span style="color: var(--warning);">Near Limit</span>
                        <?php else: ?>
                            <span style="color: var(--success);">On Track</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Alerts Section -->
        <?php if (!empty($alerts)): ?>
            <div class="alerts-section">
                <?php foreach ($alerts as $alert): ?>
                    <div class="alert-card <?php echo $alert['type']; ?>">
                        <div class="alert-icon <?php echo $alert['type']; ?>">
                            <i class="fas fa-<?php echo $alert['icon']; ?>"></i>
                        </div>
                        <div class="alert-content">
                            <div class="alert-title"><?php echo $alert['title']; ?></div>
                            <div class="alert-message"><?php echo $alert['message']; ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Budget Setting Form -->
        <div class="budget-setting-card">
            <div class="setting-header">
                <i class="fas fa-edit"></i>
                <h2>Set Monthly Budget</h2>
            </div>
            
            <form method="POST" class="budget-form" id="budget-form">
                <input type="hidden" name="action" value="set_budget">
                
                <div class="form-group">
                    <label for="monthly_budget">Monthly Transport Budget (Ksh)</label>
                    <div class="input-with-icon">
                        <i class="fas fa-money-bill-wave"></i>
                        <input 
                            type="number" 
                            id="monthly_budget" 
                            name="monthly_budget" 
                            value="<?php echo $current_budget; ?>"
                            min="0"
                            step="100"
                            placeholder="Enter amount"
                            required
                        >
                    </div>
                    
                    <div class="suggested-budgets">
                        <span class="suggested-budget" onclick="setSuggestedBudget(2000)">Ksh 2,000</span>
                        <span class="suggested-budget" onclick="setSuggestedBudget(3000)">Ksh 3,000</span>
                        <span class="suggested-budget" onclick="setSuggestedBudget(5000)">Ksh 5,000</span>
                        <span class="suggested-budget" onclick="setSuggestedBudget(8000)">Ksh 8,000</span>
                        <span class="suggested-budget" onclick="setSuggestedBudget(10000)">Ksh 10,000</span>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Save Budget
                </button>
            </form>
        </div>

        <!-- Notification Settings -->
        <div class="notification-settings">
            <div class="setting-header">
                <i class="fas fa-bell"></i>
                <h2>Budget Notifications</h2>
            </div>
            
            <form method="POST" id="notification-form">
                <input type="hidden" name="action" value="set_notification">
                
                <div class="toggle-switch">
                    <div class="toggle-label">
                        <i class="fas fa-bell"></i>
                        Enable budget alerts
                    </div>
                    <label class="switch">
                        <input 
                            type="checkbox" 
                            name="enable_notifications" 
                            <?php echo $notifications_enabled ? 'checked' : ''; ?>
                            onchange="toggleNotifications(this)"
                        >
                        <span class="slider"></span>
                    </label>
                </div>
                
                <div class="threshold-slider" id="threshold-control" style="<?php echo !$notifications_enabled ? 'opacity: 0.5; pointer-events: none;' : ''; ?>">
                    <div class="threshold-value">
                        <span>Alert me when I reach</span>
                        <span><span id="threshold-display"><?php echo $notification_threshold; ?></span>% of budget</span>
                    </div>
                    <input 
                        type="range" 
                        id="notification_threshold" 
                        name="notification_threshold" 
                        min="50" 
                        max="95" 
                        step="5"
                        value="<?php echo $notification_threshold; ?>"
                        oninput="updateThreshold(this.value)"
                        <?php echo !$notifications_enabled ? 'disabled' : ''; ?>
                    >
                </div>
                
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-save"></i>
                    Save Notification Settings
                </button>
            </form>
        </div>

        <!-- Smart Tips -->
        <div class="tips-section">
            <div class="tips-header">
                <i class="fas fa-lightbulb"></i>
                <h2>Smart Budget Tips</h2>
            </div>
            
            <div class="tips-grid">
                <?php foreach ($tips as $tip): ?>
                    <div class="tip-card">
                        <div class="tip-icon">
                            <i class="fas <?php echo $tip['icon']; ?>"></i>
                        </div>
                        <div class="tip-title"><?php echo $tip['title']; ?></div>
                        <div class="tip-message"><?php echo $tip['message']; ?></div>
                    </div>
                <?php endforeach; ?>
                
                <div class="tip-card">
                    <div class="tip-icon">
                        <i class="fas fa-piggy-bank"></i>
                    </div>
                    <div class="tip-title">Weekly Budget Breakdown</div>
                    <div class="tip-message">
                        <?php 
                        $weekly_budget = $current_budget / 4;
                        $weekly_spent = $monthly_spent / 4;
                        echo "Aim to spend Ksh " . number_format($weekly_budget, 0) . " per week";
                        ?>
                    </div>
                </div>
                
                <div class="tip-card">
                    <div class="tip-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="tip-title">Track Peak Days</div>
                    <div class="tip-message">Your spending peaks mid-month. Plan ahead for those days.</div>
                </div>
                
                <div class="tip-card">
                    <div class="tip-icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="tip-title">Alternative Routes</div>
                    <div class="tip-message">Try different SACCOs - some offer lower rates for the same route.</div>
                </div>
            </div>
        </div>

        <!-- Spending History -->
        <div class="history-section">
            <div class="history-header">
                <h2>
                    <i class="fas fa-history"></i>
                    Recent Spending History
                </h2>
                <a href="spending.php" style="color: var(--primary); text-decoration: none;">
                    View Full Analytics <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <div class="history-grid">
                <?php 
                if (!empty($history)) {
                    foreach ($history as $item) {
                        $month_name = date('M', mktime(0, 0, 0, $item['month'], 1));
                        ?>
                        <div class="history-item">
                            <div class="history-month"><?php echo $month_name . ' ' . $item['year']; ?></div>
                            <div class="history-amount">Ksh <?php echo number_format($item['total'], 0); ?></div>
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <div style="grid-column: 1 / -1; text-align: center; color: var(--gray);">
                        <i class="fas fa-chart-bar" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                        <p>No spending history yet. Start taking trips to see your history!</p>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <script>
        // Set suggested budget
        function setSuggestedBudget(amount) {
            document.getElementById('monthly_budget').value = amount;
            // Highlight the selected suggestion
            const suggestions = document.querySelectorAll('.suggested-budget');
            suggestions.forEach(s => s.style.background = 'var(--light-gray)');
            event.target.style.background = 'var(--secondary)';
            event.target.style.color = 'white';
        }
        
        // Update threshold display
        function updateThreshold(value) {
            document.getElementById('threshold-display').textContent = value;
        }
        
        // Toggle notifications
        function toggleNotifications(checkbox) {
            const thresholdControl = document.getElementById('threshold-control');
            const thresholdInput = document.getElementById('notification_threshold');
            
            if (checkbox.checked) {
                thresholdControl.style.opacity = '1';
                thresholdControl.style.pointerEvents = 'auto';
                thresholdInput.disabled = false;
            } else {
                thresholdControl.style.opacity = '0.5';
                thresholdControl.style.pointerEvents = 'none';
                thresholdInput.disabled = true;
            }
        }
        
        // Form validation
        document.getElementById('budget-form').addEventListener('submit', function(e) {
            const budget = document.getElementById('monthly_budget').value;
            
            if (budget < 0) {
                e.preventDefault();
                alert('Please enter a valid budget amount.');
            }
        });
        
        // Animate progress bar on load
        document.addEventListener('DOMContentLoaded', function() {
            const progressFill = document.querySelector('.progress-fill');
            if (progressFill) {
                const width = progressFill.style.width;
                progressFill.style.width = '0%';
                setTimeout(() => {
                    progressFill.style.width = width;
                }, 300);
            }
            
            // Add fade-in animation to cards
            const cards = document.querySelectorAll('.budget-setting-card, .notification-settings, .tips-section, .history-section');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 200 + (index * 100));
            });
        });
        
        // Confirmation before setting very high budget
        document.getElementById('budget-form').addEventListener('submit', function(e) {
            const budget = parseFloat(document.getElementById('monthly_budget').value);
            
            if (budget > 20000) {
                if (!confirm('This is a very high budget. Are you sure you want to set it to Ksh ' + budget.toLocaleString() + '?')) {
                    e.preventDefault();
                }
            }
        });
        
        // Show quick tip on hover
        const tipCards = document.querySelectorAll('.tip-card');
        tipCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.boxShadow = '0 8px 16px rgba(0, 0, 0, 0.1)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.boxShadow = 'none';
            });
        });
    </script>
</body>
</html>