<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="manifest" href="/manifest.webmanifest">
    <title>Prise de présence</title>
    <style>
        *{box-sizing:border-box}body{font-family:Roboto,ui-sans-serif,system-ui,sans-serif;margin:0;background:#F8FAFC;color:#0F172A}main{max-width:720px;margin:auto;padding:16px}
        header{position:sticky;top:0;background:#F8FAFC;padding-bottom:12px;z-index:1}.student{display:flex;justify-content:space-between;align-items:center;gap:8px;padding:12px 0;border-bottom:1px solid #e2e8f0}
        .student span:first-child{font-weight:500}.actions{display:flex;flex-wrap:wrap;justify-content:flex-end}button,select{border:1px solid #cbd5e1;border-radius:0.75rem;padding:10px 12px;margin:2px;background:#fff;min-height:42px;color:#0F172A}button.active{background:#1E3A8A;border-color:#1E3A8A;color:white}
        .bar{position:sticky;bottom:0;background:white;padding:12px;display:flex;gap:8px;box-shadow:0 -2px 8px #0001}.bar button{flex:1;background:#1E3A8A;color:white;border-color:#1E3A8A}
        #status{font-size:.9rem;color:#475569}.offline{color:#F59E0B}@media(max-width:520px){.student{align-items:flex-start;flex-direction:column}.actions{width:100%;justify-content:stretch}.actions button{flex:1;min-width:calc(50% - 4px)}.bar{flex-wrap:wrap}.bar button{flex-basis:100%}}
    </style>
</head>
<body><main>
<header><h1>Prise de présence</h1><select id="classes" aria-label="Classe"><option>Chargement...</option></select><p id="summary"></p><p id="status"></p></header>
<section id="students"></section><div class="bar"><button id="all">Tous présents</button><button id="save">Enregistrer</button><button id="validate">Valider l'appel</button></div>
</main>
<script>
const storageKey=()=>`scolaris-attendance-${document.querySelector('#classes').value}-${new Date().toISOString().slice(0,10)}`;
let records={}, sessionId=null;
const setStatus=(text,offline=false)=>{const node=document.querySelector('#status');node.textContent=text;node.className=offline?'offline':''};
const api=async (url, options={})=>{const response=await fetch('/api'+url,{credentials:'same-origin',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},...options});if(!response.ok) throw new Error(`HTTP ${response.status}`);return response.json()};
function render(){const list=Object.values(records);const labels={PRESENT:'Présent',LATE:'En retard',ABSENT:'Absent',EXCUSED:'Excusé'};document.querySelector('#summary').textContent=`${list.length} élèves · Présents ${list.filter(x=>x.status==='PRESENT').length} · Retards ${list.filter(x=>x.status==='LATE').length} · Absents ${list.filter(x=>x.status==='ABSENT').length} · Excusés ${list.filter(x=>x.status==='EXCUSED').length}`;document.querySelector('#students').innerHTML=list.map(r=>`<div class="student"><span>${r.name}</span><span class="actions">${Object.entries(labels).map(([s,label])=>`<button class="${r.status===s?'active':''}" data-id="${r.id}" data-status="${s}" aria-label="${label} pour ${r.name}">${label}</button>`).join('')}</span></div>`).join('')}
function persist(){const snapshot={records,sessionId,classId:document.querySelector('#classes').value,className:document.querySelector('#classes').selectedOptions[0]?.textContent||''};localStorage.setItem(storageKey(),JSON.stringify(snapshot));localStorage.setItem('scolaris-attendance-last',JSON.stringify(snapshot))}
async function sync(){if(!sessionId||!navigator.onLine)return;try{await api(`/attendance/sessions/${sessionId}/sync`,{method:'POST',body:JSON.stringify({records:Object.values(records).map(r=>({student_id:r.id,status:r.status,client_operation_id:r.operationId}))})});setStatus('Synchronisé');persist()}catch(error){setStatus('Hors connexion : synchronisation en attente',true)}}
document.addEventListener('click',event=>{if(event.target.dataset.id){records[event.target.dataset.id].status=event.target.dataset.status;records[event.target.dataset.id].operationId=crypto.randomUUID();persist();render()}});
document.querySelector('#all').onclick=()=>{Object.values(records).forEach(r=>{r.status='PRESENT';r.operationId=crypto.randomUUID()});persist();render()};
document.querySelector('#save').onclick=async()=>{persist();if(!sessionId&&navigator.onLine){try{const selected=document.querySelector('#classes').value;const created=await api('/attendance/sessions',{method:'POST',body:JSON.stringify({class_room_id:selected,session_date:new Date().toISOString().slice(0,10)})});sessionId=created.id;persist();await sync()}catch(error){setStatus('Enregistré localement : synchronisation en attente',true)}}else await sync()};
document.querySelector('#validate').onclick=async()=>{if(!sessionId||!navigator.onLine){setStatus('Connexion requise pour valider',true);return}if(!window.confirm('Valider définitivement cet appel ? Les corrections nécessiteront une action contrôlée.'))return;try{await sync();await api(`/attendance/sessions/${sessionId}/validate`,{method:'POST'});setStatus("Appel validé");document.querySelector('#validate').disabled=true}catch(error){setStatus('Validation impossible : vérifiez la connexion',true)}};
async function loadClass(){const selected=document.querySelector('#classes').value;const local=JSON.parse(localStorage.getItem(storageKey())||'null');if(local){records=local.records;sessionId=local.sessionId;render();setStatus('Données locales chargées',true)}try{const students=await api(`/teacher/classes/${selected}/students`);if(!local){records=Object.fromEntries(students.map(s=>[s.id,{id:s.id,name:s.full_name,status:'PRESENT',operationId:crypto.randomUUID()}]));render();persist()}setStatus(navigator.onLine?'Connecté':'Hors connexion',!navigator.onLine);if(navigator.onLine)await sync()}catch(error){if(!local)setStatus('Classe indisponible hors connexion',true)}}
document.querySelector('#classes').onchange=loadClass;
window.addEventListener('online',()=>{setStatus('Connexion rétablie');sync()});window.addEventListener('offline',()=>setStatus('Hors connexion : les modifications restent locales',true));
const last=JSON.parse(localStorage.getItem('scolaris-attendance-last')||'null');if(last?.classId){const select=document.querySelector('#classes');select.innerHTML=`<option value="${last.classId}">${last.className||'Classe hors ligne'}</option>`;select.value=last.classId;loadClass()}
api('/teacher/classes').then(classes=>{const select=document.querySelector('#classes');select.innerHTML=classes.map(c=>`<option value="${c.id}">${c.name}</option>`).join('');if(classes.length)loadClass()}).catch(()=>{if(!last)setStatus('Impossible de charger les classes',true)});
if('serviceWorker'in navigator)navigator.serviceWorker.register('/service-worker.js');
</script></body></html>
