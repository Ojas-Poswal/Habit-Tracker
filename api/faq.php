<?php
session_start();
if(!isset($_SESSION["loggedin"])){ header("location: index.php"); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FAQ - HabitFlow</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container { max-width: 800px; margin: 0 auto; padding: 40px; }
        .faq-item { background: var(--card-bg); margin-bottom: 15px; border-radius: 12px; box-shadow: var(--shadow); overflow: hidden; }
        .faq-question { padding: 20px; cursor: pointer; font-weight: bold; display: flex; justify-content: space-between; align-items: center; color: var(--text); }
        .faq-answer { padding: 0 20px; max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; color: var(--text-secondary); line-height: 1.6; }
        .faq-answer p { padding-bottom: 20px; margin: 0; }
        .arrow { transition: transform 0.3s; }
        .active .arrow { transform: rotate(180deg); }
        .active .faq-answer { max-height: 200px; }
    </style>
</head>
<body>

    <div class="app-logo" style="padding: 20px 30px;">
        <div class="logo-icon">⚡</div> <a href="dashboard.php" style="text-decoration:none; color:var(--text);">HabitFlow</a>
    </div>

    <div class="container">
        <h1 style="text-align:center; color:var(--primary); margin-bottom:30px;">Frequently Asked Questions</h1>

        <div class="faq-item">
            <div class="faq-question">How do Cheat Days work? <span class="arrow">▼</span></div>
            <div class="faq-answer"><p>You earn 1 coin for every habit you complete. Once you have enough coins (default is 30), you can redeem them for a "Banked Pass". You can use this pass to skip a habit without breaking your streak!</p></div>
        </div>

        <div class="faq-item">
            <div class="faq-question">How do I change the music in Timers? <span class="arrow">▼</span></div>
            <div class="faq-answer"><p>Go to the Timers page. In the bottom section "Focus Studio", copy a YouTube or Spotify link and paste it into the box. Click "Replace Video" and it will be saved to your profile.</p></div>
        </div>

        <div class="faq-item">
            <div class="faq-question">How do weekly habits work? <span class="arrow">▼</span></div>
            <div class="faq-answer"><p>When creating a habit, select "Weekly". You can then check specific boxes (like Mon, Wed, Fri). The habit will only appear in your "Today's Focus" list on those specific days.</p></div>
        </div>

        <div class="faq-item">
            <div class="faq-question">Is my data private? <span class="arrow">▼</span></div>
            <div class="faq-answer"><p>Yes! Your journal entries, habits, and tasks are private to your account. Only posts you explicitly share on the Community page are visible to other users.</p></div>
        </div>

    </div>

    <script>
        if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-mode');

        const items = document.querySelectorAll('.faq-item');
        items.forEach(item => {
            item.querySelector('.faq-question').addEventListener('click', () => {
                item.classList.toggle('active');
            });
        });
    </script>
</body>
</html>