<?php
session_start();
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HabitFlow</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .auth-container {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
        }
        .auth-box {
            width: 400px;
            background: var(--card-bg);
            padding: 40px;
            border-radius: 20px;
            text-align: center;
        }
        .toggle-link { color: var(--primary); cursor: pointer; text-decoration: underline; }
        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-box shadow">
            <div class="app-logo" style="justify-content: center; margin-bottom: 20px;">
                <div class="logo-icon">⚡</div> HabitFlow
            </div>

            <form id="login-form" action="auth.php" method="POST">
                <h2 style="margin-bottom: 20px;">Welcome Back</h2>
                <input type="hidden" name="action" value="login">
                <input type="text" name="username" placeholder="Username" required style="margin-bottom: 15px;">
                <input type="password" name="password" placeholder="Password" required style="margin-bottom: 15px;">
                <button type="submit" class="btn btn-primary" style="width: 100%;">Log In</button>
                <p>New here? <span class="toggle-link" onclick="toggleForms()">Create Account</span></p>
            </form>

            <form id="register-form" action="auth.php" method="POST" class="hidden">
                <h2 style="margin-bottom: 20px;">Join Us</h2>
                <input type="hidden" name="action" value="register">
                <input type="text" name="username" placeholder="Username" required style="margin-bottom: 10px;">
                <input type="text" name="name" placeholder="Full Name" required style="margin-bottom: 10px;">
                <input type="text" name="mobile" placeholder="Mobile" required style="margin-bottom: 10px;">
                <input type="password" name="password" placeholder="Password" required style="margin-bottom: 15px;">
                <button type="submit" class="btn btn-primary" style="width: 100%;">Sign Up</button>
                <p>Already a member? <span class="toggle-link" onclick="toggleForms()">Log In</span></p>
            </form>
        </div>
    </div>

    <script>
        function toggleForms() {
            document.getElementById('login-form').classList.toggle('hidden');
            document.getElementById('register-form').classList.toggle('hidden');
        }
    </script>
</body>
</html>