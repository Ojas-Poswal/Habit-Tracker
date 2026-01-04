<?php
// 1. Start Session & Config
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){ header("location: index.php"); exit; }
require_once "db_connect.php";

// 2. TIMEZONE FIX
date_default_timezone_set('Asia/Kolkata'); 
$current_date = date('Y-m-d');

$user_id = $_SESSION["id"];
$user_query = $conn->query("SELECT * FROM users WHERE id = $user_id");
$user_data = $user_query->fetch_assoc();
// If profile_photo (BLOB) is not empty, use the viewer script. Otherwise, use placeholder.
$user_photo = !empty($user_data['profile_photo']) ? "get_photo.php?id=".$user_id : "https://via.placeholder.com/150";

// Cheat Day Logic
$cheat_cost = $user_data['cheat_day_cost'] ? $user_data['cheat_day_cost'] : 30; 
$can_afford = $user_data['coins'] >= $cheat_cost;
$banked_days = $user_data['cheat_days_banked'];

// --- ADVANCED STREAK ENGINE ---
function getHabitStats($conn, $user_id, $habit_id) {
    // 1. Get ALL completion dates for this habit (Newest first)
    $sql = "SELECT DISTINCT completion_date FROM habit_log WHERE user_id = ? AND habit_id = ? ORDER BY completion_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $habit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $dates = [];
    while ($row = $result->fetch_assoc()) { 
        $dates[] = $row['completion_date']; 
    }
    
    if (empty($dates)) {
        return ['current' => 0, 'longest' => 0, 'status' => 'broken'];
    }

    // 2. Calculate CURRENT Streak
    $current_streak = 0;
    $now = new DateTime(); // Today
    $latest_log = new DateTime($dates[0]);
    $diff_from_latest = $now->diff($latest_log)->days;

    // Status Determination
    $status = 'broken';
    if ($diff_from_latest == 0) {
        $status = 'active'; // Done today
        $current_streak = 1; // Start counting from today
    } elseif ($diff_from_latest == 1) {
        $status = 'pending'; // Done yesterday (Streak alive, but needs action)
        $current_streak = 0; // We count backward from *yesterday* in the loop
    } else {
        $status = 'broken'; // Missed more than 1 day
        $current_streak = 0;
    }

    // Loop to count consecutive days for Current Streak
    // We start from index 0 if active, or index 0 if pending (which is yesterday)
    // If broken, current is 0.
    if ($status != 'broken') {
        $check_date = ($status == 'active') ? $now : (new DateTime())->modify('-1 day');
        
        foreach ($dates as $d_str) {
            $log_date = new DateTime($d_str);
            $diff = $check_date->diff($log_date)->days;
            
            if ($diff == 0) {
                if ($status == 'pending') $current_streak++; 
                // If active, we already set to 1, now we check previous
                $check_date->modify('-1 day');
            } elseif ($status == 'active' && $diff == 1) {
                 // Sequence continues
                 $current_streak++;
                 $check_date->modify('-1 day');
            } else {
                break; // Sequence broken
            }
        }
    }

    // 3. Calculate LONGEST Streak (Historical Best)
    $longest_streak = 0;
    $temp_streak = 1;
    
    for ($i = 0; $i < count($dates) - 1; $i++) {
        $d1 = new DateTime($dates[$i]);
        $d2 = new DateTime($dates[$i+1]);
        $interval = $d1->diff($d2);
        
        if ($interval->days == 1) {
            $temp_streak++;
        } else {
            if ($temp_streak > $longest_streak) $longest_streak = $temp_streak;
            $temp_streak = 1;
        }
    }
    // Final check for the last run
    if ($temp_streak > $longest_streak) $longest_streak = $temp_streak;

    // Ensure longest isn't smaller than current (edge case)
    if ($current_streak > $longest_streak) $longest_streak = $current_streak;

    return ['current' => $current_streak, 'longest' => $longest_streak, 'status' => $status];
}

// --- FETCH HABITS ---
function getHabits($conn, $user_id, $category) {
    $sql = "SELECT * FROM habits WHERE user_id = ? AND category = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $category);
    $stmt->execute();
    return $stmt->get_result();
}
$morning_habits = getHabits($conn, $user_id, 'morning');
$afternoon_habits = getHabits($conn, $user_id, 'afternoon');
$evening_habits = getHabits($conn, $user_id, 'evening');
$anytime_habits = getHabits($conn, $user_id, 'anytime');

