from pathlib import Path
import json
from reportlab.platypus import SimpleDocTemplate, Paragraph, Table, TableStyle, PageBreak, Spacer, Image
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER
from reportlab.lib.pagesizes import A4
from xml.sax.saxutils import escape
s=getSampleStyleSheet()
s.add(ParagraphStyle(name='Body9',fontName='Times-Roman',fontSize=11,leading=13.4,spaceAfter=9))
s.add(ParagraphStyle(name='Heading9',fontName='Helvetica-Bold',fontSize=11,leading=14,spaceBefore=5,spaceAfter=7))
s.add(ParagraphStyle(name='Title9',fontName='Helvetica-Bold',fontSize=16,leading=19,alignment=TA_CENTER,spaceAfter=14))
s.add(ParagraphStyle(name='Subtitle9',fontName='Times-Bold',fontSize=12,leading=15,alignment=TA_CENTER,spaceAfter=12))
s.add(ParagraphStyle(name='Cell9',fontName='Times-Roman',fontSize=10,leading=12))
story=[]
def p(t): story.append(Paragraph(t,s['Body9']))
def h(t): story.append(Paragraph(t,s['Heading9']))
def page(): story.append(PageBreak())
def table(rows,widths):
 t=Table([[Paragraph(escape(str(v)),s['Cell9']) for v in row] for row in rows],colWidths=widths,repeatRows=1)
 t.setStyle(TableStyle([('BACKGROUND',(0,0),(-1,0),colors.HexColor('#eeeeee')),('GRID',(0,0),(-1,-1),.4,colors.HexColor('#cccccc')),('VALIGN',(0,0),(-1,-1),'TOP'),('TOPPADDING',(0,0),(-1,-1),5),('BOTTOMPADDING',(0,0),(-1,-1),5)])); story.append(t); story.append(Spacer(1,12))


story.append(Paragraph('PRACTICAL 12',s['Title9']))
story.append(Paragraph('Event Management CRUD with Poster Upload and File Validation',s['Subtitle9']))
h('AIM');p('To develop an admin event management module in StudentHub with add, view, edit and delete operations, along with secure poster upload and file validation.')
h('Introduction');p('The admin dashboard provides a Manage events page. Event details and poster filenames are stored in MySQL. The form validates the event fields and uploaded image before saving. Category and open/closed filters, random image names, upload directory protection and an audit log are also included.')
h('Tools and Technology');p('VS Code, XAMPP, PHP, MySQLi, MariaDB, Bootstrap 5.3.3, HTML, CSS, JavaScript, browser, Git and GitHub.')
h('Files Used');table([['File','Purpose'],['events-manage.php','Event form, list, edit and confirmed deletion.'],['php/event-functions.php','Prepared queries, validation, posters and audit log.'],['event-poster.php','Displays a poster using its database event ID.'],['php/get-events.php','Loads database events for the campus event page.'],['css/event-management.css','Event form and list styling with Bootstrap.'],['sql/practical12.sql','Event columns, sample records and audit table.'],['sql/practical12-events-dump.sql','Events table structure and sample data.'],['scripts/setup-practical12.php','Creates the protected poster directory.'],['scripts/test-practical12.py','Repeatable CRUD and upload validation tests.']], [220,290])
h('Key Skills Used');p('PHP-MySQL CRUD, prepared statements, image upload, server-side validation, event status, admin access, filters and feedback messages.')
page();h('Implementation Overview')
for title,text in [('1 Database and Admin Access','The events table stores title, description, event date, venue, category, status and optional poster filename. The module is linked from the admin dashboard. Anonymous visitors are redirected to login and student accounts are denied access.'),('2 Add, View and Edit Events','The form checks title, venue, date, category and status. MySQLi prepared INSERT, SELECT and UPDATE statements save and display the records. Editing without a new image keeps the current poster. Changes are also displayed on the public campus event page.'),('3 Poster Type and Size Validation','JPEG, PNG and WebP images are accepted up to 2 MB. The server checks actual file size, MIME type, matching extension and image contents. Image dimensions must not exceed 4096 by 4096 pixels. Text, PHP, SVG, mismatched and oversized files are rejected with clear messages.'),('4 Delete Confirmation','Delete opens a confirmation page before making changes. Confirmation and a valid CSRF token are required. An unregistered event is removed with a prepared DELETE statement. Events with registrations are retained and can be marked closed instead.'),('5 Category and Status Filters','Search matches title or venue. Category and open/closed status filters can be combined with search. The list displays the matching records or a no-results message.'),('6 Image Renaming and Directory Protection','Each uploaded image receives a random hexadecimal filename. Direct access to the upload directory is blocked by its Apache configuration. Posters are served through the event ID. Replaced and deleted poster files are removed after a successful database commit.'),('7 Audit Log and SQL Dump','Add, update and delete actions are recorded in event_audit with the event title, admin ID, time and before/after values. Database changes and audit entries use a transaction. The events-only SQL dump was exported and imported into a temporary table for verification.')]:h(title);p(text)
def figure(filename,caption):
 from PIL import Image as PILImage
 path=Path('docs/practical12-screenshots')/filename
 with PILImage.open(path) as im:w,ht=im.size
 story.append(Image(str(path),width=510,height=510*ht/w));story.append(Spacer(1,6));p(caption);story.append(Spacer(1,9))
