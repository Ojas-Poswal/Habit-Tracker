<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../index.php");
    exit;
}

require_once "../db_connect.php";

if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
    $todo_id = trim($_GET["id"]);
    $user_id = $_SESSION["id"];

    // This query cleverly toggles the boolean value:
    // IF is_completed is 0, it becomes 1. IF it's 1, it becomes 0.
    $sql = "UPDATE todos SET is_completed = !is_completed WHERE todo_id = ? AND user_id = ?";
    
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("ii", $todo_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
    
    $conn->close();
    // Redirect back to dashboard
    header("location: ../dashboard.php");
    
} else {
    header("location: ../dashboard.php");
    exit;
}
?>