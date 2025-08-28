<?php
// /cms/api/upload_image.php
declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../auth/db.php';
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../auth/guards.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = db();
    $cfg = require __DIR__ . '/../auth/config.php';
    $APP_BASE = rtrim($cfg['app_base'] ?? '/cms', '/');

    // Nur eingeloggte Nutzer dürfen hochladen
    $me = require_auth();

    // CSRF prüfen (Header oder FormData)
    $csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $csrfBody   = $_POST['csrf'] ?? '';
    $csrfToken  = $csrfHeader ?: $csrfBody;
    verify_csrf_or_fail($pdo, $csrfToken);

    if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
      throw new RuntimeException('Keine Datei empfangen.');
    }

    $file = $_FILES['file'];

    // Grundlegende Checks
    if ($file['error'] !== UPLOAD_ERR_OK) {
      throw new RuntimeException('Upload-Fehler: ' . $file['error']);
    }
    if ($file['size'] > 5 * 1024 * 1024) { // 5 MB Limit
      throw new RuntimeException('Datei zu groß (max. 5 MB).');
    }

    // MIME/Extension Whitelist
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']) ?: 'application/octet-stream';
    $allowed = [
      'image/png'  => 'png',
      'image/jpeg' => 'jpg',
      'image/webp' => 'webp',
      'image/gif'  => 'gif',
    ];
    if (!isset($allowed[$mime])) {
      throw new RuntimeException('Nur PNG/JPG/WEBP/GIF erlaubt.');
    }

    // Zielpfad
    $root = realpath(__DIR__ . '/..'); // /cms/api -> /cms
    $relDir = '/uploads/posts';
    $targetDir = $root . $relDir;

    if (!is_dir($targetDir)) {
      if (!mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
        throw new RuntimeException('Upload-Verzeichnis nicht beschreibbar.');
      }
    }

    // Dateiname
    $ext = $allowed[$mime];
    $base = bin2hex(random_bytes(6));
    $name = sprintf('%s_%d.%s', $base, (int)$me['id'], $ext);
    $dest = $targetDir . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
      throw new RuntimeException('Speichern fehlgeschlagen.');
    }

    // Public URL bauen
    $url = $APP_BASE . $relDir . '/' . $name;

    echo json_encode(['ok' => true, 'url' => $url], JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    exit;
}

/**
 * CSRF prüfen – kompatibel zu issue_csrf()/verify_csrf().
 * Falls deine verify-Funktion anders heißt, passe hier an.
 */
function verify_csrf_or_fail(PDO $pdo, string $token): void {
  if (!$token) throw new RuntimeException('CSRF-Token fehlt.');
  if (!function_exists('verify_csrf')) {
    // Fallback: akzeptiere Token, wenn Funktion nicht existiert (anpassen!)
    return;
  }
  if (!verify_csrf($pdo, $token)) {
    throw new RuntimeException('CSRF ungültig.');
  }
}
