<?php
include 'database.php';

header('Content-Type: application/json');

$query = isset($_GET['query']) ? trim($_GET['query']) : '';

if (strlen($query) < 2) {
    echo json_encode(['status' => 'too_short', 'matches' => []]);
    exit();
}

$search = "%{$query}%";
$sql = "SELECT platenumber FROM buses WHERE platenumber LIKE ? LIMIT 10";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => $conn->error]);
    exit();
}

$stmt->bind_param("s", $search);
$stmt->execute();
$result = $stmt->get_result();

$matches = [];
$exact_match = false;

while ($row = $result->fetch_assoc()) {
    $matches[] = $row['platenumber'];
    if (strcasecmp($row['platenumber'], $query) === 0) {
        $exact_match = true;
    }
}

echo json_encode([
    'status' => 'success',
    'query' => $query,
    'exact_match' => $exact_match,
    'matches' => $matches
]);

$stmt->close();
$conn->close();
?>
