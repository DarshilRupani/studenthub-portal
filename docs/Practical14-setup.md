# Practical 14 - Admin Dashboard, Roles and Audit Log

1. Apply `sql/practical14.sql` to the existing `studenthub_db`. It creates the general `audit_logs` table and leaves existing records intact.
2. Sign in as an administrator and open `admin-dashboard.php`. Dashboard cards show account, active student, event and event-registration counts from MySQL.
3. Review the latest registrations and recent admin actions. Student add/edit/delete/restore operations and event add/edit/delete operations are recorded with actor, action, target and time. Passwords, tokens and submitted form contents are not stored in this audit table.
4. The administrator menu contains student and event management plus the Practical 13 API demo. Student and faculty accounts continue to use their own protected dashboards and do not receive admin management links.
5. Open the admin pages as a student or without a session to verify that role checks continue to reject access. The database role is checked on the server for each protected request.

Submission files:
- Report: `output/pdf/D26DCE157_(lab14).pdf`
- Migration: `sql/practical14.sql`
- Audit helper: `php/admin-audit.php`
- Dashboard: `admin-dashboard.php`
- Repeatable role and dashboard checks: `scripts/test-practical14.py`
- Test results: `docs/Practical14-test-results.json`
