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
story.append(Paragraph('PRACTICAL 9',s['Title9']))
story.append(Paragraph('Secure User Registration with Database Insert, Duplicate Email Check,<br/>and Password Hashing',s['Subtitle9']))
h('AIM'); p('To implement secure user registration in StudentHub using PHP and MySQLi, validate input on the frontend and backend, check duplicate emails, hash passwords using password_hash(), and display appropriate messages.')
h('Introduction'); p('StudentHub was extended to store user accounts in studenthub_db. Registration details are validated before inserting them using a MySQLi prepared statement. Duplicate emails are rejected, and passwords are stored as hashes. Tests confirmed successful registration, invalid-input handling and duplicate-email rejection.')
h('Tools and Technology'); p('VS Code, XAMPP, MariaDB, phpMyAdmin, PHP, MySQLi, HTML, CSS, JavaScript, SQL, browser, Git and GitHub. MariaDB is the MySQL-compatible database server supplied with XAMPP.')
h('Files Used'); table([['File','Purpose'],['register.html','User registration form and field error messages.'],['js/validation.js','Frontend validation and registration submission.'],['php/process-registration.php','Backend validation, duplicate check, hashing and insert.'],['php/db-mysqli.php','Reusable MySQLi database connection.'],['php/db-config.php','Private database settings; excluded from Git.'],['php/get-csrf-token.php','Session CSRF token and security code.'],['sql/practical9.sql','Users and registration audit table definitions.']], [190,320])
h('Key Skills Used'); p('Secure registration, frontend and backend validation, MySQLi connectivity, prepared statements, duplicate checks, password hashing, database inserts, CSRF protection and audit logging.')
page(); h('Implementation Overview')
for title,text in [ ('1 Database and Tables','The users table stores full name, email, mobile, password hash, course, year, gender, role and creation time. user_id is an AUTO_INCREMENT primary key, and email has a UNIQUE constraint. The registration_audit table records successful account creation.'),('2 Frontend and Backend Validation','JavaScript validates the form before submission. PHP repeats the validation on the server. Both apply matching rules for name, email, mobile, password and selection fields. Password confirmation and acceptance of terms are required.'),('3 MySQLi Connectivity','db-mysqli.php reads the local database configuration and connects using the studenthub_app account. The connection uses utf8mb4 and MySQLi exception reporting. The private configuration file remains excluded from Git.'),('4 Duplicate Email Check','A prepared SELECT checks whether the email already exists. The email is trimmed and converted to lowercase. A UNIQUE constraint prevents duplicate inserts, and duplicate requests receive an appropriate email error.'),('5 Password Hashing','The password is hashed using password_hash() with PASSWORD_DEFAULT before insertion. The password_hash column uses VARCHAR(255). Plaintext passwords are not stored. password_verify() confirmed the correct test password and rejected an incorrect password.'),('6 Prepared Insert and Error Handling','User details are bound as parameters to a prepared INSERT statement. Valid input creates an account and displays a success message. Validation errors are returned against their fields. Database errors return a general message, with details logged on the server.'),('7 CSRF Token and Audit Log','A session CSRF token is generated and checked using hash_equals(). Incorrect tokens are rejected. Account creation and its audit entry are saved in one transaction, so both operations commit together.')]: h(title); p(text)
page(); h('Screenshots of Registration and Validation Results')
def figure(filename, caption, width=510):
 from PIL import Image as PILImage
 path=Path('docs/practical9-screenshots')/filename
 with PILImage.open(path) as im: w,ht=im.size
 story.append(Image(str(path),width=width,height=width*ht/w))
 story.append(Spacer(1,6)); p(caption); story.append(Spacer(1,7))
