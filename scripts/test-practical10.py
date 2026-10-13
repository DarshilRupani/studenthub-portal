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
def check(name,condition,detail):
 assert condition,(name,detail)
 results.append(dict(case=name,result='PASS',observed=detail))
c=Client();s,h,b=c.request('dashboard.php');check('Anonymous dashboard blocked',s==303 and 'unauthorized' in h.get('Location',''),f'{s}: {h.get("Location")}')
old,(s,h,b)=c.login(password='Wrong@123');check('Failed login',s==200 and 'Invalid email or password.' in b,'Invalid email or password displayed')
old,(s,h,b)=c.login();check('Student login and redirect',s==303 and h.get('Location')=='dashboard.php',f'{s}: {h.get("Location")}');check('Session ID regenerated',old!=c.cookie('PHPSESSID'),'Session ID changed after login');check('HttpOnly and SameSite session cookie','HttpOnly' in h.get('Set-Cookie','') and 'SameSite=Lax' in h.get('Set-Cookie',''),'Cookie flags present')
s,h,b=c.request('dashboard.php');check('Student dashboard',s==200 and 'Lab Student' in b,'Student dashboard opened')
s,h,b=c.request('admin-dashboard.php');check('Student cannot access admin',s==403 and 'cannot open this dashboard' in b,'HTTP 403 Access denied')
s,h,b=c.request('logout.php');check('GET logout blocked',s==405,'HTTP 405')
s,h,b=c.request('logout.php',{'csrf_token':'bad'});check('Logout CSRF',s==403,'HTTP 403')
t=c.csrf('dashboard.php');oldsid=c.cookie('PHPSESSID');s,h,b=c.request('logout.php',{'csrf_token':t});check('Logout',s==303 and 'logout' in h.get('Location',''),'Logout redirect and cookie cleared')
s,h,b=c.request('dashboard.php');check('Dashboard blocked after logout',s==303,'Redirect to login')
for role,target in [('admin','admin-dashboard.php'),('faculty','faculty-dashboard.php')]:
 x=Client();old,(s,h,b)=x.login(role);check(role.title()+' redirect',s==303 and h.get('Location')==target,h.get('Location'));s,h,b=x.request(target);check(role.title()+' dashboard',s==200 and ('Lab '+role.title()) in b,'Correct dashboard opened');s,h,b=x.request('dashboard.php');check(role.title()+' cannot access student dashboard',s==403,'HTTP 403')
x=Client();x.login(remember=True);cookie=x.cookie('studenthub_remember');check('Remember cookie issued',bool(cookie) and len(urllib.parse.unquote(cookie))==89,'Random selector and validator cookie issued')
# Drop session cookie to simulate reopening a browser.
for q in list(x.jar):
 if q.name=='PHPSESSID':x.jar.clear(q.domain,q.path,q.name)
s,h,b=x.request('dashboard.php');newcookie=x.cookie('studenthub_remember');check('Remember-me restores login',s==200 and 'Lab Student' in b,'Dashboard opened with remember cookie only');check('Remember token rotates',newcookie!=cookie,'New remember token issued')
# Replaying the previous token must fail.
y=Client()
for q in x.jar:
 if q.name=='studenthub_remember':
  import copy
  stale=copy.copy(q);stale.value=cookie;y.jar.set_cookie(stale)
s,h,b=y.request('dashboard.php');check('Old token replay blocked',s==303,'Old token rejected')
# Modified validator fails.
y=Client()
for q in x.jar:
 if q.name=='studenthub_remember':
  stale=copy.copy(q);stale.value=q.value[:-1]+('0' if q.value[-1]!='0' else '1');y.jar.set_cookie(stale)
s,h,b=y.request('dashboard.php');check('Tampered remember token blocked',s==303,'Tampered token rejected')
t=x.csrf('dashboard.php');x.request('logout.php',{'csrf_token':t});check('Remember cookie cleared on logout',x.cookie('studenthub_remember') is None,'Remember cookie removed')
# Two-second local timeout allows testing without a 15-minute wait.
p=Path('php/auth-config.local.php')
if p.exists(): raise SystemExit('Remove the local timeout override before running tests.')
p.write_text("<?php return ['timeout'=>2];")
try:
 x=Client();x.login(remember=True);time.sleep(3);s,h,b=x.request('dashboard.php');check('Session timeout',s==303 and 'timeout' in h.get('Location',''),'Expired session redirected to login');check('Timeout clears remember cookie',x.cookie('studenthub_remember') is None,'Remember cookie removed on timeout')
finally:p.unlink(missing_ok=True)
Path('docs/Practical10-test-results.json').write_text(json.dumps(results,indent=2)+'\n')
print(json.dumps(results,indent=2))

