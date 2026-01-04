<?php
$servername = "gateway01.ap-southeast-1.prod.aws.tidbcloud.com"; // e.g., gateway01...tidbcloud.com
$username   = "3GAKvMAE2H3i3K3.root"; // e.g., 2G9...root

$dbname     = "habit_tracker";
$port       = 4000; // TiDB always uses 4000

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: Set charset to handle emojis correctly
$conn->set_charset("utf8mb4");
?>
}
?>