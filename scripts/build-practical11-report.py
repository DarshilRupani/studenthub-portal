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


story.append(Paragraph('PRACTICAL 11',s['Title9']))
story.append(Paragraph('Student Management CRUD with Search and Filter',s['Subtitle9']))
h('AIM');p('To create a student management module in StudentHub for adding, viewing, editing, deleting, searching and filtering student records using MySQLi prepared statements.')
h('Introduction');p('A student management page was added to the admin dashboard. Student details are stored in the students table and displayed with pagination. The page provides validation and clear success or failure messages. Twenty sample students were added, along with CSV export and soft deletion using a status column.')
h('Tools and Technology');p('VS Code, XAMPP, PHP, MySQLi, MariaDB, phpMyAdmin, HTML, CSS, browser, Git and GitHub.')
h('Files Used');table([['File','Purpose'],['students-manage.php','Student form, list, edit, delete confirmation and CSV export.'],['php/student-functions.php','Validation, search filters and prepared queries.'],['css/student-management.css','Form, table and pagination styling.'],['admin-dashboard.php','Link to the student management page.'],['sql/practical11.sql','Status column and twenty fictional seed records.'],['sql/practical11-students-dump.sql','Exported students table with structure and data.'],['scripts/test-practical11.py','Repeatable CRUD, search and access tests.'],['scripts/export-practical11.php','Generates the students-only SQL dump.']], [220,290])
h('Key Skills Used');p('PHP-MySQL CRUD, prepared statements, validation, search, filters, pagination, admin access, CSV export, soft deletion and user feedback messages.')
page();h('Implementation Overview')
for title,text in [('1 Database and Sample Records','The existing students table stores student ID, full name, email, mobile, course, year and creation time. A status column was added with active and deleted values. The seed file adds twenty fictional students while retaining the existing records.'),('2 Add and View Students','The Add student form validates the entered details and uses a prepared INSERT statement. A SELECT query loads the student list. Duplicate emails and invalid fields are rejected with appropriate messages. Only admin accounts can access this module.'),('3 Edit Student Details','Edit opens the selected record in the form. Saving the changes uses a prepared UPDATE statement. The student ID and email are checked, and a success message confirms the update.'),('4 Delete Confirmation and Soft Delete','Delete opens a confirmation page displaying the student name and email. The user must confirm before submitting. The advanced soft-delete option uses UPDATE to set status to deleted instead of physically removing the row. The record is hidden from the active list and can be restored.'),('5 Search and Filters','Search works on name, email and course, including displayed course names such as B.Tech IT. Course, year and status filters can be combined. Prepared parameters are used for search values, and no-results searches display a clear message.'),('6 Pagination and CSV Export','The list displays ten records per page. Previous and Next links preserve the current search and filters. Export CSV downloads all matching records across pages. Values that could be interpreted as spreadsheet formulas are escaped.'),('7 SQL Dump and Testing','The student table structure and records were exported to a separate SQL file. The dump was imported successfully into a temporary table for verification. CRUD operations, invalid inputs, duplicate emails, access checks, search, filters, pagination and CSV export were tested.')]:h(title);p(text)
def figure(filename,caption):
 from PIL import Image as PILImage
 path=Path('docs/practical11-screenshots')/filename
 with PILImage.open(path) as im:w,ht=im.size
 story.append(Image(str(path),width=510,height=510*ht/w));story.append(Spacer(1,6));p(caption);story.append(Spacer(1,9))
