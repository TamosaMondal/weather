/**
 * script.js
 * ----------
 * A small amount of JavaScript purely for UX polish.
 * All the actual data logic (CRUD) happens in PHP.
 */

document.addEventListener('DOMContentLoaded', function () {

    // ---- Current date in navbar ----
    const dateEl = document.getElementById('currentDate');
    if (dateEl) {
        dateEl.textContent = new Date().toLocaleDateString(undefined, {
            weekday: 'short', year: 'numeric', month: 'short', day: 'numeric'
        });
    }

    // ---- Dark mode toggle ----
    const themeToggle = document.getElementById('themeToggle');
    function applyThemeIcon() {
        if (!themeToggle) return;
        const isDark = document.documentElement.classList.contains('dark-theme');
        themeToggle.textContent = isDark ? '☀️' : '🌙';
    }
    applyThemeIcon();
    if (themeToggle) {
        themeToggle.addEventListener('click', function () {
            document.documentElement.classList.toggle('dark-theme');
            const isDark = document.documentElement.classList.contains('dark-theme');
            localStorage.setItem('wd_theme', isDark ? 'dark' : 'light');
            applyThemeIcon();
        });
    }

    // ---- Temperature unit toggle (C/F) ----
    const unitToggle = document.getElementById('unitToggle');
    function celsiusToFahrenheit(c) {
        return (c * 9 / 5) + 32;
    }
    function applyTempUnit() {
        const unit = localStorage.getItem('wd_unit') || 'C';
        document.querySelectorAll('.temp[data-temp-c]').forEach(function (el) {
            const c = parseFloat(el.getAttribute('data-temp-c'));
            if (isNaN(c)) return;
            const value = unit === 'F' ? celsiusToFahrenheit(c) : c;
            el.textContent = Math.round(value * 10) / 10 + '\u00B0' + unit;
        });
        if (unitToggle) {
            unitToggle.textContent = unit === 'F' ? '\u00B0F / \u00B0C' : '\u00B0C / \u00B0F';
        }
    }
    applyTempUnit();
    if (unitToggle) {
        unitToggle.addEventListener('click', function () {
            const current = localStorage.getItem('wd_unit') || 'C';
            localStorage.setItem('wd_unit', current === 'C' ? 'F' : 'C');
            applyTempUnit();
        });
    }

    // ---- Relative "Updated" time ----
    function relativeTime(dateStr) {
        // Treat stored timestamps as local server time (SQLite CURRENT_TIMESTAMP is UTC).
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
    setInterval(refreshRelativeTimes, 60000);

    // ---- Prevent double-submitting the add-city form ----
    // (No spinner here on purpose — this is a normal form POST that
    // reloads the page when it's done, so the only thing worth doing
    // client-side is stopping a second accidental click.)
    const addForm = document.getElementById('addCityForm');
    const addBtn = document.getElementById('addCityBtn');
    if (addForm && addBtn) {
        addForm.addEventListener('submit', function () {
            addBtn.disabled = true;
            const panel = document.getElementById('placesPanel');
            if (panel) panel.classList.add('hidden');
        });
    }
    document.querySelectorAll('.refresh-form').forEach(function (form) {
        form.addEventListener('submit', function () {
            const btn = form.querySelector('button');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner"></span>';
            }
        });
    });

    // Confirm before deleting a city
    document.querySelectorAll('.delete-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!confirm('Remove this city from your dashboard?')) {
                e.preventDefault();
            }
        });
    });

    // Toggle the inline "rename" form on each city card
    document.querySelectorAll('.edit-alias-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const card = btn.closest('.city-card');
            card.querySelector('.alias-form').classList.toggle('hidden');
        });
    });

    // Auto-hide the flash message after a few seconds
    const flash = document.querySelector('.flash-message');
    if (flash) {
        setTimeout(function () {
            flash.style.display = 'none';
        }, 4000);
    }

    // Location-suggestions dropdown — shows matching place names right
    // under the search box so the user can pick the exact place they
    // mean (there can be more than one "Springfield" or "Paris").
    // This is separate from the "famous places" panel below, which is
    // about landmarks *inside* whatever location ends up chosen.
    const cityInput = document.getElementById('city_name');
    const suggestionsBox = document.getElementById('suggestionsDropdown');
    const cityLat = document.getElementById('city_lat');
    const cityLon = document.getElementById('city_lon');
    const cityCountry = document.getElementById('city_country');
    const cityDisplayName = document.getElementById('city_display_name');

    function clearPickedLocation() {
        cityLat.value = '';
        cityLon.value = '';
        cityCountry.value = '';
        cityDisplayName.value = '';
    }

    function hideSuggestions() {
        if (suggestionsBox) {
            suggestionsBox.classList.add('hidden');
            suggestionsBox.innerHTML = '';
        }
    }

    if (cityInput && suggestionsBox && cityLat && cityLon) {
        let suggestDebounce;
        let suggestToken = 0;

        cityInput.addEventListener('input', function () {
            // Any manual edit invalidates whatever was previously picked.
            clearPickedLocation();
            clearTimeout(suggestDebounce);

            const q = cityInput.value.trim();
            if (q.length < 2) { hideSuggestions(); return; }

            suggestDebounce = setTimeout(function () {
                const myToken = ++suggestToken;

                fetch('actions/city_suggestions.php?q=' + encodeURIComponent(q))
                    .then(r => r.json())
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
                                // mousedown (not click) so this fires before
                                // the input's blur event hides the dropdown.
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
                    .catch(function () {
                        if (myToken !== suggestToken) return;
                        hideSuggestions();
                    });
            }, 300);
        });

        cityInput.addEventListener('blur', function () {
            // Small delay so a mousedown-selection above still registers.
            setTimeout(hideSuggestions, 150);
        });

        document.addEventListener('click', function (e) {
            if (e.target !== cityInput && !suggestionsBox.contains(e.target)) {
                hideSuggestions();
            }
        });
    }

    // Famous places side panel — fetched as the user types a location,
    // shown next to the form, hidden again once the city is saved
    // (search-form submit reloads the page, which naturally clears it).
    const placesPanel = document.getElementById('placesPanel');
    const placesGrid = document.getElementById('placesGrid');
    const placesPanelTitle = document.getElementById('placesPanelTitle');

    if (cityInput && placesPanel && placesGrid) {
        let debounce;
        let requestToken = 0;

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
                    .then(r => r.json())
                    .then(function (data) {
                        if (myToken !== requestToken) return; // a newer request superseded this one
                        placesGrid.innerHTML = '';

                        if (!data.found) {
                            placesPanelTitle.textContent = 'Famous places';
                            placesGrid.innerHTML = '<p class="places-empty">Couldn\'t find that location. Keep typing or check the spelling.</p>';
                            return;
                        }

                        placesPanelTitle.textContent = 'Famous places in ' + data.city_name
                            + (data.country ? ', ' + data.country : '');

                        if (!data.places || !data.places.length) {
                            placesGrid.innerHTML = '<p class="places-empty">No famous places found for this location yet.</p>';
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
                                    // Thumbnail host unreachable (e.g. blocked on a
                                    // locked-down network) — fall back quietly
                                    // instead of showing a broken-image icon.
                                    const placeholder = document.createElement('div');
                                    placeholder.className = 'place-thumb-placeholder';
                                    placeholder.textContent = '\uD83D\uDCCD';
                                    img.replaceWith(placeholder);
                                };
                                card.appendChild(img);
                            } else {
                                const placeholder = document.createElement('div');
                                placeholder.className = 'place-thumb-placeholder';
                                placeholder.textContent = '\uD83D\uDCCD';
                                card.appendChild(placeholder);
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
                        placesGrid.innerHTML = '<p class="places-empty">Something went wrong looking up places. Please try again.</p>';
                    });
            }, 450);
        });
    }

});
