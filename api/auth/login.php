<?php
declare(strict_types=1);

require_once __DIR__ . '/../../auth/db.php';
$cfg = require __DIR__ . '/../../auth/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
  $cookie = $cfg['cookies'];
  $cookieName  = $cookie['session_name'] ?? 'sess_id';
  $lifetime    = (int)($cookie['lifetime'] ?? 1209600); // 14 Tage
  $sessionsTbl = $cfg['sessions_table'] ?? 'auth_sessions';

  $pdo = db();

  $in    = json_decode(file_get_contents('php://input'), true) ?: [];
  $email = trim((string)($in['email'] ?? ''));
  $pass  = (string)($in['password'] ?? '');
  if ($email === '' || $pass === '') {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Email/Passwort fehlt']); exit;
  }

  $st = $pdo->prepare('SELECT id, email, password_hash, display_name, slug, role FROM users WHERE email=? LIMIT 1');
  $st->execute([$email]);
  $u = $st->fetch();
  if (!$u || !password_verify($pass, $u['password_hash'] ?? '')) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'error'=>'Ungültige Zugangsdaten']); exit;
  }

  // Session-Tabelle sicherstellen (falls noch nicht existiert)
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS `{$sessionsTbl}` (
      `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      `token` CHAR(64) NOT NULL,
      `user_id` INT UNSIGNED NOT NULL,
      `created_at` DATETIME NOT NULL,
      `last_seen`  DATETIME NOT NULL,
      `ip` VARCHAR(45) NULL,
      `user_agent` VARCHAR(255) NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uniq_token` (`token`),
      KEY `idx_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  ");

  $token = bin2hex(random_bytes(32));
  $now   = date('Y-m-d H:i:s');
  $ins = $pdo->prepare("INSERT INTO `{$sessionsTbl}` (token,user_id,created_at,last_seen,ip,user_agent)
                         VALUES (?,?,?,?,?,?)");
  $ins->execute([$token, (int)$u['id'], $now, $now, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null]);

  // Cookie setzen
  setcookie($cookieName, $token, [
    'expires'  => time() + $lifetime,
    'path'     => $cookie['path']     ?? '/',
    'domain'   => $cookie['domain']   ?? null,
    'secure'   => (bool)($cookie['secure'] ?? false),
    'httponly' => (bool)($cookie['httponly'] ?? true),
    'samesite' => $cookie['samesite'] ?? 'Lax',
  ]);

  echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Serverfehler: '.$e->getMessage()]);
}
