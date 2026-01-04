<?php
session_start();
require_once "../db_connect.php";

if(!isset($_SESSION["loggedin"])){ header("location: ../index.php"); exit; }

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["photo"])){
    $user_id = $_SESSION['id'];
    
    if(!empty($_FILES["photo"]["tmp_name"])) {
        // Convert image to Base64 Text
        $image_data = file_get_contents($_FILES["photo"]["tmp_name"]);
        $type = pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION);
        $base64_image = 'data:image/' . $type . ';base64,' . base64_encode($image_data);
        
        // Save the TEXT string to the DB
        $sql = "UPDATE users SET profile_photo = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $base64_image, $user_id);
        
        if($stmt->execute()){
            header("location: ../profile.php?msg=uploaded");
        } else {
            header("location: ../profile.php?error=db_error");
        }
    }
}
?>