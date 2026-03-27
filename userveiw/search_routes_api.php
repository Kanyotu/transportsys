<?php
include 'database.php';

header('Content-Type: application/json');

$query = isset($_GET['query']) ? trim($_GET['query']) : '';

if (strlen($query) < 2) {
    echo json_encode(['status' => 'too_short', 'routes' => []]);
    exit();
}

$search = "%{$query}%";

// Search routes by name or stages, and get available trip types
$sql = "
    SELECT DISTINCT r.routeid, r.routename, s.sacconame, s.saccoid,
           GROUP_CONCAT(DISTINCT t.trip_type) as available_types
    FROM routes r
    JOIN saccos s ON r.saccoid = s.saccoid
    LEFT JOIN stages st ON r.routeid = st.routeid
    LEFT JOIN trips t ON r.routeid = t.routeid
    WHERE r.routename LIKE ? OR st.stagename LIKE ? OR s.sacconame LIKE ?
    GROUP BY r.routeid
    LIMIT 10
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => $conn->error]);
    exit();
}

$stmt->bind_param("sss", $search, $search, $search);
$stmt->execute();
$result = $stmt->get_result();

$routes = [];
while ($row = $result->fetch_assoc()) {
    $route_id = $row['routeid'];
    
    // Get all stages for this route
    $stages_sql = "SELECT stagename FROM stages WHERE routeid = ? ORDER BY stageorder";
    $stages_stmt = $conn->prepare($stages_sql);
    $stages_stmt->bind_param("i", $route_id);
    $stages_stmt->execute();
    $stages_result = $stages_stmt->get_result();
    $stages = [];
    while ($stage_row = $stages_result->fetch_assoc()) {
        $stages[] = $stage_row['stagename'];
    }
    
    // Get a sample fare for price estimation
    $fare_sql = "SELECT MIN(amount) as min_fare FROM fares WHERE routeid = ?";
    $fare_stmt = $conn->prepare($fare_sql);
    $fare_stmt->bind_param("i", $route_id);
    $fare_stmt->execute();
    $fare_result = $fare_stmt->get_result()->fetch_assoc();
    
    $routes[] = [
        'route_id' => $row['routeid'],
        'route_name' => $row['routename'],
        'sacco_name' => $row['sacconame'],
        'sacco_id' => $row['saccoid'],
        'stages' => $stages,
        'min_fare' => $fare_result['min_fare'] ?? null,
        'trip_types' => $row['available_types'] ? explode(',', $row['available_types']) : []
    ];
}

echo json_encode([
    'status' => 'success',
    'query' => $query,
    'routes' => $routes
]);

$stmt->close();
$conn->close();
?>
