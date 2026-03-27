<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
include 'database.php';

$message = "";
$message_type = "success";

// Handle SACCO Registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_sacco'])) {
    $sacconame = mysqli_real_escape_string($conn, $_POST['sacconame']);
    $shortcode = mysqli_real_escape_string($conn, $_POST['mpesashortcode']);
    $passkey = mysqli_real_escape_string($conn, $_POST['mpesapasskey']);
    $qr_id = mysqli_real_escape_string($conn, $_POST['qr_identifier']);
    
    $m_username = mysqli_real_escape_string($conn, $_POST['manager_username']);
    $m_phone = mysqli_real_escape_string($conn, $_POST['manager_phone']);
    $m_password = password_hash($_POST['manager_password'], PASSWORD_DEFAULT);

    // Check for duplicates
    $check_qr = $conn->query("SELECT saccoid FROM saccos WHERE qr_identifier = '$qr_id'");
    $check_phone = $conn->query("SELECT userid FROM users WHERE phoneno = '$m_phone'");

    if ($check_qr->num_rows > 0) {
        $message = "Error: QR Identifier already in use.";
        $message_type = "error";
    } elseif ($check_phone->num_rows > 0) {
        $message = "Error: Phone number already registered to another account.";
        $message_type = "error";
    } else {
        // Start transaction
        $conn->begin_transaction();
        try {
            // 1. Insert SACCO
            $stmt = $conn->prepare("INSERT INTO saccos (sacconame, mpesashortcode, mpesapasskey, qr_identifier, status) VALUES (?, ?, ?, ?, 1)");
            $stmt->bind_param("ssss", $sacconame, $shortcode, $passkey, $qr_id);
            $stmt->execute();
            $sacco_id = $stmt->insert_id;

            // 2. Insert Manager User
            $stmt2 = $conn->prepare("INSERT INTO users (username, phoneno, hashedpassword, type, saccoid) VALUES (?, ?, ?, 'sacco', ?)");
            $stmt2->bind_param("sssi", $m_username, $m_phone, $m_password, $sacco_id);
            $stmt2->execute();

            $conn->commit();
            $message = "SACCO '$sacconame' and Manager account registered successfully!";
            $message_type = "success";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error during registration: " . $e->getMessage();
            $message_type = "error";
        }
    }
}

// Handle SACCO Editing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_sacco'])) {
    $id = intval($_POST['saccoid']);
    $sacconame = mysqli_real_escape_string($conn, $_POST['sacconame']);
    $shortcode = mysqli_real_escape_string($conn, $_POST['mpesashortcode']);
    $passkey = mysqli_real_escape_string($conn, $_POST['mpesapasskey']);
    $qr_id = mysqli_real_escape_string($conn, $_POST['qr_identifier']);
    
    // Check if new QR identifier already exists for a DIFFERENT SACCO
    $check_qr = $conn->query("SELECT saccoid FROM saccos WHERE qr_identifier = '$qr_id' AND saccoid != $id");
    
    if ($check_qr->num_rows > 0) {
        $message = "Error: QR Identifier already in use by another SACCO.";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare("UPDATE saccos SET sacconame = ?, mpesashortcode = ?, mpesapasskey = ?, qr_identifier = ? WHERE saccoid = ?");
        $stmt->bind_param("ssssi", $sacconame, $shortcode, $passkey, $qr_id, $id);
        
        if ($stmt->execute()) {
            $message = "SACCO details updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating SACCO: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    }
}

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
    header("Location: manage_saccos.php?msg=" . urlencode("Status updated successfully"));
    exit();
}

if(isset($_GET['msg'])) $message = $_GET['msg'];

// Filter Inputs
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

$where_conditions = ["1=1"];

if ($search != '') {
    $where_conditions[] = "(sacconame LIKE '%$search%' OR qr_identifier LIKE '%$search%' OR mpesashortcode LIKE '%$search%')";
}

