<?php
session_start();
if(!isset($_SESSION['sacco_manager_id'])) {
    header("Location: login.php");
    exit();
}
include 'database.php';
$sacco_id = $_SESSION['sacco_id'];
$message = "";

// Handle Route Addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_route'])) {
    $routename = mysqli_real_escape_string($conn, $_POST['routename']);
    
    $stmt = $conn->prepare("INSERT INTO routes (routename, saccoid) VALUES (?, ?)");
    $stmt->bind_param("si", $routename, $sacco_id);
    
    if($stmt->execute()) {
        $message = "Route created successfully!";
    } else {
        $message = "Error creating route.";
    }
}

// Handle Route Deletion
if (isset($_GET['delete_route'])) {
    $route_id = intval($_GET['delete_route']);
    
    // Check for active trips
    $check_trips = $conn->query("SELECT COUNT(*) as trip_count FROM trips WHERE routeid = $route_id AND status = 'active'");
    if ($check_trips->fetch_assoc()['trip_count'] > 0) {
        header("Location: manage_routes.php?msg=Error: Cannot delete route with active trips&type=error");
        exit();
    }
    
    // Delete dependent data (fares and stages)
    $conn->query("DELETE FROM fares WHERE routeid = $route_id");
    $conn->query("DELETE FROM stages WHERE routeid = $route_id");
    
    // Delete route
    $stmt = $conn->prepare("DELETE FROM routes WHERE routeid = ? AND saccoid = ?");
    $stmt->bind_param("ii", $route_id, $sacco_id);
    
    if($stmt->execute()) {
        header("Location: manage_routes.php?msg=Route deleted successfully");
    } else {
        header("Location: manage_routes.php?msg=Error deleting route&type=error");
    }
    exit();
}

// Handle Route Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_route'])) {
    $route_id = intval($_POST['routeid']);
    $routename = mysqli_real_escape_string($conn, $_POST['routename']);
    
    $stmt = $conn->prepare("UPDATE routes SET routename = ? WHERE routeid = ? AND saccoid = ?");
    $stmt->bind_param("sii", $routename, $route_id, $sacco_id);
    
    if($stmt->execute()) {
        $message = "Route updated successfully!";
    } else {
        $message = "Error updating route.";
    }
}

// Handle Stage Addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_stage'])) {
    $routeid = intval($_POST['routeid']);
    $stagename = mysqli_real_escape_string($conn, $_POST['stagename']);
    
    // Get next stage order
    $order_res = $conn->query("SELECT MAX(stageorder) as max_order FROM stages WHERE routeid = $routeid");
    $next_order = ($order_res->fetch_assoc()['max_order'] ?? 0) + 1;
    
    $stmt = $conn->prepare("INSERT INTO stages (routeid, stagename, stageorder) VALUES (?, ?, ?)");
    $stmt->bind_param("isi", $routeid, $stagename, $next_order);
    
    if($stmt->execute()) {
        $message = "Stage added successfully!";
    } else {
        $message = "Error adding stage: " . $stmt->error;
    }
}

// Handle Stage Deletion
if (isset($_GET['delete_stage'])) {
    $stageid = intval($_GET['delete_stage']);
    $conn->query("DELETE FROM stages WHERE stageid = $stageid");
    header("Location: manage_routes.php?msg=Stage deleted");
    exit();
}

if(isset($_GET['msg'])) $message = $_GET['msg'];

