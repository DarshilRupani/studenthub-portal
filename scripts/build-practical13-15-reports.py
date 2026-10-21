from pathlib import Path
from xml.sax.saxutils import escape
from reportlab.platypus import SimpleDocTemplate, Paragraph, Table, TableStyle, PageBreak, Spacer, Image
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER
from reportlab.lib.pagesizes import A4

ROOT=Path(__file__).resolve().parents[1]
styles=getSampleStyleSheet()
styles.add(ParagraphStyle(name='Body9',fontName='Times-Roman',fontSize=11,leading=13.4,spaceAfter=9))
styles.add(ParagraphStyle(name='Heading9',fontName='Helvetica-Bold',fontSize=11,leading=14,spaceBefore=5,spaceAfter=7))
styles.add(ParagraphStyle(name='Title9',fontName='Helvetica-Bold',fontSize=16,leading=19,alignment=TA_CENTER,spaceAfter=14))
styles.add(ParagraphStyle(name='Subtitle9',fontName='Times-Bold',fontSize=12,leading=15,alignment=TA_CENTER,spaceAfter=12))
styles.add(ParagraphStyle(name='Cell9',fontName='Times-Roman',fontSize=10,leading=12))
styles.add(ParagraphStyle(name='Caption9',fontName='Times-Roman',fontSize=9,leading=11,leftIndent=6,spaceAfter=10))

# These are genuine portal screenshots already present in the practical folder.
# Keep the captions explicit about the practical they document; do not present
# older screenshots as proof of the new API or dashboard features.
screenshots={
13:[('docs/practical12-screenshots/public-events.jpg','Figure 1: Existing campus events page, showing the public event listing used by the portal.'),('docs/practical12-screenshots/add-success.jpg','Figure 2: Existing event-management success state from Practical 12; this is supporting UI context, not a Fetch API screenshot.')],
14:[('docs/practical12-screenshots/audit-log.jpg','Figure 1: Existing Practical 12 event-change audit view; the new Practical 14 audit_logs dashboard view still needs a fresh capture.'),('docs/practical11-screenshots/student-list.jpg','Figure 2: Existing protected student-management listing used as role-based admin UI context.')],
15:[('docs/practical12-screenshots/public-events.jpg','Figure 1: Existing public campus-events page used for the integrated portal.'),('docs/practical12-screenshots/edit-success.jpg','Figure 2: Existing event-management update state, showing the portal UI after an event change.')]
}

def paragraph(story,text,style='Body9'):
    story.append(Paragraph(text,styles[style]))

def heading(story,text): paragraph(story,text,'Heading9')

def table(story,rows,widths):
    cells=[[Paragraph(escape(str(value)),styles['Cell9']) for value in row] for row in rows]
    item=Table(cells,colWidths=widths,repeatRows=1)
    item.setStyle(TableStyle([('BACKGROUND',(0,0),(-1,0),colors.HexColor('#eeeeee')),('GRID',(0,0),(-1,-1),.4,colors.HexColor('#cccccc')),('VALIGN',(0,0),(-1,-1),'TOP'),('TOPPADDING',(0,0),(-1,-1),5),('BOTTOMPADDING',(0,0),(-1,-1),5)]))
    story.append(item); story.append(Spacer(1,12))

def footer(canvas,document):
    canvas.setFont('Times-Roman',10)
    canvas.drawString(46,802,'Web Development Frameworks')
    canvas.drawString(254,802,'D26DCE157')
    canvas.drawRightString(550,27,str(document.page))