page();h('Screenshots of Student List and Add Operation')
figure('student-list.jpg','Figure 1: Database student list displaying the first page of records.')
figure('add-success.jpg','Figure 2: Valid student details added successfully.')
page();h('Screenshots of Edit, Search and Filter')
figure('edit-success.jpg','Figure 3: Student details updated successfully.')
figure('search-filter.jpg','Figure 4: Search by name combined with course and year filters.')
page();h('Screenshots of Delete Confirmation and Result')
figure('delete-confirm.jpg','Figure 5: Confirmation is required before deleting the selected student.')
figure('delete-success.jpg','Figure 6: Successful soft deletion with a clear confirmation message.')
page();h('Screenshots of Soft Delete and Pagination')
figure('soft-delete.jpg','Figure 7: The deleted record remains stored and has a Restore option.')
figure('pagination.jpg','Figure 8: Second page of student records with Previous and Next links.')
page();h('Database and Export Results')
r=json.loads(Path('docs/Practical11-database-results.json').read_text())
table([['Check','Observed result'],['New fictional seed records',r['seeded_records']],['Active students after testing',r['active_students']],['Records in SQL dump',r['dump_records']],['SQL dump verification','Imported successfully into a temporary table.'],['Sample CSV','25 active students; seven columns.']], [250,260])
h('Testing Summary');table([['Test input or action','Observed result'],['Admin / student / anonymous access','Allowed / HTTP 403 / login redirect.'],['Add valid student','Student added successfully.'],['Duplicate email / invalid fields','Rejected with clear validation messages.'],['Edit student','Updated name, course and year saved.'],['Search by name / email / course','Matching records returned.'],['Displayed course name search','B.Tech IT matches its stored course code.'],['Course and year filters','Only matching course/year records returned.'],['Pagination / empty search','Correct page / No students found.'],['SQL text / literal percent search','Treated as search data; no unexpected rows.'],['Delete without confirmation','Rejected; record retained.'],['Confirmed deletion','Status changed to deleted.'],['Deleted status / Restore','Record remains available and can be restored.'],['CSV export','All matching records exported across pages.'],['Invalid CSRF / missing student','HTTP 403 / clear not-found message.']], [250,260])
p('All 26 HTTP checks passed. The twenty seed records and the existing sample students remain active. The four demonstration records remain soft-deleted.')
page();h('Key Questions and Answers')
for q,a in [('Q1 Are SELECT, INSERT, UPDATE and DELETE operations working','SELECT loads the list, INSERT adds students and UPDATE saves edits. The delete action works through the advanced soft-delete approach: status is updated to deleted instead of issuing a permanent SQL DELETE. Add, edit, delete and restore tests passed.'),('Q2 Is search by name/email/course implemented','Yes. A prepared query searches name, email and course. The displayed course name is also supported. Course, year and status filters can be applied together, and pagination retains the selected filters.'),('Q3 Are delete operations confirmed before execution','Yes. Clicking Delete opens a confirmation page with the selected student details. A confirmation checkbox and CSRF token are required before the POST request changes the status. Submitting without confirmation was rejected.')]:h(q);p(a)
h('GitHub Repository');p('https://github.com/DarshilRupani/studenthub-portal<br/>Practical 11 source commit: d16c903<br/>Message: Complete Practical 11 student CRUD search filters and soft delete')
h('Post Laboratory Work');p('Submit the student management source files, sql/practical11-students-dump.sql, the CRUD screenshots and test cases in this report. The migration and seed file is sql/practical11.sql. A sample CSV is saved in output/csv/student-list-practical11.csv.')
h('Conclusion');p('The student management module was completed using MySQLi prepared statements. Add, view, edit, confirmed deletion, search, filters and pagination worked correctly in the tests. The intermediate CSV export and advanced soft-delete extensions were completed. The SQL dump was exported and verified successfully.')
def footer(c,d):
 c.setFont('Times-Roman',10);c.drawString(46,802,'Web Development Frameworks');c.drawString(254,802,'D26DCE157');c.drawRightString(550,27,str(d.page))
Path('output/pdf').mkdir(parents=True,exist_ok=True)
out='output/pdf/D26DCE157_(lab11).pdf'
SimpleDocTemplate(out,pagesize=A4,leftMargin=46,rightMargin=39,topMargin=51,bottomMargin=47).build(story,onFirstPage=footer,onLaterPages=footer)
print(out)

