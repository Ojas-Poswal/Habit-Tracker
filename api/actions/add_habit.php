<?php
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){ header("location: ../index.php"); exit; }
require_once "../db_connect.php";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $name = $_POST['name'];
    $habit_type = $_POST['habit_type'];
    $category = $_POST['category'];
    $frequency = $_POST['frequency'];
    $user_id = $_SESSION['id'];
    
    // Handle Repeat Config (Days of Week)
    $repeat_config = NULL;
    if($frequency == 'weekly' && isset($_POST['weekly_days'])){
        // Convert array ["Mon", "Wed"] to string "Mon,Wed"
        $repeat_config = implode(',', $_POST['weekly_days']);
    }

    if(!empty(trim($name))){
        $sql = "INSERT INTO habits (user_id, name, habit_type, category, frequency, repeat_config) VALUES (?, ?, ?, ?, ?, ?)";
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("isssss", $user_id, $name, $habit_type, $category, $frequency, $repeat_config);
            $stmt->execute();
            $stmt->close();
        }
    }
    header("location: ../dashboard.php");
}
?>