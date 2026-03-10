<?php
session_start();
if(!isset($_SESSION['sacco_manager_id'])) {
    header("Location: login.php");
    exit();
}
include 'database.php';

$sacco_id = $_SESSION['sacco_id'];

// Fetch reviews from users who have traveled with this SACCO
// Mapping complaints to the SACCO's users as a proxy for customer feedback
$reviews = $conn->query("
    SELECT c.*, u.username, u.phoneno 
    FROM complaint c
    JOIN users u ON c.userid = u.userid
    WHERE u.userid IN (SELECT userid FROM tripsessions WHERE saccoid = $sacco_id)
    ORDER BY c.date DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Reviews | SafiriPay SACCO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="sacco.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <header class="header">
                <div class="header-title">
                    <h1>Passenger Feedback</h1>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Improve your service by listening to your customers.</p>
                </div>
                <div class="header-actions">
                    <button class="theme-toggle" id="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            </header>

            <div class="data-card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Category</th>
                                <th>Feedback</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($reviews && $reviews->num_rows > 0): ?>
                                <?php while($row = $reviews->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($row['username']); ?></div>
                                            <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($row['phoneno']); ?></div>
                                        </td>
                                        <td>
                                            <span class="status-badge" style="background: rgba(249, 115, 22, 0.1); color: #f97316;">
                                                <i class="fas fa-tag"></i> <?php echo strtoupper($row['type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="max-width: 500px; font-size: 0.875rem; line-height: 1.5; color: var(--text-main);">
                                                "<?php echo htmlspecialchars($row['description']); ?>"
                                            </div>
                                        </td>
                                        <td style="color: var(--text-muted); font-size: 0.8125rem;">
                                            <?php echo date('M d, Y', strtotime($row['date'])); ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                                        <i class="fas fa-comment-slash" style="font-size: 2rem; display: block; margin-bottom: 1rem;"></i>
                                        No reviews found yet.
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
