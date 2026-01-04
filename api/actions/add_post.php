<?php
session_start();
require_once "../db_connect.php";
if(!isset($_SESSION["loggedin"])){ header("location: ../index.php"); exit; }

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $content = trim($_POST['content']);
    $category = $_POST['category'];
    $user_id = $_SESSION['id'];

    if(!empty($content)){
        $stmt = $conn->prepare("INSERT INTO community_posts (user_id, content, category) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $content, $category);
        $stmt->execute();
    }
    header("location: ../community.php");
}
?>