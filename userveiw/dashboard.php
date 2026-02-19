
<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

include 'database.php';
if (!$user_id) {
    header("Location: login.php");
    exit();
}
$sql = "SELECT * FROM users WHERE userid = ?";
$stmt = $conn->prepare($sql);  
if (!$stmt) {
    echo "Database error.".$conn->error;
    exit();
} 
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if (!$result->num_rows === 1) {
    $conn->close();
    header("Location: login.php");
    exit();
}

// Get user info
$sql = "SELECT * FROM users WHERE userid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get recent trips (limit 5)
$sql = "
    SELECT ts.*, r.routename, p.amount, p.createdat, 
           s1.stagename as from_stage, s2.stagename as to_stage
    FROM tripsessions ts
    JOIN trips t ON ts.tripid = t.tripid
    JOIN routes r ON t.routeid = r.routeid
    JOIN stages s1 ON ts.fromstageid = s1.stageid
    JOIN stages s2 ON ts.tostageid = s2.stageid
    JOIN payments p ON ts.sessionid = p.sessionid
    WHERE ts.userid = ? AND p.status = 'completed'
    ORDER BY p.createdat DESC
    LIMIT 5
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_trips_result = $stmt->get_result();
$recent_trips = [];
while($row = $recent_trips_result->fetch_assoc()) {
    $recent_trips[] = $row;
}

// Get total trips count
$sql = "
    SELECT COUNT(*) as total_trips 
    FROM tripsessions ts
    JOIN payments p ON ts.sessionid = p.sessionid
    WHERE ts.userid = ? AND p.status = 'completed'
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_trips_row = $stmt->get_result()->fetch_assoc();
$total_trips = $total_trips_row['total_trips'] ?? 0;

// Get total spending
$sql = "
    SELECT SUM(p.amount) as total_spent 
    FROM payments p
    JOIN tripsessions ts ON p.sessionid = ts.sessionid
    WHERE ts.userid = ? AND p.status = 'completed'
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_spent_row = $stmt->get_result()->fetch_assoc();
$total_spent = $total_spent_row['total_spent'] ?? 0;

// Get current month spending
$current_month = date('m');
$current_year = date('Y');
$sql = "
    SELECT SUM(p.amount) as monthly_spent 
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
$monthly_spent_row = $stmt->get_result()->fetch_assoc();
$monthly_spent = $monthly_spent_row['monthly_spent'] ?? 0;

// Get monthly budget
$budget = $user['monthlybudget'] ?? 0;
$budget_percentage = $budget > 0 ? min(100, ($monthly_spent / $budget) * 100) : 0;

// Get spending summary for chart
$sql = "
    SELECT ss.date, ss.totalamount 
    FROM spendingsummary ss
    WHERE ss.userid = ?
    ORDER BY ss.date DESC
    LIMIT 6
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo "Database error.".$conn->error;
    exit();
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$spending_history_result = $stmt->get_result();
$spending_history = [];
while($row = $spending_history_result->fetch_assoc()) {
    $spending_history[] = $row;
}

