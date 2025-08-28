















<!-- Mobile Bottom Bar: nur auf Handy sichtbar -->
<!-- Mobile Bottom Bar + Profil-Fullscreen -->


<!--
  Hunthub Mobile Bottom Bar – v7 (data-open-chat bridge)
  ✔ Namespaced (hhbb-*) – keine Klassennamen-Konflikte
  ✔ Nutzt BASE wie auf Desktop, API-Pfade relativ zu BASE
  ✔ Klick auf Kontakt: simuliert Click auf vorhandenes `[data-open-chat="UID"]` (wie Desktop)
     → fällt zurück auf window.openChat / Events
  Einbau: diesen Block direkt vor </body> einfügen. Für Live kannst du die Desktop-Sichtbarkeit
  via @media unten wieder abschalten.
-->

<script>
// BASE wie in deiner Desktop-Version
const HHBB_BASE = '<?= rtrim($APP_BASE ?? "", "/") ?>';
</script>

<style>
  :root { --hhbb-h: 64px; }
  .hhbb-wrap{position:fixed;left:0;right:0;bottom:0;z-index:999999990}
  .hhbb-bar{height:var(--hhbb-h);padding-bottom:env(safe-area-inset-bottom);display:flex;gap:10px;align-items:center;justify-content:space-around;background:linear-gradient(180deg,rgba(12,12,12,.65) 0%,rgba(5,5,5,.95) 100%);border-top:1px solid rgba(255,255,255,.08);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px)}
  .hhbb-btn{position:relative;appearance:none;border:0;background:transparent;color:#ededed;display:flex;flex-direction:column;align-items:center;gap:4px;padding:10px 12px;border-radius:14px;font-size:12px}
  .hhbb-icon{font-size:22px;opacity:.95}
  .hhbb-label{opacity:.85;font-weight:700;letter-spacing:.2px}
  .hhbb-badge{position:absolute;top:4px;right:6px;min-width:18px;height:18px;padding:0 5px;border-radius:10px;background:#d12;color:#fff;font-size:11px;display:flex;align-items:center;justify-content:center;box-shadow:0 0 0 2px rgba(0,0,0,.7)}
  .hhbb-badge.is-hidden{display:none}
  /* Für Live auskommentieren, damit am Desktop ausgeblendet wird
  @media(min-width:1024px){.hhbb-wrap{display:none}}
  */

  .hhbb-overlay{position:fixed;inset:0;background:rgba(0,0,0,.6);backdrop-filter:blur(2px);-webkit-backdrop-filter:blur(2px);opacity:0;pointer-events:none;transition:opacity .18s ease;z-index:999999980}
  .hhbb-overlay.hhbb-open{opacity:1;pointer-events:auto}
  .hhbb-sheet{position:fixed;left:0;right:0;bottom:0;transform:translateY(110%);transition:transform .22s ease;z-index:999999995}
  .hhbb-sheet.hhbb-open{transform:translateY(0)}
  .hhbb-panel{background:linear-gradient(180deg,#0c0c0c 0%,#0a0a0a 100%);color:#f5f5f5;border-top:1px solid rgba(255,255,255,.08);border-radius:16px 16px 0 0;padding:10px 12px 12px;max-height:min(72vh,560px);box-shadow:0 -10px 30px rgba(0,0,0,.4)}
  .hhbb-hd{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:4px 4px 8px}
  .hhbb-hd h3{margin:0;font-size:16px;letter-spacing:.4px;text-transform:uppercase;opacity:.95}
  .hhbb-close{appearance:none;border:0;background:rgba(255,255,255,.06);color:#eee;padding:8px 10px;border-radius:12px;font-weight:600}
  .hhbb-body{overflow:auto;max-height:calc(min(72vh,560px) - 42px);padding:6px 2px 12px}

  .hhbb-list{display:flex;flex-direction:column;gap:8px}
  .hhbb-li{display:flex;align-items:center;gap:12px;padding:10px;border-radius:12px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.05);cursor:pointer}
  .hhbb-ava{width:44px;height:44px;border-radius:50%;object-fit:cover;background:#222;border:1px solid rgba(255,255,255,.08)}
  .hhbb-main{flex:1;min-width:0}
  .hhbb-title{font-weight:700;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .hhbb-sub{color:#c9c9c9;font-size:12px;opacity:.85;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .hhbb-actions{display:flex;gap:8px}
  .hhbb-ghost{appearance:none;border:1px solid rgba(255,255,255,.14);background:transparent;color:#f0f0f0;padding:8px 10px;border-radius:10px;font-size:13px;font-weight:700}
  .hhbb-ghost.is-danger{border-color:rgba(220,60,60,.5);color:#ffdddd}
  .hhbb-ghost.is-primary{border-color:rgba(120,180,255,.6);color:#e9f2ff}
  .hhbb-unread{width:10px;height:10px;border-radius:50%;background:#3fa65a;box-shadow:0 0 0 2px rgba(0,0,0,.6)}
  .hhbb-empty{opacity:.7;text-align:center;padding:18px 6px}
  
</style>

<!-- Overlay -->
<div id="hhbb-overlay" class="hhbb-overlay" data-hhbb-close="overlay"></div>

<!-- Sheets -->
<div id="hhbb-requests" class="hhbb-sheet" aria-hidden="true">
  <div class="hhbb-panel">
    <div class="hhbb-hd"><h3>Freundschaftsanfragen</h3><button class="hhbb-close" data-hhbb-close="hhbb-requests">Schließen</button></div>
    <div id="hhbb-requests-list" class="hhbb-body"><div class="hhbb-empty">Lade Anfragen…</div></div>
  </div>
</div>

<div id="hhbb-messages" class="hhbb-sheet" aria-hidden="true">
  <div class="hhbb-panel">
    <div class="hhbb-hd"><h3>Nachrichten</h3><button class="hhbb-close" data-hhbb-close="hhbb-messages">Schließen</button></div>
    <div id="hhbb-recent-list" class="hhbb-body"><div class="hhbb-empty">Lade Chats…</div></div>
  </div>
</div>

<div id="hhbb-profile" class="hhbb-sheet" aria-hidden="true">
  <div class="hhbb-panel">
    <div class="hhbb-hd"><h3>Profil</h3><button class="hhbb-close" data-hhbb-close="hhbb-profile">Schließen</button></div>
    <div  class="hhbb-body"><div class="hhbb-empty">      <?php if ($me): ?>
        <div class="ps-user">
          <img class="ps-ava" src="<?= htmlspecialchars($meAvatar) ?>" alt="">
          <div>
            <div class="ps-name"><?= htmlspecialchars($me['display_name'] ?? 'Profil') ?></div>
            <a class="ps-link" href="<?= htmlspecialchars($meProfile) ?>">Profil ansehen</a>
          </div>
        </div>

        <div class="ps-actions">
          <a class="ps-btn" href="<?= htmlspecialchars($meProfile) ?>"><i class="ti ti-user"></i><span>Profil</span></a>
          <a class="ps-btn" href="<?= $APP_BASE ?>/user-settings.php"><i class="ti ti-settings"></i><span>Profil bearbeiten</span></a>
          <button class="ps-btn danger" id="logout"><i class="ti ti-logout-2"></i><span>Logout</span></button>
        </div>
      <?php else: ?>


  <div class="ps-tabs" role="tablist" aria-label="Anmeldung">
    <button type="button" class="ps-tab is-active" data-auth-tab="login" aria-selected="true">Login</button>
    <button type="button" class="ps-tab" data-auth-tab="register" aria-selected="false">Registrieren</button>
  </div>
  


  <form data-auth="login" class="ps-authform">
    <div class="ps-field">
      <label for="loginEmail">E-Mail</label>
      <input id="loginEmail" name="email" type="email" class="ps-input" required>
    </div>
    <div class="ps-field">
      <label for="loginPass">Passwort</label>
      <input id="loginPass" type="password" name="password" class="ps-input" required>
    </div>
    <button class="ps-submit" type="submit">
      <i class="ti ti-login-2"></i><span>Einloggen</span>
    </button>
   <div data-auth-msg class="text-xs mt-2"></div>
  </form>
    <form data-auth="register" id="regForm" autocomplete="on" class="ps-authform" hidden>
    <div class="ps-field">
      <label for="regName">Anzeigename</label>
      <input id="regName" name="display_name" class="ps-input" required>
    </div>
    <div class="ps-field">
      <label for="regEmail">E-Mail</label>
      <input id="regEmail" name="email" type="email" class="ps-input" required>
    </div>
    <div class="ps-field">
      <label for="regPass">Passwort</label>
      <input id="regPass" name="password" type="password" class="ps-input" required>
    </div>
    <button class="ps-submit" type="submit">
      <i class="ti ti-user-plus"></i><span>Konto erstellen</span>
    </button>
   
	<div id="loginMsg" class="msg err"></div>
  </form>
        <?php endif; ?></div></div>
  </div>
</div>

<!-- Bottom Bar -->
<div class="hhbb-wrap" role="navigation" aria-label="Mobile bottom bar">
  <div class="hhbb-bar">
    <button id="hhbb-btn-requests" class="hhbb-btn" aria-haspopup="dialog" aria-controls="hhbb-requests"><span class="hhbb-icon"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-user-screen"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M19.03 17.818a3 3 0 0 0 1.97 -2.818v-8a3 3 0 0 0 -3 -3h-12a3 3 0 0 0 -3 3v8c0 1.317 .85 2.436 2.03 2.84" /><path d="M10 14a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M8 21a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2" /></svg></span><span >Anfragen</span><span id="hhbb-badge-requests" class="hhbb-badge is-hidden">0</span></button>
    <button id="hhbb-btn-messages" class="hhbb-btn" aria-haspopup="dialog" aria-controls="hhbb-messages"><span class="hhbb-icon"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-message-plus"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 9h8" /><path d="M8 13h6" /><path d="M12.01 18.594l-4.01 2.406v-3h-2a3 3 0 0 1 -3 -3v-8a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v5.5" /><path d="M16 19h6" /><path d="M19 16v6" /></svg></span><span >Chats</span><span id="hhbb-badge-messages" class="hhbb-badge is-hidden">0</span></button>
    <?php if ($me): ?>
	<button id="hhbb-btn-profile"  class="hhbb-btn" aria-haspopup="dialog" aria-controls="hhbb-profile"><span class="hhbb-icon"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-user-square-rounded"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 13a3 3 0 1 0 0 -6a3 3 0 0 0 0 6z" /><path d="M12 3c7.2 0 9 1.8 9 9s-1.8 9 -9 9s-9 -1.8 -9 -9s1.8 -9 9 -9z" /><path d="M6 20.05v-.05a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v.05" /></svg></span><span >Profil</span></button>
	<?php else: ?>
	<button id="hhbb-btn-profile"  class="hhbb-btn" aria-haspopup="dialog" aria-controls="hhbb-profile"><span class="hhbb-icon"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-user-square-rounded"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 13a3 3 0 1 0 0 -6a3 3 0 0 0 0 6z" /><path d="M12 3c7.2 0 9 1.8 9 9s-1.8 9 -9 9s-9 -1.8 -9 -9s1.8 -9 9 -9z" /><path d="M6 20.05v-.05a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v.05" /></svg></span><span >Login</span></button>
	<?php endif; ?>
  </div>
</div>

<script>
(function(){
  const $  = (s,r=document)=>r.querySelector(s);
  const $$ = (s,r=document)=>Array.from(r.querySelectorAll(s));
  const overlay = $('#hhbb-overlay');
  const sheets = { requests: $('#hhbb-requests'), messages: $('#hhbb-messages'), profile: $('#hhbb-profile') };
  const badges = { requests: $('#hhbb-badge-requests'), messages: $('#hhbb-badge-messages') };

  // API‑Pfade relativ zu BASE
  const API = {
    recent:  (HHBB_BASE||'') + '/api/messages/recent_contacts.php?limit=10',
    pending: (HHBB_BASE||'') + '/api/friends/pending.php',
    respond: (HHBB_BASE||'') + '/api/friends/respond_request.php',
    me:      (HHBB_BASE||'') + '/api/auth/me.php',

  };
  const AVA_FALLBACK = (HHBB_BASE||'') + '/assets/images/avatars/placeholder.png';

  function openSheet(key){ const el=sheets[key]; if(!el) return; overlay.classList.add('hhbb-open'); el.classList.add('hhbb-open'); el.setAttribute('aria-hidden','false'); document.body.style.overflow='hidden'; }
  function closeSheet(key){ const el=sheets[key]; if(!el) return; el.classList.remove('hhbb-open'); el.setAttribute('aria-hidden','true'); if(!Object.values(sheets).some(e=>e.classList.contains('hhbb-open'))){ overlay.classList.remove('hhbb-open'); document.body.style.overflow=''; } }
  function closeAll(){ Object.keys(sheets).forEach(closeSheet); }

  overlay?.addEventListener('click', closeAll);
  $$('[data-hhbb-close]').forEach(b=> b.addEventListener('click', ()=> closeAll()));
  $('#hhbb-btn-requests')?.addEventListener('click', ()=>{ openSheet('requests'); renderFriendRequests(); });
  $('#hhbb-btn-messages')?.addEventListener('click', ()=>{ openSheet('messages'); renderRecentContacts(); });
  $('#hhbb-btn-profile') ?.addEventListener('click', ()=>{ openSheet('profile');  renderProfileBox(); });

  async function j(url,opt){ const res=await fetch(url,Object.assign({credentials:'include'},opt||{})); if(!res.ok) throw new Error('HTTP '+res.status); return res.json(); }
  function ava(p){ return p && p.length ? normalizeUrl(p) : AVA_FALLBACK; }
  function setBadge(el,n){ if(!el) return; if(n>0){ el.textContent=n>99?'99+':String(n); el.classList.remove('is-hidden'); } else { el.classList.add('is-hidden'); } }
  function esc(s){ const d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; }
  function normalizeUrl(u){
    if (!u) return AVA_FALLBACK;
    if (/^https?:\/\//i.test(u)) return u;
    if (u.startsWith('//')) return location.protocol + u;
    if (u.startsWith('/')) return (HHBB_BASE || '') + u;
    return (HHBB_BASE ? HHBB_BASE + '/' : '/') + u.replace(/^\.?\//,'');
  }

  // --- Friend Requests ---
  async function renderFriendRequests(){
    const box=$('#hhbb-requests-list'); box.innerHTML='<div class="hhbb-empty">Lade Anfragen…</div>';
    try{ const d=await j(API.pending); const items=d.items||d.pending||[]; setBadge(badges.requests,items.length);
      if(!items.length){ box.innerHTML='<div class="hhbb-empty">Keine offenen Anfragen.</div>'; return; }
      const list=document.createElement('div'); list.className='hhbb-list';
      items.forEach(it=>{ const li=document.createElement('div'); li.className='hhbb-li';
        li.innerHTML=`<img class="hhbb-ava" src="${ava(it.avatar_path)}"><div class="hhbb-main"><div class="hhbb-title">${esc(it.display_name||'User')}</div><div class="hhbb-sub">möchte befreundet sein</div></div><div class="hhbb-actions"><button class="hhbb-ghost is-primary" data-accept="${it.user_id}">Annehmen</button><button class="hhbb-ghost is-danger" data-decline="${it.user_id}">Ablehnen</button></div>`; list.appendChild(li); });
      box.innerHTML=''; box.appendChild(list);
      box.querySelectorAll('[data-accept]').forEach(b=> b.addEventListener('click',()=> decideFR(b.getAttribute('data-accept'),1)));
      box.querySelectorAll('[data-decline]').forEach(b=> b.addEventListener('click',()=> decideFR(b.getAttribute('data-decline'),0)));
    }catch(e){ box.innerHTML='<div class="hhbb-empty">Fehler beim Laden.</div>'; }
  }
  async function decideFR(uid,ok){ try{ await j(API.respond,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({user_id:Number(uid),accept:ok?1:0})}); renderFriendRequests(); }catch(e){ alert('Aktion fehlgeschlagen'); } }

  // --- Recent Contacts (Messages) ---
  async function renderRecentContacts(){
    const box=$('#hhbb-recent-list'); box.innerHTML='<div class="hhbb-empty">Lade Chats…</div>';
    try{ const d=await j(API.recent); const items=d.items||[]; const totalUnread=items.reduce((a,b)=>a+(b.unread||0),0); setBadge(badges.messages,totalUnread);
      if(!items.length){ box.innerHTML='<div class="hhbb-empty">Noch keine Chats.</div>'; return; }
      const list=document.createElement('div'); list.className='hhbb-list';
      items.forEach(m=>{ const li=document.createElement('div'); li.className='hhbb-li';
        li.innerHTML=`<img class="hhbb-ava" src="${ava(m.avatar_path)}"><div class="hhbb-main"><div class="hhbb-title">${esc(m.display_name||'User')}</div><div class="hhbb-sub">${esc(m.last_text||'')}</div></div>${m.unread>0?'<span class="hhbb-unread"></span>':''}`;
        li.addEventListener('click',()=> openChatOrProfile(m.user_id));
        list.appendChild(li); });
      box.innerHTML=''; box.appendChild(list);
    }catch(e){ box.innerHTML='<div class="hhbb-empty">Fehler beim Laden.</div>'; }
  }

  // --- Chat-Öffnen wie Desktop: bevorzugt existierendes [data-open-chat="UID"] anklicken
  function openChatOrProfile(uid){
    let opened = false;
    try {
      // 1) wenn irgendwo im DOM (z. B. Sidebar) ein data-open-chat vorhanden ist → click simulieren
      const target = document.querySelector(`[data-open-chat="${uid}"]`);
      if (target) {
        console.debug('[HHBB] trigger click on existing [data-open-chat]', uid);
        target.dispatchEvent(new MouseEvent('click', { bubbles: true }));
        opened = true;
      }
      // 2) Fallback: globale Funktionen
      if (!opened && typeof window.openChat === 'function') { console.debug('[HHBB] window.openChat(',uid,')'); window.openChat(uid); opened = true; }
      if (!opened && window.Chat && typeof window.Chat.open === 'function') { console.debug('[HHBB] window.Chat.open(',uid,')'); window.Chat.open(uid); opened = true; }
      if (!opened && window.HHChat && typeof window.HHChat.open === 'function') { console.debug('[HHBB] HHChat.open(',uid,')'); window.HHChat.open(uid); opened = true; }
      if (!opened && window.App && typeof window.App.openChat === 'function') { console.debug('[HHBB] App.openChat(',uid,')'); window.App.openChat(uid); opened = true; }
      // 3) Events signalisieren (falls deine App darauf hört)
      if (!opened) {
        try { window.dispatchEvent(new CustomEvent('hunthub:openChat', { detail: { userId: uid } })); } catch(_){ }
        try { document.dispatchEvent(new CustomEvent('hunthub:openChat', { detail: { userId: uid } })); } catch(_){ }
        try { window.postMessage({ type: 'hunthub:openChat', userId: uid }, '*'); } catch(_){ }
        try { localStorage.setItem('hhbb:openChat', String(uid)); } catch(_){ }
        opened = true; // assume handled
      }
    } catch(err){ console.warn('[HHBB] openChatOrProfile error', err); }
    if (opened) { closeAll(); }
  }

  // --- Profil Sheet ---


  // --- Badges zyklisch aktualisieren ---
  async function tick(){ try{ const d=await j(API.recent); setBadge(badges.messages,(d.items||[]).reduce((a,b)=>a+(b.unread||0),0)); }catch(_){ } try{ const d=await j(API.pending); setBadge(badges.requests,(d.items||d.pending||[]).length); }catch(_){ } }
  setInterval(tick,30000); document.addEventListener('visibilitychange',()=>{ if(document.visibilityState==='visible') tick(); }); tick();
})();
</script>
<script>
(function(){
const MOBILE = () => matchMedia('(max-width: 900px)').matches;


function setFS(win, on){
if (!win) return;
if (on){
document.body.classList.add('hhbb-chat-fullscreen');
win.classList.add('hh-fs');
} else {
document.body.classList.remove('hhbb-chat-fullscreen');
win.classList.remove('hh-fs');
}
}


function findWin(){
const tabs = document.getElementById('chatTabs');
const inTabs = tabs?.querySelectorAll('.hh-window');
if (inTabs && inTabs.length) return inTabs[inTabs.length-1];
const all = document.querySelectorAll('.hh-window');
return all.length ? all[all.length-1] : null;
}


function maximizeSoon(){
if (!MOBILE()) return;
let tries = 0;
const t = setInterval(()=>{
tries++;
const w = findWin();
if (w){ setFS(w, true); watch(w); clearInterval(t); }
if (tries>20) clearInterval(t);
}, 120);
}


function watch(win){
// click on close/minimize buttons
win.addEventListener('click', e=>{
const t = e.target.closest('.hh-btn, [data-close], .btn-close');
if (!t) return;
const title = (t.getAttribute('title')||'').toLowerCase();
if (t.matches('[data-close], .btn-close') || title.includes('schließ') || title.includes('close')){
setTimeout(()=> setFS(win,false), 200);
}
}, true);


// leave fullscreen when minimized or removed
const mo = new MutationObserver(()=>{
if (win.classList.contains('hh-min') || !document.body.contains(win)){
setFS(win,false); mo.disconnect();
}
});
mo.observe(win,{ attributes:true, attributeFilter:['class'], childList:true, subtree:true });


// if user rotates / resizes to desktop
addEventListener('resize', ()=>{ if (!MOBILE()) setFS(win,false); });
}


// Hooks: same signals your bottom bar v7 emits
window.addEventListener('hunthub:openChat', maximizeSoon);
window.addEventListener('message', e=>{ if (e.data?.type==='hunthub:openChat') maximizeSoon(); });
try { const v = localStorage.getItem('hhbb:openChat'); if (v){ maximizeSoon(); localStorage.removeItem('hhbb:openChat'); } } catch{}
// Also when user clicks any existing data-open-chat
document.addEventListener('click', e=>{ if (e.target.closest('[data-open-chat]')) setTimeout(maximizeSoon, 0); });
})();
</script>
<script>
(() => {
  const SHEET = document.getElementById('hhbb-profile'); // <- richtiges Sheet
  if (!SHEET) return;

  async function postJSON(url, data) {
    const res = await fetch(url, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(data)
    });
    const out = await res.json().catch(()=> ({}));
    if (!res.ok || out.ok === false) throw new Error(out?.error || `HTTP ${res.status}`);
    return out;
  }
  function hardReload(){ location.assign(location.pathname + location.search); }

  // LOGIN + REGISTER (delegiert – greift auch bei dynamischem Inhalt)
  SHEET.addEventListener('submit', async (e) => {
    const form = e.target.closest('form');
    if (!form) return;

    // login-form wird per data-auth="login" markiert; fallback auf id="loginForm"
    const isLogin = form.matches('[data-auth="login"], #loginForm');
    const isRegister = form.matches('[data-auth="register"], #regForm');

    if (!isLogin && !isRegister) return;

    e.preventDefault();
    const fd  = new FormData(form);
    const msg = form.querySelector('[data-auth-msg], #loginMsg');

    try {
      msg && (msg.textContent = 'Bitte warten …');

      if (isLogin) {
        const out = await postJSON('/api/auth/login.php', {
          email: String(fd.get('email') || '').trim(),
          password: fd.get('password')
        });
        msg && (msg.textContent = 'Eingeloggt.');
        out.redirect ? location.assign(out.redirect) : hardReload();
      } else {
        // Registrierung (optional; läuft, falls du sie nutzt)
        const out = await postJSON('/api/auth/register.php', {
          display_name: fd.get('display_name') || fd.get('username') || '',
          email: fd.get('email'),
          password: fd.get('password')
        });
        msg && (msg.textContent = 'Konto erstellt. Du wirst eingeloggt …');
        out.redirect ? location.assign(out.redirect) : hardReload();
      }
    } catch (err) {
      msg && (msg.textContent = err?.message || 'Aktion fehlgeschlagen.');
    }
  });

  // LOGOUT (delegiert – funktioniert für #logout ODER #hhbb-btn-logout)
  SHEET.addEventListener('click', async (e) => {
    const btn = e.target.closest('#logout, #hhbb-btn-logout, [data-logout]');
    if (!btn) return;
    e.preventDefault();
    try {
      await fetch('/api/auth/logout.php', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Accept': 'application/json' }
      });
    } finally {
      hardReload();
    }
  });
})();
</script>
