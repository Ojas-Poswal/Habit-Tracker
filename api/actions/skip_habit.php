<?php
session_start();
require_once "../db_connect.php";
if (!isset($_SESSION["loggedin"])) { header("location: ../index.php"); exit; }

if (isset($_GET["id"])) {
    $habit_id = $_GET["id"];
    $user_id = $_SESSION["id"];
    $today = date("Y-m-d");

    // Check if user has a banked cheat day
    $u_sql = "SELECT cheat_days_banked FROM users WHERE id = ?";
    $stmt = $conn->prepare($u_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $banked = $stmt->get_result()->fetch_assoc()['cheat_days_banked'];

    if ($banked > 0) {
        // 1. Log the skip (counts for streak, no coins)
        $sql_log = "INSERT INTO habit_log (habit_id, user_id, completion_date) VALUES (?, ?, ?)";
        $stmt_log = $conn->prepare($sql_log);
        $stmt_log->bind_param("iis", $habit_id, $user_id, $today);
        
        if ($stmt_log->execute()) {
            // 2. Deduct 1 banked cheat day
            $sql_deduct = "UPDATE users SET cheat_days_banked = cheat_days_banked - 1 WHERE id = ?";
            $stmt_deduct = $conn->prepare($sql_deduct);
            $stmt_deduct->bind_param("i", $user_id);
            $stmt_deduct->execute();
        }
    }
}
header("location: ../dashboard.php");
?>