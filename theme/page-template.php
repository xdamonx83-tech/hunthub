<?php
declare(strict_types=1);
$cfg = $cfg ?? require __DIR__ . '/../auth/config.php';
if (!isset($APP_BASE) || !is_string($APP_BASE)) {
    $APP_BASE = rtrim($cfg['app_base'] ?? '', '/'); // im Root: ''
}
require_once __DIR__ . '/../auth/guards.php';
$me = current_user();
$meAvatar  = !empty($me['avatar_path']) ? $me['avatar_path'] : ($APP_BASE . '/assets/images/avatars/placeholder.png');
$meProfile = $APP_BASE . '/profile.php?id=' . (int)($me['id'] ?? 0);
$cfg = require __DIR__ . '/../auth/config.php';
$APP_BASE = rtrim($cfg['app_base'] ?? '', '/');

$cfg = $cfg ?? require __DIR__ . '/../auth/config.php';
if (!isset($APP_BASE)) { $APP_BASE = rtrim($cfg['app_base'] ?? '', '/'); }
require_once __DIR__ . '/../auth/db.php';
require_once __DIR__ . '/../auth/csrf.php';
$pdo = $pdo ?? db();
$sessionName = $cfg['cookies']['session_name'] ?? '';
$csrf = $csrf ?? issue_csrf($pdo, $_COOKIE[$sessionName] ?? '');
function current_url_with_lang(string $lang): string {
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $uri    = $_SERVER['REQUEST_URI'] ?? '/';

  $u = parse_url($uri);
  $path = $u['path'] ?? '/';
  parse_str($u['query'] ?? '', $qs);
  $qs['lang'] = $lang;

  $q = http_build_query($qs);
  return $scheme . '://' . $host . $path . ($q ? ('?' . $q) : '');
}

function is_mobile_request(): bool {
    // Manuelle Überschreibung zum Testen: ?view=mobile / ?view=desktop
    $force = $_GET['view'] ?? null;
    if ($force === 'mobile')  return true;
    if ($force === 'desktop') return false;

    // > Für Caches: wir variieren auf UA und CH
    header('Vary: User-Agent, Sec-CH-UA-Platform, Sec-CH-UA-Mobile', false);

    // 1) Client Hints (nur über HTTPS und wenn Browser sie sendet)
    $chMobile   = $_SERVER['HTTP_SEC_CH_UA_MOBILE']   ?? '';
    $chPlatform = $_SERVER['HTTP_SEC_CH_UA_PLATFORM'] ?? '';

    // Sec-CH-UA-Mobile kommt typischerweise als "?1" für mobile
    if ($chMobile !== '') {
        if (strpos($chMobile, '?1') !== false || $chMobile === '1') {
            return true;
        }
        // iPad/Tablet kann "?0" sein; unten fällt der UA-Regex zurück
    }

    // 2) User-Agent Fallback (breiter Satz inkl. Tablets + iPadOS 13+)
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // Klassische Mobiles/Tablets
    $mobileRegex = '/Android|webOS|iPhone|iPod|BlackBerry|IEMobile|Windows Phone|Opera Mini|Opera Mobi|Mobi/i';
    $tabletRegex = '/iPad|Tablet|Nexus 7|Nexus 9|Nexus 10|SM-T|Kindle|Silk|PlayBook/i';

    if (preg_match($mobileRegex, $ua) || preg_match($tabletRegex, $ua)) {
        return true;
    }

    // iPadOS 13+: meldet sich als "Macintosh" + "Mobile/<Build>" in Safari
    if (stripos($ua, 'Macintosh') !== false && stripos($ua, 'Mobile/') !== false && stripos($ua, 'Safari') !== false) {
        return true;
    }

    // Optional: wenn CH-Platform "Android" oder "iOS" enthält
    if ($chPlatform) {
        $p = strtolower($chPlatform);
        if (strpos($p, 'android') !== false || strpos($p, 'ios') !== false) {
            return true;
        }
    }

    return false;
}

// Nutzung
if (is_mobile_request()) {
    include __DIR__ . '/partials/bottom_bar.php';
} else {
  
}

