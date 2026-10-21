# Practical 15 - Integration, Testing and Local Deployment

## Local XAMPP setup

1. Place the project at `C:\xampp\htdocs\studenthub-portal` and start Apache and MySQL in XAMPP.
2. Create or restore `studenthub_db` from the project SQL files in order: base schema, Practicals 9-12, then `sql/practical14.sql`. Keep the existing tables and records; migrations are additive.
3. Set the database host, port, database, application username and password in the ignored `php/db-config.php`. Use a database account limited to this application database. Never copy credentials into Git, screenshots or the report.
4. Set up the poster storage directory as described in `docs/Practical12-setup.md`, create the lab accounts with the Practical 10 seeding script, and sign in through `login.php`.
5. Open `http://localhost/studenthub-portal/` and verify navigation, login, role dashboards, student management, events, registrations, JSON API and admin audit log.

## Integration checks

Run PHP syntax checks on changed PHP files, then run the practical HTTP test scripts with `STUDENTHUB_DEMO_PASSWORD` set in the current shell: `scripts/test-practical10.py`, `scripts/test-practical11.py`, `scripts/test-practical12.py`, and `scripts/test-practical13.py`. The scripts use the existing lab accounts and temporary fictional records; review each script before running it against a non-lab database. Run `python scripts/check-practical15.py` for public-page and protected-route smoke checks while Apache is running.

Review `docs/Accessibility Checklist.docx` and test keyboard focus, labels, status announcements, contrast and responsive layouts at mobile, tablet and desktop widths. The report should include screenshots captured from the running local site, SQL backup, test output and setup notes.

## Virtual host option

For an Apache virtual host, point `DocumentRoot` at this project folder, grant Apache access to that directory, set `AllowOverride All` only where the project requires its upload protection rules, and enable the rewrite/headers modules required by the existing configuration. Add the local hostname to the Windows hosts file and Apache virtual-host configuration, restart Apache, then verify the hostname. Do not expose XAMPP phpMyAdmin or development credentials to a public network. HTTPS and a production-grade database account are required before any public deployment.

## Submission

The repository is `https://github.com/DarshilRupani/studenthub-portal`. Practicals 13–15 and their reports have been committed and pushed to `main`. Keep `php/db-config.php`, session data, uploaded posters and private credentials out of Git when making later updates.
