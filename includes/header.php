<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($pageTitle) ? h($pageTitle) . ' - ' : ''; ?>Weather Dashboard</title>
<link rel="stylesheet" href="css/style.css">
<?php require_once __DIR__ . '/menu.php'; ?>
<script>
(function () {
    var theme = localStorage.getItem('wd_theme') || 'light';
    if (theme === 'dark') document.documentElement.classList.add('dark-theme');
    if (theme === 'pink') document.documentElement.classList.add('pink-theme');
})();
</script>
</head>
<body>

<!-- Centered loading overlay: shown while the dashboard's data is loading
     or an action (add/refresh/delete/etc.) is in flight. Hidden by
     js/script.js once ready. Kept outside <main> so it survives SPA swaps;
     positioned via JS relative to <main>. -->
<div id="loadingOverlay" class="loading-overlay is-hidden">
    <div class="loading-overlay-box">
        <span class="loading-spinner-large"></span>
        <span class="loading-overlay-text">Loading&hellip;</span>
    </div>
</div>

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

            <?php
            $currentPage = basename($_SERVER['SCRIPT_NAME']);

            // Reused by both the logged-in and guest menus: a "Settings"
            // parent with two nested parents underneath it (Theme, Units),
            // each with their own children — a 3-level example of the
            // Menu component's parent/child nesting.
            $settingsMenuItem = [
                'label'    => 'Settings',
                'icon'     => '⚙️',
                'children' => [
                    [
                        'label'    => 'Theme',
                        'children' => [
                            ['label' => 'Light', 'href' => '#', 'attrs' => ['data-theme-option' => 'light']],
                            ['label' => 'Dark',  'href' => '#', 'attrs' => ['data-theme-option' => 'dark']],
                            ['label' => 'Pink',  'href' => '#', 'attrs' => ['data-theme-option' => 'pink']],
                        ],
                    ],
                    [
                        'label'    => 'Units',
                        'children' => [
                            ['label' => 'Celsius (°C)',    'href' => '#', 'attrs' => ['data-unit-option' => 'C']],
                            ['label' => 'Fahrenheit (°F)', 'href' => '#', 'attrs' => ['data-unit-option' => 'F']],
                        ],
                    ],
                ],
            ];

            // "Help" parent — links out to real content on help.php
            // (FAQ / Contact / About anchors), reused by both menus.
            $helpMenuItem = [
                'label'    => 'Help',
                'icon'     => '❓',
                'children' => [
                    ['label' => 'FAQ',     'href' => 'help.php#faq',     'active' => $currentPage === 'help.php'],
                    ['label' => 'Contact', 'href' => 'help.php#contact'],
                    ['label' => 'About',   'href' => 'help.php#about'],
                ],
            ];

            // "Display" parent — Layout / Card Size / Sort, each a nested
            // parent with its own option children (another 3-level example
            // of the Menu component's nesting, mirroring Settings above).
            $displayMenuItem = [
                'label'    => 'Display',
                'icon'     => '🖥️',
                'children' => [
                    [
                        'label'    => 'Layout',
                        'children' => [
                            ['label' => 'Grid', 'href' => '#', 'attrs' => ['data-layout-option' => 'grid']],
                            ['label' => 'List', 'href' => '#', 'attrs' => ['data-layout-option' => 'list']],
                        ],
                    ],
                    [
                        'label'    => 'Card Size',
                        'children' => [
                            ['label' => 'Compact',     'href' => '#', 'attrs' => ['data-card-size-option' => 'compact']],
                            ['label' => 'Comfortable', 'href' => '#', 'attrs' => ['data-card-size-option' => 'comfortable']],
                            ['label' => 'Large',       'href' => '#', 'attrs' => ['data-card-size-option' => 'large']],
                        ],
                    ],
                    [
                        'label'    => 'Sort',
                        'children' => [
                            ['label' => 'Name (A-Z)',        'href' => '#', 'attrs' => ['data-sort-option' => 'name']],
                            ['label' => 'Pinned First',      'href' => '#', 'attrs' => ['data-sort-option' => 'pinned']],
                            ['label' => 'Recently Updated',  'href' => '#', 'attrs' => ['data-sort-option' => 'updated']],
                        ],
                    ],
                ],
            ];
            // "Notifications" parent — Weather Alerts on/off and Alert
            // Threshold, each a nested parent with option children (same
            // 3-level nesting pattern as Settings/Display above).
            $notificationsMenuItem = [
                'label'    => 'Notifications',
                'icon'     => '🔔',
                'children' => [
                    [
                        'label'    => 'Weather Alerts',
                        'children' => [
                            ['label' => 'On',  'href' => '#', 'attrs' => ['data-alerts-option' => 'on']],
                            ['label' => 'Off', 'href' => '#', 'attrs' => ['data-alerts-option' => 'off']],
                        ],
                    ],
                    [
                        'label'    => 'Alert Threshold',
                        'children' => [
                            ['label' => 'All Cities',   'href' => '#', 'attrs' => ['data-alert-threshold-option' => 'all']],
                            ['label' => 'Severe Only',  'href' => '#', 'attrs' => ['data-alert-threshold-option' => 'severe']],
                        ],
                    ],
                ],
            ];
            ?>

            <?php if (is_logged_in()): ?>
                <?php
                $mainMenuItems = [
                    [
                        'label'  => 'Dashboard',
                        'href'   => 'dashboard.php',
                        'active' => $currentPage === 'dashboard.php',
                    ],
                    $helpMenuItem,
                    $displayMenuItem,
                    [
                        'label'    => 'Hi, ' . $_SESSION['username'],
                        'icon'     => '👤',
                        'children' => [
                            [
                                'label'  => 'Profile',
                                'href'   => 'profile.php',
                                'active' => $currentPage === 'profile.php',
                            ],
                            ['label' => 'Logout', 'href' => 'logout.php'],
                        ],
                    ],
                    $settingsMenuItem,
                    $notificationsMenuItem,
                ];
                render_menu($mainMenuItems, ['id' => 'mainMenu']);
                ?>
            <?php else: ?>
                <?php
                $guestMenuItems = [
                    [
                        'label'    => 'Account',
                        'icon'     => '👋',
                        'children' => [
                            [
                                'label'  => 'Login',
                                'href'   => 'login.php',
                                'active' => $currentPage === 'login.php',
                            ],
                            [
                                'label'  => 'Register',
                                'href'   => 'register.php',
                                'active' => $currentPage === 'register.php',
                            ],
                        ],
                    ],
                    $helpMenuItem,
                    $displayMenuItem,
                    $settingsMenuItem,
                    $notificationsMenuItem,
                ];
                render_menu($guestMenuItems, ['id' => 'mainMenu']);
                ?>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="container" id="spa-main">
<?php if (!empty($_SESSION['flash'])): ?>
    <div class="flash-message"><?php echo h($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
<?php endif; ?>
