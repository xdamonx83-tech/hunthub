<?php
declare(strict_types=1);
require_once __DIR__ . '/../_bootstrap.php';

$pdo->exec("TRUNCATE TABLE link_previews");
echo json_encode(['ok'=>true]);
