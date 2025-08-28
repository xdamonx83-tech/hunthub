<?php
declare(strict_types=1);

require_once __DIR__ . '/auth/guards.php';
require_once __DIR__ . '/auth/db.php';
require_once __DIR__ . '/auth/csrf.php';
require_once __DIR__ . '/lib/layout.php';
$lang = function_exists('detect_lang') ? detect_lang() : 'de';
$GLOBALS['L'] = load_lang($lang);
$me  = require_auth();
$pdo = db();

$cfg      = require __DIR__ . '/auth/config.php';
$APP_BASE = rtrim($cfg['app_base'] ?? '/', '/'); // normalisiert: '/' -> ''
$csrf     = issue_csrf($pdo, $_COOKIE[$cfg['cookies']['session_name']] ?? '');

// Fallbacks
$coverFallback  = $APP_BASE . '/assets/images/cover-placeholder.jpg';
$avatarFallback = $APP_BASE . '/assets/images/avatars/placeholder.png';

/* ---- SAFE: undefined keys abfangen ---- */
$coverSrc  = (string)($me['cover_path']  ?? '');
$avatarSrc = (string)($me['avatar_path'] ?? '');
$coverSrc  = $coverSrc  !== '' ? $coverSrc  : $coverFallback;
$avatarSrc = $avatarSrc !== '' ? $avatarSrc : $avatarFallback;

$coverX     = isset($me['cover_x'])     ? (float)$me['cover_x']     : null;
$coverY     = isset($me['cover_y'])     ? (float)$me['cover_y']     : null;
$coverScale = isset($me['cover_scale']) ? (float)$me['cover_scale'] : null;

$title = 'Profil bearbeiten – HTDA';

ob_start();
?>
<meta name="csrf" content="<?= htmlspecialchars($csrf) ?>">

