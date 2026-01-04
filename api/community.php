<?php
session_start();
if(!isset($_SESSION["loggedin"])){ header("location: index.php"); exit; }
require_once "db_connect.php";

$user_id = $_SESSION["id"];

// --- Time Ago Helper Function ---
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    if ($diff->d > 7) return $ago->format('M j'); // Show date if > 7 days
    if ($diff->d >= 1) return $diff->d . 'd';
    if ($diff->h >= 1) return $diff->h . 'h';
    if ($diff->i >= 1) return $diff->i . 'm';
    return 'now';
}

// --- Filter Logic ---
$category_filter = isset($_GET['cat']) ? $_GET['cat'] : 'all';
$sql = "SELECT p.*, u.name, u.Username, u.profile_photo,
        (SELECT COUNT(*) FROM post_likes WHERE post_id = p.post_id) as like_count,
        (SELECT COUNT(*) FROM post_likes WHERE post_id = p.post_id AND user_id = $user_id) as user_liked
        FROM community_posts p 
        JOIN users u ON p.user_id = u.id ";

if($category_filter != 'all'){
    $sql .= " WHERE p.category = '$category_filter' ";
}

$sql .= " ORDER BY p.created_at DESC";
$posts = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Community - HabitFlow</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container { max-width: 800px; margin: 0 auto; padding: 30px; }
        
        /* Feed Layout */
        .post-box { background: var(--card-bg); padding: 20px; border-radius: 16px; box-shadow: var(--shadow); margin-bottom: 20px; border: 1px solid var(--border); }
        
        /* Create Post */
        .create-post textarea { width: 100%; border: none; background: var(--bg); padding: 15px; border-radius: 12px; resize: none; font-family: inherit; outline: none; }
        .cp-actions { display: flex; justify-content: space-between; margin-top: 10px; align-items: center; }
        
        /* Post Item */
        .post-header { display: flex; gap: 12px; margin-bottom: 10px; }
        .p-avatar { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; }
        .p-info h4 { margin: 0; font-size: 1rem; color: var(--text); }
        .p-info span { color: var(--text-secondary); font-size: 0.85em; }
        
        .post-content { margin-bottom: 15px; line-height: 1.5; color: var(--text); font-size: 1.05em; }
        
        /* Tags */
        .tag { padding: 3px 10px; border-radius: 20px; font-size: 0.75em; font-weight: bold; text-transform: uppercase; }
        .tag-success { background: #e8f5e9; color: #2ecc71; }
        .tag-advice { background: #fff3e0; color: #f39c12; }
        .tag-general { background: #f4f6f7; color: #7f8c8d; }

        /* Actions */
        .p-actions { display: flex; gap: 20px; border-top: 1px solid var(--border); padding-top: 10px; }
        .action-btn { background: none; border: none; cursor: pointer; display: flex; align-items: center; gap: 5px; color: var(--text-secondary); font-weight: 600; transition: 0.2s; }
        .action-btn:hover { color: var(--primary); }
        .liked { color: #e74c3c !important; }

        /* Comments */
        .comments-section { background: var(--bg); margin-top: 10px; padding: 10px; border-radius: 8px; display: none; }
        .comment { font-size: 0.9em; padding: 8px 0; border-bottom: 1px solid var(--border); }
        .comment strong { color: var(--text); }
        
        /* Filters */
        .filters { display: flex; gap: 10px; margin-bottom: 20px; overflow-x: auto; padding-bottom: 5px; }
        .filter-btn { padding: 8px 16px; border-radius: 20px; text-decoration: none; color: var(--text-secondary); background: var(--card-bg); border: 1px solid var(--border); font-size: 0.9em; font-weight: 600; }
        .filter-btn.active { background: var(--primary); color: white; border-color: var(--primary); }
    </style>
</head>
<body>

    <div class="app-logo" style="padding: 20px 30px;">
        <div class="logo-icon">⚡</div> <a href="dashboard.php" style="text-decoration:none; color:var(--text);">HabitFlow</a>
    </div>

    <div class="container">
        
        <div class="post-box create-post">
            <form action="actions/add_post.php" method="POST">
                <textarea name="content" rows="3" placeholder="Share your journey, ask for advice, or celebrate a win..." required></textarea>
                <div class="cp-actions">
                    <select name="category" style="width: auto; padding: 8px; border-radius: 8px; background: var(--bg); color: var(--text); border: 1px solid var(--border);">
                        <option value="general">💭 General Thought</option>
                        <option value="success">🏆 Success Story</option>
                        <option value="advice">🆘 Need Advice</option>
                    </select>
                    <button type="submit" class="btn btn-primary" style="border-radius: 20px; padding: 8px 20px;">Post</button>
                </div>
            </form>
        </div>

        <div class="filters">
            <a href="community.php?cat=all" class="filter-btn <?php echo $category_filter=='all'?'active':''; ?>">All Posts</a>
            <a href="community.php?cat=success" class="filter-btn <?php echo $category_filter=='success'?'active':''; ?>">🏆 Success Stories</a>
            <a href="community.php?cat=advice" class="filter-btn <?php echo $category_filter=='advice'?'active':''; ?>">🆘 Advice Needed</a>
            <a href="community.php?cat=general" class="filter-btn <?php echo $category_filter=='general'?'active':''; ?>">💭 General</a>
        </div>

        <?php if($posts->num_rows > 0): ?>
            <?php while($post = $posts->fetch_assoc()): ?>
                <?php 
                    $p_photo = !empty($post['profile_photo']) ? "uploads/".$post['profile_photo'] : "https://via.placeholder.com/150";
                    // Logic for badges
                    $badge_class = 'tag-general';
                    $badge_text = 'General';
                    if($post['category'] == 'success') { $badge_class = 'tag-success'; $badge_text = '🏆 Success Story'; }
                    if($post['category'] == 'advice') { $badge_class = 'tag-advice'; $badge_text = '🆘 Advice Needed'; }
                ?>
                
                <div class="post-box">
                    <div class="post-header">
                        <img src="<?php echo $p_photo; ?>" class="p-avatar">
                        <div class="p-info">
                            <h4><?php echo htmlspecialchars($post['name']); ?> <span style="color:var(--text-secondary); font-weight:normal;">@<?php echo $post['Username']; ?></span></h4>
                            <span><?php echo time_elapsed_string($post['created_at']); ?> • <span class="tag <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span></span>
                        </div>
                    </div>

                    <div class="post-content">
                        <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                    </div>

                    <div class="p-actions">
                        <a href="actions/like_post.php?id=<?php echo $post['post_id']; ?>" class="action-btn <?php echo $post['user_liked'] ? 'liked' : ''; ?>">
                            <?php echo $post['user_liked'] ? '❤️' : '🤍'; ?> <?php echo $post['like_count']; ?>
                        </a>
                        <button class="action-btn" onclick="toggleComments(<?php echo $post['post_id']; ?>)">
                            💬 Comment
                        </button>
                    </div>

                    <div id="comments-<?php echo $post['post_id']; ?>" class="comments-section">
                        <?php 
                            $pid = $post['post_id'];
                            $c_sql = "SELECT c.*, u.name FROM community_comments c JOIN users u ON c.user_id = u.id WHERE c.post_id = $pid ORDER BY c.created_at ASC";
                            $comments = $conn->query($c_sql);
                        ?>
                        
                        <?php if($comments->num_rows > 0): ?>
                            <?php while($c = $comments->fetch_assoc()): ?>
                                <div class="comment">
                                    <strong><?php echo htmlspecialchars($c['name']); ?>:</strong> 
                                    <span style="color:var(--text-secondary);"><?php echo htmlspecialchars($c['comment_text']); ?></span>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="font-size:0.8em; color:var(--text-secondary);">No comments yet.</p>
                        <?php endif; ?>

                        <form action="actions/add_comment.php" method="POST" style="display:flex; gap:10px; margin-top:10px;">
                            <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                            <input type="text" name="comment" placeholder="Write a reply..." required style="padding:8px; border-radius:20px;">
                            <button type="submit" class="btn btn-primary" style="border-radius:20px; padding:5px 15px; font-size:0.8em;">Send</button>
                        </form>
                    </div>
                </div>

            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align:center; padding:40px; color:var(--text-secondary);">
                <h3>No posts here yet.</h3>
                <p>Be the first to share something!</p>
            </div>
        <?php endif; ?>

    </div>

    <script>
        if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-mode');
        
        function toggleComments(id) {
            const section = document.getElementById('comments-' + id);
            section.style.display = section.style.display === 'block' ? 'none' : 'block';
        }
    </script>
</body>
</html>