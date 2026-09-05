/**
 * menu.js
 * ----------
 * Behaviour for the reusable Menu component (includes/menu.php).
 * Works for any number of menus on the page (dropdown navbar, sidebar,
 * context menu, ...) since it just wires up every ".menu" it finds.
 *
 * Supports:
 *  - click-to-open (and click-away-to-close)
 *  - keyboard (Enter/Space to open, Escape to close, Arrow keys to move)
 *  - unlimited parent/child nesting (submenus open on top of each other)
 */
(function () {
    function closeMenu(toggle) {
        toggle.setAttribute('aria-expanded', 'false');
        const li = toggle.closest('.menu-item');
        if (li) li.classList.remove('open');
    }

    function closeSiblings(toggle) {
        const li = toggle.closest('.menu-item');
        if (!li) return;
        const parentUl = li.parentElement;
        if (!parentUl) return;
        parentUl.querySelectorAll(':scope > .menu-item.open').forEach(function (sibling) {
            if (sibling !== li) {
                sibling.classList.remove('open');
                const t = sibling.querySelector(':scope > .menu-toggle');
                if (t) t.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function openMenu(toggle) {
        closeSiblings(toggle);
        toggle.setAttribute('aria-expanded', 'true');
        const li = toggle.closest('.menu-item');
        if (li) li.classList.add('open');
    }

    function toggleMenu(toggle) {
        const expanded = toggle.getAttribute('aria-expanded') === 'true';
        if (expanded) {
            closeMenu(toggle);
        } else {
            openMenu(toggle);
        }
    }

    function closeAll(root) {
        root.querySelectorAll('.menu-item.open').forEach(function (li) {
            li.classList.remove('open');
        });
        root.querySelectorAll('.menu-toggle[aria-expanded="true"]').forEach(function (t) {
            t.setAttribute('aria-expanded', 'false');
        });
    }

    function initMenu(root) {
        // Guard: never wire the same root twice.
        if (root.dataset.menuBound) return;
        root.dataset.menuBound = '1';

        root.querySelectorAll(':scope .menu-toggle').forEach(function (toggle) {
            toggle.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                toggleMenu(toggle);
            });

            toggle.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggleMenu(toggle);
                } else if (e.key === 'Escape') {
                    closeMenu(toggle);
                    toggle.focus();
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    openMenu(toggle);
                    const submenu = toggle.closest('.menu-item').querySelector(':scope > .menu-submenu');
                    const firstLink = submenu && submenu.querySelector('.menu-link');
                    if (firstLink) firstLink.focus();
                }
            });
        });

        // Escape from anywhere inside the menu closes the whole thing.
        root.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeAll(root);
            }
        });
    }

    // Single document-level click listener — registered once, closes all
    // known menus when the user clicks outside any of them.
    document.addEventListener('click', function (e) {
        document.querySelectorAll('.menu[data-menu-bound]').forEach(function (root) {
            if (!root.contains(e.target)) {
                closeAll(root);
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.menu').forEach(initMenu);
    });

    // Exposed in case a page builds/rebuilds a menu dynamically
    // (e.g. after an AJAX call injects new menu markup).
    window.Menu = { init: initMenu, closeAll: closeAll };
})();