<main>
  <section class="section-py">
    <div class="container pt-30p">

      <!-- Cover -->
      <div class="relative rounded-32 overflow-hidden">
        <div id="coverFrame"
             class="relative w-full xl:h-[472px] lg:h-[400px] md:h-[340px] sm:h-[300px] h-[240px] bg-black/30 overflow-hidden rounded-32">
          <img id="coverImg"
               src="<?= htmlspecialchars($coverSrc) ?>"
               alt="cover"
               class="absolute top-0 left-0 will-change-transform select-none pointer-events-none"
               style="transform-origin: 0 0;">
          <label for="coverInput"
                 class="cursor-pointer absolute xl:top-[30px] md:top-5 top-4 xl:right-[30px] md:right-5 right-4 z-[5]">
            <span class="flex-c size-60p rounded-full bg-b-neutral-3 text-w-neutral-1 icon-32">
              <i class="ti ti-camera"></i>
            </span>
          </label>
          <input type="file" id="coverInput" accept="image/png,image/jpeg,image/webp" class="hidden">
        </div>
      </div>

      <!-- Avatar -->
      <div class="relative flex 3xl:items-end max-3xl:items-center 3xl:justify-between max-3xl:flex-col gap-30p
                  3xl:mt-[90px] xl:-mt-52 lg:-mt-44 md:-mt-36 sm:-mt-30 -mt-20 4xl:mb-[70px] mb-60p">
        <div class="3xl:absolute 3xl:bottom-0 3xl:left-1/2 3xl:-translate-x-1/2 max-3xl:flex-col-c z-[4]">
          <img id="avatarImg"
               class="avatar xl:size-60 lg:size-52 md:size-44 sm:size-40 size-28 border-2 border-secondary rounded-full object-cover"
               src="<?= htmlspecialchars($avatarSrc) ?>" alt="avatar">
          <label for="avatarInput"
                 class="cursor-pointer absolute lg:-bottom-6 md:-bottom-5 -bottom-4 left-1/2 -translate-x-1/2">
            <span class="flex-c size-60p rounded-full bg-primary text-b-neutral-4 icon-32">
              <i class="ti ti-camera"></i>
            </span>
          </label>
          <input type="file" id="avatarInput" accept="image/png,image/jpeg,image/webp" class="hidden">
        </div>
      </div>

      <!-- Formular -->
      <div class="grid grid-cols-12 gap-30p">
        <div class="xxl:col-start-3 xxl:col-end-11 col-span-12 ">
          <div class="bg-b-neutral-3 rounded-12 p-40p">
            <h4 class="heading-4 text-w-neutral-1 mb-60p" data-i18n="general"><?php echo $L['general']; ?></h4>
            <!-- feste absolute URL, unabhängig von app_base -->
            <form id="profileForm" action="/api/auth/update_profile.php" method="post" enctype="multipart/form-data">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

              <div class="grid grid-cols-8 gap-30p">
                <div class="sm:col-span-4 col-span-8">
                  <label class="label label-lg mb-3" data-i18n="username"><?php echo $L['username']; ?></label>
                  <input type="text" name="display_name" value="<?= htmlspecialchars($me['display_name']) ?>" required class="box-input-3" />
                </div>
        
                <div class="col-span-8">
                  <label class="label label-lg mb-3" data-i18n="email"><?php echo $L['email']; ?></label>
                  <input type="email" name="email" value="<?= htmlspecialchars($me['email']) ?>" class="box-input-3" />
                </div>
                <div class="col-span-8">
                  <label class="label label-lg mb-3" data-i18n="about"><?php echo $L['about']; ?></label>
                  <textarea class="box-input-3 h-[142px]" name="bio" rows="4"><?= htmlspecialchars($me['bio'] ?? '') ?></textarea>
                </div>
                <div class="col-span-8">
                  <label class="label label-lg mb-3" data-i18n="location"><?php echo $L['location']; ?></label>
                  <input type="text" name="location" class="box-input-3" />
                </div>
                <div class="sm:col-span-4 col-span-8">
                  <label class="label label-lg mb-3" data-i18n="slug"><?php echo $L['slug']; ?></label>
                  <input type="text" value="https://htda.de/u/<?= htmlspecialchars($me['slug']) ?>" class="box-input-3" disabled />
                </div>
              
				
				
				
				</div>
				
				
				
				
				
				
				
				
				
				
				
				<div class="grid grid-cols-8 gap-30p">
				
    <div class="sm:col-span-4 col-span-8">
      <label class="label label-lg mb-3">Twitch</label>
      <input name="twitch" type="text" class="box-input-3" placeholder="@deinname / oder URL"
             value="<?= htmlspecialchars((string)($me['social_twitch'] ?? ''), ENT_QUOTES) ?>" />
    </div>
    <div class="sm:col-span-4 col-span-8">
      <label class="label label-lg mb-3">TikTok</label>
      <input name="tiktok" type="text" class="box-input-3" placeholder="@deinname / oder URL"
             value="<?= htmlspecialchars((string)($me['social_tiktok'] ?? ''), ENT_QUOTES) ?>" />
    </div>
    <div class="sm:col-span-4 col-span-8">
      <label class="label label-lg mb-3">YouTube</label>
      <input name="youtube" type="text" class="box-input-3" placeholder="@handle | c/Name | channel/UC... | URL"
             value="<?= htmlspecialchars((string)($me['social_youtube'] ?? ''), ENT_QUOTES) ?>" />
    </div>
    <div class="sm:col-span-4 col-span-8">
      <label class="label label-lg mb-3">Instagram</label>
      <input name="instagram" type="text" class="box-input-3" placeholder="@deinname / oder URL"
             value="<?= htmlspecialchars((string)($me['social_instagram'] ?? ''), ENT_QUOTES) ?>" />
    </div>
    <div class="sm:col-span-4 col-span-8">
      <label class="label label-lg mb-3">Twitter (X)</label>
      <input name="twitter" type="text" class="box-input-3" placeholder="@deinname / oder URL"
             value="<?= htmlspecialchars((string)($me['social_twitter'] ?? ''), ENT_QUOTES) ?>" />
    </div>
    <div class="sm:col-span-4 col-span-8">
      <label class="label label-lg mb-3">Facebook</label>
      <input name="facebook" type="text" class="box-input-3" placeholder="Seitenname / Profil-URL"
             value="<?= htmlspecialchars((string)($me['social_facebook'] ?? ''), ENT_QUOTES) ?>" />
    </div>		
				
	
				
				
				
				
              </div>


              <div class="flex items-center md:justify-end justify-center">
                <button class="btn btn-md btn-primary rounded-12 mt-60p" data-i18n="save"><?php echo $L['save']; ?></button>
              </div>
            </form>
          </div>
        </div>
      </div>

    </div>
  </section>
