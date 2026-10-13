# Practical 10 - Login and Sessions

1. Start Apache and MySQL in XAMPP.
2. Import `sql/practical10.sql` into the existing `studenthub_db` using phpMyAdmin. Practical 9 must be set up first.
3. Keep using the private `php/db-config.php` application credentials.
4. Set `STUDENTHUB_DEMO_PASSWORD` to a password chosen for the lab, then run `C:\xampp\php\php.exe scripts/seed-practical10.php` in the project folder. It creates `lab10.student@example.com`, `lab10.admin@example.com` and `lab10.faculty@example.com` with hashed passwords. Running it again resets only these demo accounts.
5. Open `http://localhost/studenthub-portal/login.php` and log in. Student, admin and faculty accounts go to their corresponding dashboards.
6. Use Logout, try an incorrect password and open an admin dashboard with a student account to check the messages.

Sessions expire after 15 minutes without an authenticated request. For a short demonstration, create the ignored `php/auth-config.local.php` containing `<?php return ['timeout'=>2];`, log in, wait more than two seconds and reload the dashboard. Remove the override afterward.

Remember me lasts for 30 days and stores a random validator as a SHA-256 hash in the database. It rotates when restoring a login and is revoked on logout or session timeout. Cookies use HttpOnly and SameSite=Lax; the Secure flag is enabled when served over HTTPS. The XAMPP localhost demonstration uses HTTP.

With `STUDENTHUB_DEMO_PASSWORD` set, run `python scripts/test-practical10.py` from the project root to repeat the HTTP tests. Test results are saved in `docs/Practical10-test-results.json`. The test creates and removes a temporary timeout override.

The old `index.html` and `dashboard.html` only send visitors to the PHP pages. Protected dashboard content is served by PHP after session and role checks. Admin is never an option in public registration. Passwords, cookie values and stored token hashes are excluded from the report screenshots.
