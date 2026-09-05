<?php
/**
 * login.php
 * ----------
 * Lets an existing user log in. Verifies the password against
 * the hash stored in the database and starts a session.
 */
require_once 'includes/bootstrap.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        redirect('dashboard.php');
    } else {
        $error = 'Invalid username or password.';
    }
}

$pageTitle = 'Login';
require_once 'includes/header.php';
?>

<div class="auth-box">
    <h2>Login</h2>

    <?php if ($error): ?>
        <div class="error-message"><?php echo h($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php" class="auth-form">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required value="<?php echo h($username ?? ''); ?>">

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <button type="submit" class="btn btn-primary">Login</button>
    </form>

    <p class="auth-switch">Don't have an account? <a href="register.php">Register here</a></p>
</div>

<?php require_once 'includes/footer.php'; ?>
