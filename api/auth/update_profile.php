<?php
declare(strict_types=1);

// Immer JSON ausliefern
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Einheitliche, saubere Fehlerbehandlung -> niemals HTML zurückgeben
ini_set('display_errors', '0');
set_exception_handler(function ($e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'server_error','message'=>$e->getMessage()], JSON_UNESCAPED_SLASHES);
  exit;
});
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});
register_shutdown_function(function () {
  $e = error_get_last();
  if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'fatal'], JSON_UNESCAPED_SLASHES);
  }
});

// Abhängigkeiten
require_once __DIR__ . '/../../auth/db.php';
require_once __DIR__ . '/../../auth/guards.php';
require_once __DIR__ . '/../../auth/csrf.php';

// Nur POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false,'error'=>'method_not_allowed']);
  exit;
}

$pdo  = db();
$user = require_auth(); // liefert User-Array oder beendet mit JSON (über Handler oben)

$cfg      = require __DIR__ . '/../../auth/config.php';
$uploads  = $cfg['uploads'] ?? [];
$appBase  = rtrim((string)($cfg['app_base'] ?? '/'), '/'); // '/' -> ''

// CSRF aus Body ODER Header (beide Header-Varianten akzeptieren)
$csrf = $_POST['csrf']
     ?? ($_SERVER['HTTP_X_CSRF'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null));

$sessionCookie = $_COOKIE[$cfg['cookies']['session_name']] ?? '';
if (!check_csrf($pdo, $sessionCookie, $csrf)) {
  http_response_code(419);
  echo json_encode(['ok'=>false,'error'=>'csrf_failed']);
  exit;
}

/* ---------- Eingaben (Teil-Update fähig) ---------- */
$displayProvided = array_key_exists('display_name', $_POST);
$bioProvided     = array_key_exists('bio', $_POST);

$display_name_in = $displayProvided ? trim((string)($_POST['display_name'] ?? '')) : null;
$social_tiktok_in = $displayProvided ? trim((string)($_POST['social_tiktok'] ?? '')) : null;
$social_twitch_in = $displayProvided ? trim((string)($_POST['social_twitch'] ?? '')) : null;
$social_youtube_in = $displayProvided ? trim((string)($_POST['social_youtube'] ?? '')) : null;
$social_instagram_in = $displayProvided ? trim((string)($_POST['social_instagram'] ?? '')) : null;
$social_twitter_in = $displayProvided ? trim((string)($_POST['social_twitter'] ?? '')) : null;
$social_facebook_in = $displayProvided ? trim((string)($_POST['social_facebook'] ?? '')) : null;
$bio_in          = $bioProvided     ? trim((string)($_POST['bio'] ?? ''))          : null;

if ($displayProvided && $display_name_in === '') {
  http_response_code(422);
  echo json_encode(['ok'=>false,'error'=>'display_name_required']);
  exit;
}

$tiktok = $displayProvided ? $social_tiktok_in : ($user['social_tiktok'] ?? '');
$twitch = $displayProvided ? $social_twitch_in : ($user['social_twitch'] ?? '');
$youtube = $displayProvided ? $social_youtube_in : ($user['social_youtube'] ?? '');
$instagram = $displayProvided ? $social_instagram_in : ($user['social_instagram'] ?? '');
$twitter = $displayProvided ? $social_twitter_in : ($user['social_twitter'] ?? '');
$facebook = $displayProvided ? $social_facebook_in : ($user['social_facebook'] ?? '');
$display_name = $displayProvided ? $display_name_in : ($user['display_name'] ?? '');
$bio          = $bioProvided     ? ($bio_in !== '' ? $bio_in : null) : ($user['bio'] ?? null);

$avatar_path  = $user['avatar_path'] ?? null;
$cover_path   = $user['cover_path']  ?? null;

// Cover-Position (normiert)
$cover_x     = array_key_exists('cover_x', $_POST)     ? max(0.0, min(1.0, (float)$_POST['cover_x'])) : ($user['cover_x'] ?? null);
$cover_y     = array_key_exists('cover_y', $_POST)     ? max(0.0, min(1.0, (float)$_POST['cover_y'])) : ($user['cover_y'] ?? null);
$cover_scale = array_key_exists('cover_scale', $_POST) ? max(0.5, min(5.0, (float)$_POST['cover_scale'])) : ($user['cover_scale'] ?? null);

