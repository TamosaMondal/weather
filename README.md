# Weather Bookmark Dashboard

A simple logged-in dashboard where a user can search for a city, save it as a
"bookmark," see its latest temperature, rename it with a custom alias, and
remove it. Built with plain **HTML + CSS + PHP**, a small sprinkle of
**JavaScript** for UX, and the **OpenWeatherMap API** for live weather data.

No frameworks, no build step, no separate database server — just PHP files
you can run directly.

---

## 1. Tech Stack

| Layer      | Choice                                              |
|------------|------------------------------------------------------|
| Backend    | PHP 8 (plain, procedural, no framework)               |
| Database   | SQLite (a single file, `database.sqlite`, auto-created) |
| Frontend   | HTML + CSS (`css/style.css`)                          |
| JavaScript | Vanilla JS, only for confirm-dialogs / toggling forms |
| Weather API| [OpenWeatherMap](https://openweathermap.org/current) "Current Weather" endpoint |

SQLite was chosen instead of MySQL so the project runs with **zero database
setup** — PHP talks to it through PDO, and the file is created automatically
the first time the app runs.

---

## 2. Folder Structure

```
weather-dashboard/
├── index.php                 Entry point → redirects to login or dashboard
├── login.php                 Login form + handling
├── register.php              Registration form + handling
├── logout.php                Destroys the session
├── dashboard.php             Main screen: search box + saved city grid
│
├── config/
│   ├── config.php             API key + DB path constants
│   └── db.php                 Opens PDO/SQLite connection, creates tables
│
├── includes/
│   ├── bootstrap.php          Included at the top of every page: starts
│   │                           session, loads config/db/functions
│   ├── functions.php          Helper functions (auth checks, API call, etc.)
│   ├── header.php              Shared HTML <head> + top nav
│   └── footer.php              Shared closing HTML + <script> tag
│
├── actions/                   Scripts that only PROCESS a form and redirect
│   │                           back to dashboard.php (no visible HTML output)
│   ├── add_city.php            CREATE  – search + save a new city
│   ├── update_city.php         UPDATE  – save a custom alias
│   ├── refresh_weather.php     Re-fetch latest weather for one saved city
│   └── delete_city.php         DELETE  – remove a saved city
│
├── css/
│   └── style.css              All styling
│
├── js/
│   └── script.js              Delete confirmation, alias-form toggle, etc.
│
├── database.sqlite            Auto-created on first run (not in the zip)
├── README.md                  This file
└── SETUP.md                   First-time setup / run instructions
```

---

## 3. How the pieces connect

**Every page starts the same way.** `login.php`, `register.php`,
`dashboard.php`, `logout.php`, and every file in `actions/` begin with:

```php
require_once 'includes/bootstrap.php';
```

`bootstrap.php` does three things, in order:
1. `session_start()` — so `$_SESSION` works everywhere.
2. Loads `config/config.php` (API key + DB path) and `config/db.php`
   (gives you the `getDB()` function).
3. Loads `includes/functions.php` (helper functions like `require_login()`,
   `fetch_weather()`, `h()` for escaping, `redirect()`).

**Pages that render HTML** (`login.php`, `register.php`, `dashboard.php`)
include `includes/header.php` before their content and `includes/footer.php`
after it, so the `<head>`, top navigation bar, and closing tags/`<script>`
stay identical everywhere.

**Pages in `actions/`** never output HTML. They read `$_POST`, do one job
(insert / update / delete a row, or call the weather API), set a short
message in `$_SESSION['flash']`, and redirect back to `dashboard.php`, which
displays that message at the top of the page.

### The CRUD flow, file by file

| Action | Triggered from                     | Handled by                     | What happens |
|--------|-------------------------------------|---------------------------------|--------------|
| Create | Search form on `dashboard.php`      | `actions/add_city.php`          | Calls `fetch_weather()` (OpenWeatherMap), then `INSERT`s a row into the `cities` table |
| Read   | Loading `dashboard.php`             | `dashboard.php` itself          | `SELECT * FROM cities WHERE user_id = ?` and loops over the results to build the grid |
| Update | "Rename" button + inline form       | `actions/update_city.php`       | `UPDATE cities SET alias = ? WHERE id = ? AND user_id = ?` |
| Update (weather) | "Refresh" button           | `actions/refresh_weather.php`   | Calls `fetch_weather()` again and updates temperature/description |
| Delete | "Delete" button                     | `actions/delete_city.php`       | `DELETE FROM cities WHERE id = ? AND user_id = ?` |

Every query that touches a row is scoped with `AND user_id = ?`, so one user
can never edit or delete another user's saved cities.

### Authentication flow

- `register.php` hashes the password with `password_hash()` and inserts a row
  into `users`, then logs the user in immediately (sets `$_SESSION['user_id']`).
- `login.php` looks up the username, verifies the password with
  `password_verify()`, and sets the same session variables.
- `includes/functions.php` has `require_login()`, which every protected page
  (`dashboard.php` and everything in `actions/`) calls at the top. If there's
  no active session, it redirects to `login.php`.
- `logout.php` clears `$_SESSION` and destroys the session.

### The weather API call

All communication with OpenWeatherMap happens in **one place**:
`fetch_weather($city)` inside `includes/functions.php`. It builds the request
URL using the API key from `config/config.php`, calls it with cURL, and
returns a clean associative array (or `null` if the city wasn't found / the
API failed). Both `add_city.php` and `refresh_weather.php` call this same
function, so there's no duplicated API logic.

---

## 4. Database schema

Created automatically by `config/db.php` the first time the app runs.

```sql
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,          -- hashed with password_hash()
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE cities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,        -- which user saved this city
    city_name TEXT NOT NULL,         -- real city name returned by the API
    alias TEXT,                      -- user's custom nickname
    country TEXT,
    temperature REAL,
    weather_desc TEXT,
    weather_icon TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id)
);
```

---

## 5. Security notes (kept intentionally simple, but not careless)

- Passwords are hashed with `password_hash()` / verified with
  `password_verify()` — never stored in plain text.
- All SQL uses **prepared statements** (PDO), so there's no SQL injection.
- All output printed back into HTML goes through the `h()` helper
  (`htmlspecialchars`), so there's no XSS from city names, aliases, etc.
- Every city query is scoped to the logged-in `user_id`.

This is a learning/demo project, so things like CSRF tokens, rate limiting,
and email verification were left out on purpose to keep the code easy to
read — see `SETUP.md` if you want to extend it.

For setup instructions, see **SETUP.md**.