page();h('Screenshots of Add Operation and Poster')
figure('add-success.jpg','Figure 1: Event added successfully with a valid PNG poster.')
figure('poster-preview.jpg','Figure 2: Saved event and poster displayed in the database event list.')
page();h('Screenshots of Edit and Filters')
figure('edit-success.jpg','Figure 3: Event details updated successfully.')
figure('category-status-filter.jpg','Figure 4: Search combined with academic category and closed status.')
page();h('Screenshots of Upload Validation')
figure('invalid-type.jpg','Figure 5: Unsupported poster file rejected with a validation message.')
figure('invalid-size.jpg','Figure 6: Poster larger than 2 MB rejected.')
page();h('Screenshots of Delete Confirmation and Audit Log')
figure('delete-confirm.jpg','Figure 7: Confirmation is required before deleting the selected event.')
figure('audit-log.jpg','Figure 8: Audit entries show add, update and delete actions from testing.')
page();h('Database Results')
r=json.loads(Path('docs/Practical12-database-results.json').read_text())
table([['Check','Observed result'],['Records in events table and SQL dump',r['event_records']],['Events with posters',r['events_with_posters']],['SQL dump verification','Imported successfully into a temporary table.'],['Audit actions','Added, updated and deleted entries recorded.']], [250,260])
h('Testing Summary');table([['Test input or action','Observed result'],['Admin / student / anonymous access','Allowed / HTTP 403 / login redirect.'],['Add with poster / no poster','Both valid events saved.'],['PNG, JPEG and WebP images','Accepted with random filenames.'],['Text, PHP, SVG and spoofed image','Rejected with validation messages.'],['Wrong extension / file above 2 MB','Rejected.'],['Image above 4096 pixels / invalid fields','Rejected.'],['Direct upload directory access','HTTP 403; poster endpoint works.'],['Edit without poster / replace poster','Old image retained / replaced image cleaned up.'],['Search, category and open/closed filters','Matching records displayed.'],['Campus event page','Updated database event returned.'],['Unconfirmed delete / invalid CSRF','Rejected; event retained.'],['Event with registrations','Deletion blocked; closing suggested.'],['Confirmed delete','Event and its poster removed.'],['Deleted event audit','Entry remains after deletion.']], [250,260])
p('All 36 HTTP checks passed. Only temporary test events were deleted. Six sample events remain in the database, including the workshop demonstration event.')
page();h('Key Questions and Answers')
for q,a in [('Q1 Are add, update and delete functionalities implemented correctly','Yes. Prepared INSERT, UPDATE and DELETE statements perform these operations. Valid changes were saved, deletion required confirmation and registered events were protected.'),('Q2 Is the UI linked with the database correctly','Yes. The admin list loads records from MySQL. Form changes update the stored records, and the campus event page also loads events from the database.'),('Q3 Are poster uploads validated for type and size','Yes. Actual MIME type, matching extension, file size and image contents are checked on the server. JPEG, PNG and WebP files up to 2 MB are accepted. Invalid type and size tests were rejected.'),('Q4 Are success and failure messages displayed','Yes. Add, update and delete actions display success messages. Invalid fields, rejected posters, missing confirmation and registered-event deletion display appropriate error messages.')]:h(q);p(a)
h('GitHub Repository');p('https://github.com/DarshilRupani/studenthub-portal<br/>Practical 12 source commit: 9bc9bff<br/>Message: Complete Practical 12 event CRUD and validated poster uploads')
h('Conclusion');p('The event management module was completed with CRUD operations, validated poster upload and admin access. Category and open/closed filters were added. Random image renaming, upload directory protection and audit logging were also implemented. The test cases passed and the SQL dump was verified.')
def footer(c,d):
 c.setFont('Times-Roman',10);c.drawString(46,802,'Web Development Frameworks');c.drawString(254,802,'D26DCE157');c.drawRightString(550,27,str(d.page))
Path('output/pdf').mkdir(parents=True,exist_ok=True)
out='output/pdf/D26DCE157_(lab12).pdf'
SimpleDocTemplate(out,pagesize=A4,leftMargin=46,rightMargin=39,topMargin=51,bottomMargin=47).build(story,onFirstPage=footer,onLaterPages=footer)
print(out)
