<?php
$conn = new mysqli('localhost', 'root', '', 'something');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$res = $conn->query("SHOW COLUMNS FROM trips LIKE 'codriverid'");
if ($res->num_rows > 0) {
    echo "COLUMN_EXISTS";
} else {
    echo "COLUMN_MISSING";
}
$conn->close();
?>
