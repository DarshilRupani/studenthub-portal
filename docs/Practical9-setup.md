# Practical 9 - setup and verification

1. Start Apache and MySQL in XAMPP.
2. Open phpMyAdmin, select `studenthub_db`, and import `sql/practical9.sql` using your database administrator account. This adds `users` and `registration_audit` while preserving Practical 8 tables. It has been applied on this machine.
3. Continue using the private `php/db-config.php` application credentials. Registration connects through `php/db-mysqli.php`; Practical 8 keeps its PDO connection.
4. Open http://localhost/studenthub-portal/register.html and submit a new account.
5. Repeat the same email to see the duplicate error. Try an invalid email, weak password and mismatched confirmation.
6. In phpMyAdmin inspect `users` and `registration_audit`. Only `password_hash` is stored. Do not include real passwords or private database configuration in submissions.

Account registration uses the `users` table. Practical 8 `registrations` remains the event signup table; the old registrations.html page displays Practical 7 JSON account requests and is not a list of new MySQL accounts. Login authentication is outside Practical 9.

The form uses email as its unique login identifier; there is no separate username field. The database UNIQUE key catches concurrent duplicate inserts. Registration and the successful audit entry commit together or roll back together. The client security-code drawing is verified against a server session value, but the code is available to the client and should not be treated as bot protection.

Password policy: 8-72 printable ASCII characters including uppercase, lowercase, digit and symbol. This avoids bcrypt's 72-byte truncation. Name: 2-50 letters with spaces, dots, apostrophes and hyphens. Email: at most 150 characters and matching the same pattern on both sides. Mobile: 10 digits starting 6-9. Course, year, gender and role use allowed values; terms must be accepted. Faculty is a profile category in this practical, not an authorization grant.
