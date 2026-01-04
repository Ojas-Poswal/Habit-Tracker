<?php
// 1. Database Credentials
$servername = "gateway01.ap-southeast-1.prod.aws.tidbcloud.com"; // e.g. gateway01...tidbcloud.com
$username   = "3GAKvMAE2H3i3K3.root"; // e.g. 2G9...root
$password   = "0VqwMD9WFAmagRLo"; // Put your actual password here inside the quotes
$dbname     = "habit_tracker";
$port       = 4000;

// 2. Initialize MySQLi
$conn = mysqli_init();

// 3. Set SSL Options (Required for TiDB Cloud)
// NULL values mean we use the system's default trust store, which usually works for TiDB
$conn->ssl_set(NULL, NULL, NULL, NULL, NULL);

// 4. Connect with SSL Flag
// The constant MYSQLI_CLIENT_SSL ensures the connection is secure
if (!$conn->real_connect($servername, $username, $password, $dbname, $port, NULL, MYSQLI_CLIENT_SSL)) {
    die("Connection failed: " . mysqli_connect_error());
}

// 5. Set Charset
$conn->set_charset("utf8mb4");

?>
