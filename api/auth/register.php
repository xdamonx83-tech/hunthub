<?php
declare(strict_types=1);
header('Content-Type: application/json');

require_once __DIR__ . '/../../auth/db.php';
require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../../auth/roles.php';

$input = $_POST ?: json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');
$pass  = $input['password'] ?? '';
$name  = trim($input['display_name'] ?? '');

if (!$email || !$pass || !$name) {
  http_response_code(422);
  exit(json_encode(['ok'=>false,'error'=>'missing_fields']));
}
if (!email_valid($email)) {
  http_response_code(422);
  exit(json_encode(['ok'=>false,'error'=>'invalid_email']));
}
if (strlen($pass) < 8) {
  http_response_code(422);
  exit(json_encode(['ok'=>false,'error'=>'weak_password']));
}
if (find_user_by_email($email)) {
  http_response_code(409);
  exit(json_encode(['ok'=>false,'error'=>'email_taken']));
}

$userId = create_user($email, $pass, $name, 'user');
$session = create_session($userId);
echo json_encode(['ok'=>true,'user_id'=>$userId,'csrf'=>$session['csrf']]);
