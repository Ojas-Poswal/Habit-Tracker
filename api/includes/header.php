<?php
// --- CORE CONFIG ---
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){ header("location: index.php"); exit; }
require_once __DIR__ . '/../db_connect.php';

date_default_timezone_set('Asia/Kolkata'); 
$current_date = date('Y-m-d');
$user_id = $_SESSION["id"];

// --- FETCH GLOBAL USER DATA ---
$user_query = $conn->query("SELECT * FROM users WHERE id = $user_id");
$user_data = $user_query->fetch_assoc();

// --- IMAGE LOGIC (FOLDER METHOD) ---
$photo_name = $user_data['profile_photo'];
// Check if file exists in uploads folder
if (!empty($photo_name) && file_exists(__DIR__ . '/../uploads/' . $photo_name)) {
    $user_photo = "uploads/" . $photo_name;
} else {
    $user_photo = "https://via.placeholder.com/150";
}

$cheat_cost = $user_data['cheat_day_cost'] ? $user_data['cheat_day_cost'] : 30; 
$can_afford = $user_data['coins'] >= $cheat_cost;
$banked_days = $user_data['cheat_days_banked'];

// --- STREAK HELPER ---
function calculateStreak($conn, $user_id, $habit_id) {
    $sql = "SELECT DISTINCT completion_date FROM habit_log WHERE user_id = ? AND habit_id = ? ORDER BY completion_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $habit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $dates = [];
    while ($row = $result->fetch_assoc()) { $dates[] = $row['completion_date']; }
    if (empty($dates)) return 0;
    $now = new DateTime();
    $last = new DateTime($dates[0]);
    $interval = $now->diff($last);
    if ($interval->days > 1) return 0;
    $streak = 1;
    for ($i = 0; $i < count($dates) - 1; $i++) {
        $d1 = new DateTime($dates[$i]);
        $d2 = new DateTime($dates[$i+1]);
        if ($d1->diff($d2)->days == 1) $streak++; else break;
    }
    return $streak;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HabitFlow</title>
    <link rel="stylesheet" href="style.css">
    <style>
        html, body { height: 100%; margin: 0; }
        .layout { 
            display: grid; 
            grid-template-columns: 280px 1fr; 
            gap: 20px; 
            padding: 20px; 
            max-width:1600px; 
            margin:0 auto; 
            min-height: 100vh;
        }
        
        .sidebar { 
            display: flex; 
            flex-direction: column; 
            gap: 20px; 
            position: sticky; 
            top: 20px; 
            height: calc(100vh - 40px); 
            overflow-y: auto; 
        }
        
        .nav-links {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 15px !important;
        }
        .nav-links a {
            padding: 12px 15px;
            border-radius: 10px;
            color: var(--text);
            font-weight: 600;
            background: var(--bg);
            transition: 0.2s;
            display: block;
            text-decoration: none;
        }
        .nav-links a:hover {
            background: var(--primary);
            color: white !important;
            transform: translateX(5px);
        }
        .logout-link { color: #e74c3c !important; border: 1px solid #e74c3c; }
        .logout-link:hover { background: #e74c3c !important; color: white !important; }

        .coin-progress { background: #eee; border-radius: 10px; overflow: hidden; height: 25px; position: relative; margin-top: 10px; }
        .coin-bar { background: #f1c40f; height: 100%; width: <?php echo min(100, ($user_data['coins']/$cheat_cost)*100); ?>%; transition: width 0.5s; }
        .coin-text { position: absolute; width: 100%; text-align: center; top: 2px; font-size: 0.8em; font-weight: bold; color: #333; }

        @media screen and (max-width: 900px) {
            .layout { grid-template-columns: 1fr; padding: 10px; }
            .sidebar { position: static; height: auto; margin-bottom: 20px; }
            .nav-links { display: grid !important; grid-template-columns: repeat(3, 1fr); }
            .nav-links a { text-align: center; font-size: 0.8em; }
        }
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="card" style="text-align:center; padding:25px;">
                <img src="<?php echo $user_photo; ?>" style="width:90px; height:90px; border-radius:50%; object-fit:cover; border:3px solid var(--primary); margin-bottom:10px;">
                <h3 style="margin:0; color:var(--text);"><?php echo htmlspecialchars($user_data['name']); ?></h3>
                
                <div style="text-align:left; margin-top:15px; background:var(--bg); padding:10px; border-radius:8px;">
                    <div style="display:flex; justify-content:space-between; font-size:0.8em; color:var(--text-secondary);">
                        <span>Cheat Day Fund</span>
                        <span><?php echo $user_data['coins']; ?> / <?php echo $cheat_cost; ?> 🪙</span>
                    </div>
                    <div class="coin-progress">
                        <div class="coin-bar"></div>
                    </div>
                    <?php if($can_afford): ?>
                        <a href="actions/redeem_cheat_day.php" class="btn" style="background:#f1c40f; color:white; width:100%; margin-top:10px; text-align:center; display:block; padding:8px 0; border-radius:6px; text-decoration:none;">🎁 Redeem Pass</a>
                    <?php endif; ?>
                    <div style="margin-top:8px; font-size:0.8em; text-align:center; color:#2ecc71;">
                        🎟️ Banked Passes: <strong><?php echo $banked_days; ?></strong>
                    </div>
                </div>
            </div>
            
            <nav class="card nav-links">
                <a href="dashboard.php">🏠 Dashboard</a>
                <a href="calendar.php">📅 Calendar</a>
                <a href="community.php">🌍 Community</a>
                <a href="analytics.php">📊 Analytics</a>
                <a href="timers.php">⏱️ Focus Studio</a>
                <a href="journal.php">📓 Journal</a>
                <a href="profile.php">👤 Profile</a>
                <a href="faq.php">❓ FAQ</a>
                <a href="logout.php" class="logout-link">🚪 Logout</a>
            </nav>

            <div class="card">
                <h4 style="margin-top:0; color:var(--text);">+ New Habit</h4>
                <form action="actions/add_habit.php" method="POST" style="display:flex; flex-direction:column; gap:10px;">
                    <input type="text" name="name" placeholder="Habit Name" required style="padding:10px; border-radius:6px; border:1px solid var(--border); background:var(--bg); color:var(--text);">
                    <select name="category" style="padding:10px; border-radius:6px; border:1px solid var(--border); background:var(--bg); color:var(--text);">
                        <option value="morning">Morning</option>
                        <option value="afternoon">Afternoon</option>
                        <option value="evening">Evening</option>
                        <option value="anytime">Anytime</option>
                    </select>
                    <select name="habit_type" style="padding:10px; border-radius:6px; border:1px solid var(--border); background:var(--bg); color:var(--text);">
                        <option value="build">Build (Positive)</option>
                        <option value="quit">Quit (Negative)</option>
                    </select>
                    <select name="frequency" id="freqSelect" onchange="toggleDays()" style="padding:10px; border-radius:6px; border:1px solid var(--border); background:var(--bg); color:var(--text);">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                    <div id="daysBox" style="display:none; flex-wrap:wrap; gap:5px;">
                        <label style="font-size:0.8em; color:var(--text);"><input type="checkbox" name="weekly_days[]" value="Mon"> Mon</label>
                        <label style="font-size:0.8em; color:var(--text);"><input type="checkbox" name="weekly_days[]" value="Tue"> Tue</label>
                        <label style="font-size:0.8em; color:var(--text);"><input type="checkbox" name="weekly_days[]" value="Wed"> Wed</label>
                        <label style="font-size:0.8em; color:var(--text);"><input type="checkbox" name="weekly_days[]" value="Thu"> Thu</label>
                        <label style="font-size:0.8em; color:var(--text);"><input type="checkbox" name="weekly_days[]" value="Fri"> Fri</label>
                        <label style="font-size:0.8em; color:var(--text);"><input type="checkbox" name="weekly_days[]" value="Sat"> Sat</label>
                        <label style="font-size:0.8em; color:var(--text);"><input type="checkbox" name="weekly_days[]" value="Sun"> Sun</label>
                    </div>
                    <button type="submit" class="btn btn-primary" style="padding:10px; border-radius:6px; margin-top:5px;">Add Habit</button>
                </form>
            </div>
        </aside>
        <main style="width: 100%;">