// Close statement
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passenger Dashboard | SafiriPay</title>
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
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            background-color: #f5f7fb;
            color: var(--dark);
            line-height: 1.6;
        }

        /* Dashboard Container */
        .dashboard-content {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--card-shadow);
        }

        .welcome-text h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .welcome-text p {
            opacity: 0.9;
        }

        .scan-button {
            background: var(--secondary);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
        }

        .scan-button:hover {
            background: #1ba87e;
            transform: translateY(-2px);
        }

        /* Quick Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
        }

        .stat-icon.trips {
            background: linear-gradient(135deg, #0f5132 0%, #20c997 100%);
        }

        .stat-icon.spending {
            background: linear-gradient(135deg, #20c997 0%, #0f5132 100%);
        }

        .stat-icon.budget {
            background: linear-gradient(135deg, #198754 0%, #0f5132 100%);
        }

        .stat-info h3 {
            font-size: 0.9rem;
            color: var(--gray);
            margin-bottom: 5px;
        }

        .stat-info .value {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--dark);
        }

        .stat-info .subtext {
            font-size: 0.85rem;
            color: var(--gray);
        }

        /* Budget Progress */
        .budget-progress {
            margin-top: 10px;
        }

        .progress-bar {
            height: 8px;
            background: var(--light-gray);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 8px;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
            background: linear-gradient(90deg, #20c997 0%, #0f5132 100%);
            width: <?php echo $budget_percentage; ?>%;
            transition: width 1s ease;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .action-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            color: var(--dark);
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .action-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background: var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--primary);
        }

        .action-text h3 {
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .action-text p {
            font-size: 0.9rem;
            color: var(--gray);
        }

        /* Recent Trips */
        .section-title {
            font-size: 1.4rem;
            margin-bottom: 1.5rem;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--primary);
        }

        .recent-trips {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
        }

        .trips-table {
            width: 100%;
            border-collapse: collapse;
        }

        .trips-table th {
            text-align: left;
            padding: 12px 15px;
            border-bottom: 2px solid var(--light-gray);
            color: var(--gray);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .trips-table td {
            padding: 15px;
            border-bottom: 1px solid var(--light-gray);
        }

        .trip-route {
            font-weight: 600;
            color: var(--dark);
        }

        .trip-stops {
            font-size: 0.9rem;
            color: var(--gray);
        }

        .trip-amount {
            font-weight: bold;
            color: var(--primary);
        }

        .trip-date {
            font-size: 0.85rem;
            color: var(--gray);
        }

        .trip-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-completed {
            background: rgba(32, 201, 151, 0.1);
            color: #20c997;
        }

        /* Spending Chart */
        .spending-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
        }

        .chart-container {
            height: 250px;
            margin-top: 1rem;
            position: relative;
        }

        .chart-bars {
            display: flex;
            align-items: flex-end;
            height: 200px;
            gap: 30px;
            padding: 0 20px;
            border-bottom: 1px solid var(--light-gray);
        }

        .chart-bar {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .bar {
            width: 30px;
            background: linear-gradient(to top, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 4px 4px 0 0;
            transition: height 1s ease;
        }

        .bar-label {
            margin-top: 10px;
            font-size: 0.85rem;
            color: var(--gray);
        }

        .bar-value {
            margin-top: 5px;
            font-size: 0.8rem;
            font-weight: bold;
            color: var(--dark);
        }

        .chart-labels {
            display: flex;
            justify-content: space-between;
            padding: 10px 20px 0;
            color: var(--gray);
            font-size: 0.9rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-content {
                padding: 1rem;
            }
            
            .welcome-section {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .trips-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="dashboard-content">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="welcome-text">
                <h1>Welcome back, <?php echo htmlspecialchars(ucfirst($username)); ?>!</h1>
                <p>Track your trips, manage your budget, and travel smarter.</p>
            </div>
            <a href="scan_qr.php" class="scan-button">
                <i class="fas fa-qrcode"></i>
                Scan QR to Ride
            </a>
        </div>

        <!-- Quick Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon trips">
                    <i class="fas fa-route"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Trips</h3>
                    <div class="value"><?php echo $total_trips; ?></div>
                    <div class="subtext">Journeys completed</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon spending">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Spent</h3>
                    <div class="value">Ksh <?php echo number_format($total_spent, 0); ?></div>
                    <div class="subtext">All-time transport cost</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon budget">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="stat-info">
                    <h3>Monthly Budget</h3>
                    <div class="value">Ksh <?php echo number_format($budget, 0); ?></div>
                    <div class="subtext">
                        <div class="budget-progress">
                            <span>Used: Ksh <?php echo number_format($monthly_spent, 0); ?></span>
                            <div class="progress-bar">
                                <div class="progress-fill"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="scan_qr.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-qrcode"></i>
                </div>
                <div class="action-text">
                    <h3>Scan & Ride</h3>
                    <p>Scan QR code to board a bus</p>
                </div>
                
            </a>
            <a href="my_complaints.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-qrcode"></i>
                </div>
                <div class="action-text">
                    <h3>View Complaints</h3>
                    <p>View all your complaints</p>
                </div>
                
            </a>
            
            <a href="trip_history.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-history"></i>
                </div>
                <div class="action-text">
                    <h3>Trip History</h3>
                    <p>View all your past journeys</p>
                </div>
            </a>
            
            <a href="budget_setting.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div class="action-text">
                    <h3>Spending Analytics</h3>
                    <p>Track your transport budget</p>
                </div>
            </a>
            
            <a href="profile.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-user"></i>
                </div>
                <div class="action-text">
                    <h3>My Profile</h3>
                    <p>Update your account details</p>
                </div>
            </a>
        </div>

        <!-- Recent Trips -->
        <div class="recent-trips">
            <h2 class="section-title"><i class="fas fa-clock"></i> Recent Trips</h2>
            
            <?php if (count($recent_trips) > 0): ?>
                <table class="trips-table">
                    <thead>
                        <tr>
                            <th>Route</th>
                            <th>Stops</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_trips as $trip): ?>
                            <tr>
                                <td>
                                    <div class="trip-route"><?php echo htmlspecialchars($trip['routename']); ?></div>
                                </td>
                                <td>
                                    <div class="trip-stops">
                                        <?php echo htmlspecialchars($trip['from_stage']); ?> → 
                                        <?php echo htmlspecialchars($trip['to_stage']); ?>
                                    </div>
                                </td>
                                <td class="trip-amount">Ksh <?php echo number_format($trip['amount'], 0); ?></td>
                                <td class="trip-date"><?php echo date('M j, Y', strtotime($trip['createdat'])); ?></td>
                                <td>
                                    <span class="trip-status status-completed">Completed</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-route"></i>
                    <h3>No trips yet</h3>
                    <p>Start your first journey by scanning a QR code!</p>
                    <a href="scan_qr.php" style="display: inline-block; margin-top: 15px; padding: 10px 20px; background: var(--primary); color: white; text-decoration: none; border-radius: 5px;">Scan QR Now</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Spending Chart -->
        <div class="spending-section">
            <h2 class="section-title"><i class="fas fa-chart-line"></i> Spending Overview</h2>
            
            <?php if (count($spending_history) > 0): ?>
                <div class="chart-container">
                    <div class="chart-bars" id="chart-bars">
                        <?php 
                        $max_amount = max(array_column($spending_history, 'totalamount'));
                        foreach ($spending_history as $month_data): 
                            $height = $max_amount > 0 ? ($month_data['totalamount'] / $max_amount) * 100 : 0;
                            $month_name = date('M', mktime(0, 0, 0, $month_data['month'], 1));
                        ?>
                            <div class="chart-bar">
                                <div class="bar" style="height: <?php echo $height; ?>%"></div>
                                <div class="bar-label"><?php echo $month_name; ?></div>
                                <div class="bar-value">Ksh <?php echo number_format($month_data['totalamount'], 0); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="chart-labels">
                        <span>Months</span>
                        <span>Spending (Ksh)</span>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-chart-bar"></i>
                    <h3>No spending data yet</h3>
                    <p>Your spending chart will appear here after a few trips.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Live time update
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit',
                hour12: true 
            });
        }
        
        // Animate chart bars on page load
        document.addEventListener('DOMContentLoaded', function() {
            const bars = document.querySelectorAll('.bar');
            bars.forEach(bar => {
                const originalHeight = bar.style.height;
                bar.style.height = '0%';
                setTimeout(() => {
                    bar.style.height = originalHeight;
                }, 300);
            });
            
            // Add hover effect to action cards
            const actionCards = document.querySelectorAll('.action-card');
            actionCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
            
            // Add animation to stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.style.animation = 'fadeInUp 0.5s ease forwards';
                card.style.opacity = '0';
            });
        });
        
        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .stat-card, .action-card, .recent-trips, .spending-section {
                animation: fadeInUp 0.5s ease forwards;
                opacity: 0;
            }
            
            .action-card:nth-child(1) { animation-delay: 0.1s; }
            .action-card:nth-child(2) { animation-delay: 0.2s; }
            .action-card:nth-child(3) { animation-delay: 0.3s; }
            .action-card:nth-child(4) { animation-delay: 0.4s; }
            
            .recent-trips { animation-delay: 0.5s; }
            .spending-section { animation-delay: 0.6s; }
        `;
        document.head.appendChild(style);
        
        // Check for budget alerts
        <?php if ($budget > 0 && $budget_percentage >= 80): ?>
        setTimeout(() => {
            alert('⚠️ Budget Alert: You\'ve used <?php echo round($budget_percentage); ?>% of your monthly budget!');
        }, 1000);
        <?php endif; ?>
    </script>
</body>
</html>