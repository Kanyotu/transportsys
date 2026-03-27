<?php
// Start session safely
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

// Filter Inputs
$filter_type = $_GET['type'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$where_conditions = ["userid = ?"];
$params = [$user_id];
$types = "i";

if ($filter_type !== 'all') {
    $where_conditions[] = "type = ?";
    $params[] = $filter_type;
    $types .= "s";
}

if (!empty($date_from)) {
    $where_conditions[] = "date >= ?";
    $params[] = $date_from . " 00:00:00";
    $types .= "s";
}

if (!empty($date_to)) {
    $where_conditions[] = "date <= ?";
    $params[] = $date_to . " 23:59:59";
    $types .= "s";
}

$where_clause = implode(' AND ', $where_conditions);
$sql = "SELECT * FROM complaint WHERE $where_clause ORDER BY date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$complaints = [];
while ($row = $result->fetch_assoc()) {
    $complaints[] = $row;
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Complaints | SafiriPay</title>
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
            --secondary: #20c997;
            --danger: #dc3545;
            --warning: #ffc107;
            --success: #28a745;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
        }

        body {
            background: #f5f7fb;
        }

        .complaints-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        /* Header */
        .complaints-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .complaints-header h1 {
            font-size: 2rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-new {
            background: var(--primary);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-new:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--gray);
            font-size: 0.9rem;
        }

        /* Complaint Cards */
        .complaint-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-left: 4px solid;
            transition: all 0.3s ease;
        }

        .complaint-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .complaint-card.car {
            border-left-color: var(--primary);
        }

        .complaint-card.driver {
            border-left-color: var(--warning);
        }

        .complaint-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .complaint-type {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .type-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .type-badge.car {
            background: rgba(15, 81, 50, 0.1);
            color: var(--primary);
        }

        .type-badge.driver {
            background: rgba(255, 193, 7, 0.1);
            color: #856404;
        }

        .complaint-date {
            color: var(--gray);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .complaint-id {
            font-size: 0.9rem;
            color: var(--gray);
            background: var(--light);
            padding: 4px 10px;
            border-radius: 20px;
        }

        .complaint-description {
            color: var(--dark);
            line-height: 1.6;
            margin-bottom: 1rem;
            padding: 1rem;
            background: var(--light);
            border-radius: 8px;
        }

        .complaint-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: var(--gray);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--gray);
            opacity: 0.3;
            margin-bottom: 1rem;
        }

        .empty-state h2 {
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: var(--gray);
            margin-bottom: 2rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .complaint-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="complaints-container">
        <!-- Header -->
        <div class="complaints-header">
            <h1>
                <i class="fas fa-clipboard-list"></i>
                My Complaints
            </h1>
            <a href="submit_complaint.php" class="btn-new">
                <i class="fas fa-plus"></i>
                New Complaint
            </a>
        </div>

        <!-- Filters -->
        <div style="background: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);">
            <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <label style="font-size: 0.85rem; font-weight: 600; color: var(--gray);">Complaint Type</label>
                    <select name="type" style="padding: 10px; border-radius: 8px; border: 1px solid #ddd; background: #fff; width: 100%;">
                        <option value="all" <?php echo $filter_type == 'all' ? 'selected' : ''; ?>>All Types</option>
                        <option value="car" <?php echo $filter_type == 'car' ? 'selected' : ''; ?>>Vehicle Issues</option>
                        <option value="driver" <?php echo $filter_type == 'driver' ? 'selected' : ''; ?>>Driver Issues</option>
                    </select>
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <label style="font-size: 0.85rem; font-weight: 600; color: var(--gray);">From Date</label>
                    <input type="date" name="date_from" value="<?php echo $date_from; ?>" style="padding: 10px; border-radius: 8px; border: 1px solid #ddd; width: 100%;">
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <label style="font-size: 0.85rem; font-weight: 600; color: var(--gray);">To Date</label>
                    <input type="date" name="date_to" value="<?php echo $date_to; ?>" style="padding: 10px; border-radius: 8px; border: 1px solid #ddd; width: 100%;">
                </div>
                <div style="display: flex; gap: 0.5rem; height: 42px;">
                    <button type="submit" style="flex: 1; background: var(--primary); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="fas fa-filter"></i> Apply
                    </button>
                    <a href="my_complaints.php" style="width: 42px; background: #f0f0f0; color: var(--dark); text-decoration: none; border-radius: 8px; display: flex; align-items: center; justify-content: center; transition: background 0.3s;">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </form>
        </div>

        <?php if (!empty($complaints)): ?>
            <!-- Statistics -->
            <?php
            $total_complaints = count($complaints);
            $car_complaints = count(array_filter($complaints, fn($c) => $c['type'] == 'car'));
            $driver_complaints = count(array_filter($complaints, fn($c) => $c['type'] == 'driver'));
            ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_complaints; ?></div>
                    <div class="stat-label">Total Complaints</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $car_complaints; ?></div>
                    <div class="stat-label">Vehicle Complaints</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $driver_complaints; ?></div>
                    <div class="stat-label">Driver Complaints</div>
                </div>
            </div>

            <!-- Complaints List -->
            <?php foreach ($complaints as $complaint): ?>
                <div class="complaint-card <?php echo $complaint['type']; ?>">
                    <div class="complaint-header">
                        <div class="complaint-type">
                            <span class="type-badge <?php echo $complaint['type']; ?>">
                                <i class="fas fa-<?php echo $complaint['type'] == 'car' ? 'bus' : 'user'; ?>"></i>
                                <?php echo ucfirst($complaint['type']); ?> Complaint
                            </span>
                            <span class="complaint-id">
                                #<?php echo str_pad($complaint['complaintid'], 6, '0', STR_PAD_LEFT); ?>
                            </span>
                            <?php if (!empty($complaint['platenumber'])): ?>
                                <span class="complaint-id" style="background: rgba(15, 81, 50, 0.1); color: var(--primary);">
                                    <i class="fas fa-id-card"></i> <?php echo htmlspecialchars($complaint['platenumber']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="complaint-date">
                            <i class="far fa-calendar"></i>
                            <?php echo date('F j, Y', strtotime($complaint['date'])); ?>
                        </div>
                    </div>
                    
                    <div class="complaint-description">
                        <?php echo nl2br(htmlspecialchars($complaint['description'])); ?>
                    </div>
                    
                    <div class="complaint-footer">
                        <span>
                            <i class="far fa-clock"></i>
                            Submitted <?php echo time_elapsed_string($complaint['date']); ?>
                        </span>
                        <span style="color: var(--success);">
                            <i class="fas fa-check-circle"></i> Under Review
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <h2>No Complaints Yet</h2>
                <p>You haven't submitted any complaints. If you've experienced an issue, let us know!</p>
                <a href="submit_complaint.php" class="btn-new" style="display: inline-flex;">
                    <i class="fas fa-plus"></i>
                    Submit Your First Complaint
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php
    // Helper function to show time elapsed
    function time_elapsed_string($datetime) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
        
        if ($diff->d > 7) {
            return 'over a week ago';
        } elseif ($diff->d > 0) {
            return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
        } elseif ($diff->h > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        } elseif ($diff->i > 0) {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        } else {
            return 'just now';
        }
    }
    ?>
</body>
</html>