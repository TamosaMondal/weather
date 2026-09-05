<?php
/**
 * menu.php
 * --------------
 * Reusable, data-driven Menu component.
 *
 * Renders a nested <ul>/<li> menu (dropdowns, sidebars, context menus...)
 * from a plain PHP array of items. Each item may have a "children" array,
 * so parent/child nesting can go as deep as you like.
 *
 * USAGE
 * -----
 *   $items = [
 *       ['label' => 'Dashboard', 'href' => 'dashboard.php', 'icon' => '📊'],
 *       [
 *           'label'    => 'Account',
 *           'icon'     => '👤',
 *           'children' => [
 *               ['label' => 'Profile', 'href' => 'profile.php'],
 *               ['label' => 'Logout',  'href' => 'logout.php'],
 *           ],
 *       ],
 *   ];
 *   render_menu($items, ['id' => 'mainMenu', 'class' => 'menu--horizontal']);
 *
 * ITEM KEYS
 * ---------
 *   label     (string, required) Visible text.
 *   href      (string, optional) Link target. Omit (or use '#') for a
 *             parent item that only opens a submenu.
 *   icon      (string, optional) Emoji/text shown before the label.
 *   active    (bool, optional)   Adds an "is-active" class.
 *   children  (array, optional)  Nested items -> renders a submenu.
 *   attrs     (array, optional)  Extra raw attributes for the <a>/<span>,
 *             e.g. ['data-confirm' => 'Are you sure?'].
 *
 * OPTIONS (2nd argument)
 * -----------------------
 *   id     (string) id for the root <ul>. Needed if you have more than
 *          one menu on the page and want the JS to target it specifically.
 *   class  (string) extra class(es) on the root <ul>, e.g. 'menu--sidebar'.
 *
 * The component only renders markup. All open/close/hover/keyboard
 * behaviour lives in js/menu.js (Menu.init), and all visual variants
 * (dropdown, sidebar, context menu) live in css/menu.css. This keeps the
 * PHP side reusable for any menu, anywhere in the app.
 */

if (!function_exists('render_menu_items')) {
    function render_menu_items(array $items, bool $isRoot = true): string {
        $html = '';
        foreach ($items as $item) {
            $label    = h($item['label'] ?? '');
            $href     = $item['href'] ?? null;
            $icon     = isset($item['icon']) ? h($item['icon']) . ' ' : '';
            $hasKids  = !empty($item['children']);
            $liClass  = trim(($item['active'] ?? false) ? 'is-active' : '') . ($hasKids ? ' has-children' : '');

            $attrs = '';
            if (!empty($item['attrs']) && is_array($item['attrs'])) {
                foreach ($item['attrs'] as $k => $v) {
                    $attrs .= ' ' . h($k) . '="' . h($v) . '"';
                }
            }

            $html .= '<li class="menu-item' . ($liClass ? ' ' . h(trim($liClass)) : '') . '">';

            if ($hasKids) {
                // Parent items are a button-like trigger, not a real link,
                // unless an href was explicitly given too.
                $tag = $href ? 'a' : 'button';
                $hrefAttr = $href ? ' href="' . h($href) . '"' : ' type="button"';
                $html .= '<' . $tag . ' class="menu-link menu-toggle"' . $hrefAttr
                    . ' aria-haspopup="true" aria-expanded="false"' . $attrs . '>'
                    . $icon . '<span class="menu-label">' . $label . '</span>'
                    . '<span class="menu-caret" aria-hidden="true">&rsaquo;</span>'
                    . '</' . $tag . '>';
                $html .= '<ul class="menu-submenu">' . render_menu_items($item['children'], false) . '</ul>';
            } else {
                $html .= '<a class="menu-link" href="' . h($href ?: '#') . '"' . $attrs . '>'
                    . $icon . '<span class="menu-label">' . $label . '</span>'
                    . '</a>';
            }

            $html .= '</li>';
        }
        return $html;
    }
}

if (!function_exists('render_menu')) {
    function render_menu(array $items, array $options = []): void {
        $id    = isset($options['id']) ? ' id="' . h($options['id']) . '"' : '';
        $class = 'menu' . (!empty($options['class']) ? ' ' . h($options['class']) : '');
        echo '<ul' . $id . ' class="' . $class . '" role="menubar">'
            . render_menu_items($items)
            . '</ul>';
    }
}
