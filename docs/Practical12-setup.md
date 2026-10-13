# Practical 12 - Event Management

1. Start Apache and MySQL in XAMPP. Keep the existing database setup from Practicals 8-11.
2. Import `sql/practical12.sql` into `studenthub_db`. It adds event category, open/closed status, poster filename and the event audit table, plus two sample events.
3. Run `C:\xampp\php\php.exe scripts/setup-practical12.php` from the project root to create the protected upload folder. The folder is excluded from Git.
4. Log in as admin and choose **Manage events**. Add an event and choose the optional poster using the file picker.
5. Valid posters must be JPEG, PNG or WebP, no larger than 2 MB or 4096 by 4096 pixels. The server verifies the extension, actual MIME type and image dimensions. Uploaded files receive random names.
6. Edit updates the event and optionally replaces its poster. Leaving the file input empty keeps the existing poster. Delete requires confirmation; events with registrations are retained and can be closed instead.
7. Use category and open/closed filters. Recent event changes displays the add, update and delete audit entries. The public Campus Events page now loads the database records and posters.

The Bootstrap CSS is stored locally in `css/vendor/bootstrap.min.css` (Bootstrap 5.3.3, MIT licence) so the admin page does not depend on a CDN at runtime. The application also retains its existing StudentHub stylesheet.

Submission files:
- Report: `output/pdf/D26DCE157_(lab12).pdf`
- Events-only SQL dump: `sql/practical12-events-dump.sql`
- Migration: `sql/practical12.sql`
- Screenshots: `docs/practical12-screenshots/`
- Test results: `docs/Practical12-test-results.json`
- Valid sample poster: `images/practical12/sample-poster.png`

The events-only dump is for an empty database, not for importing over the existing events table. The additive migration is for the running portal. The dump excludes login accounts, passwords, authentication tokens and audit-user details. Poster binaries are kept out of Git; the sample image is included separately. After restoring the dump to a lab setup with application credentials, `scripts/setup-practical12.php --restore-sample` restores the included Workshop Demo image if it is missing.

Set `STUDENTHUB_DEMO_PASSWORD` to the existing lab-account password, then run `python scripts/test-practical12.py` from the project root. The tests use two temporary fictional events, delete only those test events and verify upload failures, renaming, directory protection and audit retention. Run `C:\xampp\php\php.exe scripts/export-practical12.php` to refresh the events-only SQL dump.