// --- FETCH TODOS ---
$sql_today = "SELECT * FROM todos WHERE user_id = ? AND (due_date = ? OR (due_date < ? AND is_completed = 0)) ORDER BY is_completed ASC, created_at DESC";
$stmt_today = $conn->prepare($sql_today);
$stmt_today->bind_param("iss", $user_id, $current_date, $current_date);
$stmt_today->execute();
$today_todos = $stmt_today->get_result();

// --- FETCH HISTORY ---
$sql_history = "SELECT * FROM todos WHERE user_id = ? AND due_date < ? ORDER BY due_date DESC LIMIT 10"; 
$stmt_hist = $conn->prepare($sql_history);
$stmt_hist->bind_param("is", $user_id, $current_date);
$stmt_hist->execute();
$history_todos = $stmt_hist->get_result();

// --- DUE HABITS LOGIC ---
$due_habits = [];
$all_habits = $conn->query("SELECT * FROM habits WHERE user_id = $user_id");
while($h = $all_habits->fetch_assoc()){
    $is_due = false;
    if($h['frequency'] == 'daily') $is_due = true;
    elseif($h['frequency'] == 'monthly' && date('j') == date('j', strtotime($h['created_at']))) $is_due = true;
    elseif($h['frequency'] == 'weekly'){
        if(!empty($h['repeat_config'])){
            if(strpos($h['repeat_config'], date('D')) !== false) $is_due = true;
        } else {
            if(date('w') == date('w', strtotime($h['created_at']))) $is_due = true;
        }
    }
    if($is_due) $due_habits[] = $h;
}

