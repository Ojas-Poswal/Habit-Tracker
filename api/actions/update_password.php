<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

require_once "../db_connect.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $user_id = $_SESSION['id'];

    // 1. Check if new passwords match
    if ($new_password !== $confirm_password) {
        header("location: ../profile.php?msg=mismatch");
        exit;
    }

    // 2. Fetch current password hash from DB
    $sql = "SELECT password_hash FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($db_password_hash);
    $stmt->fetch();
    $stmt->close();

    // 3. Verify current password
    if (password_verify($current_password, $db_password_hash)) {
        // 4. Hash new password and update
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        $update_sql = "UPDATE users SET password_hash = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $new_hash, $user_id);
        
        if ($update_stmt->execute()) {
            header("location: ../profile.php?msg=updated");
        } else {
            echo "Error updating password.";
        }
        $update_stmt->close();
    } else {
        // Wrong current password
        header("location: ../profile.php?msg=wrong_pass");
    }
    
    $conn->close();
}
?>