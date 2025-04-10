<?php
$servername = "localhost";
$username = "uklz9ew3hrop3";
$password = "zyrbspyjlzjb";
$dbname = "dbq9lthxq4dshs";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set
$conn->set_charset("utf8mb4");
?>
