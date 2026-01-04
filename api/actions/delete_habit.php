<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../index.php");
    exit;
}

require_once "../db_connect.php";

if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
    $habit_id = trim($_GET["id"]);
    $user_id = $_SESSION["id"];

    // Prepare a delete statement
    // We add "user_id = ?" to make sure a user can ONLY delete their own habits
    $sql = "DELETE FROM habits WHERE habit_id = ? AND user_id = ?";
    
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("ii", $habit_id, $user_id);
        
        if($stmt->execute()){
            // Redirect back to dashboard
            header("location: ../dashboard.php");
        } else {
            echo "Oops! Something went wrong.";
        }
        $stmt->close();
    }
    $conn->close();
}
?>