/* ---------- Helpers ---------- */
function detect_mime(string $tmp): string {
  if (class_exists('finfo')) {
    $fi = new finfo(FILEINFO_MIME_TYPE);
    $m  = (string)$fi->file($tmp);
    if ($m) return $m;
  }
  if (function_exists('mime_content_type')) {
    $m = (string)@mime_content_type($tmp);
    if ($m) return $m;
  }
  return '';
}
/* ---------- Social Eingaben ---------- */
$socialFields = ['twitch','tiktok','youtube','instagram','twitter','facebook'];

$inSocial = [];
foreach ($socialFields as $f) {
  if (array_key_exists($f, $_POST)) {
    $raw = (string)($_POST[$f] ?? '');
    if ($f === 'youtube') {
      $norm = norm_youtube($raw);
    } else {
      $norm = norm_handle_basic($raw);
    }
    $inSocial[$f] = $norm;
  } else {
    // nicht im POST -> alter Wert bleibt
    $inSocial[$f] = $user['social_'.$f] ?? null;
  }
}

function move_image_upload(array $file, string $targetDirFs, array $allowed, int $maxSize, int $uid): array {
  if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return [false,'no_file'];
  if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK)               return [false,'upload_error'];
  if (($file['size']  ?? 0) > $maxSize)                                  return [false,'file_too_large'];

  $mime = detect_mime($file['tmp_name']);
  if (!in_array($mime, $allowed, true))                                   return [false,'unsupported_media'];

  $ext = match($mime){ 'image/jpeg'=>'.jpg','image/png'=>'.png','image/webp'=>'.webp', default=>'.bin' };

  if (!is_dir($targetDirFs) && !@mkdir($targetDirFs,0775,true) && !is_dir($targetDirFs)) {
    return [false,'mkdir_failed'];
  }

  $name = 'u'.$uid.'_'.bin2hex(random_bytes(6)).$ext;
  $dest = rtrim($targetDirFs,'/').'/'.$name;

  if (!@move_uploaded_file($file['tmp_name'],$dest)) return [false,'move_failed'];
  return [true,$name];
}
/* ---------- Social Helpers ---------- */
function norm_handle_basic(?string $in): ?string {
  if ($in === null) return null;
  $v = trim($in);
  if ($v === '') return null;
  // Wenn komplette URL, nur den Pfad/Handle extrahieren – ansonsten führendes @ und Slashes entfernen
  if (preg_match('~^https?://~i', $v)) {
    $u = parse_url($v);
    $path = trim($u['path'] ?? '', '/');
    // Bei Links mit trailing Slashes oder Query
    if (isset($u['query'])) $path .= '';
    $v = $path;
  }
  $v = preg_replace('~^@+~', '', $v);
  $v = trim($v, "/ \t\r\n");
  return $v !== '' ? $v : null;
}

/**
 * YouTube kann vieles sein:
 *  - @handle                    -> /@handle
 *  - c/Name                     -> /c/Name
 *  - channel/UCxxxxxxxxxxxxxxx  -> /channel/UC...
 *  - user/Name                  -> /user/Name (Legacy)
 *  - komplette URL zu Video/Kanal
 * Wir normalisieren auf eines dieser Muster: @handle | c/Name | channel/UC... | user/Name
 */
function norm_youtube(?string $in): ?string {
  $v = trim((string)$in);
  if ($v === '') return null;

  if (preg_match('~^https?://~i', $v)) {
    $u = parse_url($v);
    $path = trim($u['path'] ?? '', '/');
    // Beispiele: /@name, /c/Name, /channel/UC..., /user/Name, /watch?v=...
    if ($path !== '') {
      // @handle?
      if (preg_match('~^@[\w\-.]+$~i', $path)) return ltrim($path, '@');
      // channel/UC...
      if (preg_match('~^channel/(UC[0-9A-Za-z_-]{10,})$~', $path, $m)) return 'channel/'.$m[1];
      // c/Name
      if (preg_match('~^c/([\w\-.]{1,100})$~', $path, $m)) return 'c/'.$m[1];
      // user/Name (legacy)
      if (preg_match('~^user/([\w\-.]{1,100})$~', $path, $m)) return 'user/'.$m[1];
      // @handle im Pfad?
      if (preg_match('~/(?:@([\w\-.]+))~', $path, $m)) return $m[1];
    }
    // Fallback: wenn Query v=... ist, lassen wir leer (Video ist kein Kanal/Handle)
    return null;
  }

  // @handle direkt?
  if ($v[0] === '@') return ltrim($v, '@');

  // UC-Kanal?
  if (preg_match('~^(UC[0-9A-Za-z_-]{10,})$~', $v)) return 'channel/'.$v;

  // c/Name, user/Name Prefix selbst eingegeben?
  if (preg_match('~^(c|user)/[\w\-.]{1,100}$~i', $v)) return $v;

  // ansonsten als Handle interpretieren
  return $v;
}

