# Practical 13 - PHP JSON API and Fetch CRUD

1. Start Apache and MySQL in XAMPP and use the existing `studenthub_db` configured in the private `php/db-config.php` file.
2. Make sure the Practical 12 event columns and event_audit table exist. Applying `sql/practical14.sql` enables the general admin audit feed; Practical 12 event_audit continues to record the event actions independently.
3. Sign in with an admin account and open **API demo** from the admin dashboard, or visit `http://localhost/studenthub-portal/api-events-demo.php`.
4. The page loads events with `GET php/api-events.php`. Add and edit submit JSON with Fetch API; delete uses the event ID query parameter. The page updates its list without reloading.
5. The API accepts `GET` for public event reads and `POST`, `PUT` and `DELETE` for admin changes. Mutations require an admin session and `X-CSRF-Token`. Validation errors use HTTP 422; missing IDs, missing events and registered-event deletion use HTTP 400, 404 and 409 respectively.
6. Inspect the browser Network panel to see the JSON payload, status code and response. Confirm that changing or deleting an event is reflected in the database-backed events page.

The browser demo creates no password or token in local storage. JSON output excludes poster filenames, user details and credentials. Admin CRUD actions also write to the Practical 14 audit log after that migration is installed.

Submission files:
- Report: `output/pdf/D26DCE157_(lab13).pdf`
- API: `php/api-events.php`
- Fetch demo: `api-events-demo.php`, `js/api-demo.js`
- Repeatable API checks: `scripts/test-practical13.py`
- Database-free input validation checks: `scripts/test-practical13-input.php`
- Test results: `docs/Practical13-test-results.json`