reports=[
{
'number':13,'title':'PHP JSON API and AJAX/Fetch-Based CRUD without Page Reload',
'aim':'To build PHP JSON API endpoints for campus events and consume them using JavaScript Fetch API for asynchronous list, add, update and delete operations.',
'intro':'The new API reads event records from MySQL and returns JSON with HTTP status codes. Public requests can list or read events. Create, update and delete operations require an administrator session and CSRF token. An admin-only demo page uses Fetch and updates the list without a full page reload.',
'files':[['File','Purpose'],['php/api-events.php','JSON GET, POST, PUT and DELETE API with validation and role checks.'],['api-events-demo.php','Admin-only Fetch client and event form.'],['js/api-demo.js','Asynchronous list, create, edit and delete requests.'],['php/event-functions.php','Shared event validation, prepared statements and audit helper.'],['docs/Practical13-setup.md','Local setup and browser Network panel steps.'],['scripts/test-practical13.py','Repeatable authenticated API integration checks.'],['scripts/test-practical13-input.php','Database-free validation checks.']],
'skills':'PHP JSON API, HTTP methods and status codes, Fetch API, asynchronous CRUD, JSON parsing, server-side validation, CSRF protection and frontend/backend integration.',
'impl':[('1 JSON Read Endpoints','GET returns an event collection with a count or one event by ID. Event fields are selected from the MySQL database using prepared statements. Missing and malformed identifiers return explicit JSON errors.'),('2 Create and Update','POST creates an event and returns HTTP 201. PUT updates an event by ID. Both methods validate title, date, venue, category and status before database writes, then record the admin action.'),('3 Delete and Referential Checks','DELETE removes only an event that has no registrations. A referenced event returns HTTP 409 and remains available for closure through the event management page.'),('4 Access and Error Handling','Read requests are public. Mutations require a current admin role and the session CSRF token in X-CSRF-Token. Validation failures use HTTP 422, unauthorized requests use 401 or 403, and unexpected failures return a generic 500 response.'),('5 Fetch Client without Reload','The admin demo loads JSON on page open, submits JSON with Fetch and refreshes the table after each successful change. Errors appear in a live status message. Event text is rendered with textContent to avoid treating API data as HTML.')],
'evidence':'The live API passed 9 authenticated integration checks, including JSON reads, anonymous access denial, validation, create, fetch-by-ID, update, delete and missing-record handling. Live browser screenshots remain to be captured.',
'dbrows':[['Check','Observed result'],['API data source','MySQL events table through prepared queries.'],['Mutation audit','Practical 14 audit_logs and the existing event_audit table.'],['Credential handling','No API password, cookie or token is returned in JSON.']],
'testrows':[['Test input or action','Observed result'],['Database-free event validation','4 checks passed: valid data, date, category/status and length limits.'],['Practical 13 API integration suite','9 checks passed: JSON reads, access denial, validation, create, fetch, update, delete and missing record.']],
'questions':[('Q1 Do PHP endpoints return valid JSON responses','The endpoint returns JSON for successful reads and mutations, and JSON error objects for rejected requests. The runtime checks are provided but need the configured local XAMPP database.'),('Q2 Are GET and POST requests handled properly','GET supports the event collection and an individual ID. POST creates an event. PUT updates and DELETE removes an eligible event. Unsupported methods return HTTP 405.'),('Q3 Are loading, success and error states shown on the frontend','Yes. The Fetch demo displays loading text, success messages and API errors through a role=status live region.'),('Q4 Is input validation performed before database operations','Yes. The existing shared event validator checks lengths, date, category and status before prepared INSERT or UPDATE statements execute.')],
'conclusion':'The live API and authenticated CRUD workflow passed all 9 integration checks. Screenshots from the running API demo and Network panel remain to be added if required by the instructor.'
},
{
'number':14,'title':'Admin Dashboard with Role-Based Access Control, Dynamic Menu, and Audit Log',
'aim':'To create a database-driven admin dashboard with user, student, event and registration statistics, role-appropriate navigation, recent registrations and an audit trail.',
'intro':'The admin dashboard queries current counts and latest event registrations from MySQL. Admin navigation links expose student management, event management and the Practical 13 API demo. A general audit_logs table records important student and event management actions without storing passwords, session tokens or form contents.',
'files':[['File','Purpose'],['admin-dashboard.php','Admin-only statistics, registration list and recent audit activity.'],['php/admin-audit.php','Shared helper for recording administrative changes.'],['php/event-functions.php','Adds event actions to the general audit trail.'],['students-manage.php','Records add, edit, soft-delete and restore actions.'],['sql/practical14.sql','Additive audit_logs table migration.'],['css/style.css','Responsive dashboard statistics grid.'],['scripts/test-practical14.py','Repeatable role, dashboard and menu checks.'],['docs/Practical14-setup.md','Migration and role verification steps.']],
'skills':'Dashboard design, role-based access control, SQL aggregation, prepared queries, audit logging, role-based navigation and server-side authorization.',
'impl':[('1 Database Statistics','Four live counts are displayed for users, active students, events and event registrations. Counts are calculated with SQL against the current database.'),('2 Latest Registrations','The dashboard joins registrations to student and event records and shows the five most recent entries with their registration time.'),('3 Admin-Only Access and Menu','The page calls requireRole(admin) before loading dashboard data. Management links are present on the admin dashboard; faculty and student dashboards remain separately protected.'),('4 General Audit Log','The migration creates audit_logs with actor, action, target type, target ID, summary and timestamp. Student add/edit/delete/restore and event add/edit/delete operations write entries.'),('5 Safe Audit Data','Audit summaries include record names or IDs for review. They deliberately exclude password values, CSRF tokens, remember-me validators and complete submitted forms.')],
'evidence':'The additive audit_logs migration is installed. Anonymous dashboard access redirects to login. Signed-in admin checks pass for the dashboard, four statistic cards, latest registration section, audit section and management/API navigation. Student and faculty menu checks remain pending their existing passwords.',
'dbrows':[['Check','Observed result'],['Dashboard counts','Users, active students, events and registrations.'],['Recent registrations','Latest five registration rows joined to student and event titles.'],['Audit retention','Audit entries remain after a target event is deleted.'],['Migration','Additive CREATE TABLE IF NOT EXISTS; existing data is preserved.']],
'testrows':[['Test input or action','Observed result'],['Audit table migration','PASS: audit_logs created without changing existing tables.'],['Anonymous admin dashboard access','PASS: redirects to login.'],['Anonymous API demo access','PASS: redirects to login.'],['Admin dashboard, four cards, registrations, audit and menu','PASS: all 5 admin checks.'],['Student / faculty dashboard and role menus','Pending: their existing passwords were not changed.'],['Audit entries from student/event CRUD','Hooks are implemented; authenticated record-change checks remain pending.']],
'questions':[('Q1 Are dashboard values loaded dynamically from the database','Yes. Counts and registration rows are queried for each admin dashboard request.'),('Q2 Is access restricted to admin users','Yes. requireRole checks the current database role before the page runs its dashboard queries.'),('Q3 Are role-based menus working','The management and API navigation is shown on the admin dashboard. Student and faculty pages remain separate role-protected destinations.'),('Q4 Are important admin actions recorded in audit_logs','Student record changes and event CRUD actions call the shared audit helper. The migration stores the actor, action, target and time.')],
'conclusion':'The audit migration, anonymous access gate and signed-in administrator dashboard checks pass. Student and faculty navigation checks still need their existing lab passwords; event/student audit-write behavior remains to be checked with authenticated CRUD.'
},
{
'number':15,'title':'Final Integration, Testing, Documentation, GitHub Submission, and Local Deployment',
'aim':'To integrate StudentHub modules, document the local deployment, provide repeatable integration checks, and prepare the repository for a final review.',
'intro':'The portal includes authentication, student CRUD, event management, a JSON API and an admin dashboard with audit logging. Practical 15 adds a deployment guide and smoke-check script. The local XAMPP site and configured app database connection are currently available for public and unauthenticated checks.',
'files':[['File','Purpose'],['docs/Practical15-deployment.md','Local setup, integration checklist and Apache virtual-host notes.'],['scripts/check-practical15.py','Public-page and admin-demo access smoke checks.'],['docs/Practical15-test-results.json','Records whether local smoke checks ran.'],['docs/Practical13-setup.md','API setup and test guidance.'],['docs/Practical14-setup.md','Dashboard audit migration and setup steps.'],['README.md','Project index updated for Practicals 13-15.']],
'skills':'Full-stack integration, test planning, deployment documentation, accessibility review, local Apache configuration and repository hygiene.',
'impl':[('1 Integrated Modules','Existing authentication and role checks connect to student management, events and registrations. The Practical 13 API integrates event CRUD, and Practical 14 adds dashboard statistics and audit activity.'),('2 Repeatable Verification','The API test script uses a temporary event and removes it after testing. The local smoke script checks public pages and unauthenticated access to the admin demo. Existing Practical 10-12 scripts cover their prior modules.'),('3 Deployment Documentation','The guide provides migration ordering, private database configuration, poster storage setup, lab account creation and an optional local Apache virtual host.'),('4 Security and Repository Review','The guide keeps credentials, database configuration and uploaded poster files out of Git. It calls for reviewing the diff before committing and avoids claiming a GitHub submission that has not been made.'),('5 Limitations of Current Verification','Public API and local smoke checks pass. Authenticated workflows need the lab administrator password. Live screenshots and accessibility review remain to be completed.')],
'evidence':'The reports preserve the Practical 12 report structure. Practical 13 authenticated checks passed 9/9, Practical 14 admin dashboard checks passed, and Practical 15 smoke checks passed 8/8. No remote repository changes were submitted; live screenshots and student/faculty checks remain outstanding.',
'dbrows':[['Check','Current status'],['PHP source syntax','Changed PHP files checked with PHP 8.2 lint.'],['Fetch client syntax','JavaScript syntax checked with Node.'],['Local XAMPP deployment','Apache and MySQL reachable; app database connection verified.'],['GitHub submission','Existing repository identified; no push or commit made.']],
'testrows':[['Test input or action','Observed result'],['Changed PHP files','Syntax checks passed.'],['Fetch demo JavaScript','Node syntax check passed.'],['Practical 13 API integration','9/9 authenticated and public checks passed.'],['Practical 14 admin dashboard','Migration and admin checks passed; student/faculty menu checks pending their existing passwords.'],['Practical 15 smoke script','8/8 checks passed against the local site.'],['Accessibility review','Checklist and responsive checks documented; live review pending.']],
'questions':[('Q1 Are all pages properly linked and navigable','The new admin dashboard links to the student, event and API modules. The deployment guide includes a page and responsive review checklist. Live browser verification remains pending.'),('Q2 Are sessions, roles, validations and database operations integrated','Existing authentication, role checks and prepared-query CRUD remain in place. Practicals 13 and 14 connect their API and dashboard actions to the same database.'),('Q3 Are security and accessibility checklists completed','Security and accessibility review steps are documented. The interactive checklist cannot be marked complete without reviewing the running site.'),('Q4 Is project documentation sufficient for installation and testing','Practical 13, 14 and 15 setup guides describe the migration, API, local XAMPP and test steps.'),('Q5 Is the repository clean and committed','The existing repository URL is documented. No commit or remote submission was performed in this task.')],
'conclusion':'The local smoke suite and Practical 13 API suite pass, and the Practical 14 admin dashboard checks are verified. Student/faculty checks, live screenshots, accessibility review and final GitHub submission remain outstanding.'
}
]