// Fetch Routes for this SACCO
$routes = $conn->query("
    SELECT * FROM routes 
    WHERE saccoid = $sacco_id
    ORDER BY createdat DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Routes & Stages | SafiriPay SACCO</title>
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
                    <h1>Routes & Stages</h1>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Manage pickup points for your assigned routes.</p>
                </div>
                <div class="header-actions">
                    <button class="theme-toggle" id="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                    <button class="btn btn-primary" onclick="document.getElementById('addRouteModal').style.display='flex'">
                        <i class="fas fa-plus"></i> New Route
                    </button>
                </div>
            </header>

            <?php if($message): ?>
                <div style="background: <?php echo (strpos($message, 'Error') !== false) ? 'rgba(239, 68, 68, 0.1)' : 'rgba(16, 185, 129, 0.1)'; ?>; 
                            color: <?php echo (strpos($message, 'Error') !== false) ? 'var(--danger)' : 'var(--success)'; ?>; 
                            padding: 1rem; border-radius: 12px; margin-bottom: 2rem; font-weight: 600;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="data-card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Route Name</th>
                                <th>Current Stages</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($routes && $routes->num_rows > 0): ?>
                                <?php while($row = $routes->fetch_assoc()): 
                                    $route_id = $row['routeid'];
                                    $stages_res = $conn->query("SELECT * FROM stages WHERE routeid = $route_id ORDER BY stageorder ASC");
                                ?>
                                    <tr>
                                        <td style="font-weight: 700; width: 30%;"><?php echo htmlspecialchars($row['routename']); ?></td>
                                        <td>
                                            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                                <?php if($stages_res && $stages_res->num_rows > 0): ?>
                                                    <?php while($s = $stages_res->fetch_assoc()): ?>
                                                        <span class="status-badge" style="background: var(--bg-main); color: var(--text-main); font-size: 0.75rem; display: flex; align-items: center; gap: 5px;">
                                                            <?php echo htmlspecialchars($s['stagename']); ?>
                                                            <a href="?delete_stage=<?php echo $s['stageid']; ?>" style="color: var(--danger); font-size: 0.65rem;" onclick="return confirm('Delete this stage?')"><i class="fas fa-times"></i></a>
                                                        </span>
                                                    <?php endwhile; ?>
                                                <?php else: ?>
                                                    <span style="color: var(--text-muted); font-size: 0.875rem; font-style: italic;">No stages defined.</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 8px;">
                                                <button class="btn btn-primary" 
                                                        onclick="openAddStageModal(this)" 
                                                        data-id="<?php echo $route_id; ?>" 
                                                        data-name="<?php echo htmlspecialchars($row['routename']); ?>"
                                                        style="padding: 0.5rem 0.8rem; font-size: 0.8125rem;">
                                                    <i class="fas fa-plus-circle"></i> Add Stage
                                                </button>
                                                <button class="btn" 
                                                        onclick="openEditRouteModal(this)" 
                                                        data-id="<?php echo $route_id; ?>" 
                                                        data-name="<?php echo htmlspecialchars($row['routename']); ?>"
                                                        style="padding: 0.5rem 0.8rem; font-size: 0.8125rem; background: var(--bg-main); color: var(--text-main);">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="?delete_route=<?php echo $route_id; ?>" 
                                                   class="btn" 
                                                   onclick="return confirm('Are you sure you want to delete this route? All stages and fare data for this route will also be deleted.')"
                                                   style="padding: 0.5rem 0.8rem; font-size: 0.8125rem; background: var(--bg-main); color: var(--danger); text-decoration: none;">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                                        No routes assigned to your SACCO yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Stage Modal -->
    <div id="addStageModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
        <div class="data-card" style="width:400px;">
            <h2 style="margin-bottom:0.5rem;">Add New Stage</h2>
            <p id="modalRouteName" style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 1.5rem;"></p>
            <form method="POST">
                <input type="hidden" name="routeid" id="modalRouteId">
                <div class="input-group">
                    <label>Stage Name (e.g. Kenol, Makutano)</label>
                    <input type="text" name="stagename" placeholder="Enter stage name" required>
                </div>
                <div style="display:flex; gap:10px; margin-top:1rem;">
                    <button type="submit" name="add_stage" class="btn btn-primary" style="flex:1;">Add Stage</button>
                    <button type="button" onclick="document.getElementById('addStageModal').style.display='none'" class="btn" style="flex:1; background:var(--bg-main);">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Route Modal -->
    <div id="addRouteModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
        <div class="data-card" style="width:400px;">
            <h2 style="margin-bottom:1.5rem;">Create New Route</h2>
            <form method="POST">
                <div class="input-group">
                    <label>Route Name (e.g. Nairobi - Nyeri)</label>
                    <input type="text" name="routename" placeholder="Enter route name" required>
                </div>
                <div style="display:flex; gap:10px; margin-top:1rem;">
                    <button type="submit" name="add_route" class="btn btn-primary" style="flex:1;">Create Route</button>
                    <button type="button" onclick="document.getElementById('addRouteModal').style.display='none'" class="btn" style="flex:1; background:var(--bg-main);">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Route Modal -->
    <div id="editRouteModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
        <div class="data-card" style="width:400px;">
            <h2 style="margin-bottom:1.5rem;">Edit Route Name</h2>
            <form method="POST">
                <input type="hidden" name="routeid" id="editRouteId">
                <div class="input-group">
                    <label>Route Name</label>
                    <input type="text" name="routename" id="editRouteName" placeholder="Enter route name" required>
                </div>
                <div style="display:flex; gap:10px; margin-top:1rem;">
                    <button type="submit" name="edit_route" class="btn btn-primary" style="flex:1;">Save Changes</button>
                    <button type="button" onclick="document.getElementById('editRouteModal').style.display='none'" class="btn" style="flex:1; background:var(--bg-main);">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="darkmode.js"></script>
    <script>
        function openAddStageModal(btn) {
            const id = btn.getAttribute('data-id');
            const name = btn.getAttribute('data-name');
            document.getElementById('modalRouteId').value = id;
            document.getElementById('modalRouteName').innerText = "Route: " + name;
            document.getElementById('addStageModal').style.display = 'flex';
        }

        function openEditRouteModal(btn) {
            const id = btn.getAttribute('data-id');
            const name = btn.getAttribute('data-name');
            document.getElementById('editRouteId').value = id;
            document.getElementById('editRouteName').value = name;
            document.getElementById('editRouteModal').style.display = 'flex';
        }
    </script>
</body>
</html>
