<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

require_once "../db_connect.php";

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get form data
    $task = $_POST['task'];
    $todo_id = $_POST['todo_id'];
    $user_id = $_SESSION['id'];

    // Basic validation
    if (empty(trim($task))) {
        echo "Error: The task cannot be empty.";
    } else {
        // Prepare an update statement
        // We check for user_id to ensure a user can only update their own tasks
        $sql = "UPDATE todos SET task = ? WHERE todo_id = ? AND user_id = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sii", $task, $todo_id, $user_id);
            
            if ($stmt->execute()) {
                // Redirect back to dashboard on success
                header("location: ../dashboard.php");
            } else {
                echo "Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
    $conn->close();
} else {
    // If not a POST request, just redirect
    header("location: ../dashboard.php");
    exit;
}
?>