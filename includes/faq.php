<?php
/**
 * Reusable, data-driven FAQ components — two levels.
 *
 * Top level: SECTIONS. Each section is:
 *   [ 'id' => ..., 'nav_label' => ..., 'title' => ..., 'items' => [ ...child items... ] ]
 *
 * Child level: QUESTIONS (unchanged shape from before), each item inside a
 * section's 'items' array:
 *   [ 'id' => ..., 'question' => ..., 'answer' => ..., optional 'nav_label', optional 'open' ]
 *
 * The answer value is trusted HTML so it can contain links and emphasis.
 *
 * Clicking a section expands/collapses it (revealing its questions).
 * Clicking a question inside an open section expands/collapses that answer.
 */

if (!function_exists('render_faq_item')) {
    function render_faq_item(array $item, bool $open = false): void {
        $id = h($item['id'] ?? '');
        $question = h($item['question'] ?? '');
        $answerId = $id . '-answer';
        ?>
        <div class="faq-item<?php echo $open ? ' faq-open' : ''; ?>" id="<?php echo $id; ?>">
            <button
                class="faq-question"
                type="button"
                aria-expanded="<?php echo $open ? 'true' : 'false'; ?>"
                aria-controls="<?php echo $answerId; ?>"
            >
                <?php echo $question; ?>
                <span class="faq-caret" aria-hidden="true"></span>
            </button>
            <div class="faq-answer" id="<?php echo $answerId; ?>">
                <?php echo $item['answer'] ?? ''; ?>
            </div>
        </div>
        <?php
    }
}

if (!function_exists('render_faq_section')) {
    function render_faq_section(array $section, bool $open = false): void {
        $id = h($section['id'] ?? '');
        $title = h($section['title'] ?? $section['nav_label'] ?? '');
        $bodyId = $id . '-body';
        $items = $section['items'] ?? [];
        ?>
        <div class="faq-section<?php echo $open ? ' faq-section-open' : ''; ?>" id="<?php echo $id; ?>">
            <button
                class="faq-section-header"
                type="button"
                aria-expanded="<?php echo $open ? 'true' : 'false'; ?>"
                aria-controls="<?php echo $bodyId; ?>"
            >
                <span class="faq-section-title"><?php echo $title; ?></span>
                <span class="faq-caret" aria-hidden="true"></span>
            </button>
            <div class="faq-section-body" id="<?php echo $bodyId; ?>">
                <?php foreach ($items as $index => $item): ?>
                    <?php render_faq_item($item, array_key_exists('open', $item) ? (bool) $item['open'] : $index === 0); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}

if (!function_exists('render_faq')) {
    function render_faq(array $sections): void {
        if (!$sections) {
            return;
        }
        ?>
        <div class="faq-layout">
            <nav class="faq-nav" aria-label="FAQ navigation">
                <ul>
                    <?php foreach ($sections as $section): ?>
                        <li>
                            <a class="faq-nav-link" href="#<?php echo h($section['id'] ?? ''); ?>">
                                <?php echo h($section['nav_label'] ?? $section['title'] ?? ''); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <div class="faq-list">
                <?php foreach ($sections as $index => $section): ?>
                    <?php render_faq_section($section, array_key_exists('open', $section) ? (bool) $section['open'] : $index === 0); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}
