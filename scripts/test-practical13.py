"""Practical 13 JSON API integration checks. Requires a running local lab database."""
import http.cookiejar, json, os, re, urllib.error, urllib.parse, urllib.request, uuid
from pathlib import Path

BASE='http://localhost/studenthub-portal/'
PASSWORD=os.environ.get('STUDENTHUB_DEMO_PASSWORD')
if not PASSWORD: raise SystemExit('Set STUDENTHUB_DEMO_PASSWORD before running the API checks.')

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self,*args): return None

jar=http.cookiejar.CookieJar()
opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar),NoRedirect())
def request(path,method='GET',data=None,token=None):
    headers={'Accept':'application/json'}
    body=None
    if data is not None:
        body=json.dumps(data).encode()
        headers['Content-Type']='application/json'
    if token: headers['X-CSRF-Token']=token
    req=urllib.request.Request(BASE+path,data=body,headers=headers,method=method)
    try: response=opener.open(req)
    except urllib.error.HTTPError as exc: response=exc
    return response.code,response.read().decode(errors='replace')

def check(results,name,condition,detail):
    if not condition: raise AssertionError(f'{name}: {detail}')
    results.append({'case':name,'result':'PASS','observed':detail})

results=[]
status,body=request('php/api-events.php')
check(results,'Public GET JSON',status==200 and isinstance(json.loads(body).get('data'),list),f'HTTP {status}, JSON event list returned')
status,body=request('php/api-events.php?id=invalid')
check(results,'Invalid identifier',status==400,f'HTTP {status}')
status,body=request('php/api-events.php','POST',{})
check(results,'Anonymous mutation denied',status==401,f'HTTP {status}')

login_page=opener.open(BASE+'login.php').read().decode()
csrf=re.search(r'name="csrf_token" value="([a-f0-9]+)"',login_page).group(1)
login_data=urllib.parse.urlencode({'email':'lab10.admin@example.com','password':PASSWORD,'csrf_token':csrf}).encode()
login=urllib.request.Request(BASE+'login.php',data=login_data)
try: opener.open(login)
except urllib.error.HTTPError as exc:
    if exc.code not in (303,302): raise

demo=opener.open(BASE+'api-events-demo.php').read().decode()
token=re.search(r'<meta name="csrf-token" content="([a-f0-9]+)"',demo).group(1)
suffix=uuid.uuid4().hex[:8]
payload={'title':'Practical 13 API '+suffix,'description':'Temporary integration record.','event_date':'2027-02-14T10:00','venue':'Computer Lab','category':'technical','status':'open'}
event_id=None
try:
    status,body=request('php/api-events.php','POST',payload,token)
    created=json.loads(body)
    check(results,'Admin JSON create',status==201 and created.get('id'),f'HTTP {status}')
    event_id=created['id']
    status,body=request('php/api-events.php?id='+str(event_id))
    check(results,'GET one event',status==200 and json.loads(body)['data']['event_id']==event_id,f'HTTP {status}')
    updated={**payload,'title':payload['title']+' updated','status':'closed'}
    status,body=request('php/api-events.php?id='+str(event_id),'PUT',updated,token)
    check(results,'Admin JSON update',status==200 and json.loads(body)['data']['status']=='closed',f'HTTP {status}')
    status,body=request('php/api-events.php?id='+str(event_id),'DELETE',token=token)
    check(results,'Admin JSON delete',status==200,f'HTTP {status}')
    event_id=None
    status,body=request('php/api-events.php?id=99999999','PUT',updated,token)
    check(results,'Missing event update',status==404,f'HTTP {status}')
    status,body=request('php/api-events.php','POST',{'title':'x'},token)
    check(results,'Invalid fields',status==422,f'HTTP {status}')
finally:
    if event_id:
        try: request('php/api-events.php?id='+str(event_id),'DELETE',token=token)
        except Exception: pass

Path('docs/Practical13-test-results.json').write_text(json.dumps(results,indent=2)+'\n',encoding='utf-8')
print(f'{len(results)} Practical 13 checks passed.')
