/**
 * router.js
 * ----------
 * Turns the multi-page app into a seamless SPA.
 * - Navbar, footer, and sky background never re-render.
 * - Only the <main> content swaps on every navigation / form submit.
 * - URL updates via history.pushState; back/forward work correctly.
 * - The existing loading overlay (centre-screen spinner) is reused.
 */
(function () {

    // Pages whose full response we should NOT intercept (external, logout
    // destroys the session so we let it do a real redirect).
    const BYPASS = ['logout.php'];

    // The single <main> wrapper that already exists in header.php.
    function getMain() { return document.querySelector('main#spa-main'); }

    function isSameOrigin(url) {
        try { return new URL(url, location.href).origin === location.origin; }
        catch (_) { return false; }
    }

    function shouldBypass(url) {
        const path = new URL(url, location.href).pathname;
        return BYPASS.some(function (b) { return path.endsWith(b); });
    }

    // ---- Spinner (reuse the existing overlay already in the DOM) ----
    function showSpinner() {
        const ov = document.getElementById('loadingOverlay');
        if (ov) { ov.classList.remove('is-hidden'); }
    }
    function hideSpinner() {
        const ov = document.getElementById('loadingOverlay');
        if (ov) { ov.classList.add('is-hidden'); }
    }

    // ---- Extract <main> innerHTML from a full HTML string ----
    function extractMain(html) {
        // Use a detached document so scripts in the fetched page don't run.
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const m = doc.querySelector('main#spa-main');
        return m ? m.innerHTML : null;
    }

    // ---- Re-run all page-level JS after a content swap ----
    function reinit() {
        // menu.js — only init menus that haven't been wired yet.
        // The header menus are already bound from the first load;
        // the guard in menu.js (data-menu-bound) prevents double-binding.
        if (window.Menu) {
            document.querySelectorAll('.menu:not([data-menu-bound])').forEach(window.Menu.init);
        }
        // script.js exposes a global init function we call after each swap.
        if (window.WD && typeof window.WD.init === 'function') {
            window.WD.init();
        }
    }

    // ---- Core swap ----
    function navigate(url, pushState) {
        if (!isSameOrigin(url) || shouldBypass(url)) {
            location.href = url;
            return;
        }

        showSpinner();

        fetch(url, { headers: { 'X-SPA': '1' }, credentials: 'same-origin' })
            .then(function (res) {
                // If PHP redirected us (e.g. require_login → login.php),
                // follow the final URL so the address bar stays correct.
                const finalUrl = res.url || url;
                return res.text().then(function (html) {
                    return { html: html, finalUrl: finalUrl };
                });
            })
            .then(function (result) {
                const content = extractMain(result.html);
                if (content === null) {
                    // Couldn't parse — fall back to a real navigation.
                    location.href = result.finalUrl;
                    return;
                }

                const main = getMain();
                if (main) main.innerHTML = content;

                if (pushState) {
                    history.pushState({ spa: true }, '', result.finalUrl);
                }

                // Scroll the content area back to the top.
                window.scrollTo(0, 0);

                hideSpinner();
                reinit();
            })
            .catch(function () {
                // Network error — fall back gracefully.
                location.href = url;
            });
    }

    // ---- Handle POST forms seamlessly ----
    function submitForm(form) {
        // form.action returns the absolute URL even for relative action attrs,
        // but falls back to location.href if action is empty.
        const url    = (form.getAttribute('action') ? form.action : location.href);
        const method = (form.method || 'GET').toUpperCase();

        if (!isSameOrigin(url)) { return false; } // let it submit normally

        showSpinner();

        // Spinner on the submit button (mirrors addSpinnerOnSubmit in script.js).
        const btn = form.querySelector('button[type="submit"], button:not([type])');
        if (btn && !btn.classList.contains('is-loading')) {
            btn.classList.add('is-loading');
            const sp = document.createElement('span');
            sp.className = 'spinner';
            btn.appendChild(sp);
            btn.disabled = true;
        }

        const body = method === 'POST' ? new FormData(form) : null;
        const fetchUrl = method === 'POST' ? url : url + '?' + new URLSearchParams(new FormData(form));

        fetch(fetchUrl, {
            method: method,
            body: body,
            headers: { 'X-SPA': '1' },
            credentials: 'same-origin',
            redirect: 'follow',
        })
            .then(function (res) {
                const finalUrl = res.url || url;
                return res.text().then(function (html) {
                    return { html: html, finalUrl: finalUrl };
                });
            })
            .then(function (result) {
                const content = extractMain(result.html);
                if (content === null) {
                    location.href = result.finalUrl;
                    return;
                }

                const main = getMain();
                if (main) main.innerHTML = content;

                history.pushState({ spa: true }, '', result.finalUrl);
                window.scrollTo(0, 0);
                hideSpinner();
                reinit();
            })
            .catch(function () {
                form.submit(); // fall back to normal submit
            });

        return true; // we handled it
    }

    // ---- Event delegation ----
    document.addEventListener('click', function (e) {
        // Find the closest <a> that was clicked.
        const a = e.target.closest('a[href]');
        if (!a) return;

        const href = a.getAttribute('href');
        // Skip: anchors-only, javascript:, external, bypass list.
        if (!href || href.startsWith('#') || href.startsWith('javascript') ||
            !isSameOrigin(href) || shouldBypass(href)) return;

        e.preventDefault();
        navigate(href, true);
    }, true);

    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form || form.tagName !== 'FORM') return;
        if (!isSameOrigin(form.action || location.href)) return;

        // Delete-confirm is handled inside script.js; if the user cancels,
        // script.js calls e.preventDefault() before we get here — so if we
        // reach this handler the user confirmed (or it's not a delete form).
        e.preventDefault();
        submitForm(form);
    }, true);

    // ---- Back / Forward ----
    window.addEventListener('popstate', function (e) {
        navigate(location.href, false);
    });

    // ---- Mark initial state so popstate works from page 1 ----
    history.replaceState({ spa: true }, '', location.href);

})();
