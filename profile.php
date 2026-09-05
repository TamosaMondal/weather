<?php
/**
 * profile.php
 * ------------
 * Simple account/profile page: shows the username and the date
 * the account was created, plus a form to change the password.
 */
require_once 'includes/bootstrap.php';
require_login();

$db = getDB();
$stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$pageTitle = 'Profile';
require_once 'includes/header.php';
?>

<section class="profile-section">
    <h2>Your Profile</h2>

    <div class="profile-card">
        <div class="profile-row">
            <span class="profile-label">Username</span>
            <span class="profile-value"><?php echo h($user['username']); ?></span>
        </div>
        <div class="profile-row">
            <span class="profile-label">Member since</span>
            <span class="profile-value"><?php echo h(date('F j, Y', strtotime($user['created_at']))); ?></span>
        </div>
    </div>

    <h2>Change Password</h2>
    <form method="POST" action="actions/change_password.php" class="auth-form profile-card">
        <label for="current_password">Current Password</label>
        <input type="password" id="current_password" name="current_password" required>

        <label for="new_password">New Password</label>
        <input type="password" id="new_password" name="new_password" required minlength="4">

        <label for="confirm_password">Confirm New Password</label>
        <input type="password" id="confirm_password" name="confirm_password" required minlength="4">

        <button type="submit" class="btn btn-primary">Update Password</button>
    </form>
</section>

<?php require_once 'includes/footer.php'; ?>
