<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

ini_set('display_errors', '0');
register_shutdown_function(function () {
  $e = error_get_last();
  if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'fatal']);
  }
});

require_once __DIR__ . '/../../auth/db.php';
require_once __DIR__ . '/../../auth/guards.php';
require_once __DIR__ . '/../../auth/csrf.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false,'error'=>'method_not_allowed']);
  exit;
}

$pdo  = db();
$user = require_auth();

$cfg      = require __DIR__ . '/../../auth/config.php';
$appBase  = rtrim($cfg['app_base'] ?? '', '/');
$sessName = $cfg['cookies']['session_name'] ?? 'sess_id';

$csrf = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF'] ?? null);
if (!check_csrf($pdo, $_COOKIE[$sessName] ?? '', $csrf)) {
  http_response_code(419);
  echo json_encode(['ok'=>false,'error'=>'csrf_failed']);
  exit;
}

/* ---------- Eingaben ---------- */
if (!isset($_FILES['video']['tmp_name']) || !is_uploaded_file($_FILES['video']['tmp_name'])) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'no_file']);
  exit;
}

$start = max(0.0, (float)($_POST['start'] ?? 0));
$end   = max(0.0, (float)($_POST['end']   ?? 0));
if (!($end > $start)) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'range_invalid']);
  exit;
}

$job = (string)($_POST['job'] ?? '');
if (!preg_match('/^[A-Za-z0-9_-]{8,40}$/', $job)) {
  // falls nichts Sinnvolles mitkommt, generieren wir selbst
  $job = bin2hex(random_bytes(8));
}

/* ---------- Pfade ---------- */
$rootVideos  = __DIR__ . '/../../uploads/videos';
$rootPosters = __DIR__ . '/../../uploads/video_posters';
$rootTmp     = __DIR__ . '/../../uploads/tmp';
foreach ([$rootVideos, $rootPosters, $rootTmp] as $dir) {
  if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'mkdir_failed']);
    exit;
  }
}

$progFile = $rootTmp . '/ffprog_' . $job . '.txt';
@file_put_contents($progFile, "init=1\n"); // anlegen

/* ---------- Upload temporär ablegen ---------- */
$tmpSrc = $rootTmp . '/up_' . bin2hex(random_bytes(6));
if (!@move_uploaded_file($_FILES['video']['tmp_name'], $tmpSrc)) {
  @unlink($progFile);
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'move_failed']);
  exit;
}

/* ---------- ffprobe Dauer ---------- */
function probe_duration(string $file): float {
  $cmd = 'ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 ' . escapeshellarg($file);
  $out = []; @exec($cmd, $out, $rc);
  if ($rc !== 0 || empty($out)) return 0.0;
  return max(0.0, (float)$out[0]);
}

$total = probe_duration($tmpSrc);
if ($total <= 0) {
  @unlink($tmpSrc); @unlink($progFile);
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'unsupported_media']);
  exit;
}

$start = min($start, max(0.0, $total - 0.1));
$end   = min($end, $total);
$dur   = max(0.1, $end - $start);

/* ---------- Ausgabedateien ---------- */
$baseName  = 'u' . (int)$user['id'] . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4));
$outVideo  = $rootVideos  . '/' . $baseName . '.mp4';
$outPoster = $rootPosters . '/' . $baseName . '.jpg';

/* ---------- ffmpeg-Trim (neu encodieren + Fortschritt) ---------- */
$cmdVideo = sprintf(
  'ffmpeg -hide_banner -loglevel error -y ' .
  '-ss %s -i %s -t %s ' .
  '-analyzeduration 0 -probesize 5M -sn ' .
  '-c:v libx264 -preset veryfast -crf 23 -pix_fmt yuv420p ' .
  '-c:a aac -b:a 128k -movflags +faststart ' .
  '-progress %s -nostats ' .
  '%s',
  escapeshellarg((string)$start),
  escapeshellarg($tmpSrc),
  escapeshellarg((string)$dur),
  escapeshellarg($progFile),
  escapeshellarg($outVideo)
);
exec($cmdVideo, $void, $rcVideo);

/* Poster (nicht kritisch fürs Fortschritt) */
$thumbAt = max(0.0, $dur / 2.0);
$cmdPoster = sprintf(
  'ffmpeg -hide_banner -loglevel error -y -ss %s -i %s -frames:v 1 -q:v 2 %s',
  escapeshellarg((string)$thumbAt),
  escapeshellarg($outVideo),
  escapeshellarg($outPoster)
);
exec($cmdPoster, $void2, $rcPoster);

/* Fortschritt „end“ markieren */
@file_put_contents($progFile, "progress=end\n");

/* ---------- Validierung ---------- */
$outDur = probe_duration($outVideo);
if ($rcVideo !== 0 || !is_file($outVideo) || filesize($outVideo) < 1024 || $outDur <= 0.1) {
  @unlink($outVideo); @unlink($outPoster); @unlink($tmpSrc); @unlink($progFile);
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'encode_failed']);
  exit;
}
if ($rcPoster !== 0 || !is_file($outPoster)) { $outPoster = null; }

/* ---------- Aufräumen + Antwort ---------- */
@unlink($tmpSrc); // progress-Datei nicht sofort löschen, Poll kann noch laufen

$videoUrl  = $appBase . '/uploads/videos/' . basename($outVideo);
$posterUrl = $outPoster ? ($appBase . '/uploads/video_posters/' . basename($outPoster)) : null;

echo json_encode([
  'ok'     => true,
  'job'    => $job,
  'video'  => $videoUrl,
  'poster' => $posterUrl,
  'start'  => (float)$start,
  'end'    => (float)$end,
  'length' => (float)$dur,
  'duration_out' => (float)$outDur
]);

// Progress-Datei mit leichter Verzögerung löschen (wenn möglich)
register_shutdown_function(function() use ($progFile) {
  // kleine Verzögerung, damit der letzte Poll noch „end“ lesen kann
  usleep(300000);
  @unlink($progFile);
});
