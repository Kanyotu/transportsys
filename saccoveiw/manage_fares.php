<?php
session_start();
if(!isset($_SESSION['sacco_manager_id'])) {
    header("Location: login.php");
    exit();
}
include 'database.php';

$sacco_id = $_SESSION['sacco_id'];
$message = "";

// Handle Fare Addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_fare'])) {
    $routeid = intval($_POST['routeid']);
    $fromstageid = intval($_POST['fromstageid']);
    $tostageid = intval($_POST['tostageid']);
    $amount = floatval($_POST['amount']);
    
    if($fromstageid == $tostageid) {
        $message = "From and To stages cannot be the same.";
    } else {
        $stmt = $conn->prepare("INSERT INTO fares (routeid, fromstageid, tostageid, amount) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $routeid, $fromstageid, $tostageid, $amount);
        
        if($stmt->execute()) {
            $message = "Fare added successfully!";
        } else {
            $message = "Error adding fare: " . $stmt->error;
        }
    }
}

// Handle Fare Deletion
if (isset($_GET['delete_fare'])) {
    $fareid = intval($_GET['delete_fare']);
    $conn->query("DELETE FROM fares WHERE fareid = $fareid");
    header("Location: manage_fares.php?msg=Fare deleted");
    exit();
}
if(isset($_GET['msg'])) $message = $_GET['msg'];

// Fetch Routes for this SACCO
$routes = $conn->query("
    SELECT * FROM routes 
    WHERE saccoid = $sacco_id
    ORDER BY createdat DESC
");

// Fetch Fares for this SACCO's routes
$fares = $conn->query("
    SELECT f.*, r.routename, s1.stagename as from_stage, s2.stagename as to_stage
    FROM fares f
    JOIN routes r ON f.routeid = r.routeid
    JOIN stages s1 ON f.fromstageid = s1.stageid
    JOIN stages s2 ON f.tostageid = s2.stageid
    WHERE r.saccoid = $sacco_id
    ORDER BY r.routename, f.amount ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Fares | SafiriPay SACCO</title>
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
                    <h1>Fare Prices</h1>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Set and manage fares between stages for your routes.</p>
                </div>
                <div class="header-actions">
                    <button class="theme-toggle" id="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                    <button class="btn btn-primary" onclick="document.getElementById('addFareModal').style.display='flex'">
                        <i class="fas fa-plus"></i> New Fare
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
                                <th>Route</th>
                                <th>From Stage</th>
                                <th>To Stage</th>
                                <th>Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($fares && $fares->num_rows > 0): ?>
                                <?php while($row = $fares->fetch_assoc()): ?>
                                    <tr>
                                        <td style="font-weight: 600;"><?php echo htmlspecialchars($row['routename']); ?></td>
                                        <td><?php echo htmlspecialchars($row['from_stage']); ?></td>
                                        <td><?php echo htmlspecialchars($row['to_stage']); ?></td>
                                        <td style="font-weight: 700; color: var(--primary);">KSh <?php echo number_format($row['amount'], 2); ?></td>
                                        <td>
                                            <a href="?delete_fare=<?php echo $row['fareid']; ?>" style="color: var(--danger);" onclick="return confirm('Delete this fare?')"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                                        No fares defined yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add Fare Modal -->
            <div id="addFareModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
                <div class="data-card" style="width:450px;">
                    <h2 style="margin-bottom:1.5rem;">Add New Fare</h2>
                    <form method="POST">
                        <div class="input-group">
                            <label>Route</label>
                            <select name="routeid" id="routeSelect" onchange="fetchStages(this.value)" required>
                                <option value="">Select Route</option>
                                <?php 
                                mysqli_data_seek($routes, 0);
                                while($r = $routes->fetch_assoc()): ?>
                                    <option value="<?php echo $r['routeid']; ?>"><?php echo htmlspecialchars($r['routename']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="input-group">
                                <label>From Stage</label>
                                <select name="fromstageid" id="fromStageSelect" required disabled>
                                    <option value="">Select Stage</option>
                                </select>
                            </div>
                            <div class="input-group">
                                <label>To Stage</label>
                                <select name="tostageid" id="toStageSelect" required disabled>
                                    <option value="">Select Stage</option>
                                </select>
                            </div>
                        </div>
                        <div class="input-group">
                            <label>Fare Amount (KSh)</label>
                            <input type="number" name="amount" placeholder="e.g. 50" step="0.01" required>
                        </div>
                        <div style="display:flex; gap:10px; margin-top:1rem;">
                            <button type="submit" name="add_fare" class="btn btn-primary" style="flex:1;">Save Fare</button>
                            <button type="button" onclick="document.getElementById('addFareModal').style.display='none'" class="btn" style="flex:1; background:var(--bg-main);">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                function fetchStages(routeId) {
                    const fromSelect = document.getElementById('fromStageSelect');
                    const toSelect = document.getElementById('toStageSelect');
                    
                    if (!routeId) {
                        fromSelect.disabled = true;
                        toSelect.disabled = true;
                        return;
                    }

                    fetch('get_stages.php?routeid=' + routeId)
                        .then(response => response.json())
                        .then(data => {
                            let options = '<option value="">Select Stage</option>';
                            data.forEach(stage => {
                                options += `<option value="${stage.stageid}">${stage.stagename}</option>`;
                            });
                            fromSelect.innerHTML = options;
                            toSelect.innerHTML = options;
                            fromSelect.disabled = false;
                            toSelect.disabled = false;
                        });
                }
            </script>
        </main>
    </div>
    <script src="darkmode.js"></script>
</body>
</html>
