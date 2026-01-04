<?php
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){ header("location: ../index.php"); exit; }
require_once "../db_connect.php";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $timer_name = $_POST['timer_name'];
    $duration = $_POST['duration'];
    $embed_link = $_POST['embed_link']; // New Field
    $user_id = $_SESSION['id'];

    if(empty(trim($timer_name)) || empty($duration) || $duration <= 0){
        echo "Error: Invalid input.";
    } else {
        // Updated SQL to include embed_link
        $sql = "INSERT INTO timers (user_id, timer_name, duration_minutes, embed_link) VALUES (?, ?, ?, ?)";
        
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("isis", $user_id, $timer_name, $duration, $embed_link);
            if($stmt->execute()){
                header("location: ../timers.php");
            } else {
                echo "Error saving timer.";
            }
            $stmt->close();
        }
    }
    $conn->close();
}
?>