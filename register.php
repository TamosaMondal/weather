<?php
/**
 * register.php
 * -------------
 * Lets a new visitor create an account (username + password).
 * Passwords are hashed with password_hash() before being stored.
 */
require_once 'includes/bootstrap.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } elseif (strlen($password) < 4) {
        $error = 'Password must be at least 4 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $db = getDB();
        $stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);

        if ($stmt->fetch()) {
            $error = 'That username is already taken.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
            $stmt->execute([$username, $hash]);

            $_SESSION['user_id']  = $db->lastInsertId();
            $_SESSION['username'] = $username;
            redirect('dashboard.php');
        }
    }
}

$pageTitle = 'Register';
require_once 'includes/header.php';
?>

<div class="auth-box">
    <h2>Create an Account</h2>

    <?php if ($error): ?>
        <div class="error-message"><?php echo h($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="register.php" class="auth-form">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required value="<?php echo h($username ?? ''); ?>">

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <label for="confirm_password">Confirm Password</label>
        <input type="password" id="confirm_password" name="confirm_password" required>

        <button type="submit" class="btn btn-primary">Register</button>
    </form>

    <p class="auth-switch">Already have an account? <a href="login.php">Login here</a></p>
</div>

<?php require_once 'includes/footer.php'; ?>
