<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../index.php");
    exit;
}

require_once "../db_connect.php";

if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
    $timer_id = trim($_GET["id"]);
    $user_id = $_SESSION["id"];

    // Prepare a delete statement, checking for user_id to ensure security
    $sql = "DELETE FROM timers WHERE timer_id = ? AND user_id = ?";
    
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("ii", $timer_id, $user_id);
        
        if($stmt->execute()){
            // Redirect back to timers page
            header("location: ../timers.php");
        } else {
            echo "Oops! Something went wrong.";
        }
        $stmt->close();
    }
    $conn->close();
}
?>