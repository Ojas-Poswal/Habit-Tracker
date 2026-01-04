<?php
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){ header("location: index.php"); exit; }
require_once "db_connect.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Planner - HabitFlow</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .layout { padding: 40px; max-width: 1400px; margin: 0 auto; }
        .calendar-container { background: var(--card-bg); padding: 30px; border-radius: 16px; box-shadow: var(--shadow); }
        .calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .calendar-header h2 { margin: 0; color: var(--primary); text-transform: uppercase; letter-spacing: 1px; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 1px; background: var(--border); border: 1px solid var(--border); }
        .day-name { background: var(--bg); padding: 10px; text-align: center; font-weight: bold; color: var(--text-secondary); }
        
        .calendar-day { 
            background: var(--card-bg); 
            min-height: 120px; 
            padding: 10px; 
            position: relative; 
            cursor: pointer; 
            transition: 0.2s;
            border-right: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }
        .calendar-day:hover { background: var(--bg); }
        .calendar-day.today { border: 2px solid var(--primary); }
        .day-number { font-weight: bold; color: var(--text); font-size: 0.9em; margin-bottom: 5px; display: inline-block; }
        .other-month { opacity: 0.4; background: #f9f9f9; }
        body.dark-mode .other-month { background: #151515; }

        /* Event Pills */
        .event-pill {
            font-size: 0.75em;
            padding: 2px 6px;
            border-radius: 4px;
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: white;
            font-weight: 500;
        }
        .type-habit { background: var(--secondary); } 
        .type-habit.quit { background: #e74c3c; } 
        .type-todo { background: #3498db; } 
        .type-habit.done, .type-todo.done { 
            text-decoration: line-through; 
            opacity: 0.7; 
            background-color: #95a5a6 !important; /* Grayed out when done */
        }

        /* Modal Styles */
        .modal-overlay { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.7); z-index: 1000; justify-content: center; align-items: center; }
        .modal { background: var(--card-bg); padding: 30px; border-radius: 12px; width: 600px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
        .close-modal { float: right; cursor: pointer; font-size: 1.5em; color: var(--text-secondary); }
        
        .day-event-list { max-height: 250px; overflow-y: auto; margin-bottom: 20px; padding-right: 10px; }
        .day-event-item { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 8px; 
            border-bottom: 1px solid var(--border);
            font-size: 0.9em;
        }
        .event-type-tag { font-size: 0.75em; padding: 2px 8px; border-radius: 4px; background: var(--primary); color: white; margin-left: 10px; }
    </style>
</head>
<body>

    <div class="layout">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <a href="dashboard.php" class="btn" style="background:var(--card-bg); color:var(--text); text-decoration:none;">← Back to Dashboard</a>
            <h1 style="margin:0; font-size:1.5em; color:var(--primary);">📅 Planner</h1>
            <div style="width:100px;"></div>
        </div>

        <div class="calendar-container">
            <div class="calendar-header">
                <h2 id="monthYear"></h2>
                <div>
                    <button class="btn" onclick="changeMonth(-1)">❮ Prev</button>
                    <button class="btn" onclick="changeMonth(1)">Next ❯</button>
                </div>
            </div>
            <div class="calendar-grid" id="calendar"></div>
        </div>
    </div>

    <div class="modal-overlay" id="eventModal">
        <div class="modal">
            <span class="close-modal" onclick="closeModal()">×</span>
            <h2 style="margin-top:0;">Agenda for <span id="modalDateDisplay" style="color:var(--primary);"></span></h2>

            <div class="day-event-list" id="eventList"></div>

            <h3 style="color:var(--text); border-top:1px solid var(--border); padding-top:15px; margin-top:10px;">+ Add New Task</h3>
            <form action="actions/add_todo.php" method="POST">
                <input type="hidden" name="due_date" id="modalDateInput">
                <input type="text" name="task" placeholder="Task description..." required autofocus style="margin-bottom:15px;">
                <button type="submit" class="btn btn-primary" style="width:100%;">Schedule Task</button>
            </form>
        </div>
    </div>

    <script>
        if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-mode');

        let currentDate = new Date();
        let eventsCache = [];

        // Helper: Fetches data from PHP endpoint
        async function fetchEvents(month, year) {
            const response = await fetch(`actions/fetch_calendar_events.php?month=${month}&year=${year}`);
            eventsCache = await response.json();
            renderCalendar();
        }

        function renderCalendar() {
            const dt = new Date(currentDate);
            dt.setDate(1);
            
            const month = dt.getMonth();
            const year = dt.getFullYear();
            
            const firstDayIndex = dt.getDay();
            const lastDay = new Date(year, month + 1, 0).getDate();
            const prevLastDay = new Date(year, month, 0).getDate();
            const nextDays = 7 - new Date(year, month + 1, 0).getDay() - 1;

            const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            document.getElementById('monthYear').innerText = `${monthNames[month]} ${year}`;

            let html = "";
            const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            daysOfWeek.forEach(day => html += `<div class="day-name">${day}</div>`);

            // Previous Month Days
            for (let x = firstDayIndex; x > 0; x--) {
                html += `<div class="calendar-day other-month"><span class="day-number">${prevLastDay - x + 1}</span></div>`;
            }

            // Current Month Days
            for (let i = 1; i <= lastDay; i++) {
                const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
                const isToday = i === new Date().getDate() && month === new Date().getMonth() && year === new Date().getFullYear();
                
                const dayEvents = eventsCache.filter(e => e.date === dateStr);
                
                let eventHTML = "";
                dayEvents.forEach(ev => {
                    let className = 'event-pill ';
                    if(ev.type === 'habit') className += ev.habit_type === 'quit' ? 'type-habit quit' : 'type-habit';
                    else className += 'type-todo';
                    if(ev.status === 'done') className += ' done';
                    
                    eventHTML += `<div class="${className}">${ev.type === 'habit' ? '⚡' : '•'} ${ev.title}</div>`;
                });

                html += `
                <div class="calendar-day ${isToday ? 'today' : ''}" onclick="openModal('${dateStr}')">
                    <span class="day-number">${i}</span>
                    ${eventHTML}
                </div>`;
            }

            // Next Month Days
            for (let j = 1; j <= nextDays + 1; j++) {
                html += `<div class="calendar-day other-month"><span class="day-number">${j}</span></div>`;
            }

            document.getElementById('calendar').innerHTML = html;
        }

        function changeMonth(direction) {
            currentDate.setMonth(currentDate.getMonth() + direction);
            fetchEvents(currentDate.getMonth() + 1, currentDate.getFullYear());
        }

        // --- MODAL LOGIC ---
        const modal = document.getElementById('eventModal');
        const dateDisplay = document.getElementById('modalDateDisplay');
        const dateInput = document.getElementById('modalDateInput');
        const eventListDiv = document.getElementById('eventList');

        function openModal(dateStr) {
            dateDisplay.innerText = dateStr;
            dateInput.value = dateStr;

            // Filter events for the clicked day
            const dayEvents = eventsCache.filter(e => e.date === dateStr);
            let listHTML = "";

            if (dayEvents.length > 0) {
                dayEvents.forEach(ev => {
                    const statusText = ev.status === 'done' ? '✅ Done' : '❌ Pending';
                    const statusColor = ev.status === 'done' ? '#2ecc71' : '#e74c3c';
                    const typeTag = ev.type === 'habit' ? 'HABIT' : 'TASK';
                    const linkAction = ev.type === 'habit' ? `toggle_habit.php?id=${ev.habit_id}` : `complete_todo.php?id=${ev.todo_id}`;
                    
                    // NOTE: Since the Calendar data structure doesn't contain habit_id/todo_id, 
                    // this link will not work yet! We need to update the PHP JSON to include IDs.
                    // For now, we only render the status visually.

                    listHTML += `
                    <div class="day-event-item">
                        <div>
                            <span style="color:${statusColor}; font-weight:bold;">${statusText}</span>
                            <span style="margin-left:15px;">${ev.title}</span>
                            <span class="event-type-tag">${typeTag}</span>
                        </div>
                        <a href="dashboard.php" style="color:var(--primary); font-size:0.9em;">View/Edit</a>
                    </div>`;
                });
            } else {
                listHTML = `<p style="color:var(--text-secondary); text-align:center;">Nothing scheduled for this day.</p>`;
            }

            eventListDiv.innerHTML = listHTML;
            modal.style.display = 'flex';
        }

        function closeModal() {
            modal.style.display = 'none';
            // Refresh dashboard to show correct status after an action
            window.location.reload(); 
        }

        window.onclick = function(event) {
            if (event.target == modal) closeModal();
        }

        // Initialize
        fetchEvents(currentDate.getMonth() + 1, currentDate.getFullYear());
    </script>
</body>
</html>