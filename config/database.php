<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "localphone_marketplace";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Select database
if (!$conn->select_db($dbname)) {
    // Database might not exist, try to create it
    $sql = "CREATE DATABASE IF NOT EXISTS $dbname";
    if ($conn->query($sql) === TRUE) {
        $conn->select_db($dbname);
    } else {
        die("Error creating database: " . $conn->error);
    }
}

// Set charset
$conn->set_charset("utf8mb4");
?>