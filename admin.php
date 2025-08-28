<?php
declare(strict_types=1);

require_once __DIR__ . '/auth/db.php';
require_once __DIR__ . '/auth/guards.php';
require_once __DIR__ . '/auth/csrf.php';
require_once __DIR__ . '/lib/layout.php';

$pdo = db();
$me  = require_admin(); // blockt Nicht-Admins

$cfg = require __DIR__ . '/auth/config.php';
$APP_BASE = rtrim($cfg['app_base'] ?? '', '/');

// CSRF-Token passend zu deiner csrf.php erzeugen:
$sessionCookie = $_COOKIE[$cfg['cookies']['session_name']] ?? '';
$csrf = issue_csrf($pdo, $sessionCookie);

$view = $_GET['view'] ?? 'dashboard';

function count_row(PDO $pdo, string $table): int {
  $st = $pdo->query("SELECT COUNT(*) AS c FROM {$table}");
  return (int)$st->fetchColumn();
}

ob_start();
?>
<meta name="csrf" content="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>">
<style>
  .admin-wrap{max-width:1200px;margin:2rem auto;padding:1rem;padding-top:10%;}
  .admin-nav{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem}
  .admin-nav a{padding:.5rem .75rem;border:1px solid rgba(255,255,255,.15);border-radius:8px;text-decoration:none;color:#e5e7eb;background:#141414}
  .admin-card{background:#0e0e0e;border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:1rem;margin-bottom:1rem}
  .grid{display:grid;gap:.75rem}
  .grid.cols-4{grid-template-columns:repeat(4,minmax(0,1fr))}
  table{width:100%;border-collapse:collapse}
  th,td{padding:.5rem;border-bottom:1px solid rgba(255,255,255,.08);text-align:left;vertical-align:top}
  .btn{display:inline-block;padding:.4rem .65rem;border:1px solid rgba(255,255,255,.15);border-radius:8px;background:#1b1b1b;color:#e5e7eb;text-decoration:none;cursor:pointer}
  .danger{border-color:#f87171;color:#fecaca}
  .ok{border-color:#4ade80;color:#bbf7d0}
  .muted{opacity:.85}
  .kpi{font-size:1.25rem;font-weight:600}
  input[type="text"],input[type="number"],textarea,select{
    padding:.5rem;border-radius:8px;border:1px solid rgba(255,255,255,.15);background:#111;color:#e5e7eb;width:100%
  }
  textarea{min-height:110px}
</style>

<div class="admin-wrap">
  <h1>Admin</h1>
  <div class="admin-nav">
    <a href="<?= $APP_BASE ?>/admin.php?view=dashboard">Dashboard</a>
    <a href="<?= $APP_BASE ?>/admin.php?view=users">Users</a>
    <a href="<?= $APP_BASE ?>/admin.php?view=forum">Forum</a>
    <a href="<?= $APP_BASE ?>/admin.php?view=uploads">Uploads</a>
    <a href="<?= $APP_BASE ?>/admin.php?view=cache">Cache</a>
    <a href="<?= $APP_BASE ?>/admin.php?view=health">Health</a>
    <a href="<?= $APP_BASE ?>/admin.php?view=notifications">Notifications</a>
    <a href="<?= $APP_BASE ?>/admin.php?view=gamification">Gamification</a>
    <a href="<?= $APP_BASE ?>/admin.php?view=messages">Messages</a>
  </div>

  <?php if ($view==='dashboard'): ?>
    <?php
      $k_users   = count_row($pdo,'users');
      $k_threads = count_row($pdo,'threads');
      $k_posts   = count_row($pdo,'posts');
      $k_msgs    = count_row($pdo,'messages');
      $k_notif   = count_row($pdo,'notifications');
      $k_lp      = count_row($pdo,'link_previews');
      $k_ach     = count_row($pdo,'achievements');
      $k_uach    = count_row($pdo,'user_achievements');
    ?>
    <div class="grid cols-4">
      <div class="admin-card"><div class="muted">Users</div><div class="kpi"><?= $k_users ?></div></div>
      <div class="admin-card"><div class="muted">Threads</div><div class="kpi"><?= $k_threads ?></div></div>
      <div class="admin-card"><div class="muted">Posts</div><div class="kpi"><?= $k_posts ?></div></div>
      <div class="admin-card"><div class="muted">Messages</div><div class="kpi"><?= $k_msgs ?></div></div>
      <div class="admin-card"><div class="muted">Notifications</div><div class="kpi"><?= $k_notif ?></div></div>
      <div class="admin-card"><div class="muted">Link-Previews</div><div class="kpi"><?= $k_lp ?></div></div>
      <div class="admin-card"><div class="muted">Achievements</div><div class="kpi"><?= $k_ach ?></div></div>
      <div class="admin-card"><div class="muted">User-Achievements</div><div class="kpi"><?= $k_uach ?></div></div>
    </div>

  <?php elseif ($view==='users'): ?>
    <div class="admin-card">
      <form id="userSearch" onsubmit="return false" style="display:flex;gap:.5rem;flex-wrap:wrap">
        <input type="text" id="q" placeholder="Suche Name/Email/ID…" style="flex:1;min-width:240px">
        <button class="btn" onclick="loadUsers()">Suchen</button>
      </form>
    </div>
    <div class="admin-card">
      <table id="usersTable"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Aktionen</th></tr></thead><tbody></tbody></table>
    </div>
<?php elseif ($view==='forum'): ?>
  <div class="admin-card">
    <h3 style="margin:0 0 .75rem 0">Threads verwalten</h3>
    <div class="grid" style="grid-template-columns: 2fr 1fr 140px; align-items:end;">
      <div>
        <label>Suche (Titel/Slug)</label>
        <input id="thr_q" type="text" placeholder="z. B. bug, willkommen">
      </div>
      <div>
        <label>Board</label>
        <select id="thr_board"><option value="0">Alle</option></select>
      </div>
      <div><button class="btn" onclick="forumThreadsLoad()">Neu laden</button></div>
    </div>
  </div>

  <div class="admin-card">
    <table id="thrTable">
      <thead>
        <tr>
          <th>ID</th><th>Board</th><th>Titel</th><th>Slug</th>
          <th>Autor</th><th>Posts</th><th>Locked</th><th>Pinned</th>
          <th>Erstellt</th><th>Aktion</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
    <div style="margin-top:.75rem;display:flex;gap:.5rem">
      <button class="btn" onclick="forumPrev()">« Zurück</button>
      <button class="btn" onclick="forumNext()">Weiter »</button>
      <span class="muted" id="thrInfo" style="align-self:center"></span>
    </div>
  </div>

  <!-- Edit-Modal -->
  <div id="thrModal" style="display:none;position:fixed;inset:0;backdrop-filter:blur(2px);background:rgba(0,0,0,.55);z-index:9999;">
    <div style="max-width:640px;margin:5vh auto;background:#0e0e0e;border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:1rem">
      <h3 style="margin-top:0">Thread bearbeiten</h3>
      <div class="grid" style="grid-template-columns:1fr 1fr">
        <div>
          <label>Thread-ID</label>
          <input id="thr_id" type="number" disabled>
        </div>
        <div>
          <label>Board</label>
          <select id="thr_edit_board"></select>
        </div>
        <div style="grid-column:1/-1">
          <label>Titel</label>
          <input id="thr_title" type="text">
        </div>
        <div style="grid-column:1/-1">
          <label>Slug</label>
          <input id="thr_slug" type="text" placeholder="optional – leer lassen um nicht zu ändern">
        </div>
        <div>
          <label>Locked</label>
          <select id="thr_locked"><option value="0">0</option><option value="1">1</option></select>
        </div>
        <div>
          <label>Pinned</label>
          <select id="thr_pinned"><option value="0">0</option><option value="1">1</option></select>
        </div>
      </div>
      <div style="margin-top:1rem;display:flex;gap:.5rem">
        <button class="btn ok" onclick="forumThreadSave()">Speichern</button>
        <button class="btn" onclick="forumModalClose()">Abbrechen</button>
      </div>
    </div>
  </div>

<!-- Posts-Modal -->
<div id="postModal" style="display:none;position:fixed;inset:0;backdrop-filter:blur(2px);background:rgba(0,0,0,.55);z-index:9999;">
  <div style="max-width:900px;margin:4vh auto;background:#0e0e0e;border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:1rem">
    <h3 style="margin-top:0">Posts in Thread <span id="pm_thread_id"></span></h3>
    <div class="grid" style="grid-template-columns: 1fr 140px 140px; align-items:end; gap:.5rem">
      <div><label>Suche</label><input id="pm_q" type="text" placeholder="Textsuche"></div>
      <div><button class="btn" onclick="forumPostsPrev()">« Zurück</button></div>
      <div><button class="btn" onclick="forumPostsNext()">Weiter »</button></div>
    </div>

    <div class="admin-card" style="margin-top:.75rem">
      <table id="pm_table"><thead>
        <tr><th>ID</th><th>Autor</th><th>Inhalt</th><th>Erstellt</th><th>Aktion</th></tr>
      </thead><tbody></tbody></table>
      <div class="muted" id="pm_info" style="margin-top:.5rem"></div>
    </div>

    <div class="admin-card">
      <h4 style="margin:0 0 .5rem 0">Post bearbeiten</h4>
      <input type="hidden" id="pm_edit_id">
      <textarea id="pm_edit_content" style="min-height:120px"></textarea>
      <div style="margin-top:.5rem;display:flex;gap:.5rem">
        <button class="btn ok" onclick="forumPostSave()">Speichern</button>
        <button class="btn danger" onclick="forumPostDelete()">Löschen</button>
        <button class="btn" onclick="forumPostsClose()">Schließen</button>
      </div>
    </div>
  </div>
</div>

  <?php elseif ($view==='uploads'): ?>
    <div class="admin-card">
      <button class="btn" onclick="listUploads('images')">Bilder auflisten</button>
      <button class="btn" onclick="listUploads('videos')">Videos auflisten</button>
    </div>
    <div class="admin-card">
<table id="uploadsTable">
  <thead>
    <tr><th>Preview</th><th>Typ</th><th>Pfad</th><th>Thema</th><th>Size</th><th>Aktionen</th></tr>
  </thead>
  <tbody></tbody>
</table>
    </div>

  <?php elseif ($view==='cache'): ?>
    <div class="admin-card">
      <button class="btn danger" onclick="clearLinkPreviews()">Link-Preview Cache leeren</button>
    </div>

  <?php elseif ($view==='health'): ?>
    <div class="admin-card" id="healthBox">Health wird geladen…</div>

  <?php elseif ($view==='notifications'): ?>
    <div class="admin-card">
      <h3 style="margin:0 0 .5rem 0">Broadcast senden</h3>
      <div class="grid" style="grid-template-columns:1fr 1fr;gap:.75rem">
        <div><label>Titel</label><input id="bc_title" type="text"></div>
        <div><label>Level</label>
          <select id="bc_level"><option>info</option><option>warn</option><option>critical</option></select>
        </div>
      </div>
      <div style="margin-top:.5rem"><label>Body</label><textarea id="bc_body"></textarea></div>
      <div style="margin-top:.5rem" class="grid" style="grid-template-columns:1fr 200px">
        <div><label>Link (optional)</label><input id="bc_link" type="text" placeholder="https://…"></div>
        <div><label>&nbsp;</label><label style="display:flex;align-items:center;gap:.5rem"><input id="bc_fan" type="checkbox"> Fan-Out in notifications</label></div>
      </div>
      <div style="margin-top:.75rem"><button class="btn ok" onclick="broadcastSend()">Senden</button></div>
    </div>
    <div class="admin-card">
      <h3 style="margin:0 0 .5rem 0">Letzte Broadcasts</h3>
      <table id="bcTable"><thead><tr><th>ID</th><th>Titel</th><th>Level</th><th>Body (Preview)</th><th>Link</th><th>Active</th><th>Aktion</th></tr></thead><tbody></tbody></table>
      <div style="margin-top:.75rem"><button class="btn" onclick="broadcastList()">Neu laden</button></div>
    </div>

  <?php elseif ($view==='gamification'): ?>
    <div class="admin-card">
      <h3 style="margin:0 0 .5rem 0">Achievement anlegen/ändern</h3>
      <div class="grid" style="grid-template-columns: repeat(2, minmax(0,1fr));">
        <div><label>ID (leer = neu)</label><input id="ach_id" type="number"></div>
        <div><label>Key</label><input id="ach_key" type="text" placeholder="z.B. posts_10"></div>
        <div><label>Titel</label><input id="ach_title" type="text"></div>
        <div><label>Icon</label><input id="ach_icon" type="text" placeholder="/assets/images/achievements/x.png"></div>
        <div><label>Event</label><input id="ach_event" type="text" placeholder="z.B. post_created"></div>
        <div><label>Stat</label><input id="ach_stat" type="text" placeholder="z.B. posts_count"></div>
        <div><label>Threshold</label><input id="ach_thr" type="number" value="1"></div>
        <div><label>Punkte</label><input id="ach_pts" type="number" value="0"></div>
        <div><label>Aktiv</label><select id="ach_active"><option value="1">1</option><option value="0">0</option></select></div>
        <div style="grid-column:1/-1"><label>Beschreibung</label><input id="ach_desc" type="text"></div>
      </div>
      <div style="margin-top:.75rem"><button class="btn ok" onclick="achSave()">Speichern</button></div>
    </div>
    <div class="admin-card">
      <h3 style="margin:0 0 .5rem 0">Achievements</h3>
      <table id="achTable"><thead><tr><th>ID</th><th>Key</th><th>Titel</th><th>Event/Stat</th><th>Threshold</th><th>Punkte</th><th>Aktiv</th></tr></thead><tbody></tbody></table>
      <div style="margin-top:.75rem"><button class="btn" onclick="achList()">Neu laden</button></div>
    </div>
    <div class="admin-card">
      <h3 style="margin:0 0 .5rem 0">Achievement vergeben</h3>
      <div class="grid" style="grid-template-columns:repeat(3,minmax(0,1fr))">
        <div><label>User-ID</label><input id="grant_uid" type="number" placeholder="oder Email nutzen"></div>
        <div><label>Email</label><input id="grant_email" type="text" placeholder="oder User-ID nutzen"></div>
        <div><label>Achievement (ID oder Key)</label><input id="grant_aid" type="text" placeholder="z.B. 5 oder posts_10"></div>
      </div>
      <div style="margin-top:.75rem"><button class="btn ok" onclick="achGrant()">Vergeben</button></div>
    </div>

  <?php elseif ($view==='messages'): ?>
    <div class="admin-card">
      <h3 style="margin:0 0 .5rem 0">Messages Cleanup</h3>
      <div class="grid" style="grid-template-columns:repeat(3,minmax(0,1fr))">
        <div><label>Retention (Tage)</label><input id="mc_days" type="number" value="15"></div>
        <div><label>Batch</label><input id="mc_batch" type="number" value="500"></div>
        <div><label>Update-Chunk</label><input id="mc_chunk" type="number" value="200"></div>
      </div>
      <div style="margin-top:.5rem"><label>Platzhalter-Text</label>
        <input id="mc_placeholder" type="text" value="Anhang (Bild/Video) automatisch gelöscht – älter als 15 Tage.">
      </div>
      <div style="margin-top:.5rem;display:flex;align-items:center;gap:.75rem">
        <label style="display:flex;align-items:center;gap:.4rem"><input id="mc_dry" type="checkbox" checked> Dry-Run</label>
        <button class="btn" onclick="mcRun(true)">Preview (Dry-Run)</button>
        <button class="btn danger" onclick="mcRun(false)">Cleanup ausführen</button>
      </div>
    </div>
    <div class="admin-card"><pre id="mc_result" style="white-space:pre-wrap"></pre></div>
  <?php endif; ?>
</div>

<script>
const BASE = "<?= $APP_BASE ?>";
const CSRF = document.querySelector('meta[name="csrf"]')?.content || '';

async function api(p, data){
  try {
    const res  = await fetch(`${BASE}/api/admin/${p}`, {
      method: 'POST',
      headers: { 'Content-Type':'application/json', 'X-CSRF': CSRF },
      body: JSON.stringify(Object.assign({ csrf: CSRF }, data || {}))
    });
    const text = await res.text();
    let json;
    try { json = JSON.parse(text); } catch { json = null; }

    if (!res.ok || !json || json.ok === false) {
      const msg = json?.error || text.slice(0, 600) || 'unbekannter Fehler';
      alert(`API-Fehler (${p}): HTTP ${res.status}\n${msg}`);
      return json || { ok:false, error: msg, http: res.status };
    }
    return json;
  } catch (e) {
    alert(`Netzwerkfehler (${p}): ${e.message}`);
    return { ok:false, error: e.message };
  }
}

/* Users */
async function loadUsers(){
  const q = document.getElementById('q').value.trim();
  const j = await api('users/list.php', {q});
  const tb = document.querySelector('#usersTable tbody'); tb.innerHTML='';
  (j.items||[]).forEach(u=>{
    const tr=document.createElement('tr');
    tr.innerHTML=`<td>${u.id}</td><td>${u.display_name||''}</td><td>${u.email||''}</td>
      <td>${u.role|| (u.is_admin ? 'admin*' : 'user')}</td>
      <td>
        <button class="btn ok" onclick="setRole(${u.id},'admin')">zu Admin</button>
        <button class="btn" onclick="setRole(${u.id},'user')">zu User</button>
      </td>`;
    tb.appendChild(tr);
  });
}
async function setRole(id, role){
  const j = await api('users/update_role.php', {id, role});
  alert(j.ok ? 'Gespeichert' : ('Fehler: '+(j.error||'unknown')));
  loadUsers();
}

/* Forum */
async function deletePost(){
  const id = parseInt(document.getElementById('postId').value||'0',10);
  if (!id) return alert('Post-ID fehlt');
  const j = await api('forum/delete_post.php', {id});
  alert(j.ok ? 'Post gelöscht' : ('Fehler: '+(j.error||'unknown')));
}
async function deleteThread(){
  const id = parseInt(document.getElementById('threadId').value||'0',10);
  if (!id) return alert('Thread-ID fehlt');
  const j = await api('forum/delete_thread.php', {id});
  alert(j.ok ? 'Thread gelöscht' : ('Fehler: '+(j.error||'unknown')));
}

/* Uploads */
async function listUploads(kind){
  const j = await api('uploads/list.php', {kind});
  const tb = document.querySelector('#uploadsTable tbody'); tb.innerHTML='';
  if (!j.ok) return;

  const rels = [];

  (j.items||[]).forEach(f=>{
    const url   = `${BASE}/api/admin/uploads/preview.php?rel=${encodeURIComponent(f.rel)}`;
    const isVid = (f.kind === 'videos') || (f.mime && f.mime.startsWith('video/'));

    const preview = isVid
      ? `<video class="admin-thumb" src="${url}" style="max-width:420px;" preload="metadata" playsinline muted controls></video>`
      : `<img class="admin-thumb" src="${url}" alt="" style="width:200px;">`;

    const tr = document.createElement('tr');
    tr.dataset.rel = f.rel; // merken
    tr.innerHTML = `
      <td>${isVid ? preview : `<a href="${url}" target="_blank" rel="noreferrer noopener">${preview}</a>`}</td>
      <td>${f.kind}</td>
      <td class="muted" style="max-width:420px;word-break:break-all">${f.rel}</td>
      <td class="theme-cell muted">–</td>
      <td>${(f.size||0).toLocaleString('de-DE')}</td>
      <td><button class="btn danger" onclick="delUpload('${(f.rel||'').replace(/'/g,"\\'")}')">löschen</button></td>
    `;
    tb.appendChild(tr);

    // Nur für ordnerbezogene Inhalte, die typischerweise in Posts vorkommen, auflösen:
    if (/\/uploads\/(posts|forum|messages|video_posters|videos)\//.test(f.rel)) {
      rels.push(f.rel);
    }
  });

  // Inline Video Click Play/Pause (optional)
  tb.querySelectorAll('video.admin-thumb').forEach(v=>{
    v.addEventListener('click', ()=>{ if (v.paused) v.play(); else v.pause(); });
  });

  // Zuordnung in einem Rutsch nachladen
  if (rels.length) resolveUploadsForum(rels);
}



async function delUpload(rel){
  const j = await api('uploads/delete.php', {rel});
  alert(j.ok ? 'Datei gelöscht' : ('Fehler: '+(j.error||'unknown')));
}

/* Cache */
async function clearLinkPreviews(){
  const j = await api('cache/clear_link_previews.php', {});
  alert(j.ok ? 'Cache geleert' : ('Fehler: '+(j.error||'unknown')));
}

/* Health */
(async function(){
  if ("<?= $view ?>" === 'health') {
    const j = await api('system/health.php', {});
    document.getElementById('healthBox').textContent = j.ok ? `OK (PHP ${j.php}, DB ${j.db})` : `Fehler: ${j.error||'unknown'}`;
  }
})();

/* Notifications - Broadcast */
async function broadcastSend(){
  const title = document.getElementById('bc_title').value.trim();
  const body  = document.getElementById('bc_body').value.trim();
  const link  = document.getElementById('bc_link').value.trim();
  const level = document.getElementById('bc_level').value;
  const fan   = document.getElementById('bc_fan').checked;
  if (!title || !body) return alert('Titel und Body erforderlich.');

  const j = await api('notifications/broadcast.php', {title, body, link, level, fan_out:fan});
  if (!j.ok) return alert('Fehler: '+(j.error||'unknown'));
  alert('Broadcast gesendet' + (j.fan_out_inserted ? ` (+${j.fan_out_inserted} Notifs)` : ''));
  broadcastList();
}
async function broadcastList(){
  const j = await api('notifications/list.php', {});
  const tb = document.querySelector('#bcTable tbody'); tb.innerHTML='';
  (j.items||[]).forEach(b=>{
    const tr=document.createElement('tr');
    tr.innerHTML = `<td>${b.id}</td><td>${b.title}</td><td>${b.level}</td><td class="muted">${b.body_preview||''}</td><td>${b.link||''}</td><td>${b.is_active?'1':'0'}</td>
      <td>${b.is_active?`<button class="btn" onclick="broadcastDeactivate(${b.id})">deaktivieren</button>`:''}</td>`;
    tb.appendChild(tr);
  });
}
async function broadcastDeactivate(id){
  const j = await api('notifications/deactivate.php', {id});
  if (!j.ok) return alert('Fehler: '+(j.error||'unknown'));
  broadcastList();
}

/* Gamification */
async function achList(){
  const j = await api('gamification/achievements_list.php', {});
  const tb = document.querySelector('#achTable tbody'); tb.innerHTML='';
  (j.items||[]).forEach(a=>{
    const tr=document.createElement('tr');
    tr.innerHTML = `<td>${a.id}</td><td>${a.key}</td><td>${a.title}</td><td>${a.rule_event}/${a.rule_stat}</td>
      <td>${a.threshold}</td><td>${a.points}</td><td>${a.is_active?1:0}</td>`;
    tr.style.cursor='pointer';
    tr.onclick = ()=>{
      document.getElementById('ach_id').value = a.id;
      document.getElementById('ach_key').value = a.key||'';
      document.getElementById('ach_title').value = a.title||'';
      document.getElementById('ach_icon').value = a.icon||'';
      document.getElementById('ach_event').value = a.rule_event||'';
      document.getElementById('ach_stat').value = a.rule_stat||'';
      document.getElementById('ach_thr').value = a.threshold||0;
      document.getElementById('ach_pts').value = a.points||0;
      document.getElementById('ach_active').value = a.is_active? '1':'0';
      document.getElementById('ach_desc').value = a.description||'';
    };
    tb.appendChild(tr);
  });
}
async function achSave(){
  const id    = parseInt(document.getElementById('ach_id').value||'0',10);
  const key   = document.getElementById('ach_key').value.trim();
  const title = document.getElementById('ach_title').value.trim();
  const icon  = document.getElementById('ach_icon').value.trim();
  const event = document.getElementById('ach_event').value.trim();
  const stat  = document.getElementById('ach_stat').value.trim();
  const thr   = parseInt(document.getElementById('ach_thr').value||'0',10);
  const pts   = parseInt(document.getElementById('ach_pts').value||'0',10);
  const active= document.getElementById('ach_active').value === '1';
  const desc  = document.getElementById('ach_desc').value.trim();

  const j = await api('gamification/achievements_save.php', {id, key, title, description:desc, icon, rule_event:event, rule_stat:stat, threshold:thr, points:pts, is_active:active});
  alert(j.ok ? 'Gespeichert' : ('Fehler: '+(j.error||'unknown')));
  achList();
}
async function achGrant(){
  const uidStr = document.getElementById('grant_uid').value.trim();
  const email  = document.getElementById('grant_email').value.trim();
  const aidKey = document.getElementById('grant_aid').value.trim();
  let user_id = parseInt(uidStr||'0',10);
  let achievement_id = 0, key = '';

  if (!user_id && !email) return alert('User-ID oder Email angeben.');
  if (/^\d+$/.test(aidKey)) achievement_id = parseInt(aidKey,10); else key = aidKey;

  const j = await api('gamification/grant.php', {user_id, email, achievement_id, key});
  alert(j.ok ? `Vergeben (User ${j.user_id}, Ach ${j.achievement_id}, +${j.points_added} Punkte)` : ('Fehler: '+(j.error||'unknown')));
}

/* Messages Cleanup */
async function mcRun(dry){
  const days = parseInt(document.getElementById('mc_days').value||'15',10);
  const placeholder = document.getElementById('mc_placeholder').value;
  const batch = parseInt(document.getElementById('mc_batch').value||'500',10);
  const chunk = parseInt(document.getElementById('mc_chunk').value||'200',10);
  const j = await api('messages/cleanup_run.php', {retention_days:days, placeholder, dry_run:dry, batch, update_chunk:chunk});
  document.getElementById('mc_result').textContent = JSON.stringify(j, null, 2);
}

/* Auto-Init pro Tab */
(async function init(){
  const v = "<?= $view ?>";
  if (v==='users') loadUsers();
  if (v==='health') {
    const j = await api('system/health.php', {});
    document.getElementById('healthBox').textContent = j.ok ? `OK (PHP ${j.php}, DB ${j.db})` : `Fehler: ${j.error||'unknown'}`;
  }
  if (v==='notifications') broadcastList();
  if (v==='gamification') achList();
  if (v==='forum') { await forumBoardsLoad(); await forumThreadsLoad(); }
})();
/* ---- Forum: Threads Listing & Edit ---- */
let thrOffset = 0;
const thrLimit = 50;

async function forumBoardsLoad(){
  const j = await api('forum/boards_list.php', {});
  const sel = document.getElementById('thr_board');
  const sel2 = document.getElementById('thr_edit_board');
  [sel, sel2].forEach(s => { if (s) s.innerHTML = '<option value="0">Alle</option>'; });

  (j.items||[]).forEach(b=>{
    if (sel)  sel.insertAdjacentHTML('beforeend', `<option value="${b.id}">${b.name||('Board '+b.id)}</option>`);
    if (sel2) sel2.insertAdjacentHTML('beforeend', `<option value="${b.id}">${b.name||('Board '+b.id)}</option>`);
  });
}

async function forumThreadsLoad(){
  const q   = document.getElementById('thr_q').value.trim();
  const bid = parseInt(document.getElementById('thr_board').value||'0',10);
  const j = await api('forum/threads_list.php', {q, board_id: bid, limit: thrLimit, offset: thrOffset});
  const tb = document.querySelector('#thrTable tbody'); tb.innerHTML = '';
  (j.items||[]).forEach(t=>{
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${t.id}</td>
      <td>${t.board_name||t.board_id}</td>
      <td>${escapeHtml(t.title||'')}</td>
      <td class="muted">${t.slug||''}</td>
      <td>${escapeHtml(t.author||'')}</td>
      <td>${t.posts_count||0}</td>
      <td>${t.is_locked?1:0}</td>
      <td>${t.is_pinned?1:0}</td>
      <td>${t.created_at||''}</td>
      <td>
       <button class="btn" onclick='forumThreadEdit(${JSON.stringify(t).replace(/'/g,"&#39;")})'>Edit</button>
  <button class="btn" onclick="forumPostsOpen(${t.id})">Posts</button>
  <button class="btn danger" onclick="deleteThreadId(${t.id})">Del</button>
      </td>
    `;
    tb.appendChild(tr);
  });
  document.getElementById('thrInfo').textContent = `Offset ${thrOffset} · geladen: ${(j.items||[]).length}`;
}
function forumPrev(){ thrOffset = Math.max(0, thrOffset - thrLimit); forumThreadsLoad(); }
function forumNext(){ thrOffset += thrLimit; forumThreadsLoad(); }

function forumThreadEdit(t){
  document.getElementById('thr_id').value     = t.id;
  document.getElementById('thr_title').value  = t.title||'';
  document.getElementById('thr_slug').value   = t.slug||'';
  document.getElementById('thr_locked').value = (t.is_locked?1:0);
  document.getElementById('thr_pinned').value = (t.is_pinned?1:0);

  // Board-Dropdown füllen, wenn leer
  const sel = document.getElementById('thr_edit_board');
  if (!sel.options || sel.options.length<=1) forumBoardsLoad();
  // später setzen (kleines Delay), falls boards gerade laden
  setTimeout(()=>{ sel.value = t.board_id; }, 50);

  document.getElementById('thrModal').style.display = 'block';
}
function forumModalClose(){ document.getElementById('thrModal').style.display = 'none'; }

async function forumThreadSave(){
  const id     = parseInt(document.getElementById('thr_id').value||'0',10);
  const title  = document.getElementById('thr_title').value.trim();
  const slug   = document.getElementById('thr_slug').value.trim();
  const board  = parseInt(document.getElementById('thr_edit_board').value||'0',10);
  const locked = document.getElementById('thr_locked').value === '1';
  const pinned = document.getElementById('thr_pinned').value === '1';

  const payload = { id, title, board_id: board, is_locked: locked, is_pinned: pinned };
  if (slug !== '') payload.slug = slug; // nur senden, wenn gesetzt

  const j = await api('forum/thread_update.php', payload);
  if (!j.ok) { alert('Fehler: ' + (j.error||'unknown')); return; }

  forumModalClose();
  forumThreadsLoad();
}

async function deleteThreadId(id){
  const tid = parseInt(id, 10);
  if (!tid) { alert('Ungültige Thread-ID'); return; }
  if (!confirm(`Thread ${tid} wirklich löschen?`)) return;
  const j = await api('forum/delete_thread.php', { id: tid }); // id explizit
  alert(j.ok ? 'Thread gelöscht' : ('Fehler: '+(j.error||'unknown')));
  forumThreadsLoad();
}

/* ---- Forum: Posts pro Thread ---- */
let pmThreadId = 0;
let pmOffset = 0;
const pmLimit = 50;
function threadUrl(id, slug){
  // Falls deine App Slugs in der URL nutzt, kannst du das hier anpassen:
  // return `${BASE}/forum/${encodeURIComponent(slug||('thread-'+id))}`;
  return `${BASE}/forum/thread.php?t=${id}`;
}
function forumPostsOpen(threadId){
  pmThreadId = threadId;
  pmOffset = 0;
  document.getElementById('pm_thread_id').textContent = threadId;
  document.getElementById('postModal').style.display = 'block';
  forumPostsLoad();
}
function forumPostsClose(){
  document.getElementById('postModal').style.display = 'none';
  document.getElementById('pm_table').querySelector('tbody').innerHTML = '';
  document.getElementById('pm_edit_id').value = '';
  document.getElementById('pm_edit_content').value = '';
}

async function forumPostsLoad(){
  const q = document.getElementById('pm_q').value.trim();
  const j = await api('forum/posts_list.php', {thread_id: pmThreadId, q, limit: pmLimit, offset: pmOffset});
  const tb = document.querySelector('#pm_table tbody'); tb.innerHTML = '';
  (j.items||[]).forEach(p=>{
    const tr = document.createElement('tr');
    const contentPreview = (p.content||'').toString().slice(0, 180).replace(/\n/g,' ');
    tr.innerHTML = `
      <td>${p.id}</td>
      <td>${escapeHtml(p.author||'')}</td>
      <td class="muted" title="${escapeHtml(p.content||'')}">${escapeHtml(contentPreview)}</td>
      <td>${p.created_at||''}</td>
      <td><button class="btn" onclick='forumPostEdit(${p.id}, ${JSON.stringify(p.content||'').replace(/'/g,"&#39;")})'>Edit</button></td>
    `;
    tb.appendChild(tr);
  });
  document.getElementById('pm_info').textContent = `Offset ${pmOffset} · geladen: ${(j.items||[]).length}`;
}
function forumPostsPrev(){ pmOffset = Math.max(0, pmOffset - pmLimit); forumPostsLoad(); }
function forumPostsNext(){ pmOffset += pmLimit; forumPostsLoad(); }

function forumPostEdit(id, content){
  document.getElementById('pm_edit_id').value = id;
  document.getElementById('pm_edit_content').value = content || '';
  // Scroll zum Editor
  document.getElementById('pm_edit_content').scrollIntoView({behavior:'smooth', block:'center'});
}

async function forumPostSave(){
  const id = parseInt(document.getElementById('pm_edit_id').value||'0',10);
  const content = document.getElementById('pm_edit_content').value;
  if (!id) return alert('Kein Post ausgewählt.');
  const j = await api('forum/post_update.php', {id, content});
  if (!j.ok) return;
  alert('Gespeichert');
  forumPostsLoad();
}

async function forumPostDelete(){
  const id = parseInt(document.getElementById('pm_edit_id').value||'0',10);
  if (!id) return alert('Kein Post ausgewählt.');
  if (!confirm('Post wirklich löschen?')) return;
  const j = await api('forum/post_delete.php', {id});
  if (!j.ok) return;
  alert('Gelöscht');
  document.getElementById('pm_edit_id').value = '';
  document.getElementById('pm_edit_content').value = '';
  forumPostsLoad();
}
async function resolveUploadsForum(rels){
  const j = await api('uploads/resolve_forum.php', {rels});
  if (!j.ok) return;

const map = j.map || {};
document.querySelectorAll('#uploadsTable tbody tr').forEach(tr=>{
  const rel = tr.dataset.rel;
  const cell = tr.querySelector('.theme-cell');
  if (!cell) return;
  const hits = map[rel];
  if (hits && hits.length) {
    const first = hits[0];
    const url   = threadUrl(first.thread_id, first.slug);
    const extra = hits.length > 1 ? ` (+${hits.length-1})` : '';
    cell.innerHTML = `<a href="${url}" target="_blank" rel="noreferrer noopener">#${first.thread_id} – ${escapeHtml(first.thread_title)}</a>${extra}`;
    cell.classList.remove('muted');
  } else {
    cell.textContent = '—';
    cell.classList.add('muted');
  }
});
}

// kleiner Helper für HTML-Escapes
function escapeHtml(s){ return (s||'').replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])); }

</script>
<?php
$content = ob_get_clean();
render_theme_page($content, 'Admin');
