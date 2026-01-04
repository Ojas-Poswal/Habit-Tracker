<?php
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){ header("location: index.php"); exit; }
require_once "db_connect.php";
$user_id = $_SESSION["id"];
// Include user photo logic for sidebar consistency if desired, or keep simple
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics - HabitFlow</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .layout { display: grid; grid-template-columns: 250px 1fr; gap: 20px; padding: 20px; max-width: 1400px; margin: 0 auto; }
        .chart-container { background: var(--card-bg); padding: 30px; border-radius: 16px; box-shadow: var(--shadow); margin-bottom: 20px; }
        h2 { margin-top: 0; color: var(--primary); }
    </style>
</head>
<body>
    <div class="layout">
        <aside style="display:flex; flex-direction:column; gap:10px;">
             <a href="dashboard.php" class="btn" style="background:var(--card-bg); text-align:center;">← Back to Dashboard</a>
        </aside>

        <main>
            <h1>📊 Your Progress</h1>
            
            <div class="chart-container">
                <h2>Habit Completion Trend (Last 7 Days)</h2>
                <canvas id="habitTrendChart"></canvas>
            </div>

            <div class="chart-container">
                <h2>Task Completion Ratio</h2>
                <canvas id="todoSummaryChart" style="max-height: 400px;"></canvas>
            </div>
        </main>
    </div>

    <script>
        // Dark Mode Check
        if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-mode');

        fetch('analytics_data.php')
            .then(response => response.json())
            .then(data => {
                if (data.error) return;
                
                // Line Chart
                new Chart(document.getElementById('habitTrendChart').getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: data.habit_trend.labels,
                        datasets: [{
                            label: 'Habits Completed',
                            data: data.habit_trend.data,
                            borderColor: '#6C63FF',
                            backgroundColor: 'rgba(108, 99, 255, 0.2)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: { 
                        responsive: true,
                        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } 
                    }
                });

                // Pie Chart
                new Chart(document.getElementById('todoSummaryChart').getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Completed', 'Pending'],
                        datasets: [{
                            data: [data.todo_summary.completed, data.todo_summary.pending],
                            backgroundColor: ['#2ecc71', '#e74c3c'],
                            borderWidth: 0
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                });
            });
    </script>
</body>
</html>