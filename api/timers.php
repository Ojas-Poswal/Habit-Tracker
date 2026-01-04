<?php
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){ header("location: index.php"); exit; }
require_once "db_connect.php";

$user_id = $_SESSION["id"];

// Fetch Saved Timers
$sql_fetch = "SELECT * FROM timers WHERE user_id = ? ORDER BY timer_name ASC";
$timers_result = $conn->prepare($sql_fetch);
$timers_result->bind_param("i", $user_id);
$timers_result->execute();
$timers = $timers_result->get_result();

// Fetch User's Focus Video
$user_sql = "SELECT focus_video_link FROM users WHERE id = ?";
$u_stmt = $conn->prepare($user_sql);
$u_stmt->bind_param("i", $user_id);
$u_stmt->execute();
$user_data = $u_stmt->get_result()->fetch_assoc();
$current_video = $user_data['focus_video_link'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Focus Studio - HabitFlow</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .layout { max-width: 1200px; margin: 0 auto; padding: 30px; display: grid; gap: 30px; }
        
        /* Top Section: Timers & Stopwatch */
        .time-zone { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        
        .digital-display {
            font-family: 'Courier New', monospace;
            font-size: 3.5rem;
            font-weight: 800;
            color: #6C63FF; /* Primary Color */
            text-align: center;
            background: #ffffff;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 2px solid #e0e0e0;
            box-shadow: inset 0 0 10px rgba(0,0,0,0.05);
        }

        /* CONTROLS - FORCE COLORS to ensure visibility */
        .controls { display: flex; gap: 10px; justify-content: center; margin-bottom: 20px; }
        .btn-lg { 
            padding: 12px 30px; 
            font-size: 1.1em; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            color: white !important; /* Force text white */
            font-weight: bold; 
            transition: 0.2s;
        }
        
        .btn-start { background-color: #6C63FF !important; }
        .btn-stop { background-color: #e74c3c !important; } /* RED */
        .btn-reset { background-color: #95a5a6 !important; } /* GRAY */
        
        .btn-start:hover { opacity: 0.9; }
        .btn-stop:hover { background-color: #c0392b !important; }

        /* Presets List */
        .presets-list { display: flex; flex-wrap: wrap; gap: 10px; }
        .preset-tag {
            background: #ffffff; border: 1px solid #e0e0e0;
            padding: 8px 15px; border-radius: 20px; cursor: pointer;
            transition: 0.2s; display: flex; align-items: center; gap: 8px;
            color: #333;
        }
        .preset-tag:hover { background: #6C63FF; color: white; border-color: #6C63FF; }
        .delete-x { color: #e74c3c; font-weight: bold; text-decoration: none; padding-left: 5px; }
        .delete-x:hover { color: white; }

        /* Bottom Section: The Big Video Window */
        .video-section { background: #000; padding: 10px; border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,0.3); }
        .video-wrapper {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 Aspect Ratio */
            height: 0;
            overflow: hidden;
            border-radius: 8px;
            background: #111;
        }
        .video-wrapper iframe {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        }
        
        .url-input-group { display: flex; gap: 10px; margin-top: 15px; padding: 0 10px; }
        
        @media (max-width: 800px) { .time-zone { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <div class="layout">
        <a href="dashboard.php" class="btn" style="background:#e0e0e0; color:#333; width: fit-content; text-decoration:none;">← Back to Dashboard</a>

        <div class="time-zone">
            
            <div class="card">
                <h2 style="text-align:center; margin-top:0; color:#333;">⏱️ Stopwatch</h2>
                <div class="digital-display" id="swDisplay">00:00:00</div>
                <div class="controls">
                    <button class="btn-lg btn-start" id="swStart">Start</button>
                    <button class="btn-lg btn-stop" id="swStop">Stop</button>
                    <button class="btn-lg btn-reset" id="swReset">Reset</button>
                </div>
            </div>

            <div class="card">
                <h2 style="text-align:center; margin-top:0; color:#333;">⏳ Timer</h2>
                <div class="digital-display" id="cdDisplay">25:00</div>
                
                <div class="controls">
                    <button class="btn-lg btn-start" id="cdStart">Start</button>
                    <button class="btn-lg btn-stop" id="cdStop">Stop</button>
                    <button class="btn-lg btn-reset" id="cdReset">Reset</button>
                </div>

                <div style="display:flex; gap:10px; justify-content:center; margin-bottom:20px;">
                    <input type="number" id="customMins" placeholder="Mins" style="width:80px; text-align:center; padding:8px; border:1px solid #ccc; border-radius:4px;">
                    <button class="btn" id="setCustomBtn" style="background:#6C63FF; color:white; border:none; padding:8px 15px; border-radius:4px; cursor:pointer;">Set Time</button>
                </div>

                <hr style="border:0; border-top:1px solid #e0e0e0;">
                
                <h4 style="margin:10px 0; color:#555;">Saved Presets</h4>
                <div class="presets-list">
                    <?php while($timer = $timers->fetch_assoc()): ?>
                        <div class="preset-tag cd-load-btn" data-time="<?php echo $timer['duration_minutes']; ?>">
                            <?php echo htmlspecialchars($timer['timer_name']); ?> (<?php echo $timer['duration_minutes']; ?>m)
                            <a href="actions/delete_timer.php?id=<?php echo $timer['timer_id']; ?>" class="delete-x" onclick="event.stopPropagation(); return confirm('Delete?');">×</a>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <form action="actions/add_timer.php" method="POST" style="margin-top:15px; display:flex; gap:5px;">
                    <input type="text" name="timer_name" placeholder="New Preset Name" required style="padding:8px; border:1px solid #ccc; border-radius:4px; flex:1;">
                    <input type="number" name="duration" placeholder="Min" style="width:70px; padding:8px; border:1px solid #ccc; border-radius:4px;" required>
                    <input type="hidden" name="embed_link" value=""> <button type="submit" class="btn" style="background:#6C63FF; color:white; border:none; padding:8px 15px; border-radius:4px; cursor:pointer;">+</button>
                </form>
            </div>
        </div>

        <div class="video-section">
            <?php if(!empty($current_video)): ?>
                <div class="video-wrapper">
                    <iframe src="<?php echo htmlspecialchars($current_video); ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
            <?php else: ?>
                <div class="video-wrapper" style="display:grid; place-items:center; color:white;">
                    <h3>No Video Loaded</h3>
                </div>
            <?php endif; ?>

            <form action="actions/update_music.php" method="POST" class="url-input-group">
                <input type="text" name="video_link" placeholder="Paste new YouTube URL here..." style="background:#222; color:white; border:1px solid #444; flex:1; padding:10px; border-radius:4px;">
                <button type="submit" class="btn" style="background:#6C63FF; color:white; border:none; padding:10px 20px; border-radius:4px; cursor:pointer;">Replace Video</button>
            </form>
        </div>

    </div>

    <script>
        if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-mode');
        
        // --- STOPWATCH LOGIC ---
        let swInterval;
        let swSeconds = 0;
        const swDisplay = document.getElementById('swDisplay');

        document.getElementById('swStart').addEventListener('click', () => {
            if (swInterval) return;
            swInterval = setInterval(() => {
                swSeconds++;
                const h = Math.floor(swSeconds / 3600).toString().padStart(2, '0');
                const m = Math.floor((swSeconds % 3600) / 60).toString().padStart(2, '0');
                const s = (swSeconds % 60).toString().padStart(2, '0');
                swDisplay.textContent = `${h}:${m}:${s}`;
            }, 1000);
        });

        document.getElementById('swStop').addEventListener('click', () => {
            clearInterval(swInterval);
            swInterval = null;
        });

        document.getElementById('swReset').addEventListener('click', () => {
            clearInterval(swInterval);
            swInterval = null;
            swSeconds = 0;
            swDisplay.textContent = "00:00:00";
        });

        // --- COUNTDOWN LOGIC ---
        let cdInterval;
        let cdTime = 25 * 60;
        const cdDisplay = document.getElementById('cdDisplay');

        function updateCdDisplay() {
            const m = Math.floor(cdTime / 60).toString().padStart(2, '0');
            const s = (cdTime % 60).toString().padStart(2, '0');
            cdDisplay.textContent = `${m}:${s}`;
        }

        document.getElementById('cdStart').addEventListener('click', () => {
            if (cdInterval) return;
            cdInterval = setInterval(() => {
                if (cdTime <= 0) {
                    clearInterval(cdInterval);
                    alert("Time is up!");
                } else {
                    cdTime--;
                    updateCdDisplay();
                }
            }, 1000);
        });

        document.getElementById('cdStop').addEventListener('click', () => {
            clearInterval(cdInterval);
            cdInterval = null;
        });

        document.getElementById('cdReset').addEventListener('click', () => {
            clearInterval(cdInterval);
            cdInterval = null;
            cdTime = 25 * 60; // Default to 25
            updateCdDisplay();
        });

        document.getElementById('setCustomBtn').addEventListener('click', () => {
            const mins = document.getElementById('customMins').value;
            if(mins > 0) {
                clearInterval(cdInterval);
                cdInterval = null;
                cdTime = mins * 60;
                updateCdDisplay();
            }
        });

        // Load Preset Buttons
        document.querySelectorAll('.cd-load-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                if(e.target.classList.contains('delete-x')) return;

                const mins = btn.dataset.time;
                clearInterval(cdInterval);
                cdInterval = null;
                cdTime = mins * 60;
                updateCdDisplay();
            });
        });
    </script>
</body>
</html>