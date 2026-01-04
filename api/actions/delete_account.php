<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

require_once "../db_connect.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['id'];

    // Delete the user. 
    // Because of Foreign Keys with ON DELETE CASCADE, 
    // this will automatically delete all their habits, logs, entries, and todos.
    $sql = "DELETE FROM users WHERE id = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            // Destroy session and redirect to login
            session_destroy();
            header("location: ../index.php");
            exit;
        } else {
            echo "Error deleting account.";
        }
        $stmt->close();
    }
    $conn->close();
}
?>