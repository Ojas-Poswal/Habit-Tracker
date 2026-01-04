</main>
    </div>

    <div onclick="toggleTheme()" style="position:fixed; bottom:20px; right:20px; background:var(--text); color:var(--bg); width:50px; height:50px; border-radius:50%; display:grid; place-items:center; cursor:pointer; box-shadow:0 4px 10px rgba(0,0,0,0.3); font-size:1.5rem; z-index:1000;">🌓</div>
    
    <footer style="text-align: center; margin-top: 30px; padding: 15px; font-size: 0.8em; color: var(--text-secondary); border-top: 1px solid var(--border);">
        &copy; <?php echo date('Y'); ?> **HabitFlow**. All rights reserved. Built by **Parv Chaudhary**.
    </footer>

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
</body>
</html>