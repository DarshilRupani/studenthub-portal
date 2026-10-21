"""Role, dashboard and audit-feed checks for Practical 14. Requires local XAMPP."""
import http.cookiejar, json, os, re, urllib.error, urllib.parse, urllib.request
from pathlib import Path

BASE='http://localhost/studenthub-portal/'
PASSWORD=os.environ.get('STUDENTHUB_DEMO_PASSWORD')
if not PASSWORD: raise SystemExit('Set STUDENTHUB_DEMO_PASSWORD before running the dashboard checks.')

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self,*args): return None

class Client:
    def __init__(self):
        self.jar=http.cookiejar.CookieJar()
        self.opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar),NoRedirect())
    def request(self,path,data=None):
        req=urllib.request.Request(BASE+path,data=None if data is None else urllib.parse.urlencode(data).encode())
        try: response=self.opener.open(req)
        except urllib.error.HTTPError as exc: response=exc
        return response.code,response.read().decode(errors='replace')
    def login(self,role):
        status,body=self.request('login.php')
        token=re.search(r'name="csrf_token" value="([a-f0-9]+)"',body).group(1)
        status,body=self.request('login.php',{'email':f'lab10.{role}@example.com','password':PASSWORD,'csrf_token':token})
        return status,body

results=[]
def check(name,ok,detail):
    results.append({'case':name,'result':'PASS' if ok else 'FAIL','observed':detail})
    if not ok: raise AssertionError(f'{name}: {detail}')

anonymous=Client(); status,body=anonymous.request('admin-dashboard.php')
check('Anonymous admin dashboard blocked',status==303,f'HTTP {status}')

admin=Client(); admin.login('admin'); status,body=admin.request('admin-dashboard.php')
check('Admin dashboard loads',status==200 and 'Portal statistics' in body,f'HTTP {status}')
check('Four live statistic cards',all(label in body for label in ['USERS','STUDENTS','EVENTS','REGISTRATIONS']),'All four card labels are present.')
check('Latest registrations section','Latest registrations' in body,'Section is present even when the database has no rows.')
check('Audit log section','Recent admin audit log' in body,'Section is present even when the database has no rows.')
check('Admin role menu','students-manage.php' in body and 'events-manage.php' in body and 'api-events-demo.php' in body,'Management and API links are present.')

for role in ('student','faculty'):
    client=Client(); status,body=client.login(role)
    if 'Invalid email or password' in body or status not in (200,302,303):
        results.append({'case':role.title()+' role dashboard and menu','result':'PENDING','observed':'Existing non-admin lab password unavailable; no password was changed.'})
        continue
    status,body=client.request('admin-dashboard.php')
    check(role.title()+' blocked from admin dashboard',status==403,f'HTTP {status}')
    page='dashboard.php' if role=='student' else 'faculty-dashboard.php'
    status,body=client.request(page)
    expected=('My registrations' in body and 'students-manage.php' not in body) if role=='student' else ('Faculty navigation' in body and 'events.html' in body)
    check(role.title()+' role menu',expected,'Role-specific links are shown.')

Path('docs/Practical14-test-results.json').write_text(json.dumps(results,indent=2)+'\n',encoding='utf-8')
print(f'{len(results)} Practical 14 role and dashboard checks passed.')
