# Setup Guide — Run the Project for the First Time (Windows)

This project has **no build step** and **no external database server** to
install. You only need PHP itself.

---

## 1. Install PHP on Windows

The easiest way is to install **XAMPP**, which bundles PHP with the SQLite
and cURL extensions already enabled.

1. Download XAMPP from <https://www.apachefriends.org/download.html>
2. Run the installer (Apache + PHP is enough, you can uncheck MySQL/phpMyAdmin
   if you want — this project doesn't need them).
3. Install it to the default location, e.g. `C:\xampp`

This gives you `php.exe` at:

```
C:\xampp\php\php.exe
```

### Add PHP to your PATH (so you can just type `php`)

1. Press `Win + S`, search for **Environment Variables**, open
   **"Edit the system environment variables"**.
2. Click **Environment Variables**.
3. Under **System variables**, select `Path` → **Edit** → **New**.
4. Add: `C:\xampp\php`
5. Click OK on all windows.
6. Open a **new** Command Prompt or PowerShell window (important — old
   windows won't see the change) and check it worked:

```powershell
php -v
```

You should see something like `PHP 8.x.x ...`.

### Confirm the required extensions are enabled

```powershell
php -m | findstr /i sqlite
php -m | findstr /i curl
```

Both `pdo_sqlite` and `curl` should be enabled by default in XAMPP. If either
is missing, open `C:\xampp\php\php.ini` in Notepad, find these lines, and
remove the leading `;` if present, then restart your terminal:

```
extension=curl
extension=pdo_sqlite
extension=sqlite3
```

---

## 2. Get a free OpenWeatherMap API key

1. Go to <https://home.openweathermap.org/users/sign_up> and create a free
   account.
2. After signing up, go to the **API keys** tab in your account.
3. Copy your default API key.
   > Note: a brand-new key can take up to a couple of hours to activate.
   > If city searches fail right after signing up, wait a bit and try again.

---

## 3. Add your API key to the project

Open `config\config.php` in Notepad, VS Code, or any text editor, and
replace the placeholder:

```php
define('OWM_API_KEY', 'YOUR_OPENWEATHERMAP_API_KEY_HERE');
```

with your real key:

```php
define('OWM_API_KEY', 'abcd1234yourrealkeyhere');
```

That's the only setting you need to change. Save the file.

---

## 4. Run the project

1. Extract the project zip somewhere convenient, e.g. `C:\projects\weather-dashboard`
2. Open **Command Prompt** or **PowerShell**.
3. Navigate into the folder:

```powershell
cd C:\projects\weather-dashboard
```

4. Start PHP's built-in web server:

```powershell
php -S localhost:8000
```

Leave this window open — it's your running server. Then open your browser
at:

```
http://localhost:8000
```

The SQLite database file (`database.sqlite`) is created automatically the
first time you load the app or register a user — no manual database setup
required.

To stop the server later, click the terminal window and press `Ctrl + C`.

> Prefer running it through XAMPP's Apache instead of the built-in server?
> Copy the `weather-dashboard` folder into `C:\xampp\htdocs\`, start Apache
> from the XAMPP Control Panel, and visit
> `http://localhost/weather-dashboard` instead.

---

## 5. Using the app

1. Go to `http://localhost:8000` — you'll be redirected to the login page.
2. Click **Register here** and create an account (username + password).
3. You'll be logged in automatically and taken to the dashboard.
4. Type a city name (e.g. `London`, `Tokyo`, `New York`) into the search box
   and click **Search & Save** — this calls the OpenWeatherMap API and saves
   the city with its current temperature.
5. On each saved city card you can:
   - **Rename** — give it a custom alias (e.g. "Office Location").
   - **Refresh** — re-fetch the latest temperature for that city.
   - **Delete** — remove it from your dashboard.
6. Click **Logout** in the top-right corner when you're done.

---

## 6. Resetting the app

To wipe all users and saved cities and start fresh, simply delete the
database file and reload the app — it will be recreated automatically.

In Command Prompt:

```cmd
del database.sqlite
```

In PowerShell:

```powershell
Remove-Item database.sqlite
```

Or just delete `database.sqlite` in File Explorer.

---

## 7. Troubleshooting

| Problem | Likely cause |
|---|---|
| `'php' is not recognized as an internal or external command` | PHP isn't on your PATH yet — redo step 1's PATH steps, then open a **new** terminal window |
| "City not found or the weather API is unavailable" for a valid city | API key not set yet, key not activated yet (new keys take a bit to go live), or no internet access |
| Blank page / 500 error | Check the `pdo_sqlite` and `curl` extensions are enabled in `php.ini` (see step 1) |
| Redirected to login even though you just registered | Make sure cookies/sessions aren't being blocked by your browser |
| Styles not loading / page looks unstyled | Make sure you're browsing via `http://localhost:8000`, not double-clicking the `.php` files directly (they won't run as plain files in a browser) |
| Port 8000 already in use | Run `php -S localhost:8080` instead and visit `http://localhost:8080` |