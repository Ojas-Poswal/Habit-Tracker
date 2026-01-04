<?php
session_start();
require_once "../db_connect.php";

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){ header("location: ../index.php"); exit; }

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $link = $_POST['video_link'];
    $user_id = $_SESSION['id'];
    $final_link = $link; // Default to what they typed if we can't figure it out

    // --- SMART YOUTUBE CONVERTER ---
    
    // Case 1: Standard "watch?v=" link (e.g. youtube.com/watch?v=VIDEO_ID)
    if (strpos($link, 'watch?v=') !== false) {
        $parts = explode('v=', $link);
        $video_id = explode('&', $parts[1])[0]; // Get ID before any extra params like &t=
        $final_link = "https://www.youtube.com/embed/" . $video_id;
    }
    
    // Case 2: Shortened "youtu.be" link (e.g. https://youtu.be/VIDEO_ID)
    elseif (strpos($link, 'youtu.be/') !== false) {
        $parts = explode('youtu.be/', $link);
        $video_id = explode('?', $parts[1])[0]; // Get ID
        $final_link = "https://www.youtube.com/embed/" . $video_id;
    }

    // --- SPOTIFY CONVERTER (Basic) ---
    // Case 3: Spotify Track Link -> Embed Link
    elseif (strpos($link, 'open.spotify.com') !== false && strpos($link, 'embed') === false) {
        // Convert 'open.spotify.com/track/ID' to 'open.spotify.com/embed/track/ID'
        $final_link = str_replace('open.spotify.com/', 'open.spotify.com/embed/', $link);
    }

    // Update Database
    $sql = "UPDATE users SET focus_video_link = ? WHERE id = ?";
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("si", $final_link, $user_id);
        $stmt->execute();
        $stmt->close();
    }
    
    header("location: ../timers.php");
}
?>