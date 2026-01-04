<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../index.php"); // Redirect to login
    exit;
}

// Include DB connection
require_once "../db_connect.php";

// Check if form was submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    // Get form data
    $title = $_POST['title'];
    $content = $_POST['content'];
    $user_id = $_SESSION['id'];
    $today = date("Y-m-d");

    // Basic validation
    if(empty(trim($content))){
        echo "Error: The journal entry content cannot be empty.";
    } else {
        // Prepare an insert statement
        $sql = "INSERT INTO journal_entries (user_id, title, content, entry_date) VALUES (?, ?, ?, ?)";
        
        if($stmt = $conn->prepare($sql)){
            // Bind variables
            $stmt->bind_param("isss", $user_id, $title, $content, $today);
            
            if($stmt->execute()){
                // Redirect back to journal page on success
                header("location: ../journal.php");
            } else {
                echo "Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
    $conn->close();
}
?>