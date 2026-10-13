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

story.append(Paragraph('PRACTICAL 10',s['Title9']))
story.append(Paragraph('Secure Login/Logout with Sessions, Role-Based Access,<br/>Timeout, and Remember-Me Token',s['Subtitle9']))
h('AIM'); p('To implement secure login and logout in StudentHub using PHP sessions, verify passwords, protect dashboards according to user roles, handle session timeout, and add a secure remember-me option.')
h('Introduction'); p('The StudentHub login form was connected to the users table created in Practical 9. Passwords are checked using password_verify(). After login, the session ID is regenerated and the user is redirected to the appropriate dashboard. Logout, unauthorized access and inactivity timeout were tested using sample accounts.')
h('Tools and Technology'); p('VS Code, XAMPP, PHP, MySQLi, MariaDB, phpMyAdmin, HTML, CSS, browser, Git and GitHub.')
h('Files Used'); table([['File','Purpose'],['login.php','Login form, password verification and role redirect.'],['php/auth.php','Session settings, timeout, role checks and remember tokens.'],['dashboard.php','Protected student dashboard.'],['admin-dashboard.php / faculty-dashboard.php','Protected admin and faculty dashboards.'],['dashboard-router.php','Opens the dashboard matching the account role.'],['logout.php','POST logout, token revocation and session destruction.'],['php/access-denied.php','Unauthorized role message.'],['sql/practical10.sql','Admin role, last_login and remember_tokens table.'],['scripts/seed-practical10.php','Creates hashed student, admin and faculty lab accounts.']], [235,275])
h('Key Skills Used'); p('PHP sessions, cookies, password verification, session ID regeneration, role-based access, inactivity timeout, prepared statements, token hashing and logout.')
page(); h('Implementation Overview')
for title,text in [('1 Session Settings','PHP sessions use strict mode and cookie-only session IDs. Cookies are HttpOnly and SameSite=Lax. The Secure flag is enabled when the site uses HTTPS. Protected responses use Cache-Control: no-store.'),('2 Login and Password Verification','A prepared query finds the user by email. password_verify() checks the supplied password against the stored hash. Incorrect credentials display Invalid email or password. Successful login calls session_regenerate_id(true) before storing the user ID, name, role and activity time.'),('3 Role-Based Dashboards','Student, admin and faculty accounts open separate dashboards. Each dashboard checks the logged-in user and current database role before displaying content. A student opening the admin dashboard receives HTTP 403. Visitors without a session are sent to the login page.'),('4 Logout','The logout button submits a POST request with a CSRF token. The remember token is revoked, session data is cleared, the session cookie is expired and session_destroy() is called. The login page displays a logout confirmation.'),('5 Session Timeout','The server checks the time since the last authenticated request. After 15 minutes of inactivity, it clears the session and remember cookie and returns to login. A temporary two-second setting was used to test and capture timeout, then removed.'),('6 Last-Login Timestamp','The users table contains a last_login column. Successful password login and remember-me restoration update this timestamp. Database checks confirmed timestamps for all three lab accounts.'),('7 Remember-Me Token','Remember me creates a random selector and validator using random_bytes(). The database stores the SHA-256 hash of the validator with a 30-day expiry. The token is verified and rotated when restoring a session. Old and modified tokens are rejected. Logout and timeout revoke the token.')]: h(title);p(text)
def figure(filename,caption):
 from PIL import Image as PILImage
 path=Path('docs/practical10-screenshots')/filename
 with PILImage.open(path) as im:w,ht=im.size
 story.append(Image(str(path),width=510,height=510*ht/w));story.append(Spacer(1,6));p(caption);story.append(Spacer(1,9))
page();h('Screenshots of Successful Login and Role-Based Redirects')
figure('student-login.jpg','Figure 1: Successful student login displaying the student dashboard.')
figure('admin-login.jpg','Figure 2: Successful admin login displaying the admin dashboard.')
page();h('Screenshots of Failed Login and Logout')
figure('failed-login.jpg','Figure 3: Incorrect password displaying the failed-login message.')
figure('logout.jpg','Figure 4: Successful logout returning the user to the login page.')
page();h('Screenshots of Timeout and Unauthorized Role Access')
figure('timeout.jpg','Figure 5: Expired session displaying the inactivity-timeout message.')
figure('unauthorized-role.jpg','Figure 6: A student account is blocked from opening the admin dashboard.')
page();h('Unauthorized Access and Testing Summary')
figure('unauthorized-login.jpg','Figure 7: Opening the dashboard without login redirects to the login page.')
table([['Test input or action','Observed result'],['Correct student / admin / faculty login','Redirected to the matching dashboard.'],['Incorrect password','Invalid email or password displayed.'],['Session ID after login','Changed successfully.'],['Session cookie settings','HttpOnly and SameSite=Lax confirmed.'],['Anonymous / incorrect-role access','Login redirect / HTTP 403.'],['Logout / dashboard after logout','Session closed / login required.'],['Session timeout','Session expired; remember cookie cleared.'],['Remember me / token rotation','Login restored; a new token was issued.'],['Old / modified remember token','Rejected.'],['Last-login timestamp','Updated for all three lab accounts.']], [245,265])
p('All 25 HTTP authentication checks passed. The normal session timeout is 15 minutes. Local tests used HTTP; the HTTPS Secure-cookie flag was not tested in a browser.')
page();h('Key Questions and Answers')
for q,a in [('Q1 Are sessions started and destroyed securely','Sessions start with strict mode and cookie-only IDs. HttpOnly and SameSite settings are applied, and the ID is regenerated after login. Logout clears the session, expires the cookie and calls session_destroy().'),('Q2 Is password_verify() used correctly','Yes. The supplied login password is checked against password_hash from the users table. Correct credentials allow login, while incorrect credentials display a general error message.'),('Q3 Is unauthorized dashboard access blocked','Yes. PHP checks authentication and the database role before rendering a dashboard. Anonymous users must log in, and users with the wrong role receive HTTP 403.'),('Q4 Is session timeout implemented','Yes. The last_activity value is checked on authenticated requests. After 15 minutes of inactivity, the session is destroyed and the user is asked to log in again.'),('Q5 Are admin and student users redirected to appropriate dashboards','Yes. Student accounts open dashboard.php and admin accounts open admin-dashboard.php. Faculty accounts have their own dashboard. All three redirects were tested.')]:h(q);p(a)
h('GitHub Repository');p('https://github.com/DarshilRupani/studenthub-portal<br/>Practical 10 source commit: aebd4ed<br/>Message: Complete Practical 10 session login logout and role-based access')
h('Post Laboratory Work');p('Submit the login and session source files, sql/practical10.sql and this report containing screenshots for login success, failed login, logout, timeout and unauthorized access. Use the seeded sample accounts to repeat the tests.')
h('Conclusion');p('Secure login and logout were implemented using PHP sessions. Role-based dashboard access, password verification and inactivity timeout worked correctly in the tests. The intermediate last-login timestamp and advanced remember-me token extensions were also completed.')
def footer(c,d):
 c.setFont('Times-Roman',10);c.drawString(46,802,'Web Development Frameworks');c.drawString(254,802,'D26DCE157');c.drawRightString(550,27,str(d.page))
Path('output/pdf').mkdir(parents=True,exist_ok=True)
out='output/pdf/D26DCE157_(lab10).pdf'
SimpleDocTemplate(out,pagesize=A4,leftMargin=46,rightMargin=39,topMargin=51,bottomMargin=47).build(story,onFirstPage=footer,onLaterPages=footer)
print(out)
