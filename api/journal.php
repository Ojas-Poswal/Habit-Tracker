<?php
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}
require_once "db_connect.php";

$user_id = $_SESSION["id"];

// Fetch all past journal entries
$sql_fetch = "SELECT * FROM journal_entries WHERE user_id = ? ORDER BY entry_date DESC";
$entries_result = $conn->prepare($sql_fetch);
$entries_result->bind_param("i", $user_id);
$entries_result->execute();
$entries = $entries_result->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Journal - HabitFlow</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container { max-width: 1000px; margin: 0 auto; padding: 30px; }
        
        /* Masonry Layout */
        .journal-grid { column-count: 3; column-gap: 20px; margin-top: 30px; }
        .journal-card { 
            break-inside: avoid; 
            margin-bottom: 20px; 
            background: var(--card-bg); 
            padding: 20px; 
            border-radius: 16px; 
            box-shadow: var(--shadow);
            transition: transform 0.2s;
            border: 1px solid var(--border);
        }
        .journal-card:hover { transform: translateY(-5px); }
        
        .journal-date { font-size: 0.8em; color: var(--text-secondary); margin-bottom: 10px; display: block; font-weight:600; text-transform:uppercase; letter-spacing:1px; }
        .journal-content { line-height: 1.6; color: var(--text); white-space: pre-wrap; }
        
        /* Form Elements */
        textarea {
            width: 100%; background: var(--bg); color: var(--text);
            border: 1px solid var(--border); padding: 15px; border-radius: 8px;
            font-family: inherit; resize: vertical;
        }
        input[type="text"] {
            width: 100%; background: var(--bg); color: var(--text);
            border: 1px solid var(--border); padding: 12px; border-radius: 8px;
            font-weight: bold;
        }
        
        .btn-save { background: #6C63FF; color: white; border: none; padding: 10px 25px; border-radius: 8px; cursor: pointer; font-weight:bold; }
        .btn-save:hover { opacity: 0.9; }

        @media (max-width: 800px) { .journal-grid { column-count: 2; } }
        @media (max-width: 500px) { .journal-grid { column-count: 1; } }
    </style>
</head>
<body>
    
    <div class="app-logo" style="padding: 20px 30px;">
        <div class="logo-icon">⚡</div> <a href="dashboard.php" style="text-decoration:none; color:var(--text);">HabitFlow</a>
    </div>

    <div class="container">
        
        <div class="card">
            <h2 style="margin-top:0; color:var(--text);">✍️ Daily Reflection</h2>
            <form action="actions/add_entry.php" method="POST">
                <input type="text" name="title" placeholder="Title of the day..." style="margin-bottom: 15px; font-size:1.1em;">
                <textarea name="content" placeholder="How was your day? What did you achieve?" style="min-height: 120px; margin-bottom: 15px;"></textarea>
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span style="color:var(--text-secondary)"><?php echo date("F j, Y"); ?></span>
                    <button type="submit" class="btn-save">Save Entry</button>
                </div>
            </form>
        </div>

        <div class="journal-grid">
            <?php if ($entries->num_rows > 0): ?>
                <?php while($entry = $entries->fetch_assoc()): ?>
                    <div class="journal-card">
                        <span class="journal-date"><?php echo date("M j, Y", strtotime($entry['entry_date'])); ?></span>
                        <?php if(!empty($entry['title'])): ?>
                            <h3 style="margin: 0 0 10px 0; color:#6C63FF;"><?php echo htmlspecialchars($entry['title']); ?></h3>
                        <?php endif; ?>
                        <p class="journal-content"><?php echo nl2br(htmlspecialchars($entry['content'])); ?></p>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align:center; color:var(--text-secondary); column-span:all;">No entries yet. Start writing!</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-mode');
    </script>
</body>
</html>