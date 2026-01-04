<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

require_once "../db_connect.php";

if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    $habit_id = trim($_GET["id"]);
    $user_id = $_SESSION["id"];
    $today = date("Y-m-d");

    // We must use a transaction to ensure data consistency
    $conn->begin_transaction();

    try {
        // --- 1. Check if the habit is ALREADY logged today ---
        $sql_check = "SELECT log_id FROM habit_log WHERE habit_id = ? AND user_id = ? AND completion_date = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("iis", $habit_id, $user_id, $today);
        $stmt_check->execute();
        $result = $stmt_check->get_result();

        if ($result->num_rows > 0) {
            // --- IT IS LOGGED: We need to "Undo" it ---
            $log = $result->fetch_assoc();
            $log_id = $log['log_id'];

            // 1. Delete the log entry
            $sql_delete = "DELETE FROM habit_log WHERE log_id = ?";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bind_param("i", $log_id);
            $stmt_delete->execute();
            $stmt_delete->close();

            // 2. Remove one coin
            $sql_coin = "UPDATE users SET coins = coins - 1 WHERE id = ? AND coins > 0";
            $stmt_coin = $conn->prepare($sql_coin);
            $stmt_coin->bind_param("i", $user_id);
            $stmt_coin->execute();
            $stmt_coin->close();

        } else {
            // --- IT IS NOT LOGGED: We need to "Complete" it ---
            
            // 1. Log the completion
            $sql_log = "INSERT INTO habit_log (habit_id, user_id, completion_date) VALUES (?, ?, ?)";
            $stmt_log = $conn->prepare($sql_log);
            $stmt_log->bind_param("iis", $habit_id, $user_id, $today);
            $stmt_log->execute();
            $stmt_log->close();

            // 2. Award 1 coin to the user
            $sql_coin = "UPDATE users SET coins = coins + 1 WHERE id = ?";
            $stmt_coin = $conn->prepare($sql_coin);
            $stmt_coin->bind_param("i", $user_id);
            $stmt_coin->execute();
            $stmt_coin->close();
        }

        $stmt_check->close();
        
        // --- THIS IS THE MOST IMPORTANT PART ---
        // If everything was successful, commit (SAVE) the changes
        $conn->commit();
        
        // Redirect back to the dashboard
        header("location: ../dashboard.php");
        exit;

    } catch (mysqli_sql_exception $exception) {
        // If anything failed, roll back (UNDO)
        $conn->rollback();
        echo "Error: Something went wrong. Please try again.";
        // You can log the error message for debugging
        // error_log($exception->getMessage());
    }

    $conn->close();

} else {
    // No ID was provided
    header("location: ../dashboard.php");
    exit;
}
?>