figure('invalid-form.jpg','Figure 1: Registration form displaying required-field validation errors.',350)
figure('registration-results.jpg','Figure 2: Recorded HTTP responses for valid, duplicate and invalid email registration.')
page(); h('Screenshots of Database Insert and Audit Log')
figure('database-user.jpg','Figure 3: phpMyAdmin users table structure showing the password_hash column.')
figure('audit-log.jpg','Figure 4: Successful account_created entry in registration_audit.')
page(); h('Database Structure and Registration Results')
table([['Table','Primary key','Other constraints'],['users','user_id','Unique email; year CHECK 1-4'],['registration_audit','audit_id','Foreign key referencing users.user_id']], [140,120,250])
r=json.loads(Path('docs/Practical9-test-results.json').read_text()); db=r['database_verification']
h('Valid Registration'); p('Full name: Practical Test<br/>Email: '+escape(r['email'])+'<br/>Course: BCA; year: 1<br/>Result: Welcome, Practical Test! Your StudentHub account has been created.')
h('Database Verification'); table([['Check','Observed result'],['Inserted account','User ID 1; Practical Test'],['Password storage','Hash stored instead of plaintext'],['Correct password','Verified successfully'],['Incorrect password','Rejected'],['Audit log','One account_created entry']], [250,260])
h('Testing Summary'); table([['Test input or action','Observed result'],['Valid registration','Account created successfully.'],['Duplicate email / changed email case','Rejected: this email is already registered.'],['Invalid email','Rejected with an email validation error.'],['Weak password / mismatched confirmation','Rejected with password errors.'],['Invalid course, year or role','Rejected with field validation errors.'],['Terms not accepted','Rejected: terms must be accepted.'],['Incorrect security code','Rejected with a security-code error.'],['Incorrect CSRF token','Rejected with a session error.']], [250,260])
p('Registration URL: http://localhost/studenthub-portal/register.html')
page(); h('Key Questions and Answers')
for q,a in [('Q1 Is password_hash() used before storing passwords','Yes. PHP calls password_hash() with PASSWORD_DEFAULT before inserting the account. Only the resulting hash is stored in password_hash. The stored hash was verified using password_verify().'),('Q2 Is duplicate email/username checked','Email is used as the unique identifier in the StudentHub registration form. A prepared query checks for an existing email, and a UNIQUE constraint prevents duplicate inserts. Tests rejected identical emails and duplicates with different capitalization.'),('Q3 Are frontend and backend validations consistent','JavaScript and PHP apply matching rules for names, emails, mobile numbers, passwords and allowed field values. PHP also verifies the CSRF token and security code. Invalid-input tests confirmed that backend validation rejects unsuitable data.'),('Q4 Why use a prepared statement','Prepared statements keep SQL structure separate from supplied values. User input is bound as parameters rather than concatenated into the query, protecting the insert and duplicate-email query from SQL injection.')]: h(q); p(a)
h('GitHub Repository'); p('https://github.com/DarshilRupani/studenthub-portal<br/>Practical 9 source commit: 6a89671<br/>Message: Complete Practical 9 secure MySQLi user registration')
h('Post Laboratory Work'); p('Submit the registration source files, sql/practical9.sql and this registration test report covering valid, invalid and duplicate cases. Start Apache and MySQL, import the new table definitions into studenthub_db, and use the local database configuration to reproduce the setup.')
h('Conclusion'); p('Secure user registration was implemented using MySQLi. Valid details were inserted, duplicate emails were rejected, and passwords were stored as hashes. Validation and password verification tests passed. The intermediate prepared-statement extension and the advanced CSRF token and audit log extension were completed.')
p('Verification scope: HTTP and database tests confirm the recorded results. Required-field validation was also checked in the browser. Concurrent duplicate requests and a forced audit-write failure were not tested.')
def footer(c,d):
 c.setFont('Times-Roman',10); c.drawString(46,802,'Web Development Frameworks'); c.drawString(254,802,'D26DCE157'); c.drawRightString(550,27,str(d.page))
Path('output/pdf').mkdir(parents=True, exist_ok=True)
out='output/pdf/D26DCE157_(lab9).pdf'
SimpleDocTemplate(out,pagesize=A4,leftMargin=46,rightMargin=39,topMargin=51,bottomMargin=47).build(story,onFirstPage=footer,onLaterPages=footer)
print(out)

