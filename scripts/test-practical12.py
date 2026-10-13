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

import io,hashlib,uuid
from PIL import Image
folder=Path('php/storage/event-posters')
results=[]
def check(name,ok,detail):
 assert ok,(name,detail)
 results.append(dict(case=name,result='PASS',observed=detail))
def send(c,values,file=None):
 values=dict(csrf_token=c.csrf('events-manage.php'),**values)
 if file is None:return c.request('events-manage.php',values)
 filename,content,mime=file;boundary='LabBoundary'+uuid.uuid4().hex;parts=[]
 for key,value in values.items():parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode())
 parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="poster"; filename="{filename}"\r\nContent-Type: {mime}\r\n\r\n'.encode()+content+b'\r\n');parts.append(f'--{boundary}--\r\n'.encode())
 req=urllib.request.Request(BASE+'events-manage.php',b''.join(parts),headers={'Content-Type':'multipart/form-data; boundary='+boundary})
 try:r=c.opener.open(req)
 except urllib.error.HTTPError as e:r=e
 return r.status,dict(r.headers),r.read().decode()
def imagebytes(format):
 stream=io.BytesIO();Image.new('RGB',(40,60),'#5145cd').save(stream,format=format);return stream.getvalue()
client=Client();s,h,b=client.request('events-manage.php');check('Anonymous access blocked',s==303,'Redirect to login')
client.login('student');s,h,b=client.request('events-manage.php');check('Student role blocked',s==403,'HTTP 403')
client=Client();client.login('admin');s,h,b=client.request('events-manage.php');check('Admin access and sample events',s==200 and 'Campus Sports Day' in b,'Sample database events displayed')
title='Practical Twelve Test '+str(int(time.time()))
data=dict(action='add',title=title,description='Fictional event for upload tests.',event_date='2026-11-14T10:00',venue='Computer Lab',category='technical',status='open')
png=Path('images/practical12/sample-poster.png').read_bytes();before=set(folder.glob('*'))
s,h,b=send(client,data,('original-poster.png',png,'image/png'));check('Add event with PNG poster',s==303 and 'added' in h.get('Location',''),'Event and poster saved')
newfiles=set(folder.glob('*'))-before;check('Random image renaming',len(newfiles)==1 and re.fullmatch(r'[a-f0-9]{32}\.png',next(iter(newfiles)).name),'Original name replaced by random hexadecimal filename');saved=next(iter(newfiles))
s,h,b=client.request('events-manage.php?q='+urllib.parse.quote(title));eid=re.search(r'events-manage.php\?edit=(\d+)',b).group(1)
req=client.opener.open(BASE+'event-poster.php?id='+eid);body=req.read();check('Poster endpoint serves image',req.status==200 and req.headers['Content-Type']=='image/png' and body==png,'Validated PNG returned with image content type');check('Poster response nosniff',req.headers.get('X-Content-Type-Options')=='nosniff','nosniff header present')
s,h,b=client.request('php/storage/event-posters/'+saved.name);check('Direct image access blocked',s==403,'HTTP 403 from protected directory')
s,h,b=client.request('php/storage/event-posters/');check('Upload directory browsing blocked',s==403,'HTTP 403')
s,h,b=send(client,dict(data,title='Optional Poster Test'));check('Optional poster accepted',s==303,'Event added without poster')
s,h,b=client.request('events-manage.php?q=Optional%20Poster%20Test');optionalid=re.search(r'events-manage.php\?edit=(\d+)',b).group(1)
s,h,b=send(client,data,('not-image.png',b'not an image','image/png'));check('Spoofed image MIME rejected',s==422 and 'file content and extension' in b,'Text disguised as PNG rejected')
s,h,b=send(client,data,('invalid-poster.txt',b'text','text/plain'));check('Text file rejected',s==422 and 'JPEG, PNG or WebP' in b,'Unsupported file type rejected')
s,h,b=send(client,data,('poster.php',b'<?php echo "test"; ?>','image/png'));check('Executable upload rejected',s==422,'PHP file rejected')
s,h,b=send(client,data,('poster.svg',b'<svg xmlns="http://www.w3.org/2000/svg"></svg>','image/svg+xml'));check('SVG upload rejected',s==422,'SVG not permitted')
s,h,b=send(client,data,('mismatch.jpg',png,'image/jpeg'));check('Extension mismatch rejected',s==422,'PNG bytes with JPEG extension rejected')
s,h,b=send(client,data,('large.png',png+b'0'*(2097153),'image/png'));check('Oversized poster rejected',s in [413,422] and '2 MB or smaller' in b,'More than 2 MB rejected')
stream=io.BytesIO();Image.new('RGB',(5000,1),'white').save(stream,format='PNG')
s,h,b=send(client,data,('dimensions.png',stream.getvalue(),'image/png'));check('Oversized dimensions rejected',s==422 and '4096' in b,'5000-pixel image rejected')
s,h,b=send(client,dict(data,event_date='2026-02-30T10:00',category='invalid'));check('Invalid event fields rejected',s==422 and 'valid event date' in b and 'valid category' in b,'Date and category errors shown')
s,h,b=client.request('events-manage.php',dict(data,csrf_token='bad'));check('CSRF rejected',s==403,'HTTP 403')
edit=dict(data,action='edit',event_id=eid,title=title+' Updated',status='closed',category='academic')
s,h,b=send(client,edit);check('Update event and status',s==303 and 'updated' in h.get('Location',''),'Title, category and status updated');check('Empty upload retains poster',saved.exists(),'Existing PNG retained')
s,h,b=send(client,edit,('replacement.jpg',imagebytes('JPEG'),'image/jpeg'));check('JPEG replacement accepted',s==303,'JPEG poster saved');check('Old poster removed after replacement',not saved.exists(),'Previous PNG cleaned up')
s,h,b=send(client,edit,('replacement.webp',imagebytes('WEBP'),'image/webp'));check('WebP replacement accepted',s==303,'WebP poster saved')
s,h,b=client.request('events-manage.php?q='+urllib.parse.quote(title)+'&category=academic&status=closed');check('Category and closed filter',s==200 and '1 matching event' in b and title+' Updated' in b,'Combined filters return the updated event')
s,h,b=client.request('events-manage.php?q='+urllib.parse.quote(title)+'&status=open');check('Open filter excludes closed event',s==200 and 'No events found.' in b,'Closed test event excluded from open filter')
s,h,b=client.request('php/get-events.php');public=json.loads(b);check('Campus list uses database',any(x['id']==int(eid) and x['status']=='closed' for x in public),'Updated event available in public event feed')
s,h,b=client.request('events-manage.php?q=NoSuchEvent12');check('Empty search result',s==200 and 'No events found.' in b,'No-results message shown')
s,h,b=client.request('events-manage.php?delete='+eid);check('Delete confirmation screen',s==200 and 'Confirm event deletion' in b,'Confirmation shown without deletion')
s,h,b=send(client,dict(action='delete',event_id=eid));check('Unconfirmed delete rejected',s==422,'Confirmation required')
s,h,b=send(client,dict(action='delete',event_id='1',confirm_delete='1'));check('Registered event protected',s==409 and 'has registrations' in b,'Referenced event retained; closing suggested')
filesbefore=set(folder.glob('*'))
s,h,b=send(client,dict(action='delete',event_id=eid,confirm_delete='1'));check('Delete event',s==303 and 'deleted' in h.get('Location',''),'Unregistered test event deleted');check('Poster removed on deletion',len(set(folder.glob('*')))<len(filesbefore),'Deleted event poster cleaned up')
s,h,b=client.request('event-poster.php?id='+eid);check('Deleted poster endpoint blocked',s==404,'HTTP 404')
s,h,b=client.request('events-manage.php?q='+urllib.parse.quote(title));check('Delete audit remains visible',title+' Updated' in b and 'deleted' in b and 'No events found.' in b,'Audit retains deleted event title')
s,h,b=send(client,dict(action='delete',event_id=optionalid,confirm_delete='1'));check('Temporary no-poster event removed',s==303,'Only generated test event removed')
Path('docs/Practical12-test-results.json').write_text(json.dumps(results,indent=2)+'\n')
print(str(len(results))+' checks passed')
