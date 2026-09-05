<?php
require_once 'includes/bootstrap.php';
require_once 'includes/faq.php';

// FAQ is grouped into SECTIONS. Each section has an id, a nav_label/title,
// and an 'items' array of child questions in the same shape as before
// (id, question, answer, optional nav_label, optional open). To add a new
// question, push another item into the right section's 'items' array; to
// add a whole new section, copy one of the blocks below. Everything below
// updates the left nav, the section accordion, and the question accordion
// automatically. The answer field accepts trusted HTML.
$faqItems = [
    [
        'id' => 'faq-section-app',
        'nav_label' => 'Using the Application',
        'title' => 'Using the Application',
        'items' => [
            [
                'id' => 'faq-add-city',
                'question' => 'How do I add a city to my dashboard?',
                'answer' => '<p>Go to the Dashboard, type a city name into the search box, and pick a match from the dropdown. It\'s saved automatically along with the current weather and air quality.</p>',
            ],
            [
                'id' => 'faq-rename',
                'question' => 'How do I rename or remove a saved city?',
                'answer' => '<p>Each city card has <strong>Rename</strong>, <strong>Pin</strong>, <strong>Refresh</strong>, and <strong>Delete</strong> buttons. Renaming only changes the display alias &mdash; the underlying city data stays the same.</p>',
            ],
            [
                'id' => 'faq-pin',
                'question' => 'What does pinning a city do?',
                'answer' => '<p>Pinned cities always appear first in the grid and get a &#128204; badge. Use it to keep your most important locations at the top regardless of sort order.</p>',
            ],
            [
                'id' => 'faq-refresh',
                'question' => 'How often does the data refresh?',
                'answer' => '<p>Each card refreshes automatically in the background, or you can tap the <strong>Refresh</strong> button on any city card to pull the latest reading right away.</p>',
            ],
            [
                'id' => 'faq-account',
                'question' => 'Do I need an account to use the dashboard?',
                'answer' => '<p>Yes &mdash; creating a free account lets your saved cities, aliases, and preferences follow you across devices. There are no usage limits.</p>',
            ],
        ],
    ],
    [
        'id' => 'faq-section-weather',
        'nav_label' => 'Weather & Air Quality',
        'title' => 'Weather & Air Quality',
        'items' => [
            [
                'id' => 'faq-aqi',
                'question' => 'What do the AQI colors mean?',
                'answer' => '<p>The badge shows the Air Quality Index category for that city, from <strong>Good</strong> (green) to <strong>Hazardous</strong> (dark red), based on the latest reading from Open-Meteo.</p>',
            ],
            [
                'id' => 'faq-units',
                'question' => 'Can I change the temperature unit?',
                'answer' => '<p>Yes &mdash; open <strong>Settings &rsaquo; Units</strong> in the navbar and choose Celsius or Fahrenheit. Your choice is remembered on this device.</p>',
            ],
            [
                'id' => 'faq-alerts',
                'question' => 'How do weather alerts work?',
                'answer' => '<p>When <strong>Notifications &rsaquo; Weather Alerts</strong> is On, an amber banner appears above your cities if any of them have extreme temperatures or poor air quality. You can set the threshold to <em>All Cities</em> or <em>Severe Only</em>.</p>',
            ],
            [
                'id' => 'faq-source',
                'question' => 'Where does the weather data come from?',
                'answer' => '<p>All weather and air quality readings are pulled live from <a href="https://open-meteo.com" target="_blank" rel="noopener">Open-Meteo</a>, a free open-data weather API.</p>',
            ],
            [
                'id' => 'faq-forecast',
                'question' => 'Can I see a forecast, or only current conditions?',
                'answer' => '<p>Each city card shows current conditions plus a short-range forecast &mdash; open a city to see the hour-by-hour and day-by-day breakdown.</p>',
            ],
        ],
    ],
    [
        'id' => 'faq-section-display',
        'nav_label' => 'Display & Preferences',
        'title' => 'Display & Preferences',
        'items' => [
            [
                'id' => 'faq-display',
                'question' => 'How do I change how the dashboard looks?',
                'answer' => '<p>Open <strong>Display</strong> in the navbar to switch between Grid/List layout, Card Size (Compact / Comfortable / Large), and how your cities are Sorted.</p>',
            ],
            [
                'id' => 'faq-themes',
                'question' => 'How do I switch themes?',
                'answer' => '<p>Open <strong>Settings &rsaquo; Theme</strong> in the navbar and choose Light, Dark, or Pink. The choice is saved in your browser and applied instantly on every page.</p>',
            ],
            [
                'id' => 'faq-sort',
                'question' => 'What sort options are available?',
                'answer' => '<p>You can sort your cities by <strong>Name</strong>, <strong>Temperature</strong>, or <strong>Air Quality</strong>, in addition to keeping pinned cities fixed at the top.</p>',
            ],
            [
                'id' => 'faq-mobile',
                'question' => 'Does the dashboard work well on mobile?',
                'answer' => '<p>Yes &mdash; the layout adapts to smaller screens automatically, and the List layout is often the most compact choice on a phone.</p>',
            ],
        ],
    ],
];

$pageTitle = 'Help & Support';
require_once 'includes/header.php';
?>

<section class="help-section">

    <!-- ===== FAQ ===== -->
    <h2 id="faq">FAQ</h2>
    <?php render_faq($faqItems); ?>

    <!-- ===== Contact ===== -->
    <h2 id="contact">Contact</h2>
    <div class="help-card">
        <p>Have a question, bug report, or feature idea? We'd love to hear from you.</p>
        <ul class="contact-list">
            <li>&#128231; <a href="mailto:support@weatherbookmark.app">support@weatherbookmark.app</a></li>
            <li>&#128027; <a href="https://github.com" target="_blank" rel="noopener">Report an issue</a></li>
        </ul>
    </div>

    <!-- ===== About ===== -->
    <h2 id="about">About</h2>
    <div class="help-card">
        <p>Weather Bookmark Dashboard is a lightweight way to keep tabs on the weather and air quality in the places you care about &mdash; no account limits, no ads, no API key required to run it yourself.</p>
        <p>Weather and air quality data come from <a href="https://open-meteo.com" target="_blank" rel="noopener">Open-Meteo</a>, and place lookups use <a href="https://www.wikipedia.org" target="_blank" rel="noopener">Wikipedia</a>.</p>
    </div>

</section>

<?php require_once 'includes/footer.php'; ?>