/** Baut eine anklickbare URL aus Handle/Teil-String. */
function social_url(string $platform, ?string $value): ?string {
  if (!$value) return null;
  switch ($platform) {
    case 'twitch':    return 'https://twitch.tv/'.ltrim($value, '@/');
    case 'tiktok':    return 'https://www.tiktok.com/@'.ltrim($value, '@/');
    case 'instagram': return 'https://instagram.com/'.ltrim($value, '@/');
    case 'twitter':   return 'https://twitter.com/'.ltrim($value, '@/'); // X
    case 'facebook':
      // erlaubt Seiten oder Profile: value kann "pageName" oder "profile.php?id=..." sein
      return preg_match('~^https?://~i', $value) ? $value : 'https://facebook.com/'.ltrim($value, '/');
    case 'youtube':
      // value ist einer von: handle | c/Name | channel/UC... | user/Name
      if (preg_match('~^(c|user|channel)/~i', $value)) return 'https://www.youtube.com/'.$value;
      if ($value && $value[0] !== '@') $value = '@'.$value;
      return 'https://www.youtube.com/'.$value;
    default: return null;
  }
}

/* ---------- Avatar-Upload (optional) ---------- */
if (!empty($_FILES['avatar']['tmp_name'])) {
  [$ok,$nameOrErr] = move_image_upload(
    $_FILES['avatar'],
    (string)($uploads['avatars_dir'] ?? __DIR__.'/../../uploads/avatars'),
    (array)($uploads['allowed'] ?? ['image/jpeg','image/png','image/webp']),
    (int)($uploads['max_size'] ?? 2*1024*1024),
    (int)$user['id']
  );
  if (!$ok) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'avatar_'.$nameOrErr]); exit; }

  if ($avatar_path) {
    $oldFs = rtrim((string)($uploads['avatars_dir'] ?? ''),'/').'/'.basename($avatar_path);
    if ($oldFs && is_file($oldFs)) @unlink($oldFs);
  }
  $avatar_path = ($appBase ? $appBase : '').'/uploads/avatars/'.$nameOrErr;
}

/* ---------- Cover entfernen? ---------- */
if (!empty($_POST['cover_remove']) && $cover_path) {
  $oldFs = rtrim((string)($uploads['covers_dir'] ?? ''),'/').'/'.basename($cover_path);
  if ($oldFs && is_file($oldFs)) @unlink($oldFs);
  $cover_path = null;
  $cover_x = $cover_y = $cover_scale = null;
}

/* ---------- Cover-Upload (optional) ---------- */
if (!empty($_FILES['cover']['tmp_name'])) {
  [$ok,$nameOrErr] = move_image_upload(
    $_FILES['cover'],
    (string)($uploads['covers_dir'] ?? __DIR__.'/../../uploads/covers'),
    (array)($uploads['allowed'] ?? ['image/jpeg','image/png','image/webp']),
    (int)($uploads['max_size'] ?? 5*1024*1024),
    (int)$user['id']
  );
  if (!$ok) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'cover_'.$nameOrErr]); exit; }

  if ($cover_path) {
    $oldFs = rtrim((string)($uploads['covers_dir'] ?? ''),'/').'/'.basename($cover_path);
    if ($oldFs && is_file($oldFs)) @unlink($oldFs);
  }
  $cover_path = ($appBase ? $appBase : '').'/uploads/covers/'.$nameOrErr;
}

/* ---------- Persistieren ---------- */
$stmt = $pdo->prepare("
  UPDATE users
     SET display_name = ?,
         bio          = ?,
         avatar_path  = ?,
         cover_path   = ?,
         cover_x      = ?,
         cover_y      = ?,
         cover_scale  = ?,
		 social_twitch    = ?,
         social_tiktok    = ?,
         social_youtube   = ?,
         social_instagram = ?,
         social_twitter   = ?,
         social_facebook  = ?
   WHERE id = ?
");
$stmt->execute([
  $display_name,
  $bio,
  $avatar_path ?: null,
  $cover_path  ?: null,
  $cover_x,
  $cover_y,
  $cover_scale,
  $inSocial['twitch'],
  $inSocial['tiktok'],
  $inSocial['youtube'],
  $inSocial['instagram'],
  $inSocial['twitter'],
  $inSocial['facebook'],
  $user['id']
]);

echo json_encode([
  'ok'          => true,
  'avatar'      => $avatar_path,
  'cover'       => $cover_path,
  'cover_x'     => $cover_x,
  'cover_y'     => $cover_y,
  'cover_scale' => $cover_scale
], JSON_UNESCAPED_SLASHES);