</main>

<!-- Overlay/Editor -->
<style>
  .modal-mask{position:fixed;inset:0;background:rgba(0,0,0,.7);display:none;z-index:9999;}
  .modal-wrap{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:20px;}
  .modal-card{background:#0b0b0b;border:1px solid rgba(255,255,255,.1);border-radius:16px;max-width:980px;width:100%;}
  .modal-hd{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;border-bottom:1px solid rgba(255,255,255,.08);}
  .modal-bd{padding:18px;}
  .frame{position:relative;overflow:hidden;background:#111;border-radius:12px;}
  .editor-img{position:absolute;top:0;left:0;will-change:transform;user-select:none;cursor:grab;transform-origin:0 0;}
  .editor-img.dragging{cursor:grabbing;}
  .controls{display:flex;gap:10px;align-items:center;justify-content:flex-end;margin-top:14px;}
  input[type="range"]{width:220px;}
  .btnx{display:inline-flex;align-items:center;gap:8px;border-radius:10px;padding:10px 14px;border:1px solid rgba(255,255,255,.15);background:#1b1b1b;color:#fff}
  .btnx.primary{background:#f29620;color:#111;border-color:#f29620}
</style>
<div id="coverModal" class="modal-mask">
  <div class="modal-wrap">
    <div class="modal-card">
      <div class="modal-hd">
        <strong>Cover positionieren</strong>
        <button type="button" class="btnx" id="coverCancel">Abbrechen</button>
      </div>
      <div class="modal-bd">
        <div id="editFrame" class="frame" style="width:100%;height:472px;max-height:60vh;">
          <img id="editImg" class="editor-img" src="" alt="">
        </div>
        <div class="controls">
          <label>Zoom</label>
          <input id="zoom" type="range" min="0.5" max="3" step="0.01" value="1">
          <button class="btnx" id="centerBtn" type="button">Zentrieren</button>
          <button class="btnx primary" id="saveCover" type="button">Speichern</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(() => {
  const CSRF = document.querySelector('meta[name="csrf"]')?.content || '';
  const API  = document.getElementById('profileForm')?.action || '/api/auth/update_profile.php';

  async function postJson(url, formData) {
    const res = await fetch(url, {
      method: 'POST',
      body: formData,
      credentials: 'include',
      headers: { 'Accept': 'application/json' }
    });
    const txt = await res.text();
    const ct = res.headers.get('content-type') || '';
    if (!ct.includes('application/json')) {
      console.error('Nicht-JSON:', res.status, txt.slice(0,300));
      throw new Error('Server lieferte keine JSON-Antwort.');
    }
    const json = JSON.parse(txt);
    if (!res.ok || json?.ok === false) throw new Error(json?.error || 'Fehler');
    return json;
  }

  // Avatar Upload
  document.getElementById('avatarInput')?.addEventListener('change', async (e) => {
    const file = e.target.files?.[0]; if (!file) return;
    const img = document.getElementById('avatarImg');
    const old = img.src, url = URL.createObjectURL(file); img.src = url;
    const fd = new FormData(); fd.append('avatar', file); fd.append('csrf', CSRF);
    try { const out = await postJson(API, fd); if (out.avatar) img.src = out.avatar; }
    catch (err) { alert(err.message); img.src = old; }
    finally { URL.revokeObjectURL(url); e.target.value=''; }
  });

  /* ===== Cover-Editor: pan-normalisierte Speicherung (u,v ∈ [0..1]) ===== */
  const coverInput = document.getElementById('coverInput');
  const modal      = document.getElementById('coverModal');
  const editFrame  = document.getElementById('editFrame');
  const editImg    = document.getElementById('editImg');
  const zoomEl     = document.getElementById('zoom');
  const centerBtn  = document.getElementById('centerBtn');
  const saveBtn    = document.getElementById('saveCover');
  const cancelBtn  = document.getElementById('coverCancel');
  const liveCover  = document.getElementById('coverImg');
  const liveFrame  = document.getElementById('coverFrame');

  let fileObj = null, natW = 0, natH = 0;
  let posX = 0, posY = 0, scale = 1;
  let dragging = false, startX = 0, startY = 0, startPX = 0, startPY = 0;

  function setTransform() { editImg.style.transform = `translate(${posX}px, ${posY}px) scale(${scale})`; }
  function clamp(frameW, frameH, imgW, imgH) {
    const minX = Math.min(0, frameW - imgW), minY = Math.min(0, frameH - imgH);
    posX = Math.min(0, Math.max(minX, posX));
    posY = Math.min(0, Math.max(minY, posY));
  }
  function centerFit() {
    const frameW = editFrame.clientWidth, frameH = editFrame.clientHeight;
    const fit = Math.max(frameW / natW, frameH / natH);
    scale = Math.max(0.5, Math.min(3, fit));
    const imgW = natW * scale, imgH = natH * scale;
    posX = (frameW - imgW) / 2; posY = (frameH - imgH) / 2;
    clamp(frameW, frameH, imgW, imgH); setTransform(); zoomEl.value = String(scale);
  }
  function openModal(url) {
    editImg.onload = () => { natW = editImg.naturalWidth; natH = editImg.naturalHeight; centerFit(); };
    editImg.src = url; modal.style.display = 'block';
  }
  function closeModal() { modal.style.display = 'none'; editImg.src=''; fileObj=null; }

  // Drag
  editImg.addEventListener('mousedown', (e) => { dragging=true; editImg.classList.add('dragging');
    startX=e.clientX; startY=e.clientY; startPX=posX; startPY=posY; e.preventDefault(); });
  window.addEventListener('mousemove', (e) => { if(!dragging) return;
    posX = startPX + (e.clientX - startX); posY = startPY + (e.clientY - startY);
    clamp(editFrame.clientWidth, editFrame.clientHeight, natW*scale, natH*scale); setTransform(); });
  window.addEventListener('mouseup',   () => { dragging=false; editImg.classList.remove('dragging'); });
  // Touch
  editImg.addEventListener('touchstart', (e)=>{ const t=e.touches[0]; dragging=true; startX=t.clientX; startY=t.clientY; startPX=posX; startPY=posY; }, {passive:true});
  editImg.addEventListener('touchmove',  (e)=>{ if(!dragging) return; const t=e.touches[0];
    posX=startPX+(t.clientX-startX); posY=startPY+(t.clientY-startY);
    clamp(editFrame.clientWidth, editFrame.clientHeight, natW*scale, natH*scale); setTransform(); }, {passive:true});
  editImg.addEventListener('touchend',   ()=>{ dragging=false; });

  // Zoom Slider
  zoomEl.addEventListener('input', () => {
    const frameW=editFrame.clientWidth, frameH=editFrame.clientHeight;
    const centerFx=(frameW/2 - posX)/scale, centerFy=(frameH/2 - posY)/scale;
    const newScale=parseFloat(zoomEl.value);
    posX = frameW/2 - centerFx*newScale; posY = frameH/2 - centerFy*newScale;
    scale=newScale; clamp(frameW, frameH, natW*scale, natH*scale); setTransform();
  });

  // Zoom per Wheel
  editFrame.addEventListener('wheel', (e) => {
    e.preventDefault();
    const delta = -Math.sign(e.deltaY)*0.05;
    const newScale = Math.min(3, Math.max(0.5, scale + delta));
    const rect = editFrame.getBoundingClientRect();
    const cx = e.clientX - rect.left, cy = e.clientY - rect.top;
    const imgCx = (cx - posX)/scale, imgCy = (cy - posY)/scale;
    posX = cx - imgCx*newScale; posY = cy - imgCy*newScale;
    scale=newScale; clamp(editFrame.clientWidth, editFrame.clientHeight, natW*scale, natH*scale); setTransform(); zoomEl.value=String(scale);
  }, {passive:false});

  centerBtn.addEventListener('click', centerFit);
  cancelBtn.addEventListener('click', closeModal);

  // Datei wählen → Editor öffnen
  coverInput?.addEventListener('change', (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    fileObj = file;
    const url = URL.createObjectURL(file);
    openModal(url);
  });

  // ===== Pan-normalisierte Anwendung auf beliebiges Frame =====
  function applyPanNormalized(imgEl, frameEl, relScale, u, v) {
    if (!imgEl || !frameEl) return;
    const probe = new Image();
    probe.onload = () => {
      const natW = probe.naturalWidth, natH = probe.naturalHeight;
      const fw = frameEl.clientWidth, fh = frameEl.clientHeight;
      const fit = Math.max(fw / natW, fh / natH);
      const s   = (relScale ?? 1) * fit;

      const imgW = natW * s, imgH = natH * s;
      const overflowX = Math.max(0, imgW - fw);
      const overflowY = Math.max(0, imgH - fh);

      const uu = (u == null) ? 0.5 : Math.min(1, Math.max(0, u));
      const vv = (v == null) ? 0.5 : Math.min(1, Math.max(0, v));

      // negatives Vorzeichen für Links-/Oben-Verschiebung
      const x = overflowX > 0 ? -overflowX * uu : (fw - imgW) / 2;
      const y = overflowY > 0 ? -overflowY * vv : (fh - imgH) / 2;

      imgEl.style.transformOrigin = '0 0';
      imgEl.style.transform = `translate(${x}px, ${y}px) scale(${s})`;
    };
    probe.src = imgEl.currentSrc || imgEl.src;
  }

  // global verfügbar machen für das zweite <script>
  window.applyPanNormalized = applyPanNormalized;

  // Speichern → Datei + pan-normalisierte Position an API
// Speichern → Datei + pan-normalisierte Position (u,v ∈ [0..1]) an API
saveBtn.addEventListener('click', async () => {
  if (!fileObj) return;

  const frameW = editFrame.clientWidth, frameH = editFrame.clientHeight;
  const fit    = Math.max(frameW / natW, frameH / natH); // Fit im Editor
  const relScale = scale / fit;

  const imgW = natW * scale, imgH = natH * scale;
  const overflowX = Math.max(0, imgW - frameW);
  const overflowY = Math.max(0, imgH - frameH);

  // WICHTIG: negatives Vorzeichen → links schieben = u steigt nach rechts
  let u = overflowX > 0 ? (-posX) / overflowX : 0.5; // 0..1
  let v = overflowY > 0 ? (-posY) / overflowY : 0.5; // 0..1
  u = Math.min(1, Math.max(0, u));
  v = Math.min(1, Math.max(0, v));

  const fd = new FormData();
  fd.append('cover', fileObj);
  fd.append('cover_x', String(u));       // u ∈ [0,1]
  fd.append('cover_y', String(v));       // v ∈ [0,1]
  fd.append('cover_scale', String(relScale));
  fd.append('csrf', CSRF);

  try {
    const out = await postJson(API, fd);
    if (out.cover) liveCover.src = out.cover;

    const rx = ('cover_x' in out) ? out.cover_x : u;
    const ry = ('cover_y' in out) ? out.cover_y : v;
    const rs = ('cover_scale' in out) ? out.cover_scale : relScale;

    applyPanNormalized(liveCover, liveFrame, rs, rx, ry);
    closeModal();
  } catch (err) { alert(err.message || 'Fehler beim Speichern'); }
});


  // Formular (andere Felder)
  document.getElementById('profileForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    try { await postJson(e.target.action, new FormData(e.target)); location.reload(); }
    catch (err) { alert(err.message); }
  });
})();
</script>

<script>
/* ===== Initiale Anzeige der gespeicherten pan-normalisierten Werte ===== */
(function initCoverFromSaved() {
  const frame = document.getElementById('coverFrame');
  const img   = document.getElementById('coverImg');
  if (!frame || !img) return;

  const saved = {
    u:   <?= json_encode($coverX) ?>,
    v:   <?= json_encode($coverY) ?>,
    srl: <?= json_encode($coverScale) ?>
  };

  const apply = () => applyPanNormalized(img, frame, saved.srl, saved.u, saved.v);

  window.addEventListener('load', apply);
  window.addEventListener('resize', () => {
    clearTimeout(window.__coverFitTimer);
    window.__coverFitTimer = setTimeout(apply, 80);
  });
})();
();
</script>

<?php
$content = ob_get_clean();
render_theme_page($content, $title);