// --- CHECK DONE HABITS ---
$done_ids = [];
$check_sql = "SELECT habit_id FROM habit_log WHERE user_id = ? AND completion_date = ?";
$stmt_check = $conn->prepare($check_sql);
if ($stmt_check) {
    $stmt_check->bind_param("is", $user_id, $current_date);
    if ($stmt_check->execute()) {
        $res = $stmt_check->get_result();
        if ($res) { 
            while($r = $res->fetch_assoc()) { $done_ids[] = $r['habit_id']; }
        }
    }
    $stmt_check->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - HabitFlow</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* --- GLOBAL UI --- */
        a { text-decoration: none !important; }
        .layout { display: grid; grid-template-columns: 260px 1fr; gap: 20px; padding: 20px; max-width:1400px; margin:0 auto; }
        .sidebar { display: flex; flex-direction: column; gap: 15px; }
        
        .coin-progress { background: #eee; border-radius: 10px; overflow: hidden; height: 25px; position: relative; margin-top: 5px; }
        .coin-bar { background: #f1c40f; height: 100%; width: <?php echo min(100, ($user_data['coins']/$cheat_cost)*100); ?>%; transition: width 0.5s; }
        .coin-text { position: absolute; width: 100%; text-align: center; top: 2px; font-size: 0.8em; font-weight: bold; color: #333; }
        
        .item-row { display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--card-bg); border-bottom: 1px solid var(--border); }
        .item-row:first-child { border-top-left-radius: 12px; border-top-right-radius: 12px; }
        .item-row:last-child { border-bottom-left-radius: 12px; border-bottom-right-radius: 12px; border-bottom: none; }
        
        .days-selector { display: none; flex-wrap: wrap; gap: 5px; margin-top: 5px; }
        .days-selector label { font-size: 0.8em; display: flex; align-items: center; gap: 3px; cursor: pointer; color: var(--text); }
        
        /* STREAK VISUALS */
        .streak-container { display: flex; gap: 5px; align-items: center; font-size: 0.75em; }
        .streak-tag { padding: 2px 8px; border-radius: 4px; font-weight: bold; color: white; display: inline-flex; align-items: center; gap: 4px; }
        
        .streak-active { background: #e67e22; box-shadow: 0 0 5px rgba(230, 126, 34, 0.5); } /* Fire Orange */
        .streak-pending { background: #95a5a6; opacity: 0.8; } /* Gray - Needs action */
        .streak-record { background: #f1c40f; color: #333; } /* Gold - Best */

        .history-item { opacity: 0.6; font-size: 0.9em; }
        .history-date { font-size: 0.8em; color: var(--text-secondary); margin-right: 10px; }
        
        .habits-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media screen and (max-width: 800px) {
            .layout { grid-template-columns: 1fr; gap: 15px; padding: 10px; }
            .habits-grid { grid-template-columns: 1fr; }
            .sidebar { position: static !important; height: auto !important; }
            .nav-links { display: grid !important; grid-template-columns: 1fr 1fr 1fr; gap: 5px; }
            .nav-links a { text-align: center; padding: 8px 5px; font-size: 0.9em; }
        }
        
        .action-toggle { font-size: 1.2em; margin-right: 10px; }
        .completed-text { text-decoration: line-through; color: var(--text-secondary); }
        .btn-done { background: #2ecc71; color: white; padding: 5px 15px; border-radius: 6px; font-weight: bold; font-size: 0.85em; }
        .btn-undo { background: #f1c40f; color: white; padding: 5px 15px; border-radius: 6px; font-weight: bold; font-size: 0.85em; }
        .btn-skip { background: #95a5a6; color: white; padding: 5px 15px; border-radius: 6px; font-weight: bold; font-size: 0.85em; }
        
        .nav-links { padding: 10px; display: flex; flex-direction: column; gap: 5px; }
        .nav-links a { padding: 10px 15px; border-radius: 8px; transition: 0.2s; color: var(--text); font-weight: 500; text-align: left; background: var(--bg); }
        .nav-links a:hover { background: var(--primary); color: white !important; }
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="card" style="text-align:center; padding:20px;">
                <img src="<?php echo $user_photo; ?>" style="width:80px; height:80px; border-radius:50%; object-fit:cover; border:3px solid var(--primary);">
                <h3><?php echo htmlspecialchars($user_data['name']); ?></h3>
                
                <div style="text-align:left; margin-top:15px;">
                    <small style="color:var(--text-secondary)">Cheat Day Progress:</small>
                    <div class="coin-progress">
                        <div class="coin-bar"></div>
                        <div class="coin-text"><?php echo $user_data['coins']; ?> / <?php echo $cheat_cost; ?> 🪙</div>
                    </div>
                    <?php if($can_afford): ?>
                        <a href="actions/redeem_cheat_day.php" class="btn" style="background:#f1c40f; color:white; width:100%; margin-top:10px; text-align:center; display:block; animation: pulse 2s infinite;">🎁 Redeem Pass</a>
                    <?php else: ?>
                        <p style="font-size:0.8em; color:var(--text-secondary); text-align:center; margin-top:5px;"><?php echo $cheat_cost - $user_data['coins']; ?> more coins needed</p>
                    <?php endif; ?>
                    <div style="margin-top:10px; font-size:0.85em; text-align:center; color:#2ecc71;">
                        🎟️ Banked Passes: <strong><?php echo $banked_days; ?></strong>
                    </div>
                </div>
            </div>
            
            <nav class="card nav-links">
                <a href="dashboard.php" class="btn-primary">🏠 Dashboard</a>
                <a href="calendar.php" class="btn" style="background:var(--bg); color:var(--text);">📅 Calendar</a>
                <a href="community.php" class="btn" style="background:var(--bg); color:var(--text);">🌍 Community</a>
                <a href="analytics.php" class="btn" style="background:var(--bg); color:var(--text);">📊 Analytics</a>
                <a href="timers.php" class="btn" style="background:var(--bg); color:var(--text);">⏱️ Timers</a>
                <a href="journal.php" class="btn" style="background:var(--bg); color:var(--text);">📓 Journal</a>
                <a href="profile.php" class="btn" style="background:var(--bg); color:var(--text);">👤 Profile</a>
                <a href="faq.php" class="btn" style="background:var(--bg); color:var(--text);">❓ FAQ</a>
                <a href="logout.php" class="btn" style="background:var(--bg); color:#e74c3c;">🚪 Logout</a>
            </nav>

            <div class="card">
                <h4>+ New Habit</h4>
                <form action="actions/add_habit.php" method="POST">
                    <input type="text" name="name" placeholder="Habit Name" required style="margin-bottom:10px;">
                    <select name="category" style="margin-bottom:10px;">
                        <option value="morning">Morning</option>
                        <option value="afternoon">Afternoon</option>
                        <option value="evening">Evening</option>
                        <option value="anytime">Anytime</option>
                    </select>
                    <select name="habit_type" style="margin-bottom:10px;">
                        <option value="build">Build (Good Habit)</option>
                        <option value="quit">Quit (Bad Habit)</option>
                    </select>
                    <select name="frequency" id="freqSelect" onchange="toggleDays()" style="margin-bottom:10px;">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly (Specific Days)</option>
                        <option value="monthly">Monthly (Today's Date)</option>
                    </select>
                    <div id="daysBox" class="days-selector">
                        <label><input type="checkbox" name="weekly_days[]" value="Mon"> Mon</label>
                        <label><input type="checkbox" name="weekly_days[]" value="Tue"> Tue</label>
                        <label><input type="checkbox" name="weekly_days[]" value="Wed"> Wed</label>
                        <label><input type="checkbox" name="weekly_days[]" value="Thu"> Thu</label>
                        <label><input type="checkbox" name="weekly_days[]" value="Fri"> Fri</label>
                        <label><input type="checkbox" name="weekly_days[]" value="Sat"> Sat</label>
                        <label><input type="checkbox" name="weekly_days[]" value="Sun"> Sun</label>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%; margin-top:10px;">Add</button>
                </form>
            </div>
        </aside>

        <main>
            <div class="card">
                <h2 style="color:var(--text);">🚀 Today's Focus</h2>
                <form action="actions/add_todo.php" method="POST" style="display:flex; gap:10px; margin-bottom:20px; padding:10px; border-bottom:1px solid var(--border);">
                    <input type="text" name="task" placeholder="Add a task for today..." required>
                    <input type="hidden" name="due_date" value="<?php echo $current_date; ?>">
                    <button type="submit" class="btn btn-primary">+</button>
                </form>
                
                <?php if (count($due_habits) > 0): ?>
                    <?php foreach($due_habits as $h): ?>
                        <?php $done = in_array($h['habit_id'], $done_ids); ?>
                        <div class="item-row">
                            <div style="display:flex; align-items:center; flex:1;">
                                <a href="actions/toggle_habit.php?id=<?php echo $h['habit_id']; ?>" class="action-toggle">
                                    <?php echo $done ? '✅' : '⬜'; ?>
                                </a>
                                <span style="<?php echo $done ? 'text-decoration:line-through; color:var(--text-secondary);' : 'color:var(--text);'; ?>">
                                    ⚡ <?php echo htmlspecialchars($h['name']); ?>
                                </span>
                            </div>
                            <div style="display:flex; gap:5px;">
                                <?php if($done): ?>
                                    <a href="actions/toggle_habit.php?id=<?php echo $h['habit_id']; ?>" class="btn-undo">↩️ Undo</a>
                                <?php else: ?>
                                    <a href="actions/toggle_habit.php?id=<?php echo $h['habit_id']; ?>" class="btn-done">✅ Done</a>
                                    <?php if($banked_days > 0): ?>
                                        <a href="actions/skip_habit.php?id=<?php echo $h['habit_id']; ?>" class="btn-skip" onclick="return confirm('Use 1 Banked Pass to skip?');">Skip</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php while($todo = $today_todos->fetch_assoc()): ?>
                    <div class="item-row">
                        <div style="flex:1; display:flex; align-items:center;">
                            <a href="actions/complete_todo.php?id=<?php echo $todo['todo_id']; ?>" class="action-toggle">
                                <?php echo $todo['is_completed'] ? '✅' : '⬜'; ?>
                            </a>
                            <span id="text-<?php echo $todo['todo_id']; ?>" class="<?php echo $todo['is_completed'] ? 'completed-text' : ''; ?>" style="color:var(--text);">
                                <?php echo htmlspecialchars($todo['task']); ?>
                                <?php if($todo['due_date'] < date('Y-m-d') && $todo['is_completed'] == 0): ?>
                                    <span style="color:#e74c3c; font-size:0.8em; font-weight:bold;"> (OVERDUE)</span>
                                <?php endif; ?>
                            </span>
                            <form id="form-<?php echo $todo['todo_id']; ?>" action="actions/update_todo.php" method="POST" class="edit-form" style="display:none; gap:5px; margin-left:10px; flex:1;">
                                <input type="hidden" name="todo_id" value="<?php echo $todo['todo_id']; ?>">
                                <input type="text" name="task" value="<?php echo htmlspecialchars($todo['task']); ?>">
                                <button type="submit" class="btn btn-primary" style="padding:2px 10px;">Save</button>
                            </form>
                        </div>
                        <div>
                            <button class="btn edit-btn" data-id="<?php echo $todo['todo_id']; ?>" style="background:transparent; color:var(--primary);">✏️</button>
                            <a href="actions/delete_todo.php?id=<?php echo $todo['todo_id']; ?>" class="btn" style="background:transparent; color:#e74c3c;">✖</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <div class="card" style="margin-top:20px;">
                 <h2 style="color:var(--text);">🗂️ All Habits</h2>
                 <div class="habits-grid">
                     <?php 
                     $categories = [
                         'Morning' => $morning_habits, 
                         'Afternoon' => $afternoon_habits, 
                         'Evening' => $evening_habits, 
                         'Anytime' => $anytime_habits
                     ]; 
                     ?>
                     <?php foreach($categories as $catName => $catData): ?>
                         <div>
                             <h3 style="color:var(--primary);"><?php echo $catName; ?></h3>
                             <?php while($h = $catData->fetch_assoc()): ?>
                                 <?php $stats = getHabitStats($conn, $user_id, $h['habit_id']); ?>
                                 <div class="item-row">
                                     <div>
                                         <?php echo htmlspecialchars($h['name']); ?> 
                                         <div class="streak-container" style="margin-top:5px;">
                                             <span class="streak-tag <?php echo $stats['status']=='active' ? 'streak-active' : 'streak-pending'; ?>">
                                                 🔥 <?php echo $stats['current']; ?>
                                             </span>
                                             <?php if($stats['longest'] > 0): ?>
                                                 <span class="streak-tag streak-record">🏆 <?php echo $stats['longest']; ?></span>
                                             <?php endif; ?>
                                         </div>
                                     </div>
                                     <a href="actions/delete_habit.php?id=<?php echo $h['habit_id']; ?>" style="color:#e74c3c;">🗑️</a>
                                 </div>
                             <?php endwhile; ?>
                         </div>
                     <?php endforeach; ?>
                 </div>
            </div>

            <div class="card" style="margin-top:20px; background:var(--bg); border:1px solid var(--border); box-shadow:none;">
                <h3 style="color:var(--text-secondary); font-size:1em; text-transform:uppercase;">📜 Task History</h3>
                <?php if ($history_todos->num_rows > 0): ?>
                    <?php while($hist = $history_todos->fetch_assoc()): ?>
                        <div class="item-row history-item">
                            <div>
                                <span class="history-date"><?php echo date('M j', strtotime($hist['due_date'])); ?></span>
                                <span style="text-decoration:line-through;"><?php echo htmlspecialchars($hist['task']); ?></span>
                            </div>
                            <span style="font-weight:bold; color:<?php echo $hist['is_completed'] ? '#2ecc71' : '#e74c3c'; ?>">
                                <?php echo $hist['is_completed'] ? '✅ COMPLETED' : '❌ LEFT (MISSED)'; ?>
                            </span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color:var(--text-secondary); font-size:0.9em;">No history yet.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <div onclick="toggleTheme()" style="position:fixed; bottom:20px; right:20px; background:var(--text); color:var(--bg); width:50px; height:50px; border-radius:50%; display:grid; place-items:center; cursor:pointer; box-shadow:0 4px 10px rgba(0,0,0,0.3); font-size:1.5rem; z-index:1000;">🌓</div>

    <script>
        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
        }
        if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-mode');

        function toggleDays() {
            const val = document.getElementById('freqSelect').value;
            document.getElementById('daysBox').style.display = (val === 'weekly') ? 'flex' : 'none';
        }

        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = e.target.dataset.id;
                document.getElementById('text-'+id).style.display = 'none';
                document.getElementById('form-'+id).style.display = 'flex';
            });
        });
    </script>
    <style> @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.02); } 100% { transform: scale(1); } } </style>
</body>
</html>