def build(report):
    number=report['number']; story=[]
    paragraph(story,f'PRACTICAL {number}','Title9')
    paragraph(story,report['title'],'Subtitle9')
    heading(story,'AIM'); paragraph(story,report['aim'])
    heading(story,'Introduction'); paragraph(story,report['intro'])
    heading(story,'Tools and Technology'); paragraph(story,'VS Code, XAMPP, PHP, MySQLi, MariaDB, HTML, CSS, JavaScript Fetch API, browser, Git and GitHub.')
    heading(story,'Files Used'); table(story,report['files'],[220,290])
    heading(story,'Key Skills Used'); paragraph(story,report['skills'])
    story.append(PageBreak()); heading(story,'Implementation Overview')
    for title,body in report['impl']: heading(story,title); paragraph(story,body)
    story.append(PageBreak()); heading(story,'Implementation Evidence'); paragraph(story,report['evidence'])
    heading(story,'Database Results'); table(story,report['dbrows'],[250,260])
    heading(story,'Testing Summary'); table(story,report['testrows'],[250,260])
    story.append(PageBreak()); heading(story,'Screenshots of Portal Implementation')
    for relpath,caption in screenshots[number]:
        img_path=ROOT/relpath
        img=Image(str(img_path),width=500,height=1,kind='proportional')
        scale=min(500/img.imageWidth,290/img.imageHeight)
        img.drawWidth=img.imageWidth*scale; img.drawHeight=img.imageHeight*scale
        story.append(img); paragraph(story,escape(caption),'Caption9')
    story.append(PageBreak()); heading(story,'Key Questions and Answers')
    for question,answer in report['questions']: heading(story,question); paragraph(story,answer)
    heading(story,'GitHub Repository'); paragraph(story,'https://github.com/DarshilRupani/studenthub-portal<br/>Repository changes remain in the local working tree until reviewed and committed.')
    heading(story,'Conclusion'); paragraph(story,report['conclusion'])
    path=ROOT/'output'/'pdf'/f'D26DCE157_(lab{number}).pdf'
    path.parent.mkdir(parents=True,exist_ok=True)
    SimpleDocTemplate(str(path),pagesize=A4,leftMargin=46,rightMargin=39,topMargin=51,bottomMargin=47).build(story,onFirstPage=footer,onLaterPages=footer)
    print(path)

for item in reports: build(item)
