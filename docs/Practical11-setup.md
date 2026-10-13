# Practical 11 - Student Management

1. Start Apache and MySQL in XAMPP. Use the existing StudentHub database and application account from Practicals 8-10.
2. Import `sql/practical11.sql` into `studenthub_db` in phpMyAdmin. This adds the status column and twenty fictional students without replacing existing records.
3. Log in as the lab administrator and choose **Manage students** on the admin dashboard, or open `http://localhost/studenthub-portal/students-manage.php`.
4. Add a student, edit the record, search by name/email/course, and use the course, year and status filters.
5. Delete opens a confirmation page. Tick the confirmation checkbox and click **Delete student**. The record changes to `deleted`; choose the Deleted status filter to find and restore it.
6. Export CSV downloads all records matching the current filters, across all pages. Active records are exported by default.

The page uses MySQLi prepared statements, server-side validation, duplicate-email handling, admin access checks, escaped HTML output and CSRF tokens. Each page displays ten records. Existing event registrations are retained when a student is soft-deleted. The older `students.html` JSON directory remains available for the earlier practical.

Submission files:
- Report: `output/pdf/D26DCE157_(lab11).pdf`
- Student SQL dump: `sql/practical11-students-dump.sql`
- Migration and seed data: `sql/practical11.sql`
- CSV sample: `output/csv/student-list-practical11.csv`
- Screenshots: `docs/practical11-screenshots/`
- Recorded test results: `docs/Practical11-test-results.json`

The students-only dump is for an empty database. Do not import it over the existing students table. For the running portal, use the additive migration. The dump does not contain login accounts, passwords or remember-me tokens.

Set `STUDENTHUB_DEMO_PASSWORD` to the lab account password and run `python scripts/test-practical11.py` from the project root to repeat the tests. Tests create one fictional record and leave it soft-deleted. Run `C:\xampp\php\php.exe scripts/export-practical11.php` to refresh the students-only SQL dump.
