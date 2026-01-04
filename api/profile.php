<?php
require_once 'includes/header.php'; // Sidebar is auto-loaded

// Fetch user again for specifics
$msg = "";
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'uploaded') $msg = "✅ Photo updated!";
    if ($_GET['msg'] == 'updated') $msg = "✅ Settings saved!";
}
?>

<div class="card" style="max-width:800px; margin:0 auto;">
    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border); padding-bottom:20px; margin-bottom:20px;">
        <h1 style="margin:0; color:var(--primary);">Profile Settings</h1>
        <?php if($msg): ?><span style="color:#2ecc71; font-weight:bold;"><?php echo $msg; ?></span><?php endif; ?>
    </div>

    <div style="display:flex; gap:20px; margin-bottom:30px; align-items:center; background:var(--bg); padding:20px; border-radius:12px;">
        <img src="<?php echo $user_photo; ?>" style="width:100px; height:100px; border-radius:50%; object-fit:cover; border:3px solid var(--primary);">
        <form action="actions/upload_photo.php" method="POST" enctype="multipart/form-data">
            <h3 style="margin:0 0 5px 0; color:var(--text);">Change Photo</h3>
            <input type="file" name="photo" accept="image/*" required onchange="this.form.submit()" style="color:var(--text);">
        </form>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:30px;">
        <div><label style="color:var(--text-secondary); font-size:0.9em;">Name</label><div style="font-weight:bold; font-size:1.1em; color:var(--text);"><?php echo htmlspecialchars($user_data['name']); ?></div></div>
        <div><label style="color:var(--text-secondary); font-size:0.9em;">Username</label><div style="font-weight:bold; font-size:1.1em; color:var(--text);">@<?php echo htmlspecialchars($user_data['Username']); ?></div></div>
    </div>

    <hr style="border:0; border-top:1px solid var(--border); margin:30px 0;">

    <h3 style="color:var(--text);">🎮 Game Settings</h3>
    <form action="actions/update_settings.php" method="POST" style="margin-bottom:30px;">
        <label style="color:var(--text);">Coins cost for 1 Cheat Day:</label>
        <div style="display:flex; gap:10px; margin-top:5px;">
            <input type="number" name="cheat_cost" value="<?php echo $cheat_cost; ?>" min="1" style="padding:10px; border-radius:6px; border:1px solid var(--border); background:var(--bg); color:var(--text);">
            <button type="submit" class="btn btn-primary" style="padding:10px 20px; border-radius:6px;">Save Cost</button>
        </div>
    </form>

    <h3 style="color:var(--text);">🔐 Security</h3>
    <form action="actions/update_password.php" method="POST" style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:30px;">
        <input type="password" name="new_password" placeholder="New Password" required style="padding:10px; border-radius:6px; border:1px solid var(--border); background:var(--bg); color:var(--text);">
        <input type="password" name="confirm_password" placeholder="Confirm Password" required style="padding:10px; border-radius:6px; border:1px solid var(--border); background:var(--bg); color:var(--text);">
        <input type="password" name="current_password" placeholder="Current Password (to verify)" required style="grid-column: span 2; padding:10px; border-radius:6px; border:1px solid var(--border); background:var(--bg); color:var(--text);">
        <button type="submit" class="btn btn-primary" style="grid-column: span 2; padding:12px; border-radius:6px;">Update Password</button>
    </form>

    <div style="border:1px solid #e74c3c; background:rgba(231, 76, 60, 0.1); padding:20px; border-radius:12px;">
        <h3 style="color:#e74c3c; margin-top:0;">Danger Zone</h3>
        <form action="actions/delete_account.php" method="POST" onsubmit="return confirm('Are you sure? This deletes EVERYTHING.');">
            <button type="submit" class="btn" style="background:#e74c3c; color:white; padding:10px 20px; border-radius:6px;">Delete Account</button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>