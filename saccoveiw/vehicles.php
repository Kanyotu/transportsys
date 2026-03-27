<?php
session_start();
if(!isset($_SESSION['sacco_manager_id'])) {
    header("Location: login.php");
    exit();
}
include 'database.php';

$sacco_id = $_SESSION['sacco_id'];
$sacco_id = intval($sacco_id);
$message = "";

// Handle Vehicle Registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_vehicle'])) {
    $platenumber = mysqli_real_escape_string($conn, $_POST['platenumber']);
    $capacity = intval($_POST['capacity']);
    $driverid = intval($_POST['driverid']);
    
    $stmt = $conn->prepare("INSERT INTO buses (saccoid, platenumber, capacity, driverid, status) VALUES (?, ?, ?, ?, 1)");
    $stmt->bind_param("isii", $sacco_id, $platenumber, $capacity, $driverid);
    
    if($stmt->execute()) {
        $bus_id = $stmt->insert_id;
        // Automatically generate seats for the new bus
        $seat_stmt = $conn->prepare("INSERT INTO seats (busid, seatnumber, status) VALUES (?, ?, 'available')");
        for ($i = 1; $i <= $capacity; $i++) {
            $s_num = "S" . $i;
            $seat_stmt->bind_param("is", $bus_id, $s_num);
            $seat_stmt->execute();
        }
        $message = "Vehicle and $capacity seats registered successfully!";
    } else {
        $message = "Error registering vehicle: " . $stmt->error;
    }
}

// Handle Trip Addition (Quick Schedule)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_trip'])) {
    $routeid = intval($_POST['routeid']);
    $busid = intval($_POST['busid']);
    $driverid = intval($_POST['driverid']);
    $starttime = $_POST['starttime'];
    $trip_type = $_POST['trip_type'];
    
    $stmt = $conn->prepare("INSERT INTO trips (routeid, busid, driverid, starttime, status, trip_type) VALUES (?, ?, ?, ?, 'active', ?)");
    $stmt->bind_param("iiiss", $routeid, $busid, $driverid, $starttime, $trip_type);
    
    if($stmt->execute()) {
        $message = "Trip scheduled successfully!";
    } else {
        $message = "Error scheduling trip.";
    }
}

// Handle Vehicle Deletion
if (isset($_GET['delete_vehicle'])) {
    $busid = intval($_GET['delete_vehicle']);
    
    // Check for active trips
    $check_trips = $conn->query("SELECT COUNT(*) as trip_count FROM trips WHERE busid = $busid AND status = 'active'");
    if ($check_trips->fetch_assoc()['trip_count'] > 0) {
        header("Location: vehicles.php?msg=Error: Cannot delete vehicle with active trips&type=error");
        exit();
    }
    
    // Delete seats first
    $conn->query("DELETE FROM seats WHERE busid = $busid");
    
    // Delete vehicle
    $stmt = $conn->prepare("DELETE FROM buses WHERE busid = ? AND saccoid = ?");
    $stmt->bind_param("ii", $busid, $sacco_id);
    
    if($stmt->execute()) {
        header("Location: vehicles.php?msg=Vehicle deleted successfully");
    } else {
        header("Location: vehicles.php?msg=Error deleting vehicle&type=error");
    }
    exit();
}

// Handle Vehicle Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_vehicle'])) {
    $busid = intval($_POST['busid']);
    $platenumber = mysqli_real_escape_string($conn, $_POST['platenumber']);
    $capacity = intval($_POST['capacity']);
    $driverid = intval($_POST['driverid']);
    $status = intval($_POST['status']);
    
    // Get current capacity to see if we need to regenerate seats
    $curr_res = $conn->query("SELECT capacity FROM buses WHERE busid = $busid");
    $old_capacity = $curr_res->fetch_assoc()['capacity'] ?? 0;
    
    if ($capacity != $old_capacity) {
        // Check for active bookings before changing capacity
        $check_bookings = $conn->query("SELECT COUNT(*) as booking_count FROM tripsessions ts JOIN trips t ON ts.tripid = t.tripid WHERE t.busid = $busid AND ts.status IN ('pending', 'paid') AND t.status = 'active'");
        if ($check_bookings->fetch_assoc()['booking_count'] > 0) {
            $message = "Error: Cannot change capacity for a vehicle with active bookings.";
        } else {
            // Update capacity and regenerate seats
            $stmt = $conn->prepare("UPDATE buses SET platenumber = ?, capacity = ?, driverid = ?, status = ? WHERE busid = ? AND saccoid = ?");
            $stmt->bind_param("siiiii", $platenumber, $capacity, $driverid, $status, $busid, $sacco_id);
            if ($stmt->execute()) {
                $conn->query("DELETE FROM seats WHERE busid = $busid");
                $seat_stmt = $conn->prepare("INSERT INTO seats (busid, seatnumber, status) VALUES (?, ?, 'available')");
                for ($i = 1; $i <= $capacity; $i++) {
                    $s_num = "S" . $i;
                    $seat_stmt->bind_param("is", $busid, $s_num);
                    $seat_stmt->execute();
                }
                $message = "Vehicle updated and seats regenerated successfully!";
            }
        }
    } else {
        // Just update details
        $stmt = $conn->prepare("UPDATE buses SET platenumber = ?, driverid = ?, status = ? WHERE busid = ? AND saccoid = ?");
        $stmt->bind_param("siiii", $platenumber, $driverid, $status, $busid, $sacco_id);
        if($stmt->execute()) {
            $message = "Vehicle updated successfully!";
        } else {
            $message = "Error updating vehicle.";
        }
    }
}

