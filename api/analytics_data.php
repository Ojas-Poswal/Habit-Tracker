<?php
// We MUST start the session to access 'loggedin' and 'id'
session_start();
// Tell the browser this is a JSON file, not an HTML page
header('Content-Type: application/json');

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

// Include database connection
require_once "db_connect.php";

// Check the connection
if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

// Get user's ID
$user_id = $_SESSION["id"];
$data = [];

// --- 1. Data for Habits Completed Per Day (Line Chart) ---
$sql_habits = "SELECT 
                    DATE(completion_date) as date, 
                    COUNT(*) as count 
                FROM 
                    habit_log 
                WHERE 
                    user_id = ? AND completion_date >= CURDATE() - INTERVAL 7 DAY 
                GROUP BY 
                    DATE(completion_date) 
                ORDER BY 
                    date ASC";

if($stmt_habits = $conn->prepare($sql_habits)) {
    $stmt_habits->bind_param("i", $user_id);
    $stmt_habits->execute();
    $result_habits = $stmt_habits->get_result();

    $habit_trend_labels = [];
    $habit_trend_data = [];
    while($row = $result_habits->fetch_assoc()) {
        $habit_trend_labels[] = $row['date'];
        $habit_trend_data[] = $row['count'];
    }
    $data['habit_trend'] = ['labels' => $habit_trend_labels, 'data' => $habit_trend_data];
    $stmt_habits->close();
} else {
    // If the query fails, send the error
    $data['habit_trend_error'] = $conn->error;
}


// --- 2. Data for To-Do List Summary (Pie Chart) ---
$sql_todos = "SELECT 
                    (SELECT COUNT(*) FROM todos WHERE user_id = ? AND is_completed = 1) as completed,
                    (SELECT COUNT(*) FROM todos WHERE user_id = ? AND is_completed = 0) as pending";

if($stmt_todos = $conn->prepare($sql_todos)) {
    $stmt_todos->bind_param("ii", $user_id, $user_id);
    $stmt_todos->execute();
    $result_todos = $stmt_todos->get_result();
    $todo_data = $result_todos->fetch_assoc();
    $data['todo_summary'] = [
        'completed' => $todo_data['completed'], 
        'pending' => $todo_data['pending']
    ];
    $stmt_todos->close();
} else {
    // If the query fails, send the error
    $data['todo_summary_error'] = $conn->error;
}


// --- Output the final JSON data ---
echo json_encode($data);

$conn->close();
?>