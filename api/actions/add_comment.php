<?php
session_start();
require_once "../db_connect.php";
if(!isset($_SESSION["loggedin"])){ header("location: ../index.php"); exit; }

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $comment = trim($_POST['comment']);
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['id'];

    if(!empty($comment)){
        $stmt = $conn->prepare("INSERT INTO community_comments (post_id, user_id, comment_text) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $post_id, $user_id, $comment);
        $stmt->execute();
    }
    header("location: ../community.php"); // Or redirect to specific anchor
}
?>