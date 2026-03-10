<?php
$conn = new mysqli('localhost', 'root', '', 'something');
if (!$conn) {
    die("Connection failed: " . $conn->connect_error);
}
?>