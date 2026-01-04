<?php
session_start();
require_once "../db_connect.php";
if(!isset($_SESSION["loggedin"])){ exit; }

if(isset($_GET['id'])){
    $post_id = $_GET['id'];
    $user_id = $_SESSION['id'];

    // Check if already liked
    $check = $conn->query("SELECT like_id FROM post_likes WHERE post_id = $post_id AND user_id = $user_id");
    
    if($check->num_rows > 0){
        // Unlike
        $conn->query("DELETE FROM post_likes WHERE post_id = $post_id AND user_id = $user_id");
    } else {
        // Like
        $conn->query("INSERT INTO post_likes (post_id, user_id) VALUES ($post_id, $user_id)");
    }
    header("location: ../community.php");
}
?>