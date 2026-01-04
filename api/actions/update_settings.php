<?php
session_start();
require_once "../db_connect.php";
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){ header("location: ../index.php"); exit; }

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $cost = $_POST['cheat_cost'];
    $user_id = $_SESSION['id'];
    
    $sql = "UPDATE users SET cheat_day_cost = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $cost, $user_id);
    $stmt->execute();
    header("location: ../profile.php?msg=updated");
}
?>