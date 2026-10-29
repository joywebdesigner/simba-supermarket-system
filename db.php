<?php
$conn = new mysqli("localhost", "root", "", "retail_system");

if ($conn->connect_error) {
    die("Database connection failed");
}
?>
