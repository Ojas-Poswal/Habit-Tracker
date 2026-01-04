<?php
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){ header("location: ../index.php"); exit; }
require_once "../db_connect.php";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $task = $_POST['task'];
    $user_id = $_SESSION['id'];
    // Use provided date OR default to today
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : date('Y-m-d');

    if(empty(trim($task))){
        echo "Error: Task cannot be empty.";
    } else {
        $sql = "INSERT INTO todos (user_id, task, due_date) VALUES (?, ?, ?)";
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("iss", $user_id, $task, $due_date);
            if($stmt->execute()){
                // Redirect back to the page user came from
                $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../dashboard.php';
                header("location: " . $redirect);
            }
            $stmt->close();
        }
    }
    $conn->close();
}
?>