if(isset($_GET['msg'])) $message = $_GET['msg'];

// Fetch Vehicles for this SACCO with available seat count
$vehicles = $conn->query("
    SELECT b.*, d.dname, d.phoneno as dphone,
           (SELECT COUNT(*) FROM seats WHERE busid = b.busid AND status = 'available') as available_seats
    FROM buses b
    LEFT JOIN drivers d ON b.driverid = d.driverid
    WHERE b.saccoid = $sacco_id
");

// Fetch Routes for the Quick Schedule modal
$routes_list = $conn->query("SELECT * FROM routes WHERE saccoid = $sacco_id");


// Fetch Drivers for the dropdown
$drivers_list = $conn->query("SELECT * FROM drivers");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Vehicles | SafiriPay SACCO</title>
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
                    <h1>Vehicle Management</h1>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Register and assign your fleet.</p>
                </div>
                <div class="header-actions">
                    <button class="theme-toggle" id="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                    <button class="btn btn-primary" onclick="document.getElementById('addVehicleModal').style.display='flex'">
                        <i class="fas fa-plus"></i> Add Vehicle
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
                                <th>Plate Number</th>
                                <th>Capacity</th>
                                <th>Available Seats</th>
                                <th>Assigned Driver</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $vehicles->fetch_assoc()): ?>
                                <tr>
                                    <td style="font-weight: 700; font-size: 1.125rem;"><?php echo htmlspecialchars($row['platenumber']); ?></td>
                                    <td><span style="font-weight: 600;"><?php echo $row['capacity']; ?></span> Total</td>
                                    <td><span class="status-badge" style="background: rgba(16, 185, 129, 0.1); color: var(--success); font-weight: 700;"><?php echo $row['available_seats']; ?> Available</span></td>
                                    <td>
                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($row['dname'] ?: 'None'); ?></div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($row['dphone'] ?: '-'); ?></div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $row['status'] ? 'active' : 'deactivated'; ?>">
                                            <?php echo $row['status'] ? 'In Service' : 'Maintenance'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 8px;">
                                            <button class="btn" onclick="openScheduleModal('<?php echo $row['busid']; ?>', '<?php echo $row['platenumber']; ?>', '<?php echo $row['driverid']; ?>')" style="padding: 0.5rem; background: var(--bg-main); color: var(--success);" title="Schedule Trip">
                                                <i class="fas fa-calendar-plus"></i>
                                            </button>
                                            <button class="btn" onclick="openEditVehicleModal(this)" 
                                                    data-id="<?php echo $row['busid']; ?>" 
                                                    data-plate="<?php echo htmlspecialchars($row['platenumber']); ?>"
                                                    data-capacity="<?php echo $row['capacity']; ?>" 
                                                    data-driver="<?php echo $row['driverid']; ?>"
                                                    data-status="<?php echo $row['status']; ?>"
                                                    style="padding: 0.5rem; background: var(--bg-main); color: var(--primary);" title="Edit Vehicle">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?delete_vehicle=<?php echo $row['busid']; ?>" 
                                               class="btn" 
                                               onclick="return confirm('Are you sure you want to delete this vehicle? All seat data will also be removed.')"
                                               style="padding: 0.5rem; background: var(--bg-main); color: var(--danger); text-decoration: none;" title="Delete Vehicle">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add Vehicle Modal -->
            <div id="addVehicleModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
                <div class="data-card" style="width:400px;">
                    <h2 style="margin-bottom:1.5rem;">Register New Vehicle</h2>
                    <form method="POST">
                        <div class="input-group">
                            <label>Plate Number</label>
                            <input type="text" name="platenumber" placeholder="e.g. KDD 123A" required>
                        </div>
                        <div class="input-group">
                            <label>Capacity (Seats)</label>
                            <input type="number" name="capacity" value="14" required>
                        </div>
                        <div class="input-group">
                            <label>Assign Driver</label>
                            <select name="driverid" required>
                                <option value="">Select Driver</option>
                                <?php 
                                $drivers_list->data_seek(0);
                                while($d = $drivers_list->fetch_assoc()): ?>
                                    <option value="<?php echo $d['driverid']; ?>"><?php echo htmlspecialchars($d['dname']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div style="display:flex; gap:10px; margin-top:1rem;">
                            <button type="submit" name="add_vehicle" class="btn btn-primary" style="flex:1;">Register</button>
                            <button type="button" onclick="document.getElementById('addVehicleModal').style.display='none'" class="btn" style="flex:1; background:var(--bg-main);">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Edit Vehicle Modal -->
            <div id="editVehicleModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
                <div class="data-card" style="width:400px;">
                    <h2 style="margin-bottom:1.5rem;">Edit Vehicle</h2>
                    <form method="POST">
                        <input type="hidden" name="busid" id="editBusId">
                        <div class="input-group">
                            <label>Plate Number</label>
                            <input type="text" name="platenumber" id="editPlateNumber" required>
                        </div>
                        <div class="input-group">
                            <label>Capacity (Seats)</label>
                            <input type="number" name="capacity" id="editCapacity" required>
                            <small style="color: var(--danger); font-size: 0.7rem;">Changing capacity will reset all seats!</small>
                        </div>
                        <div class="input-group">
                            <label>Assigned Driver</label>
                            <select name="driverid" id="editDriverId" required>
                                <option value="">Select Driver</option>
                                <?php 
                                $drivers_list->data_seek(0);
                                while($d = $drivers_list->fetch_assoc()): ?>
                                    <option value="<?php echo $d['driverid']; ?>"><?php echo htmlspecialchars($d['dname']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="input-group">
                            <label>Status</label>
                            <select name="status" id="editStatus">
                                <option value="1">In Service</option>
                                <option value="0">Maintenance</option>
                            </select>
                        </div>
                        <div style="display:flex; gap:10px; margin-top:1rem;">
                            <button type="submit" name="edit_vehicle" class="btn btn-primary" style="flex:1;">Save Changes</button>
                            <button type="button" onclick="document.getElementById('editVehicleModal').style.display='none'" class="btn" style="flex:1; background:var(--bg-main);">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Quick Schedule Modal -->
            <div id="quickScheduleModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
                <div class="data-card" style="width:450px;">
                    <h2 style="margin-bottom:0.5rem;">Quick Schedule</h2>
                    <p id="selectedVehicleLabel" style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 1.5rem;"></p>
                    <form method="POST">
                        <input type="hidden" name="busid" id="modalBusId">
                        <input type="hidden" name="driverid" id="modalDriverId">
                        
                        <div class="input-group">
                            <label>Select Route</label>
                            <select name="routeid" required>
                                <?php 
                                $routes_list->data_seek(0);
                                while($r = $routes_list->fetch_assoc()): ?>
                                    <option value="<?php echo $r['routeid']; ?>"><?php echo htmlspecialchars($r['routename']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="input-group">
                            <label>Departure Time</label>
                            <input type="datetime-local" name="starttime" required>
                        </div>
                        <div class="input-group">
                            <label>Trip Type</label>
                            <select name="trip_type">
                                <option value="short">Short Distance</option>
                                <option value="long">Long Distance</option>
                            </select>
                        </div>
                        <div style="display:flex; gap:10px; margin-top:1rem;">
                            <button type="submit" name="add_trip" class="btn btn-primary" style="flex:1;">Schedule Trip</button>
                            <button type="button" onclick="document.getElementById('quickScheduleModal').style.display='none'" class="btn" style="flex:1; background:var(--bg-main);">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                function openScheduleModal(busId, plateNumber, driverId) {
                    document.getElementById('modalBusId').value = busId;
                    document.getElementById('modalDriverId').value = driverId;
                    document.getElementById('selectedVehicleLabel').innerText = "Vehicle: " + plateNumber;
                    document.getElementById('quickScheduleModal').style.display = 'flex';
                }

                function openEditVehicleModal(btn) {
                    const id = btn.getAttribute('data-id');
                    const plate = btn.getAttribute('data-plate');
                    const capacity = btn.getAttribute('data-capacity');
                    const driver = btn.getAttribute('data-driver');
                    const status = btn.getAttribute('data-status');
                    
                    document.getElementById('editBusId').value = id;
                    document.getElementById('editPlateNumber').value = plate;
                    document.getElementById('editCapacity').value = capacity;
                    document.getElementById('editDriverId').value = driver;
                    document.getElementById('editStatus').value = status;
                    
                    document.getElementById('editVehicleModal').style.display = 'flex';
                }
            </script>
    </div>
    <script src="darkmode.js"></script>
</body>
</html>
