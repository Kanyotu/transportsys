<?php
session_start();
if(!isset($_SESSION['sacco_manager_id'])) {
    header("Location: login.php");
    exit();
}
include 'database.php';

$sacco_id = $_SESSION['sacco_id'];
$message = "";

// Handle Driver Addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_driver'])) {
    $dname = mysqli_real_escape_string($conn, $_POST['dname']);
    $phoneno = mysqli_real_escape_string($conn, $_POST['phoneno']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    $stmt = $conn->prepare("INSERT INTO drivers (dname, phoneno, email, saccoid) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $dname, $phoneno, $email, $sacco_id);
    
    if($stmt->execute()) {
        $message = "Driver added successfully!";
    } else {
        $message = "Error adding driver: " . $stmt->error;
    }
}

// Handle Password Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['set_password'])) {
    $driverid = intval($_POST['driverid']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("UPDATE drivers SET hashedpassword = ? WHERE driverid = ? AND saccoid = ?");
    $stmt->bind_param("sii", $password, $driverid, $sacco_id);
    
    if($stmt->execute()) {
        $message = "Password updated successfully for the driver!";
    } else {
        $message = "Error updating password.";
    }
}

// Fetch Drivers for this SACCO
// Note: We'll fetch drivers where saccoid is this SACCO's ID, 
// AND we'll also fetch drivers assigned to buses of this SACCO (in case they weren't assigned a saccoid in drivers table yet)
$drivers = $conn->query("
    SELECT DISTINCT d.* 
    FROM drivers d
    LEFT JOIN buses b ON d.driverid = b.driverid
    WHERE d.saccoid = $sacco_id OR b.saccoid = $sacco_id
    ORDER BY d.dname ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Drivers | SafiriPay SACCO</title>
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
                    <h1>Driver Management</h1>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Add drivers and manage their portal access.</p>
                </div>
                <div class="header-actions">
                    <button class="theme-toggle" id="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                    <button class="btn btn-primary" onclick="document.getElementById('addDriverModal').style.display='flex'">
                        <i class="fas fa-user-plus"></i> Add Driver
                    </button>
                </div>
            </header>

            <?php if($message): ?>
                <div style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 12px; margin-bottom: 2rem; font-weight: 600;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="data-card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Driver Name</th>
                                <th>Phone Number</th>
                                <th>Email</th>
                                <th>Login Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($drivers && $drivers->num_rows > 0): ?>
                                <?php while($row = $drivers->fetch_assoc()): ?>
                                    <tr>
                                        <td style="font-weight: 700;"><?php echo htmlspecialchars($row['dname']); ?></td>
                                        <td><?php echo htmlspecialchars($row['phoneno']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email'] ?: '-'); ?></td>
                                        <td>
                                            <?php if($row['hashedpassword']): ?>
                                                <span class="status-badge" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">Password Set</span>
                                            <?php else: ?>
                                                <span class="status-badge" style="background: rgba(245, 158, 11, 0.1); color: var(--warning);">No Password</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn" 
                                                    onclick="openSetPasswordModal(<?php echo $row['driverid']; ?>, '<?php echo htmlspecialchars($row['dname']); ?>')"
                                                    style="padding: 0.5rem; background: var(--bg-main); color: var(--primary);" 
                                                    title="Set/Reset Password">
                                                <i class="fas fa-key"></i> Set Password
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                                        No drivers found for your SACCO.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Driver Modal -->
    <div id="addDriverModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
        <div class="data-card" style="width:400px;">
            <h2 style="margin-bottom:1.5rem;">Add New Driver</h2>
            <form method="POST">
                <div class="input-group">
                    <label>Full Name</label>
                    <input type="text" name="dname" placeholder="Enter driver's name" required>
                </div>
                <div class="input-group">
                    <label>Phone Number</label>
                    <input type="text" name="phoneno" placeholder="07XXXXXXXX" required>
                </div>
                <div class="input-group">
                    <label>Email Address (Optional)</label>
                    <input type="email" name="email" placeholder="driver@example.com">
                </div>
                <div style="display:flex; gap:10px; margin-top:1rem;">
                    <button type="submit" name="add_driver" class="btn btn-primary" style="flex:1;">Add Driver</button>
                    <button type="button" onclick="document.getElementById('addDriverModal').style.display='none'" class="btn" style="flex:1; background:var(--bg-main);">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Set Password Modal -->
    <div id="setPasswordModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
        <div class="data-card" style="width:400px;">
            <h2 style="margin-bottom:0.5rem;">Set Driver Password</h2>
            <p id="modalDriverName" style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 1.5rem;"></p>
            <form method="POST">
                <input type="hidden" name="driverid" id="modalDriverId">
                <div class="input-group">
                    <label>New Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <div style="display:flex; gap:10px; margin-top:1rem;">
                    <button type="submit" name="set_password" class="btn btn-primary" style="flex:1;">Save Password</button>
                    <button type="button" onclick="document.getElementById('setPasswordModal').style.display='none'" class="btn" style="flex:1; background:var(--bg-main);">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="darkmode.js"></script>
    <script>
        function openSetPasswordModal(id, name) {
            document.getElementById('modalDriverId').value = id;
            document.getElementById('modalDriverName').innerText = "Driver: " + name;
            document.getElementById('setPasswordModal').style.display = 'flex';
        }
    </script>
</body>
</html>