$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$is_mobile = (bool) preg_match('/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $ua);

?>


<!doctype html>
<html dir="ltr" lang="de">
<meta name="app-base" content="<?= htmlspecialchars($APP_BASE ?? '', ENT_QUOTES) ?>">
<script>
// Verfügbar für alle Skripte:
window.I18N = <?= json_encode($__i18n, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;

/** Simple t() ohne Netzwerkanfragen */
window.t = function(key) {
  var val = (window.I18N && window.I18N[key]) || key;
  // einfache %s-Platzhalter:
  if (arguments.length > 1) {
    var args = Array.prototype.slice.call(arguments, 1);
    var i = 0;
    val = val.replace(/%s/g, function(){ return String(args[i++] ?? ''); });
  }
  return val;
};

/** APP_BASE für sichere Pfade */
window.APP_BASE = document.querySelector('meta[name="app-base"]')?.content || '';
</script>

<head>
    <meta name="app-base" content="<?= rtrim($APP_BASE, '/') ?>">
    <meta name="csrf" content="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="me-id" content="<?= (int)($me['id'] ?? 0) ?>">
<html lang="<?= htmlspecialchars($lang ?? 'de') ?>">
<meta name="lang" content="<?= htmlspecialchars($lang ?? 'de') ?>">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" href="../assets/images/icons/favicon.ico" type="image/x-icon" />
    <title>HuntHub.online</title>
<script defer src="../assets/js/app.js"></script><link href="../assets/styles/app.css" rel="stylesheet">
<!-- Toast UI Editor (CSS/JS via CDN) -->
<link rel="stylesheet" href="https://uicdn.toast.com/editor/latest/toastui-editor.min.css">
<script src="https://uicdn.toast.com/editor/latest/toastui-editor-all.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js"></script>
<script defer src="/assets/js/editor.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/plyr@3/dist/plyr.css">
<script defer src="https://cdn.jsdelivr.net/npm/plyr@3/dist/plyr.polyfilled.min.js"></script>
<script defer src="/assets/js/plyr-init.js"></script>

<link href="/theme/css/style.css" rel="stylesheet">
<script src="/socket.io/socket.io.js"></script>
<style>
.achv-toast{position:fixed;right:16px;bottom:16px;max-width:320px;padding:14px;background:rgba(20,20,20,.92);
  border:1px solid rgba(255,255,255,.12);border-radius:12px;backdrop-filter:blur(6px);box-shadow:0 10px 20px rgba(0,0,0,.35);
  color:#e5e7eb;z-index:9999;display:flex;gap:12px;align-items:center;animation:achv-pop .25s ease-out}
.achv-toast__icon{width:40px;height:40px;border-radius:8px;background:#111;overflow:hidden}
.achv-toast__icon img{width:100%;height:100%;object-fit:cover;display:block}
.achv-toast__title{font-weight:700;font-size:15px;margin-bottom:4px}
.achv-toast__desc{font-size:13px;opacity:.9}
.achv-toast__points{font-size:12px;opacity:.75;margin-top:6px}
@keyframes achv-pop{from{transform:translateY(8px);opacity:0}to{transform:translateY(0);opacity:1}}
        :root {
            --background-color: #1a1a1a;
            --text-color: #c5b7a3;
            --accent-color: #e66a35;
            --overlay-color: rgba(26, 26, 26, 0.98);
        }

  @media (min-width: 1024px) {
    .mobile-bottom-bar { display: none !important; }
	
  }
  /* Desktop-only */
  @media (max-width: 1023.98px) {
    .nav-item { display: none !important; }
	.grid-mobile { margin-top:-240px !important; }
  }

        /* Preloader Container */
        #preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 9999;
            background-color: var(--overlay-color);
            /* SVG als Hintergrund für eine schmutzige Textur */
            background-image: url('data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"%3E%3Cfilter id="noise"%3E%3CfeTurbulence type="fractalNoise" baseFrequency="0.65" numOctaves="3" stitchTiles="stitch"/%3E%3C/filter%3E%3Crect width="100%" height="100%" filter="url(%23noise)" opacity="0.05"/%3E%3C/svg%3E');
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            transition: opacity 0.8s ease-in-out, visibility 0.8s ease-in-out;
            opacity: 1;
            visibility: visible;
        }

        /* Klasse, die hinzugefügt wird, um den Preloader auszublenden */
        #preloader.loaded {
            opacity: 0;
            visibility: hidden;
        }

        /* Lade-Animation Wrapper */
        .loader-container {
            position: relative;
            width: 150px;
            height: 150px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        /* Das zentrale Symbol - ein SVG für scharfe Kanten */
        .loader-icon {
            width: 100px;
            height: 100px;
            animation: pulse 2s infinite ease-in-out;
        }

        /* Äußere rotierende Kreise */
        .loader-spinner {
            position: absolute;
            width: 100%;
            height: 100%;
            border: 2px solid transparent;
            border-top-color: var(--text-color);
            border-radius: 50%;
            animation: spin 1.5s linear infinite;
        }

        .loader-spinner.inner {
            width: 80%;
            height: 80%;
            border-top-color: transparent;
            border-bottom-color: var(--text-color);
            animation: spin-reverse 2.5s linear infinite;
        }

        /* Lade-Text */
        .loader-text {
            margin-top: 25px;
            font-size: 1.2rem;
            color: var(--text-color);
            letter-spacing: 3px;
            text-transform: uppercase;
            animation: flicker 1.5s infinite alternate;
        }

        /* Hauptinhalt der Seite */
        #page-content {
            display: none; /* Initial versteckt */
            padding: 2rem;
            text-align: center;
        }
        
        #page-content h1 {
            color: var(--accent-color);
            font-size: 3rem;
        }


        /* Keyframe-Animationen */
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes spin-reverse {
            from { transform: rotate(0deg); }
            to { transform: rotate(-360deg); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(0.95); opacity: 0.8; }
            50% { transform: scale(1.05); opacity: 1; }
        }

        @keyframes flicker {
            0%, 100% { opacity: 1; text-shadow: 0 0 5px var(--text-color); }
            50% { opacity: 0.7; text-shadow: none; }
        }
		/* Legal Modal */
.hh-legal.hidden { display: none; }
.hh-legal { position: fixed; inset: 0; z-index: 9999; }
.hh-legal__backdrop {
  position: absolute; inset: 0; background: rgba(0,0,0,.7);
  backdrop-filter: blur(2px);
}
.hh-legal__panel {
  position: relative; max-width: 900px; max-height: 85vh;
  margin: 5vh auto; padding: 1.25rem 1.25rem 1.5rem;
  overflow: auto; border-radius: 16px;
  background: #151312; /* dunkles Braun/Schwarz, Hunt-Style */
  box-shadow: 0 20px 60px rgba(0,0,0,.6);
}
.hh-legal__title {
  margin: 0 2.5rem .75rem 0; font-size: 1.4rem; color: #eee;
  letter-spacing: .02em;
}
.hh-legal__content { line-height: 1.7; color: #ddd; }
.hh-legal__content h1,.hh-legal__content h2,.hh-legal__content h3 { color:#f0f0f0; margin:1rem 0 .25rem; }
.hh-legal__content a { text-decoration: underline; }
.hh-legal__close {
  position: absolute; top: .75rem; right: .75rem;
  background: transparent; border: 0; font-size: 1.25rem; color: #bbb; cursor: pointer;
}
.hh-legal__close:hover { color: #fff; }

</style>
<script>
window.showAchievementPopup = function (data) {
  const el = document.createElement('div');
  el.className = 'achv-toast';
  el.innerHTML = `
    ${data.icon ? `<div class="achv-toast__icon"><img alt="" src="${String(data.icon)}"></div>` : ''}
    <div class="achv-toast__body">
      <div class="achv-toast__title">Erfolg freigeschaltet</div>
      <div class="achv-toast__desc">${String(data.title||'')}</div>
      ${data.points ? `<div class="achv-toast__points">+${data.points} Punkte</div>` : ''}
    </div>`;
  document.body.appendChild(el);
  setTimeout(()=>{ el.style.opacity='0'; el.style.transform='translateY(6px)'; }, 3200);
  setTimeout(()=>{ el.remove(); }, 3600);
};
</script>

</head>
<body>

    <!-- preloader start -->
    <div id="preloader">
        <div class="loader-container">
            <div class="loader-spinner"></div>
            <div class="loader-spinner inner"></div>
            <!-- SVG-Icon im Hunt-Stil -->
            <svg class="loader-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M50 10 L50 90 M10 50 L90 50" stroke-width="1" stroke-opacity="0.5"/>
                <path d="M20 20 L80 80 M80 20 L20 80" stroke-width="1" stroke-opacity="0.5"/>
                <circle cx="50" cy="50" r="40" stroke-width="2" />
                <circle cx="50" cy="50" r="30" stroke-width="1.5" />
                <path d="M50 25 L40 50 L50 75 L60 50 Z" stroke-width="2.5" fill="rgba(197, 183, 163, 0.1)"/>
            </svg>
        </div>
        <div class="loader-text">LADE...</div>
    </div>
	    <script>
        // Simuliert eine Ladezeit und blendet dann den Preloader aus
        window.addEventListener('load', function() {
            const preloader = document.getElementById('preloader');
            const pageContent = document.getElementById('page-content');

            // Simuliert eine Mindest-Ladezeit von 2.5 Sekunden, damit man die Animation sieht
setTimeout(() => {
                // Fügt die 'loaded'-Klasse hinzu, um die Fade-Out-Animation zu starten
                preloader.classList.add('loaded');

                // Stellt sicher, dass pageContent korrekt ausgewählt wird, z.B. nach einer Klasse
                const pageContent = document.querySelector('.page-content'); // Beispiel: Wählt ein Element mit der Klasse 'page-content'

                // Macht den Hauptinhalt sichtbar, nachdem die Animation fast fertig ist
                preloader.addEventListener('transitionend', () => {
                    document.body.style.overflow = 'auto'; // Scrollen wieder erlauben
                    if (pageContent) { // Überprüft, ob pageContent gefunden wurde, bevor darauf zugegriffen wird
                        pageContent.style.display = 'block';
                    }
                    // Optional: Preloader komplett aus dem DOM entfernen
                    // preloader.remove();
                });
            }, 300);
        });
    </script>
    <!-- preloader end -->

    <!-- scroll to top button start -->
    <button class="scroll-to-top show" id="scrollToTop">
    <i class="ti ti-arrow-up"></i>
</button>
    <!-- scroll to top button end -->

    <!-- header start -->
 <header id="header" class="absolute w-full z-[999]">
  <div class="mx-auto relative">
    <div id="header-nav" class="w-full px-24p bg-b-neutral-3 relative">
      <div class="flex items-center justify-between gap-x-2 mx-auto py-20p">
        <nav class="relative xl:grid xl:grid-cols-12 flex justify-between items-center gap-24p text-semibold w-full">
          <div class="3xl:col-span-6 xl:col-span-5 flex items-center 3xl:gap-x-10 gap-x-5">
            <a href="/" class="shrink-0">
              <img class="xl:w-[170px] sm:w-36 w-30 h-auto shrink-0" src="../assets/images/icons/logo.png" alt="brand" />
            </a>







    <form action="/forum/search.php" method="GET" id="headerSearchContainer"
              class="relative hidden lg:flex items-center sm:gap-3 gap-2 min-w-[300px] max-w-[670px] w-full px-20p py-16p bg-b-neutral-4 rounded-full">
              <span class="flex-c icon-20 text-white">
                <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-zoom-code"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" /><path d="M21 21l-6 -6" /><path d="M8 8l-2 2l2 2" /><path d="M12 8l2 2l-2 2" /></svg>
              </span>
              <input autocomplete="off" id="headerSearchInput" class="bg-transparent w-full" type="text" name="q" 
                placeholder="<?php echo $L['fsearch']; ?>" /><button type="submit" class="btn-c btn-c-md text-w-neutral-4">
            <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-send-2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4.698 4.034l16.302 7.966l-16.302 7.966a.503 .503 0 0 1 -.546 -.124a.555 .555 0 0 1 -.12 -.568l2.468 -7.274l-2.468 -7.274a.555 .555 0 0 1 .12 -.568a.503 .503 0 0 1 .546 -.124z" /><path d="M6.5 12h14.5" /></svg>
        </button>
            </form>
<div id="headerSearchResults" style="margin-top: 180px;width: 700px;margin-left: 200px;" class="hidden absolute top-full mt-2 w-full bg-b-neutral-3 rounded-lg shadow-lg border border-w-neutral-4/20 z-50 overflow-hidden">
        </div>














          </div>
		  <style>
/* Kleines Styling für das Popup */
#headerSearchResults a {
    display: flex;
    align-items: center;
    padding: 10px 14px;
    gap: 12px;
    text-decoration: none;
    transition: background-color 0.15s ease;
}
#headerSearchResults a:hover {
    background-color: rgba(255, 255, 255, 0.05);
}
#headerSearchResults .result-content {
    min-width: 0; /* Wichtig für Flexbox + Text-Umbruch */
}
#headerSearchResults .result-title {
    color: #e5e7eb;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
#headerSearchResults .result-meta {
    font-size: 0.8rem;
    color: #9aa5b1;
}
#headerSearchResults .search-loader {
    padding: 16px;
    text-align: center;
    color: #9aa5b1;
}
#headerSearchResults .search-no-results {
    padding: 16px;
    text-align: center;
    color: #9aa5b1;
}
#headerSearchResults .search-view-all {
    display: block;
    text-align: center;
    padding: 12px;
    background-color: rgba(0,0,0,0.2);
    font-weight: 600;
    color: #f29620;
}
.hh-lang__item.is-active{background:#1a1f25;color:#fff;border:1px solid rgba(255,255,255,.12)}
#lang-switcher .dropdown-content[hidden] {
  display: none !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('headerSearchContainer');
    const input = document.getElementById('headerSearchInput');
    const resultsDiv = document.getElementById('headerSearchResults');
    let searchTimeout;

    if (!container || !input || !resultsDiv) return;

    input.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        const query = input.value.trim();

        if (query.length < 3) {
            resultsDiv.classList.add('hidden');
            return;
        }

        resultsDiv.classList.remove('hidden');
        resultsDiv.innerHTML = '<div class="search-loader">Suche...</div>';

        searchTimeout = setTimeout(() => {
            fetch(`/api/forum/search.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.ok && data.threads) {
                        renderResults(data.threads, query);
                    }
                })
                .catch(error => {
                    console.error('Search error:', error);
                    resultsDiv.classList.add('hidden');
                });
        }, 300); // 300ms Verzögerung, um nicht bei jedem Tastenanschlag eine Anfrage zu senden
    });

    function renderResults(threads, query) {
        if (threads.length === 0) {
            resultsDiv.innerHTML = '<div class="search-no-results">Keine Treffer gefunden</div>';
            return;
        }

        let html = threads.map(thread => `
            <a href="${thread.url}">
                <img src="${thread.avatar}" class="avatar size-40p shrink-0" alt="${thread.author_name}">
                <div class="result-content">
                    <div class="result-title">${thread.title}</div>
                    <div class="result-meta">von ${thread.author_name} in ${thread.board_name}</div>
                </div>
            </a>
        `).join('');

        // "Alle Ergebnisse anzeigen"-Link
        html += `<a href="/forum/search.php?q=${encodeURIComponent(query)}" class="search-view-all">Alle Ergebnisse anzeigen</a>`;
        resultsDiv.innerHTML = html;
    }

    // Popup schließen, wenn man außerhalb klickt
    document.addEventListener('click', (e) => {
        if (!container.contains(e.target)) {
            resultsDiv.classList.add('hidden');
        }
    });
    
    // Popup beim Fokussieren wieder öffnen, falls es Ergebnisse gibt
    input.addEventListener('focus', () => {
        if (resultsDiv.innerHTML !== '' && input.value.length >= 3) {
            resultsDiv.classList.remove('hidden');
        }
    });
});
</script>
          <div class="3xl:col-span-6 xl:col-span-7 flex items-center xl:justify-between justify-end w-full">


<?php
// Aktuelle Sprache robust ermitteln (dein Original-Code)
$lang = $lang
  ?? (function_exists('detect_lang') ? detect_lang() : null)
  ?? ($_GET['lang'] ?? $_COOKIE['lang'] ?? 'de');
$lang = ($lang === 'en') ? 'en' : 'de';
$label = $lang === 'en' ? 'English' : 'Deutsch';
?>

<!-- Language Switcher Komponente -->
<div id="lang-switcher" class="relative">
  <!-- Button zum Öffnen/Schließen -->
  <button type="button"
          class="inline-flex items-center gap-2 pl-3 pr-3 py-2 rounded-full bg-b-neutral-4/50 hover:bg-b-neutral-4 text-w-neutral-1 transition-colors"
          aria-haspopup="true" aria-expanded="false">
    <i class="ti ti-language icon-18 opacity-80"></i>
    <span class="text-base"><?= $label ?></span>
    <i class="ti ti-chevron-down opacity-70 transition-transform duration-300"></i>
  </button>

  <!-- Dropdown-Menü -->
  <div class="dropdown-content absolute right-0 mt-2 w-[220px] rounded-12 border border-w-neutral-4/20 bg-b-neutral-3 shadow-xl p-6 z-[1000]"
       hidden> <!-- 'hidden' sorgt dafür, dass es beim Laden geschlossen ist -->
    <a href="?lang=de"
       class="lang-opt flex items-center gap-2 px-3 py-2 rounded-10 hover:bg-b-neutral-4/60 transition
              <?= $lang==='de' ? 'is-active' : '' ?>"
       data-lang="de">
      <span class="text-w-neutral-1 flex-1">Deutsch</span>
      <span class="px-1.5 py-0.5 text-xs rounded bg-b-neutral-4/70 text-w-neutral-2">DE</span>
      <?= $lang==='de' ? '<i class="ti ti-check ml-1"></i>' : '' ?>
    </a>

    <a href="?lang=en"
       class="lang-opt flex items-center gap-2 px-3 py-2 rounded-10 hover:bg-b-neutral-4/60 transition mt-2
              <?= $lang==='en' ? 'is-active' : '' ?>"
       data-lang="en">
      <span class="text-w-neutral-1 flex-1">English</span>
      <span class="px-1.5 py-0.5 text-xs rounded bg-b-neutral-4/70 text-w-neutral-2">EN</span>
      <?= $lang==='en' ? '<i class="ti ti-check ml-1"></i>' : '' ?>
    </a>
  </div>
</div>
<script>
(() => {
  const root = document.getElementById('lang-switcher');
  if (!root) return;
  const btn  = root.querySelector('button');
  const menu = root.querySelector('.dropdown-content');

  // Initial sicher schließen
  menu.setAttribute('hidden','');

  function open()  { menu.removeAttribute('hidden'); btn.setAttribute('aria-expanded','true'); }
  function close() { menu.setAttribute('hidden','');  btn.setAttribute('aria-expanded','false'); }

  btn.addEventListener('click', e => {
    e.preventDefault();
    e.stopPropagation();
    menu.hasAttribute('hidden') ? open() : close();
  });

  document.addEventListener('click', e => {
    if (!root.contains(e.target)) close();
  });
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') close();
  });

  // Sprachwechsel
  root.querySelectorAll('.lang-opt').forEach(a => {
    a.addEventListener('click', e => {
      e.preventDefault();
      const lang = a.dataset.lang || 'de';
      document.cookie = `lang=${lang}; path=/; max-age=${60*60*24*365}; SameSite=Lax`;
      const url = new URL(window.location.href);
      url.searchParams.set('lang', lang);
      window.location.href = url.toString();
    });
  });
})();
</script>

<!-- JavaScript für die Interaktivität -->
<script>
  // Stellt sicher, dass das Skript erst läuft, wenn die Seite komplett geladen ist.
  document.addEventListener('DOMContentLoaded', function () {
    const langSwitcher = document.getElementById('lang-switcher');
    
    // Bricht ab, wenn die Komponente nicht auf der Seite gefunden wird.
    if (!langSwitcher) {
      return; 
    }

    const button = langSwitcher.querySelector('button');
    const dropdown = langSwitcher.querySelector('.dropdown-content');
    const chevronIcon = button.querySelector('.ti-chevron-down');

    // Funktion, die das Dropdown ein- oder ausblendet
    function toggleDropdown(show) {
      const isHidden = dropdown.hasAttribute('hidden');
      // Entscheiden, ob angezeigt oder ausgeblendet werden soll
      const shouldShow = show === true || (show === undefined && isHidden);

      if (shouldShow) {
        dropdown.removeAttribute('hidden');
        button.setAttribute('aria-expanded', 'true');
        if (chevronIcon) chevronIcon.style.transform = 'rotate(180deg)';
      } else {
        dropdown.setAttribute('hidden', '');
        button.setAttribute('aria-expanded', 'false');
        if (chevronIcon) chevronIcon.style.transform = 'rotate(0deg)';
      }
    }

    // Event-Listener für den Klick auf den Button
    button.addEventListener('click', function (event) {
      // Verhindert, dass der Klick sofort das Menü wieder schließt,
      // falls das document-Event ebenfalls ausgelöst wird.
      event.stopPropagation(); 
      toggleDropdown(); // Zustand umschalten (auf/zu)
    });

    // Schließt das Menü bei einem Klick irgendwo anders auf der Seite
    document.addEventListener('click', function () {
      if (button.getAttribute('aria-expanded') === 'true') {
        toggleDropdown(false); // Schließen erzwingen
      }
    });

    // Schließt das Menü bei Drücken der 'Escape'-Taste für bessere Bedienbarkeit
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && button.getAttribute('aria-expanded') === 'true') {
            toggleDropdown(false);
        }
    });
  });
</script>





                                     
            <div class="flex items-center lg:gap-x-32p gap-x-2">
         
   
 
				  <?php include __DIR__ . '/partials/header_user_dropdown.php'; ?>



    <!-- right sidebar end -->
</div>
    <!-- sidebar end -->

    <!-- app layout start -->
    <div class="app-layout">

        <!-- main start -->


<?= $CONTENT ?>

        <!-- main end -->

        <!-- footer start -->
    <!-- footer start -->
    <footer class="relative section-pt overflow-hidden bg-b-neutral-3">
			<div class="container">
				<div class="relative z-10 lg:px-10">
					<div class="grid 4xl:grid-cols-12 3xl:grid-cols-4 sm:grid-cols-2 grid-cols-1 4xl:gap-x-6 max-4xl:gap-40p border-y-2 border-dashed border-shap py-80p">
						<div class="4xl:col-start-1 4xl:col-end-4">
							<img alt="logo" class="mb-16p" src="../assets/images/icons/logo.png">
							<p class="text-base text-w-neutral-3 mb-32p" data-i18n="descmenu">
							<?php echo $L['descmenu']; ?>
							</p>
							<div class="flex items-center gap-3">
								<a class="btn-socal-primary" href="#" onclick="return openSharePopup('https://www.facebook.com/sharer/sharer.php?u=https://huntmaps.online')"><i class="ti ti-brand-facebook"></i></a> <a class="btn-socal-primary" href="#" onclick="return openSharePopup('https://api.whatsapp.com/send?text=https://huntmaps.online')"><i class="ti ti-brand-whatsapp"></i></a> <a class="btn-socal-primary" href="#" onclick="copyToClipboard('https://huntmaps.online'); alert('Link in Zwischenablage kopiert. Jetzt in Discord einfügen.'); return false;"><i class="ti ti-brand-discord"></i></a> 
								<script defer src="/theme/js/util-clipboard.js"></script>
							</div>
						</div>
						<div class="4xl:col-start-5 4xl:col-end-7">
							<div class="flex items-center gap-24p mb-24p">
								<h4 class="heading-4 text-w-neutral-1 whitespace-nowrap" data-i18n="rights"><?php echo $L['rights']; ?></h4><span class="w-full max-w-[110px] h-0.5 bg-w-neutral-1"></span>
							</div>
							<ul class="grid grid-cols-2 sm:gap-y-16p gap-y-2 gap-x-32p *:flex *:items-center">
								<li class="group hover:translate-x-0 -translate-x-5 inline-flex items-center gap-1 hover:text-primary transition-1 max-w-fit">
									<i class="ti ti-chevron-right group-hover:visible invisible text-primary group-hover:opacity-100 opacity-0 transition-1"></i> <a class="text-m-regular text-w-neutral-3" href="<?= $base ?>/legal/rechtlich.php?doc=impressum" data-legal="impressum" class="open-impressum">Impressum</a>
								</li>
								<li class="group hover:translate-x-0 -translate-x-5 inline-flex items-center gap-1 hover:text-primary transition-1 max-w-fit">
									<i class="ti ti-chevron-right group-hover:visible invisible text-primary group-hover:opacity-100 opacity-0 transition-1"></i> <a href="<?= $base ?>/legal/rechtlich.php?doc=privacy" data-legal="privacy" class="open-privacy">Datenschutz</a>
								</li>
								<li class="group hover:translate-x-0 -translate-x-5 inline-flex items-center gap-1 hover:text-primary transition-1 max-w-fit">
									<i class="ti ti-chevron-right group-hover:visible invisible text-primary group-hover:opacity-100 opacity-0 transition-1"></i> <a href="<?= $base ?>/legal/rechtlich.php?doc=netiquette" data-legal="netiquette" class="open-netiquette">Netiquette</a>
								</li>
								<li class="group hover:translate-x-0 -translate-x-5 inline-flex items-center gap-1 hover:text-primary transition-1 max-w-fit">
									<i class="ti ti-chevron-right group-hover:visible invisible text-primary group-hover:opacity-100 opacity-0 transition-1"></i>
								<a href="<?= $base ?>/legal/rechtlich.php?doc=terms" data-legal="terms" class="open-terms">Nutzungsbedingungen</a>
								</li>
							</ul>
							
						</div>
						
						<div class="4xl:col-start-8 4xl:col-end-10">
							<div class="flex items-center gap-24p mb-24p">
								<h4 class="heading-4 text-w-neutral-1 whitespace-nowrap" data-i18n="partner"><?php echo $L['partner']; ?></h4><span class="w-full max-w-[110px] h-0.5 bg-w-neutral-1"></span>
							</div>
							<ul class="grid grid-cols-2 sm:gap-y-16p gap-y-2 gap-x-32p *:flex *:items-center">
								<li class="group hover:translate-x-0 -translate-x-5 inline-flex items-center gap-1 hover:text-primary transition-1 max-w-fit">
									<i class="ti ti-chevron-right group-hover:visible invisible text-primary group-hover:opacity-100 opacity-0 transition-1"></i> <a class="text-m-regular text-w-neutral-3" href="https://huntmaps.online">HuntMaps.online</a>
								</li>
							</ul>
						</div>
						<div class="4xl:col-start-11 4xl:col-end-13">
							<h4 class="heading-4 text-w-neutral-1 whitespace-nowrap mb-3" data-i18n="contactus"><?php echo $L['contactus']; ?></h4><a class="text-base text-w-neutral-3 mb-32p" href="mailto:info@htda.de">info@htda.de</a>
						</div>
					</div>
					<div id="legal-modal" class="hh-legal hidden" aria-hidden="true" role="dialog" aria-modal="true">
  <div class="hh-legal__backdrop" data-legal-close></div>
  <div class="hh-legal__panel" role="document" aria-labelledby="legal-title">
    <button class="hh-legal__close" type="button" data-legal-close aria-label="Schließen">✕</button>
    <h2 id="legal-title" class="hh-legal__title">Titel</h2>
    <div id="legal-content" class="hh-legal__content"><!-- wird per JS gefüllt --></div>
  </div>
</div>
					<div class="flex items-center justify-between flex-wrap gap-24p py-30p"><div class="htda-copyright-box">
               <span data-i18n="copyright">
			   <?php echo $L['copyright']; ?>
			   </span>
            </div></div>
				</div>
			</div>
		</footer><!-- footer end -->
		
        <!-- footer end -->
    </div>
    <!-- app layout end -->
<?php $meId = (int)($me['id'] ?? 0); ?>
<div id="chatDock" class="hh-chatdock" aria-live="polite" data-me="<?= $meId ?>">
  <button id="chatOpenBtn" class="hh-fab" title="Nachrichten" style="display:none;"><i class="ti ti-message"></i></button>
  <div id="chatTabs" class="hh-tabs"></div>
</div>



































<!-- Fullscreen Profil Popup -->
<div id="profileSheet" class="sheet" role="dialog" aria-modal="true" aria-labelledby="psTitle" hidden>
  <div class="sheet-backdrop" data-close="1"></div>
  <div class="sheet-panel">
    <header class="sheet-hd">
      <strong id="psTitle">Konto</strong>
      <button class="sheet-close" data-close="1" aria-label="Schließen"><i class="ti ti-x"></i></button>
    </header>


 

  <!-- Login-Form -->

<?php include __DIR__ . '/partials/header_user_mobile.php'; ?>
  <!-- Registrieren-Form -->

  </div>
</div>
<script defer src="/theme/js/ui-auth-tabs.js"></script>
<script defer src="/theme/js/ui-profile-sheet.js"></script>
<script defer src="/theme/js/chat.js"></script>
<script defer src="/theme/js/config.js"></script>
<script>
(function(){
  const BASE = "<?= $APP_BASE ?? '' ?>";
  const grid = document.getElementById('achievementsGrid');
  if (!grid) return; // Seite ohne Grid? leise aussteigen

  function esc(s){ return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }

  function renderAchievements(items){
    grid.innerHTML = '';
    for (const a of items){
      const pct = Math.max(0, Math.min(100, a.percent|0));
      const card = document.createElement('div');
      card.className = 'achv-card' + (a.unlocked ? ' achv-card--done' : '');
      card.innerHTML = `
        <div class="achv-card__icon">
          ${a.icon ? `<img src="${esc(a.icon)}" alt="">` : ''}
        </div>
        <div class="achv-title">${esc(a.title || 'Erfolg')}</div>
        <div class="achv-sub">${a.current} von ${a.threshold}</div>
        <div class="achv-track">
          <div class="achv-fill" style="width:${pct}%"></div>
          <div class="achv-knob" style="left:${pct}%"></div>
        </div>
        ${a.unlocked ? `<div class="achv-badge">Freigeschaltet</div>` : ``}
      `;
      grid.appendChild(card);
    }
  }

  fetch(`${BASE}/api/gamification/user_progress.php`, { credentials: 'include' })
    .then(r => r.ok ? r.json() : Promise.reject(r.status))
    .then(j => { if (j.ok) renderAchievements(j.items); })
    .catch(console.error);
})();
</script>
<script>
(function(){
  const show = window.showAchievementPopup || function(){};
  const origFetch = window.fetch;

  // Nur eure API treffen (sonst poppt alles)
  const allow = /\/api\/forum\/(create_post|create_thread|toggle_like)\.php$/;

  window.fetch = function(input, init){
    const url = typeof input === 'string' ? input : (input && input.url) || '';
    const p = origFetch(input, init);

    if (allow.test(url)) {
      p.then(resp => {
        const ct = resp.headers && resp.headers.get('content-type') || '';
        if (ct.includes('application/json')) {
          resp.clone().json().then(j => {
            if (j && j.ok && Array.isArray(j.achievements_unlocked)) {
              j.achievements_unlocked.forEach(show);
            }
          }).catch(()=>{});
        }
      }).catch(()=>{});
    }

    return p;
  };
})();
</script>

<script>
(function(){
  const show = window.showAchievementPopup || function(){};
  try {
    const raw = sessionStorage.getItem('ach_popup_queue');
    if (raw) {
      JSON.parse(raw).forEach(show);
      sessionStorage.removeItem('ach_popup_queue');
    }
  } catch(e){}
})();


</script>
<script>
(function(){
  const root = document.getElementById('lang-switcher');
  if (!root) return;
  const btn  = root.querySelector('button');
  const menu = root.querySelector('.dropdown-content');

  // Dropdown öffnen/schließen
  function open(){ menu.hidden = false; btn.setAttribute('aria-expanded','true'); }
  function close(){ menu.hidden = true; btn.setAttribute('aria-expanded','false'); }

  btn.addEventListener('click', (e) => {
    e.preventDefault();
    menu.hidden ? open() : close();
  });
  document.addEventListener('click', (e) => {
    if (!root.contains(e.target)) close();
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') close();
  });

  // Sprachwechsel: &lang=... an aktuelle URL setzen (alle anderen Params bleiben erhalten)
  root.querySelectorAll('.lang-opt').forEach(a => {
    a.addEventListener('click', (e) => {
      e.preventDefault();
      const lang = a.dataset.lang || 'de';
      const url  = new URL(window.location.href);
      url.searchParams.set('lang', lang);   // ersetzt/fügt &lang=
      // Cookie (optional) für Persistenz setzen:
      document.cookie = "lang=" + lang + "; path=/; max-age=" + (60*60*24*365) + "; SameSite=Lax";
      window.location.href = url.toString(); // Hash bleibt erhalten
    });
  });
})();
(function () {
  const modal   = document.getElementById('legal-modal');
  const titleEl = document.getElementById('legal-title');
  const bodyEl  = document.getElementById('legal-content');

  if (!modal || !titleEl || !bodyEl) return;

  let lastFocus = null;

  function lockScroll(lock) {
    document.documentElement.style.overflow = lock ? 'hidden' : '';
  }

  function openModal() {
    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');
    lockScroll(true);
    const closeBtn = modal.querySelector('[data-legal-close]');
    if (closeBtn) closeBtn.focus();
    document.addEventListener('keydown', onKey);
  }

  function closeModal() {
    modal.classList.add('hidden');
    modal.setAttribute('aria-hidden', 'true');
    lockScroll(false);
    document.removeEventListener('keydown', onKey);
    if (lastFocus) lastFocus.focus();
  }

  function onKey(e) {
    if (e.key === 'Escape') closeModal();
  }

  async function loadDoc(doc) {
    try {
      const url = `/legal/rechtlich.php?doc=${encodeURIComponent(doc)}&partial=1`;
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const data = await res.json();
      if (!data.ok) throw new Error(data.error || 'Fehler beim Laden');
      titleEl.textContent = data.title || 'Rechtliches';
      bodyEl.innerHTML = data.html || '<p>Kein Inhalt.</p>';
      openModal();
    } catch (err) {
      console.error(err);
      // Fallback: normale Seite öffnen
      window.location.href = `/legal/rechtlich.php?doc=${encodeURIComponent(doc)}`;
    }
  }

  // Click-Delegation: Links mit data-legal="privacy|terms|netiquette"
  document.addEventListener('click', (e) => {
    const a = e.target.closest('a[data-legal]');
    if (!a) return;

    const doc = a.getAttribute('data-legal');
    if (!doc) return;

    e.preventDefault();
    lastFocus = a;
    loadDoc(doc);
  });

  // Close via Button/Backdrop
  modal.addEventListener('click', (e) => {
    if (e.target.matches('[data-legal-close]') || e.target.classList.contains('hh-legal__backdrop')) {
      closeModal();
    }
  });
})();

</script>


<script src="<?= $APP_BASE; ?>/assets/js/global-chat.js" defer></script>


</body>
