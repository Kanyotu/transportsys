<?php 
include 'database.php'; 
$sql = "SELECT * FROM buses";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<p>Bus ID: " . $row["busid"]. " - numberplate  " .$row["platenumber"]. "</p>";
    }
} else {
    echo "0 results";
}