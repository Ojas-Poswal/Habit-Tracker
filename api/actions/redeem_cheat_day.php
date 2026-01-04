<?php
session_start();
require_once "../db_connect.php";
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){ header("location: ../index.php"); exit; }

$user_id = $_SESSION['id'];

// Fetch current coins and cost
$sql = "SELECT coins, cheat_day_cost FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if($user['coins'] >= $user['cheat_day_cost']) {
    // Perform Transaction
    $new_coins = $user['coins'] - $user['cheat_day_cost'];
    
    // Update DB: Subtract coins, Add 1 to banked cheat days
    $update = $conn->prepare("UPDATE users SET coins = ?, cheat_days_banked = cheat_days_banked + 1 WHERE id = ?");
    $update->bind_param("ii", $new_coins, $user_id);
    $update->execute();
}

header("location: ../dashboard.php");
?>