/**
 * script.js
 * ----------
 * All UX logic is wrapped in WD.init() so router.js can re-run it
 * after every SPA content swap without a full page reload.
 */

window.WD = (function () {

    function init() {

        // ---- Current date in navbar ----
        const dateEl = document.getElementById('currentDate');
        if (dateEl) {
            dateEl.textContent = new Date().toLocaleDateString(undefined, {
                weekday: 'short', year: 'numeric', month: 'short', day: 'numeric'
            });
        }

        // ---- Theme selection ----
        const THEME_CLASSES = ['dark-theme', 'pink-theme'];
        function applyTheme(theme) {
            document.documentElement.classList.remove(...THEME_CLASSES);
            if (theme === 'dark') document.documentElement.classList.add('dark-theme');
            if (theme === 'pink') document.documentElement.classList.add('pink-theme');
            localStorage.setItem('wd_theme', theme);
            document.querySelectorAll('[data-theme-option]').forEach(function (link) {
                link.classList.toggle('is-selected', link.getAttribute('data-theme-option') === theme);
            });
        }
        applyTheme(localStorage.getItem('wd_theme') || 'light');

        document.querySelectorAll('[data-theme-option]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                applyTheme(link.getAttribute('data-theme-option'));
                if (window.Menu) window.Menu.closeAll(document.getElementById('mainMenu'));
            });
        });

        // ---- Temperature unit ----
        function celsiusToFahrenheit(c) { return (c * 9 / 5) + 32; }
        function applyTempUnit(unit) {
            document.querySelectorAll('.temp[data-temp-c]').forEach(function (el) {
                const c = parseFloat(el.getAttribute('data-temp-c'));
                if (isNaN(c)) return;
                const value = unit === 'F' ? celsiusToFahrenheit(c) : c;
                el.textContent = Math.round(value * 10) / 10 + '\u00B0' + unit;
            });
            localStorage.setItem('wd_unit', unit);
            document.querySelectorAll('[data-unit-option]').forEach(function (link) {
                link.classList.toggle('is-selected', link.getAttribute('data-unit-option') === unit);
            });
        }
        applyTempUnit(localStorage.getItem('wd_unit') || 'C');

        document.querySelectorAll('[data-unit-option]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                applyTempUnit(link.getAttribute('data-unit-option'));
                if (window.Menu) window.Menu.closeAll(document.getElementById('mainMenu'));
            });
        });

        // ---- Display: Layout ----
        const cityGrid = document.getElementById('cityGrid');

        function applyLayout(layout) {
            if (cityGrid) cityGrid.classList.toggle('layout-list', layout === 'list');
            localStorage.setItem('wd_layout', layout);
            document.querySelectorAll('[data-layout-option]').forEach(function (link) {
                link.classList.toggle('is-selected', link.getAttribute('data-layout-option') === layout);
            });
        }
        applyLayout(localStorage.getItem('wd_layout') || 'grid');

        document.querySelectorAll('[data-layout-option]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                applyLayout(link.getAttribute('data-layout-option'));
                if (window.Menu) window.Menu.closeAll(document.getElementById('mainMenu'));
            });
        });

        // ---- Display: Card Size ----
        const CARD_SIZE_CLASSES = ['card-size-compact', 'card-size-comfortable', 'card-size-large'];
        function applyCardSize(size) {
            if (cityGrid) {
                cityGrid.classList.remove(...CARD_SIZE_CLASSES);
                cityGrid.classList.add('card-size-' + size);
            }
            localStorage.setItem('wd_card_size', size);
            document.querySelectorAll('[data-card-size-option]').forEach(function (link) {
                link.classList.toggle('is-selected', link.getAttribute('data-card-size-option') === size);
            });
        }
        applyCardSize(localStorage.getItem('wd_card_size') || 'comfortable');

        document.querySelectorAll('[data-card-size-option]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                applyCardSize(link.getAttribute('data-card-size-option'));
                if (window.Menu) window.Menu.closeAll(document.getElementById('mainMenu'));
            });
        });

        // ---- Display: Sort ----
        function applySort(sortBy) {
            if (cityGrid) {
                const cards = Array.from(cityGrid.querySelectorAll('.city-card'));
                cards.sort(function (a, b) {
                    if (sortBy === 'name') return (a.dataset.name || '').localeCompare(b.dataset.name || '');
                    if (sortBy === 'pinned') return (b.dataset.pinned === '1' ? 1 : 0) - (a.dataset.pinned === '1' ? 1 : 0);
                    if (sortBy === 'updated') {
                        const da = new Date((a.dataset.updated || '').replace(' ', 'T') + 'Z');
                        const db = new Date((b.dataset.updated || '').replace(' ', 'T') + 'Z');
                        return db - da;
                    }
                    return 0;
                });
                cards.forEach(function (card) { cityGrid.appendChild(card); });
            }
            localStorage.setItem('wd_sort', sortBy);
            document.querySelectorAll('[data-sort-option]').forEach(function (link) {
                link.classList.toggle('is-selected', link.getAttribute('data-sort-option') === sortBy);
            });
        }
        applySort(localStorage.getItem('wd_sort') || 'pinned');

        document.querySelectorAll('[data-sort-option]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                applySort(link.getAttribute('data-sort-option'));
                if (window.Menu) window.Menu.closeAll(document.getElementById('mainMenu'));
            });
        });

        // ---- Notifications: Weather Alerts ----
        const ALERT_HOT_LIMIT  = { all: 35, severe: 40 };
        const ALERT_COLD_LIMIT = { all: 5,  severe: 0  };
        const ALERT_AQI_LEVELS = {
            all:    ['Unhealthy for Sensitive Groups', 'Unhealthy', 'Very Unhealthy', 'Hazardous'],
            severe: ['Very Unhealthy', 'Hazardous']
        };
        const weatherAlertsBanner = document.getElementById('weatherAlertsBanner');

        function computeAndRenderAlerts() {
            if (!weatherAlertsBanner || !cityGrid) return;
            const enabled = (localStorage.getItem('wd_alerts_enabled') || 'on') === 'on';
            if (!enabled) {
                weatherAlertsBanner.classList.add('hidden');
                weatherAlertsBanner.innerHTML = '';
                return;
            }
            const threshold = localStorage.getItem('wd_alert_threshold') || 'all';
            const hotLimit  = ALERT_HOT_LIMIT[threshold];
            const coldLimit = ALERT_COLD_LIMIT[threshold];
            const aqiLevels = ALERT_AQI_LEVELS[threshold];
            const alerts = [];

            cityGrid.querySelectorAll('.city-card').forEach(function (card) {
                const tempEl = card.querySelector('.temp[data-temp-c]');
                const tempC  = tempEl ? parseFloat(tempEl.getAttribute('data-temp-c')) : NaN;
                const nameEl = card.querySelector('.alias-display');
                const name   = nameEl ? nameEl.textContent : (card.dataset.name || 'A city');
                const aqiCategory = card.getAttribute('data-aqi-category');
                const reasons = [];
                if (!isNaN(tempC)) {
                    if (tempC >= hotLimit)  reasons.push(Math.round(tempC) + '\u00B0C heat');
                    if (tempC <= coldLimit) reasons.push(Math.round(tempC) + '\u00B0C cold');
                }
                if (aqiCategory && aqiLevels.indexOf(aqiCategory) !== -1) {
                    reasons.push(aqiCategory + ' air quality');
                }
                if (reasons.length) alerts.push(name + ': ' + reasons.join(', '));
            });

            if (alerts.length) {
                weatherAlertsBanner.innerHTML = '\u26A0\uFE0F <strong>Weather Alerts:</strong> ' + alerts.join(' &middot; ');
                weatherAlertsBanner.classList.remove('hidden');
            } else {
                weatherAlertsBanner.innerHTML = '';
                weatherAlertsBanner.classList.add('hidden');
            }
        }

        function applyAlertsEnabled(state) {
            localStorage.setItem('wd_alerts_enabled', state);
            document.querySelectorAll('[data-alerts-option]').forEach(function (link) {
                link.classList.toggle('is-selected', link.getAttribute('data-alerts-option') === state);
            });
            computeAndRenderAlerts();
        }
        applyAlertsEnabled(localStorage.getItem('wd_alerts_enabled') || 'on');

        document.querySelectorAll('[data-alerts-option]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                applyAlertsEnabled(link.getAttribute('data-alerts-option'));
                if (window.Menu) window.Menu.closeAll(document.getElementById('mainMenu'));
            });
        });

        function applyAlertThreshold(threshold) {
            localStorage.setItem('wd_alert_threshold', threshold);
            document.querySelectorAll('[data-alert-threshold-option]').forEach(function (link) {
                link.classList.toggle('is-selected', link.getAttribute('data-alert-threshold-option') === threshold);
            });
            computeAndRenderAlerts();
        }
        applyAlertThreshold(localStorage.getItem('wd_alert_threshold') || 'all');

        document.querySelectorAll('[data-alert-threshold-option]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                applyAlertThreshold(link.getAttribute('data-alert-threshold-option'));
                if (window.Menu) window.Menu.closeAll(document.getElementById('mainMenu'));
            });
        });

        // ---- Relative "Updated" time ----
        function relativeTime(dateStr) {
            const then = new Date(dateStr.replace(' ', 'T') + 'Z');
            if (isNaN(then.getTime())) return dateStr;
            const seconds = Math.floor((Date.now() - then.getTime()) / 1000);
            if (seconds < 60) return 'Updated just now';
            const minutes = Math.floor(seconds / 60);
            if (minutes < 60) return 'Updated ' + minutes + ' minute' + (minutes === 1 ? '' : 's') + ' ago';
            const hours = Math.floor(minutes / 60);
            if (hours < 24) return 'Updated ' + hours + ' hour' + (hours === 1 ? '' : 's') + ' ago';
            const days = Math.floor(hours / 24);
            return 'Updated ' + days + ' day' + (days === 1 ? '' : 's') + ' ago';
        }
        function refreshRelativeTimes() {
            document.querySelectorAll('.updated[data-updated]').forEach(function (el) {
                el.textContent = relativeTime(el.getAttribute('data-updated'));
            });
        }
        refreshRelativeTimes();
        // Only register one interval — clear any previous one.
        if (window._wd_time_interval) clearInterval(window._wd_time_interval);
        window._wd_time_interval = setInterval(refreshRelativeTimes, 60000);

        // ---- Loading overlay ----
        const loadingOverlay = document.getElementById('loadingOverlay');
        const skeletonGrid   = document.querySelector('.skeleton-grid');
        const realGrid       = document.querySelector('.city-grid.content-loading');

        function hideLoadingOverlay() {
            if (realGrid)       realGrid.classList.remove('content-loading');
            if (skeletonGrid)   skeletonGrid.remove();
            if (loadingOverlay) loadingOverlay.classList.add('is-hidden');
        }
        // Reveal the real grid (skeleton → real) after the swap settles.
        setTimeout(hideLoadingOverlay, 350);

        function showLoadingOverlay() {
            if (loadingOverlay) loadingOverlay.classList.remove('is-hidden');
        }

        // ---- Spinner on submit buttons ----
        // NOTE: router.js intercepts the actual submit; this just adds the
        // visual spinner + overlay so the button gives instant feedback.
        function addSpinnerOnSubmit(form) {
            // Avoid double-binding across reinit calls.
            if (form.dataset.spinnerBound) return;
            form.dataset.spinnerBound = '1';
            form.addEventListener('submit', function () {
                const btn = form.querySelector('button[type="submit"], button:not([type])');
                if (btn && !btn.classList.contains('is-loading')) {
                    btn.classList.add('is-loading');
                    const spinner = document.createElement('span');
                    spinner.className = 'spinner';
                    btn.appendChild(spinner);
                    btn.disabled = true;
                }
                showLoadingOverlay();
            });
        }
        document.querySelectorAll('.refresh-form, .inline-form, .alias-form').forEach(addSpinnerOnSubmit);

        const addForm = document.getElementById('addCityForm');
        const addBtn  = document.getElementById('addCityBtn');
        if (addForm && addBtn && !addForm.dataset.spinnerBound) {
            addForm.dataset.spinnerBound = '1';
            addForm.addEventListener('submit', function () {
                addBtn.disabled = true;
                addBtn.classList.add('is-loading');
                const spinner = document.createElement('span');
                spinner.className = 'spinner';
                addBtn.appendChild(spinner);
                const panel = document.getElementById('placesPanel');
                if (panel) panel.classList.add('hidden');
                showLoadingOverlay();
            });
        }

        // ---- Delete: inline two-step confirm (no browser confirm() dialog) ----
        document.querySelectorAll('.delete-form').forEach(function (form) {
            if (form.dataset.confirmBound) return;
            form.dataset.confirmBound = '1';

            const deleteBtn  = form.querySelector('.delete-btn');
            const confirmBtn = form.querySelector('.delete-confirm');
            const cancelBtn  = form.querySelector('.delete-cancel');
            if (!deleteBtn || !confirmBtn || !cancelBtn) return;

            deleteBtn.addEventListener('click', function () {
                deleteBtn.classList.add('hidden');
                confirmBtn.classList.remove('hidden');
                cancelBtn.classList.remove('hidden');
            });

            cancelBtn.addEventListener('click', function () {
                confirmBtn.classList.add('hidden');
                cancelBtn.classList.add('hidden');
                deleteBtn.classList.remove('hidden');
            });

            // confirmBtn is type="submit" — router.js handles the actual submit.
        });

        // ---- Rename toggle ----
        document.querySelectorAll('.edit-alias-btn').forEach(function (btn) {
            if (btn.dataset.bound) return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', function () {
                const card = btn.closest('.city-card');
                card.querySelector('.alias-form').classList.toggle('hidden');
            });
        });

        // ---- Flash message auto-hide ----
        const flash = document.querySelector('.flash-message');
        if (flash) {
            setTimeout(function () { flash.style.display = 'none'; }, 4000);
        }

        // ---- City suggestions dropdown ----
        const cityInput      = document.getElementById('city_name');
        const suggestionsBox = document.getElementById('suggestionsDropdown');
        const cityLat        = document.getElementById('city_lat');
        const cityLon        = document.getElementById('city_lon');
        const cityCountry    = document.getElementById('city_country');
        const cityDisplayName = document.getElementById('city_display_name');

        function clearPickedLocation() {
            if (cityLat) cityLat.value = '';
            if (cityLon) cityLon.value = '';
            if (cityCountry) cityCountry.value = '';
            if (cityDisplayName) cityDisplayName.value = '';
        }
        function hideSuggestions() {
            if (suggestionsBox) {
                suggestionsBox.classList.add('hidden');
                suggestionsBox.innerHTML = '';
            }
        }

        if (cityInput && suggestionsBox && cityLat && cityLon && !cityInput.dataset.suggestBound) {
            cityInput.dataset.suggestBound = '1';
            let suggestDebounce, suggestToken = 0;

            cityInput.addEventListener('input', function () {
                clearPickedLocation();
                clearTimeout(suggestDebounce);
                const q = cityInput.value.trim();
                if (q.length < 2) { hideSuggestions(); return; }

                suggestDebounce = setTimeout(function () {
                    const myToken = ++suggestToken;
                    fetch('actions/city_suggestions.php?q=' + encodeURIComponent(q))
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            if (myToken !== suggestToken) return;
                            if (!data.matches || !data.matches.length) { hideSuggestions(); return; }
                            suggestionsBox.innerHTML = '';
                            data.matches.forEach(function (m) {
                                const li = document.createElement('li');
                                li.className = 'suggestion-item';
                                const nameEl = document.createElement('span');
                                nameEl.className = 'suggestion-name';
                                nameEl.textContent = m.name;
                                li.appendChild(nameEl);
                                const subParts = [m.admin1, m.country_name || m.country].filter(Boolean);
                                if (subParts.length) {
                                    const subEl = document.createElement('span');
                                    subEl.className = 'suggestion-sub';
                                    subEl.textContent = subParts.join(', ');
                                    li.appendChild(subEl);
                                }
                                li.addEventListener('mousedown', function (e) {
                                    e.preventDefault();
                                    const label = m.name + (subParts.length ? ', ' + subParts.join(', ') : '');
                                    cityInput.value = label;
                                    cityLat.value = m.latitude;
                                    cityLon.value = m.longitude;
                                    cityCountry.value = m.country || '';
                                    cityDisplayName.value = m.name;
                                    hideSuggestions();
                                });
                                suggestionsBox.appendChild(li);
                            });
                            suggestionsBox.classList.remove('hidden');
                        })
                        .catch(function () { if (myToken === suggestToken) hideSuggestions(); });
                }, 300);
            });

            cityInput.addEventListener('blur', function () { setTimeout(hideSuggestions, 150); });
            document.addEventListener('click', function (e) {
                if (e.target !== cityInput && !suggestionsBox.contains(e.target)) hideSuggestions();
            });
        }

        // ---- Famous places panel ----
        const placesPanel      = document.getElementById('placesPanel');
        const placesGrid       = document.getElementById('placesGrid');
        const placesPanelTitle = document.getElementById('placesPanelTitle');

        if (cityInput && placesPanel && placesGrid && !cityInput.dataset.placesBound) {
            cityInput.dataset.placesBound = '1';
            let debounce, requestToken = 0;

            function hidePanel() {
                placesPanel.classList.add('hidden');
                placesGrid.innerHTML = '';
            }

            cityInput.addEventListener('input', function () {
                clearTimeout(debounce);
                const q = cityInput.value.trim();
                if (q.length < 2) { hidePanel(); return; }

                debounce = setTimeout(function () {
                    const myToken = ++requestToken;
                    placesPanelTitle.textContent = 'Searching for "' + q + '"\u2026';
                    placesGrid.innerHTML = '<div class="places-loading"><span class="spinner"></span> Looking up famous places\u2026</div>';
                    placesPanel.classList.remove('hidden');

                    fetch('actions/city_places.php?q=' + encodeURIComponent(q))
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            if (myToken !== requestToken) return;
                            placesGrid.innerHTML = '';
                            if (!data.found) {
                                placesPanelTitle.textContent = 'Famous places';
                                placesGrid.innerHTML = '<p class="places-empty">Couldn\'t find that location.</p>';
                                return;
                            }
                            placesPanelTitle.textContent = 'Famous places in ' + data.city_name + (data.country ? ', ' + data.country : '');
                            if (!data.places || !data.places.length) {
                                placesGrid.innerHTML = '<p class="places-empty">No famous places found.</p>';
                                return;
                            }
                            data.places.forEach(function (place) {
                                const card = document.createElement('div');
                                card.className = 'place-card';
                                if (place.thumbnail) {
                                    const img = document.createElement('img');
                                    img.src = place.thumbnail;
                                    img.alt = place.title;
                                    img.loading = 'lazy';
                                    img.onerror = function () {
                                        const ph = document.createElement('div');
                                        ph.className = 'place-thumb-placeholder';
                                        ph.textContent = '\uD83D\uDCCD';
                                        img.replaceWith(ph);
                                    };
                                    card.appendChild(img);
                                } else {
                                    const ph = document.createElement('div');
                                    ph.className = 'place-thumb-placeholder';
                                    ph.textContent = '\uD83D\uDCCD';
                                    card.appendChild(ph);
                                }
                                const name = document.createElement('span');
                                name.className = 'place-name';
                                name.textContent = place.title;
                                card.appendChild(name);
                                placesGrid.appendChild(card);
                            });
                        })
                        .catch(function () {
                            if (myToken !== requestToken) return;
                            placesPanelTitle.textContent = 'Famous places';
                            placesGrid.innerHTML = '<p class="places-empty">Something went wrong.</p>';
                        });
                }, 450);
            });
        }

        // ---- FAQ accordion + sticky nav (help.php) ----
        initFaq();

    } // end init()

    // ---- FAQ two-level accordion (sections -> questions) + left-nav highlight ----
    function initFaq() {
        const sections = document.querySelectorAll('.faq-section');
        const navLinks = document.querySelectorAll('.faq-nav-link');
        if (!sections.length) return;

        // Level 1: section header toggle (expands/collapses its questions)
        sections.forEach(function (section) {
            const header = section.querySelector('.faq-section-header');
            if (!header || header.dataset.faqBound) return;
            header.dataset.faqBound = '1';
            header.addEventListener('click', function () {
                const isOpen = section.classList.contains('faq-section-open');
                // Close all sections, then open the clicked one (unless it was already open)
                sections.forEach(function (s) {
                    s.classList.remove('faq-section-open');
                    const h = s.querySelector('.faq-section-header');
                    if (h) h.setAttribute('aria-expanded', 'false');
                });
                if (!isOpen) {
                    section.classList.add('faq-section-open');
                    header.setAttribute('aria-expanded', 'true');
                }
            });
        });

        // Level 2: question toggle inside each section
        const items = document.querySelectorAll('.faq-item');
        items.forEach(function (item) {
            const btn = item.querySelector('.faq-question');
            if (!btn || btn.dataset.faqBound) return;
            btn.dataset.faqBound = '1';
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const parentSection = item.closest('.faq-section');
                const siblingItems = parentSection ? parentSection.querySelectorAll('.faq-item') : items;
                const isOpen = item.classList.contains('faq-open');
                // Close sibling questions within the same section, then open the clicked one
                siblingItems.forEach(function (i) {
                    i.classList.remove('faq-open');
                    const question = i.querySelector('.faq-question');
                    if (question) question.setAttribute('aria-expanded', 'false');
                });
                if (!isOpen) {
                    item.classList.add('faq-open');
                    btn.setAttribute('aria-expanded', 'true');
                }
            });
        });

        // Left-nav click → scroll to section + open it (leaving its default question open)
        navLinks.forEach(function (link) {
            if (link.dataset.faqNavBound) return;
            link.dataset.faqNavBound = '1';
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.getElementById(link.getAttribute('href').replace('#', ''));
                if (!target) return;
                // Open the target section, close the rest
                sections.forEach(function (s) {
                    s.classList.remove('faq-section-open');
                    const h = s.querySelector('.faq-section-header');
                    if (h) h.setAttribute('aria-expanded', 'false');
                });
                target.classList.add('faq-section-open');
                const targetHeader = target.querySelector('.faq-section-header');
                if (targetHeader) targetHeader.setAttribute('aria-expanded', 'true');
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });

        // Scroll spy — highlight the nav link whose section is in view
        if (window._faq_observer) {
            window._faq_observer.disconnect();
            window._faq_observer = null;
        }
        window._faq_observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    const id = entry.target.id;
                    navLinks.forEach(function (l) {
                        l.classList.toggle('faq-nav-active', l.getAttribute('href') === '#' + id);
                    });
                }
            });
        }, { rootMargin: '0px 0px -60% 0px', threshold: 0 });
        sections.forEach(function (section) { window._faq_observer.observe(section); });
    }

    // Boot on first load
    document.addEventListener('DOMContentLoaded', init);

    return { init: init };

})();
