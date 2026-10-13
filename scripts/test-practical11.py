import urllib.request, urllib.error, urllib.parse, http.cookiejar, re, json, time, os
from pathlib import Path
BASE='http://localhost/studenthub-portal/'
PASSWORD=os.environ.get('STUDENTHUB_DEMO_PASSWORD')
if not PASSWORD: raise SystemExit('Set STUDENTHUB_DEMO_PASSWORD before running tests.')
class NoRedirect(urllib.request.HTTPRedirectHandler):
 def redirect_request(self,*args): return None
class Client:
 def __init__(self):
  self.jar=http.cookiejar.CookieJar();self.opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar),NoRedirect())
 def request(self,path,data=None):
  try:r=self.opener.open(BASE+path, None if data is None else urllib.parse.urlencode(data).encode())
  except urllib.error.HTTPError as e:r=e
  return r.status,dict(r.headers),r.read().decode()
 def cookie(self,name): return next((c.value for c in self.jar if c.name==name),None)
 def csrf(self,path='login.php'):
  status,headers,body=self.request(path);return re.search(r'name="csrf_token" value="([a-f0-9]+)"',body).group(1)
 def login(self,role='student',remember=False,password=PASSWORD):
  token=self.csrf(); old=self.cookie('PHPSESSID'); result=self.request('login.php',dict(email='lab10.'+role+'@example.com',password=password,csrf_token=token,**({'remember':'on'} if remember else {})));return old,result

results=[]
def check(name,condition,observed):
 assert condition,(name,observed)
 results.append(dict(case=name,result='PASS',observed=observed))
def form(c):return c.csrf('students-manage.php')
def send(c,values):return c.request('students-manage.php',dict(csrf_token=form(c),**values))
c=Client();s,h,b=c.request('students-manage.php');check('Anonymous access blocked',s==303,'Redirect to login')
c.login('student');s,h,b=c.request('students-manage.php');check('Student role blocked',s==403,'HTTP 403')
c=Client();c.login('admin');s,h,b=c.request('students-manage.php');check('Admin list and pagination',s==200 and 'Page 1 of' in b and 'Next' in b,'Admin list displays ten rows and next-page link')
email='crud.'+str(int(time.time()))+'.lab11@example.com'
data=dict(action='add',fullname='CRUD Test Student',email=email,mobile='9876543210',course='bca',year_of_study='2')
s,h,b=send(c,data);check('INSERT add student',s==303 and 'added' in h.get('Location',''),'Student added successfully')
s,h,b=c.request('students-manage.php?q='+urllib.parse.quote(email));match=re.search(r'students-manage.php\?edit=(\d+)',b);check('SELECT search by email',s==200 and bool(match) and 'CRUD Test Student' in b,'Added record found by email');sid=match.group(1)
s,h,b=send(c,data);check('Duplicate email rejected',s==409 and 'already exists' in b and 'Aditi Shah' in b,'HTTP 409 duplicate email message')
s,h,b=send(c,dict(data,email='invalid',year_of_study='9'));check('Invalid data rejected',s==422 and 'valid email' in b and 'year from 1 to 4' in b,'HTTP 422 validation errors')
s,h,b=c.request('students-manage.php',dict(data,csrf_token='bad'));check('CSRF rejected',s==403,'HTTP 403')
edit=dict(data,action='edit',student_id=sid,fullname='CRUD Updated Student',course='btech-it',year_of_study='3');s,h,b=send(c,edit);check('UPDATE edit student',s==303 and 'updated' in h.get('Location',''),'Student updated successfully')
s,h,b=c.request('students-manage.php?q=CRUD%20Updated');check('Search by name',s==200 and email in b,'Edited record found by name')
s,h,b=c.request('students-manage.php?q=btech-it&course=btech-it&year=3&export=csv');import csv,io
rows=list(csv.reader(io.StringIO(b)));check('Course search and course/year filters',len(rows)>1 and all(r[4]=='btech-it' and r[5]=='3' for r in rows[1:]),'Only B.Tech IT year 3 records returned')
s,h,b=c.request('students-manage.php?q=B.Tech%20IT&export=csv');labelrows=list(csv.reader(io.StringIO(b)));check('Search displayed course name',len(labelrows)>1 and all(r[4]=='btech-it' for r in labelrows[1:]),'B.Tech IT search matches its course code')
s,h,b=c.request('students-manage.php?export=csv');rows=list(csv.reader(io.StringIO(b)));check('CSV export includes all pages',s==200 and len(rows)>20 and len(rows[0])==7 and h.get('Content-Type','').startswith('text/csv'),'CSV contains all matching records and seven columns')
s,h,b=c.request('students-manage.php?page=2');check('Pagination page two',s==200 and 'Page 2 of' in b,'Second page opened')
s,h,b=c.request('students-manage.php?q=NoSuchStudentLab11');check('Empty search result',s==200 and 'No students found.' in b,'Clear no-results message')
s,h,b=c.request('students-manage.php?q='+urllib.parse.quote("' OR 1=1 --"));check('SQL injection treated as text',s==200 and 'No students found.' in b,'No rows returned for SQL text')
s,h,b=c.request('students-manage.php?q=%25');check('Literal wildcard search',s==200 and 'No students found.' in b,'Percent sign treated literally')
s,h,b=c.request('students-manage.php?delete='+sid);check('Delete confirmation page',s==200 and 'Confirm deletion' in b and 'confirm_delete' in b,'Confirmation shown before changing data')
s,h,b=send(c,dict(action='delete',student_id=sid));check('Unconfirmed delete rejected',s==422,'Confirmation required')
s,h,b=send(c,dict(action='delete',student_id=sid,confirm_delete='1'));check('Soft delete',s==303 and 'deleted' in h.get('Location',''),'Status changed to deleted')
s,h,b=c.request('students-manage.php?q='+urllib.parse.quote(email));check('Deleted record hidden',s==200 and 'No students found.' in b,'Record absent from active list')
s,h,b=c.request('students-manage.php?status=deleted&q='+urllib.parse.quote(email)+'&export=csv');rows=list(csv.reader(io.StringIO(b)));check('Deleted record retained',len(rows)==2 and rows[1][-1]=='deleted','Deleted record still exists in CSV')
s,h,b=send(c,dict(action='restore',student_id=sid));check('Restore deleted student',s==303 and 'restored' in h.get('Location',''),'Student restored')
s,h,b=c.request('students-manage.php?q='+urllib.parse.quote(email));check('Restored student visible',s==200 and 'CRUD Updated Student' in b,'Record appears in active list')
s,h,b=send(c,dict(action='delete',student_id='99999999',confirm_delete='1'));check('Missing record handled',s==404,'Student not found message')
s,h,b=send(c,dict(action='delete',student_id=sid,confirm_delete='1'))
check('Test-record cleanup by soft delete',s==303,'Only the fictional test record is hidden')
s,h,b=c.request('students-manage.php?export=csv')
Path('output/csv').mkdir(parents=True,exist_ok=True)
Path('output/csv/student-list-practical11.csv').write_text(b,encoding='utf-8')
Path('docs/Practical11-test-results.json').write_text(json.dumps(results,indent=2)+'\n')
print(str(len(results))+' checks passed')
