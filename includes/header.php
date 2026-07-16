<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($pageTitle) ? h($pageTitle) . ' - ' : ''; ?>Weather Dashboard</title>
<link rel="stylesheet" href="css/style.css">
<script>
(function () {
    var theme = localStorage.getItem('wd_theme') || 'light';
    if (theme === 'dark') document.documentElement.classList.add('dark-theme');
})();
</script>
</head>
<body>

<div class="sky-bg" aria-hidden="true">
    <div class="sun-glow"></div>
    <div class="cloud cloud-a"></div>
    <div class="cloud cloud-b"></div>
    <div class="cloud cloud-c"></div>
    <div class="cloud cloud-d"></div>
    <div class="stars"></div>
</div>

<header class="site-header">
    <div class="container header-inner">
        <h1 class="logo">🌤️ Weather Bookmark Dashboard</h1>
        <nav class="nav">
            <span class="current-date" id="currentDate"></span>
            <?php if (is_logged_in()): ?>
                <a href="dashboard.php" class="btn btn-small btn-outline">Dashboard</a>
                <a href="profile.php" class="btn btn-small btn-outline">Profile</a>
                <span class="hello">Hi, <?php echo h($_SESSION['username']); ?></span>
            <?php endif; ?>

            <button type="button" id="unitToggle" class="btn btn-small btn-outline unit-toggle" title="Switch temperature unit">&deg;C / &deg;F</button>
            <button type="button" id="themeToggle" class="btn btn-small btn-outline theme-toggle" title="Toggle dark mode">🌙</button>

            <?php if (is_logged_in()): ?>
                <a href="logout.php" class="btn btn-small btn-outline">Logout</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="container">
<?php if (!empty($_SESSION['flash'])): ?>
    <div class="flash-message"><?php echo h($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
<?php endif; ?>