if ($status_filter != 'all') {
    $where_conditions[] = "status = " . intval($status_filter);
}

$where_clause = implode(' AND ', $where_conditions);

// Fetch SACCOs
$query = "SELECT * FROM saccos WHERE $where_clause ORDER BY createdat DESC";
$saccos = $conn->query($query);
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
                <li><a href="manage_users.php"><i class="fas fa-users-cog"></i> <span>People</span></a></li>
                <li><a href="manage_saccos.php" class="active"><i class="fas fa-building"></i> <span>Sacco List</span></a></li>
                <li><a href="manage_routes.php"><i class="fas fa-route"></i> <span>Routes & Stages</span></a></li>
                <li><a href="manage_trips.php"><i class="fas fa-calendar-alt"></i> <span>Trips & Schedules</span></a></li>
                <li><a href="manage_bookings.php"><i class="fas fa-ticket-alt"></i> <span>Bookings</span></a></li>
                <li><a href="manage_payments.php"><i class="fas fa-file-invoice-dollar"></i> <span>Money</span></a></li>
                <li><a href="manage_feedback.php"><i class="fas fa-comment-dots"></i> <span>Talk</span></a></li>
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
                    <button class="btn btn-primary" onclick="document.getElementById('registerSaccoModal').style.display='flex'" style="padding: 0.6rem 1rem; font-size: 0.875rem;">
                        <i class="fas fa-plus"></i> Register SACCO
                    </button>
                </div>
            </header>
            
            <!-- Filter Section -->
            <div class="data-card" style="margin-bottom: 2rem; padding: 1.5rem 2rem;">
                <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; align-items: end;">
                    <div class="input-group" style="margin-bottom: 0;">
                        <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem; display: block;">SEARCH SACCOs</label>
                        <div style="position: relative;">
                            <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, QR, Shortcode..." style="padding-left: 35px; width: 100%;">
                        </div>
                    </div>
                    <div class="input-group" style="margin-bottom: 0;">
                        <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem; display: block;">STATUS</label>
                        <select name="status">
                            <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="1" <?php echo $status_filter == '1' ? 'selected' : ''; ?>>Approved</option>
                            <option value="0" <?php echo $status_filter == '0' ? 'selected' : ''; ?>>Deactivated</option>
                            <option value="2" <?php echo $status_filter == '2' ? 'selected' : ''; ?>>Pending</option>
                        </select>
                    </div>
                    <div style="display: flex; gap: 0.5rem; height: 42px;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="manage_saccos.php" class="btn" style="background: var(--border); color: var(--text-main); text-decoration: none; display: flex; align-items: center; justify-content: center; width: 42px;">
                            <i class="fas fa-undo"></i>
                        </a>
                    </div>
                    <div></div> <!-- Spacer -->
                </form>
            </div>

            <?php if ($message): ?>
                <div style="background: <?php echo ($message_type == 'error' || strpos($message, 'Error') !== false) ? 'rgba(239, 68, 68, 0.1)' : 'rgba(16, 185, 129, 0.1)'; ?>; 
                            color: <?php echo ($message_type == 'error' || strpos($message, 'Error') !== false) ? 'var(--danger)' : 'var(--success)'; ?>; 
                            padding: 1rem; border-radius: 12px; margin-bottom: 2rem; font-weight: 600;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

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
                                                <button onclick="viewSaccoDetails(<?php echo $row['saccoid']; ?>)" title="View Details" style="background: none; border: none; color: var(--primary); cursor: pointer;"><i class="fas fa-eye"></i></button>
                                                <button onclick="openEditSaccoModal(this)" 
                                                        data-id="<?php echo $row['saccoid']; ?>" 
                                                        data-name="<?php echo htmlspecialchars($row['sacconame']); ?>" 
                                                        data-shortcode="<?php echo htmlspecialchars($row['mpesashortcode']); ?>" 
                                                        data-passkey="<?php echo htmlspecialchars($row['mpesapasskey']); ?>" 
                                                        data-qr="<?php echo htmlspecialchars($row['qr_identifier']); ?>" 
                                                        title="Edit SACCO" style="background: none; border: none; color: var(--primary); cursor: pointer;">
                                                    <i class="fas fa-edit"></i>
                                                </button>
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

    <!-- Register SACCO Modal -->
    <div id="registerSaccoModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
        <div class="data-card" style="width:550px; max-height: 90vh; overflow-y: auto;">
            <h2 style="margin-bottom:1.5rem;">Register New SACCO</h2>
            <form method="POST">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div>
                        <h4 style="margin-bottom: 1rem; color: var(--primary); font-size: 0.9rem; text-transform: uppercase;">1. SACCO Details</h4>
                        <div class="input-group">
                            <label>SACCO Name</label>
                            <input type="text" name="sacconame" placeholder="e.g. Neo Trans" required>
                        </div>
                        <div class="input-group">
                            <label>QR Identifier</label>
                            <input type="text" name="qr_identifier" placeholder="e.g. NEO001" required>
                        </div>
                        <div class="input-group">
                            <label>M-Pesa Shortcode</label>
                            <input type="text" name="mpesashortcode" placeholder="6 digits" required>
                        </div>
                        <div class="input-group">
                            <label>M-Pesa Passkey</label>
                            <input type="password" name="mpesapasskey" placeholder="••••••••" required>
                        </div>
                    </div>
                    <div>
                        <h4 style="margin-bottom: 1rem; color: var(--primary); font-size: 0.9rem; text-transform: uppercase;">2. Manager Account</h4>
                        <div class="input-group">
                            <label>Full Name</label>
                            <input type="text" name="manager_username" placeholder="Manager's Name" required>
                        </div>
                        <div class="input-group">
                            <label>Phone Number</label>
                            <input type="text" name="manager_phone" placeholder="07XXXXXXXX" required>
                        </div>
                        <div class="input-group">
                            <label>Initial Password</label>
                            <input type="password" name="manager_password" placeholder="••••••••" required>
                        </div>
                    </div>
                </div>
                
                <div style="display:flex; gap:10px; margin-top:2rem;">
                    <button type="submit" name="register_sacco" class="btn btn-primary" style="flex:2;">Register SACCO</button>
                    <button type="button" onclick="document.getElementById('registerSaccoModal').style.display='none'" class="btn" style="flex:1; background:var(--bg-main);">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit SACCO Modal -->
    <div id="editSaccoModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
        <div class="data-card" style="width:400px;">
            <h2 style="margin-bottom:1.5rem;">Edit SACCO Details</h2>
            <form method="POST">
                <input type="hidden" name="saccoid" id="editSaccoId">
                <div class="input-group">
                    <label>SACCO Name</label>
                    <input type="text" name="sacconame" id="editSaccoName" required>
                </div>
                <div class="input-group">
                    <label>QR Identifier</label>
                    <input type="text" name="qr_identifier" id="editSaccoQr" required>
                </div>
                <div class="input-group">
                    <label>M-Pesa Shortcode</label>
                    <input type="text" name="mpesashortcode" id="editSaccoShortcode" required>
                </div>
                <div class="input-group">
                    <label>M-Pesa Passkey</label>
                    <input type="password" name="mpesapasskey" id="editSaccoPasskey" required>
                </div>
                <div style="display:flex; gap:10px; margin-top:2rem;">
                    <button type="submit" name="edit_sacco" class="btn btn-primary" style="flex:1;">Update SACCO</button>
                    <button type="button" onclick="document.getElementById('editSaccoModal').style.display='none'" class="btn" style="flex:1; background:var(--bg-main);">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View SACCO Details Modal -->
    <div id="viewSaccoModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
        <div class="data-card" style="width:600px; max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem;">
                <h2 id="viewSaccoNameHeader">SACCO Details</h2>
                <button onclick="document.getElementById('viewSaccoModal').style.display='none'" style="background:none; border:none; font-size:1.5rem; color:var(--text-muted); cursor:pointer;">&times;</button>
            </div>
            
            <div id="saccoDetailsContent">
                <div style="text-align: center; padding: 2rem;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary);"></i>
                </div>
            </div>
        </div>
    </div>

    <script src="darkmode.js"></script>
    <script>
        function viewSaccoDetails(id) {
            const modal = document.getElementById('viewSaccoModal');
            const content = document.getElementById('saccoDetailsContent');
            const header = document.getElementById('viewSaccoNameHeader');
            
            modal.style.display = 'flex';
            content.innerHTML = '<div style="text-align: center; padding: 2rem;"><i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary);"></i></div>';
            
            fetch('get_sacco_details.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        content.innerHTML = `<div style="color: var(--danger); text-align: center; padding: 2rem;">${data.error}</div>`;
                        return;
                    }
                    
                    header.innerText = data.sacco.sacconame;
                    
                    content.innerHTML = `
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                            <div>
                                <h4 style="color: var(--primary); margin-bottom: 1rem; font-size: 0.8rem; text-transform: uppercase;">Organization</h4>
                                <div style="margin-bottom: 1rem;">
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">QR Identifier</div>
                                    <div style="font-weight: 600;">${data.sacco.qr_identifier}</div>
                                </div>
                                <div style="margin-bottom: 1rem;">
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">M-Pesa Shortcode</div>
                                    <div style="font-weight: 600;">${data.sacco.mpesashortcode}</div>
                                </div>
                                <div style="margin-bottom: 1rem;">
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">Status</div>
                                    <div style="font-weight: 600;">${data.sacco.status == 1 ? 'Approved' : 'Inactive'}</div>
                                </div>
                                <div style="margin-bottom: 1rem;">
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">Date Registered</div>
                                    <div style="font-weight: 600;">${new Date(data.sacco.createdat).toLocaleDateString()}</div>
                                </div>
                            </div>
                            <div>
                                <h4 style="color: var(--primary); margin-bottom: 1rem; font-size: 0.8rem; text-transform: uppercase;">Manager Info</h4>
                                <div style="margin-bottom: 1rem;">
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">Name</div>
                                    <div style="font-weight: 600;">${data.manager.username}</div>
                                </div>
                                <div style="margin-bottom: 1rem;">
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">Phone</div>
                                    <div style="font-weight: 600;">${data.manager.phoneno}</div>
                                </div>
                                <div style="margin-bottom: 1rem;">
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">Email</div>
                                    <div style="font-weight: 600;">${data.manager.email || '-'}</div>
                                </div>
                            </div>
                        </div>
                        
                        <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color); display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; text-align: center;">
                            <div style="background: var(--bg-main); padding: 1rem; border-radius: 12px;">
                                <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary);">${data.stats.buses}</div>
                                <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Total Buses</div>
                            </div>
                            <div style="background: var(--bg-main); padding: 1rem; border-radius: 12px;">
                                <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary);">${data.stats.routes}</div>
                                <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Total Routes</div>
                            </div>
                        </div>
                    `;
                })
                .catch(error => {
                    content.innerHTML = `<div style="color: var(--danger); text-align: center; padding: 2rem;">Error fetching details.</div>`;
                });
        }

        function openEditSaccoModal(btn) {
            const id = btn.getAttribute('data-id');
            const name = btn.getAttribute('data-name');
            const shortcode = btn.getAttribute('data-shortcode');
            const passkey = btn.getAttribute('data-passkey');
            const qr = btn.getAttribute('data-qr');
            
            document.getElementById('editSaccoId').value = id;
            document.getElementById('editSaccoName').value = name;
            document.getElementById('editSaccoShortcode').value = shortcode;
            document.getElementById('editSaccoPasskey').value = passkey;
            document.getElementById('editSaccoQr').value = qr;
            
            document.getElementById('editSaccoModal').style.display = 'flex';
        }
    </script>
</body>
</html>
