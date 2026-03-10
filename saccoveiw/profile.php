<?php
session_start();
if(!isset($_SESSION['sacco_manager_id'])) {
    header("Location: login.php");
    exit();
}
include 'database.php';

$sacco_id = $_SESSION['sacco_id'];
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sacconame = mysqli_real_escape_string($conn, $_POST['sacconame']);
    $shortcode = mysqli_real_escape_string($conn, $_POST['shortcode']);
    $qr = mysqli_real_escape_string($conn, $_POST['qr']);

    $stmt = $conn->prepare("UPDATE saccos SET sacconame = ?, mpesashortcode = ?, qr_identifier = ? WHERE saccoid = ?");
    $stmt->bind_param("sssi", $sacconame, $shortcode, $qr, $sacco_id);
    
    if($stmt->execute()) {
        $message = "Profile updated successfully!";
    } else {
        $message = "Error updating profile.";
    }
}

$sacco = $conn->query("SELECT * FROM saccos WHERE saccoid = $sacco_id")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SACCO Profile | SafiriPay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="sacco.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar (Same as dashboard) -->
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <header class="header">
                <div class="header-title">
                    <h1>SACCO Profile Management</h1>
                </div>
                <div class="header-actions">
                    <button class="theme-toggle" id="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            </header>

            <div class="data-card" style="max-width: 600px;">
                <?php if($message): ?>
                    <div style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 12px; margin-bottom: 2rem; font-weight: 600;">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="input-group">
                        <label>SACCO Name</label>
                        <input type="text" name="sacconame" value="<?php echo htmlspecialchars($sacco['sacconame']); ?>" required>
                    </div>
                    <div class="input-group">
                        <label>M-Pesa Shortcode</label>
                        <input type="text" name="shortcode" value="<?php echo htmlspecialchars($sacco['mpesashortcode']); ?>" required>
                    </div>
                    <div class="input-group">
                        <label>QR Identifier</label>
                        <input type="text" name="qr" value="<?php echo htmlspecialchars($sacco['qr_identifier']); ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Update SACCO Details</button>
                </form>
            </div>
        </main>
    </div>
    <script src="darkmode.js"></script>
</body>
</html>
