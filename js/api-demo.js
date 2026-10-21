// Practical 13: browser Fetch client for php/api-events.php.
(() => {
  const endpoint = 'php/api-events.php';
  const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const rows = document.getElementById('api-events');
  const loading = document.getElementById('api-loading');
  const message = document.getElementById('api-message');
  const form = document.getElementById('api-event-form');
  let editingId = null;

  async function request(url, options = {}) {
    const response = await fetch(url, { headers: { Accept: 'application/json', ...(options.body ? {'Content-Type':'application/json','X-CSRF-Token':token} : {}) }, ...options });
    const result = await response.json();
    if (!response.ok) throw new Error(result.error || `Request failed (${response.status}).`);
    return result;
  }
  function cell(row, value) { const td=document.createElement('td'); td.textContent=value ?? ''; row.append(td); return td; }
  async function loadEvents() {
    loading.textContent='Loading events…';
    try {
      const result=await request(endpoint);
      rows.replaceChildren();
      result.data.forEach(event => {
        const tr=document.createElement('tr');
        cell(tr,event.title); cell(tr,event.event_date); cell(tr,event.venue); cell(tr,event.category); cell(tr,event.status);
        const action=cell(tr,''); const edit=document.createElement('button'); edit.type='button'; edit.textContent='Edit'; edit.addEventListener('click',()=>{ editingId=event.event_id; for(const [key,value] of Object.entries(event)) { const field=form.elements.namedItem(key); if(field) field.value=key==='event_date'?event.event_date.replace(' ','T').slice(0,16):value; } form.querySelector('[type=submit]').textContent='Save changes'; form.scrollIntoView({behavior:'smooth',block:'center'}); });
        const button=document.createElement('button'); button.type='button'; button.textContent='Delete'; button.setAttribute('aria-label',`Delete ${event.title}`);
        button.addEventListener('click',async()=>{ if(!window.confirm(`Delete “${event.title}”?`)) return; try { await request(`${endpoint}?id=${event.event_id}`,{method:'DELETE',headers:{Accept:'application/json','X-CSRF-Token':token}}); message.textContent='Event deleted.'; await loadEvents(); } catch(error) { message.textContent=error.message; } }); action.append(edit,button); rows.append(tr);
      });
      loading.textContent=result.count ? `${result.count} event(s) loaded.` : 'No events found.';
    } catch(error) { loading.textContent=error.message; }
  }
  form.addEventListener('submit',async event=>{
    event.preventDefault(); message.textContent='Saving…';
    const data=Object.fromEntries(new FormData(form));
    try { await request(editingId ? `${endpoint}?id=${editingId}` : endpoint,{method:editingId?'PUT':'POST',body:JSON.stringify(data)}); form.reset(); editingId=null; form.querySelector('[type=submit]').textContent='Add event'; message.textContent='Event saved successfully.'; await loadEvents(); }
    catch(error) { message.textContent=error.message; }
  });
  loadEvents();
})();
