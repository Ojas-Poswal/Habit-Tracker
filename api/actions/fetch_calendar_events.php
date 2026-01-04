<?php
session_start();
header('Content-Type: application/json');
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){ echo json_encode([]); exit; }
require_once "../db_connect.php";

$user_id = $_SESSION["id"];
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

$events = [];

// 1. FETCH COMPLETED HABIT LOGS FOR THIS MONTH (The "Look-up" Array)
// We fetch all completion dates for this user in this month so we can check against them later.
$completed_map = [];
$sql_logs = "SELECT habit_id, DATE(completion_date) as cdate FROM habit_log 
             WHERE user_id = ? AND MONTH(completion_date) = ? AND YEAR(completion_date) = ?";
$stmt_l = $conn->prepare($sql_logs);
$stmt_l->bind_param("iss", $user_id, $month, $year);
$stmt_l->execute();
$res_l = $stmt_l->get_result();
while($row = $res_l->fetch_assoc()){
    // Key format: "2023-11-20_5" (Date_HabitID)
    $key = $row['cdate'] . '_' . $row['habit_id'];
    $completed_map[$key] = true;
}

// 2. FETCH ONE-OFF TASKS (TODOS)
$sql_todos = "SELECT task, due_date, is_completed FROM todos WHERE user_id = ? AND MONTH(due_date) = ? AND YEAR(due_date) = ?";
$stmt = $conn->prepare($sql_todos);
$stmt->bind_param("iss", $user_id, $month, $year);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()){
    $events[] = [
        'title' => $row['task'],
        'date' => $row['due_date'],
        'type' => 'todo',
        'status' => $row['is_completed'] ? 'done' : 'pending'
    ];
}

// 3. CALCULATE RECURRING HABITS
$sql_habits = "SELECT * FROM habits WHERE user_id = ?";
$stmt_h = $conn->prepare($sql_habits);
$stmt_h->bind_param("i", $user_id);
$stmt_h->execute();
$habits = $stmt_h->get_result();

$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);

while($habit = $habits->fetch_assoc()){
    for($d = 1; $d <= $days_in_month; $d++){
        $current_date_str = sprintf("%04d-%02d-%02d", $year, $month, $d);
        $ts = strtotime($current_date_str);
        if($ts < strtotime($habit['created_at'])) continue; 

        $add = false;
        if($habit['frequency'] == 'daily') $add = true;
        elseif($habit['frequency'] == 'monthly' && date('j', $ts) == date('j', strtotime($habit['created_at']))) $add = true;
        elseif($habit['frequency'] == 'weekly'){
            if(!empty($habit['repeat_config'])){
                $day_name = date('D', $ts);
                $selected_days = explode(',', $habit['repeat_config']);
                if(in_array($day_name, $selected_days)) $add = true;
            } else {
                if(date('w', $ts) == date('w', strtotime($habit['created_at']))) $add = true;
            }
        }

        if($add){
            // CHECK IF IT WAS ACTUALLY DONE
            $lookup_key = $current_date_str . '_' . $habit['habit_id'];
            $is_done = isset($completed_map[$lookup_key]);

            $events[] = [
                'title' => $habit['name'],
                'date' => $current_date_str,
                'type' => 'habit',
                'habit_type' => $habit['habit_type'],
                'status' => $is_done ? 'done' : 'pending' // <--- NEW STATUS
            ];
        }
    }
}

echo json_encode($events);
?>