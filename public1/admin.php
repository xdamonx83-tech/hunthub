<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth/guards.php';
require_once __DIR__ . '/../auth/roles.php';

$user = require_role('administrator');
?>
<!doctype html>
<html lang="de">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin</title></head>
<body>
<p><a href="/cms/public/index.php">← Zurück</a></p>
<h1>Adminbereich</h1>
<p>Nur Administratoren dürfen das sehen. Eingeloggt als <strong><?=htmlspecialchars($user['display_name'])?></strong>.</p>
</body>
</html>
