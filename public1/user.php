<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth/db.php';
require_once __DIR__ . '/../auth/guards.php';

$pdo = db();

$id   = isset($_GET['id'])   ? (int)$_GET['id']   : 0;
$slug = isset($_GET['slug']) ? trim((string)$_GET['slug']) : '';

if ($id <= 0 && $slug === '') {
  http_response_code(400); echo 'Bad Request'; exit;
}

if ($slug !== '') {
  $st = $pdo->prepare("SELECT id, display_name, slug, role, bio, avatar_path, created_at FROM users WHERE slug=? LIMIT 1");
  $st->execute([$slug]);
} else {
  $st = $pdo->prepare("SELECT id, display_name, slug, role, bio, avatar_path, created_at FROM users WHERE id=? LIMIT 1");
  $st->execute([$id]);
}
$user = $st->fetch();
if (!$user) { http_response_code(404); echo 'Profil nicht gefunden.'; exit; }

$me = current_user();

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
$avatar = $user['avatar_path'] ?: 'https://placehold.co/160x160';
$memberSince = date('d.m.Y', strtotime($user['created_at']));
$isOwn = $me && ((int)$me['id'] === (int)$user['id']);
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($user['display_name']) ?> – Profil</title>
<style>
  :root{--bg:#fafafa;--card:#fff;--bord:#e5e7eb;--text:#111;--muted:#6b7280;}
  *{box-sizing:border-box}
  body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:0;background:var(--bg);color:var(--text)}
  .wrap{max-width:900px;margin:32px auto;padding:0 16px}
  .card{background:var(--card);border:1px solid var(--bord);border-radius:16px;padding:24px}
  .row{display:grid;grid-template-columns:160px 1fr;gap:24px;align-items:start}
  .avatar{width:160px;height:160px;border-radius:50%;object-fit:cover;border:1px solid var(--bord);background:#fff}
  h1{margin:0 0 6px 0;font-size:28px;line-height:1.2}
  .role{display:inline-block;padding:4px 10px;border:1px solid var(--bord);border-radius:999px;font-size:12px;color:var(--muted);margin-left:8px}
  .meta{color:var(--muted);font-size:14px;margin:6px 0 16px}
  .bio{white-space:pre-wrap;line-height:1.5}
  .actions{margin-top:16px}
  .btn{display:inline-block;background:#111;color:#fff;padding:10px 14px;border-radius:10px;text-decoration:none}
  .back{display:inline-block;margin:16px 0;color:#111;text-decoration:none}
  @media (max-width:640px){ .row{grid-template-columns:1fr; } .avatar{margin:0 auto} h1{text-align:center} .meta{text-align:center}}
</style>
</head>
<body>
<div class="wrap">
  <a class="back" href="/cms/index.php">← Zurück</a>

  <div class="card">
    <div class="row">
      <img class="avatar" src="<?= e($avatar) ?>" alt="Avatar von <?= e($user['display_name']) ?>">
      <div>
        <h1><?= e($user['display_name']) ?><span class="role"><?= e($user['role']) ?></span></h1>
        <div class="meta">Mitglied seit <?= e($memberSince) ?></div>

        <?php if (!empty($user['bio'])): ?>
          <div class="bio"><?= nl2br(e($user['bio'])) ?></div>
        <?php else: ?>
          <div class="bio" style="color:var(--muted)">Keine Bio vorhanden.</div>
        <?php endif; ?>

        <div class="actions">
          <?php if ($isOwn): ?>
            <a class="btn" href="/cms/profile.php">Eigenes Profil bearbeiten</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
