"""Local integration smoke checks for public pages and basic form accessibility."""
import json, urllib.error, urllib.request
from html.parser import HTMLParser
from pathlib import Path

BASE='http://localhost/studenthub-portal/'
PAGES=['home.html','about.html','events.html','contact.html','faq.html','login.php','register.html','api-events-demo.php']

class Forms(HTMLParser):
    def __init__(self): super().__init__(); self.ids=set(); self.labels=[]; self.inputs=[]
    def handle_starttag(self,tag,attrs):
        a=dict(attrs)
        if tag=='input' and a.get('id'): self.ids.add(a['id'])
        if tag=='textarea' and a.get('id'): self.ids.add(a['id'])
        if tag=='select' and a.get('id'): self.ids.add(a['id'])
        if tag=='label' and a.get('for'): self.labels.append(a['for'])
        if tag in ('input','textarea','select') and a.get('type') not in ('hidden','submit','button','reset'):
            self.inputs.append((a.get('id'),a.get('aria-label'),a.get('type')))

results=[]
for page in PAGES:
    try:
        response=urllib.request.urlopen(BASE+page,timeout=5)
        body=response.read().decode(errors='replace')
        status=response.status
        if page=='api-events-demo.php' and 'StudentHub | Login' in body:
            results.append({'case':'Admin API demo access control','result':'PASS','observed':'Unauthenticated request redirected to login.'})
            continue
        result={'case':'Page '+page,'result':'PASS' if status==200 else 'FAIL','observed':f'HTTP {status}'}
        if page=='api-events-demo.php' and status==200:
            parser=Forms(); parser.feed(body)
            missing=[control for control in parser.inputs if control[0] not in parser.labels and not control[1]]
            result={'case':'API demo form labels','result':'PASS' if not missing else 'FAIL','observed':f'{len(missing)} unlabeled controls'}
            results.append(result)
            continue
    except urllib.error.HTTPError as exc:
        result={'case':'Page '+page,'result':'FAIL','observed':f'HTTP {exc.code}'}
    except urllib.error.URLError as exc:
        detail=type(exc.reason).__name__+': '+str(exc.reason)
        blocked='WinError 10013' in detail or 'WinError 10061' in detail
        result={'case':'Page '+page,'result':'BLOCKED' if blocked else 'FAIL','observed':detail}
    except Exception as exc:
        result={'case':'Page '+page,'result':'FAIL','observed':type(exc).__name__+': '+str(exc)}
    results.append(result)

Path('docs/Practical15-test-results.json').write_text(json.dumps(results,indent=2)+'\n',encoding='utf-8')
failed=[item for item in results if item['result']=='FAIL']
blocked=[item for item in results if item['result']=='BLOCKED']
print(f"{len(results)-len(failed)-len(blocked)}/{len(results)} smoke checks passed; {len(blocked)} blocked.")
raise SystemExit(2 if blocked and not failed else bool(failed))
