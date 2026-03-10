<?php
include 'database.php';

if (isset($_GET['routeid'])) {
    $routeid = intval($_GET['routeid']);
    $result = $conn->query("SELECT stageid, stagename FROM stages WHERE routeid = $routeid ORDER BY stageorder ASC");
    
    $stages = [];
    while($row = $result->fetch_assoc()) {
        $stages[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($stages);
}
?>
