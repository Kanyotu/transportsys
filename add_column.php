<?php
$conn = new mysqli('localhost', 'root', '', 'something');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$sql = "ALTER TABLE trips ADD COLUMN codriverid INT(11) DEFAULT NULL";
if($conn->query($sql)) {
    echo "COLUMN_ADDED";
} else {
    echo "ERROR: " . $conn->error;
}
$conn->close();
?>
