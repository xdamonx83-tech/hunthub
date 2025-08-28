async function renderProfileBox(){
  const box=$('#profile-box'); box.innerHTML='<div class="empty">Lade Profil…</div>';
  const BASE = (window.HHBB_BASE||'');
  const esc = s => (s||'').replace(/[&<>"']/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;"}[c]));
  const ava = (p)=>{ const f = BASE + '/assets/images/avatars/placeholder.png';
                     if(!p) return f;
                     if(/^https?:\/\//i.test(p)) return p;
                     if(p.startsWith('//')) return location.protocol + p;
                     if(p.startsWith('/')) return BASE + p;
                     return (BASE?BASE+'/':'/') + p.replace(/^\.?\//,''); };

  try{
    let u = (window.HH_ME && window.HH_ME.id) ? window.HH_ME : null;
    if (!u) {
      const raw = await j(BASE + '/api/auth/me.php');
      u = (raw && (raw.user || raw.me || raw?.data?.user || raw?.data?.me || raw.profile || raw)) || {};
    }
    const uid = u.id ?? u.user_id ?? u.uid ?? null;
    if(!uid){ box.innerHTML='<div class="empty">Nicht eingeloggt.</div>'; return; }

    box.innerHTML=`<div class="li" style="align-items:flex-start"><img class="ava" src="${ava(u.avatar_path||u.avatar)}"><div class="li-main"><div class="li-title" style="font-size:16px">${esc(u.display_name||u.email||('User '+uid))}</div><div class="li-sub">${esc(u.email||'')}</div></div></div><div class="list" style="margin-top:10px"><a class="li" href="${BASE}/profile.php">Mein Profil</a><a class="li" href="${BASE}/cms/public/admin.php">Einstellungen</a><button class="li" id="btn-logout">Logout</button></div>`;
    $('#btn-logout')?.addEventListener('click',async()=>{ try{ await j(BASE + '/api/auth/logout.php'); location.reload(); }catch(_){ } });
  }catch(e){
    console.error('[MBB] me.php',e); box.innerHTML='<div class="empty">Fehler beim Laden.</div>';
  }
}
