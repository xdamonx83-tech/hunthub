<?php
declare(strict_types=1);
require_once __DIR__ . '/../_bootstrap.php';

$q = trim((string) (in_json()['q'] ?? ''));
$sql = "SELECT id, display_name, email, role, is_admin FROM users";
$params = [];
if ($q !== '') {
  $sql .= " WHERE id = :id OR display_name LIKE :q OR email LIKE :q";
  $params[':id'] = ctype_digit($q) ? (int)$q : -1;
  $params[':q']  = "%{$q}%";
}
$sql .= " ORDER BY id DESC LIMIT 200";
$st = $pdo->prepare($sql);
$st->execute($params);
echo json_encode(['ok'=>true,'items'=>$st->fetchAll()]);
