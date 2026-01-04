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

    // Prepare a delete statement
    $sql = "DELETE FROM todos WHERE todo_id = ? AND user_id = ?";
    
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("ii", $todo_id, $user_id);
        
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