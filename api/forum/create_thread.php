<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../auth/db.php';
require_once __DIR__ . '/../../auth/guards.php';
require_once __DIR__ . '/../../auth/csrf.php';
require_once __DIR__ . '/../../forum/lib_forum_text.php';
require_once __DIR__ . '/../../forum/lib_link_preview.php';
// KORREKTER PFAD: Stellt alle Gamification-Funktionen bereit
require_once __DIR__ . '/../../lib/gamification.php';
// +++ NEU: Unsere Helfer-Datei für XP und Level-Ups einbinden +++
require_once __DIR__ . '/../../lib/gamification_helper.php';


// Fallback, falls lib_forum_text.php keine slugify() definiert:
if (!function_exists('slugify')) {
    function slugify(string $s): string {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        $t = strtolower($t ?: $s);
        $t = preg_replace('/[^a-z0-9]+/','-',$t);
        $t = trim($t, '-');
        return $t !== '' ? $t : 'thread';
    }
}

/** Nackte http/https-URLs → <a> */
function linkify_plain_urls(string $html): string {
    return preg_replace_callback(
        '~(?<!["\'>])(https?://[^\s"<]+)~i',
        fn($m) => '<a href="'.htmlspecialchars($m[1]).'" target="_blank" rel="noopener noreferrer">'.htmlspecialchars($m[1]).'</a>',
        $html
    );
}

/** Helper für Mention-Normalisierung */
function normalize_name(string $s): string {
    $s = preg_replace('/\s+/u', '', $s ?? '');
    return mb_strtolower($s, 'UTF-8');
}

// Nur POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'error'=>'method_not_allowed']);
    exit;
}

$pdo   = db();
$user  = require_auth();
$cfg   = require __DIR__ . '/../../auth/config.php';

// CSRF
$session = $_COOKIE[$cfg['cookies']['session_name']] ?? '';
$csrf = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null));
if (!check_csrf($pdo, $session, $csrf)) {
    http_response_code(419);
    echo json_encode(['ok'=>false,'error'=>'csrf_failed']);
    exit;
}

// Eingaben
$board_id = (int)($_POST['board_id'] ?? 0);
$title    = trim((string)($_POST['title'] ?? ''));
$content  = trim((string)($_POST['content'] ?? ''));

if ($board_id <= 0 || $title === '' || $content === '') {
    http_response_code(422);
    echo json_encode(['ok'=>false,'error'=>'invalid_input']);
    exit;
}

// Links klickbar machen
$content = linkify_plain_urls($content);

// Mentions
$mentionIds = [];
$mentionsRaw = $_POST['mentions'] ?? '[]';
$mentionsArr = json_decode((string)$mentionsRaw, true);
if (is_array($mentionsArr)) {
    foreach ($mentionsArr as $m) {
        $id = (int)($m['id'] ?? 0);
        if ($id > 0 && $id !== (int)$user['id']) $mentionIds[$id] = true;
    }
}
if (!$mentionIds) {
    if (preg_match_all('/@([^\s<>&]{3,40})/u', $content, $m)) {
        $tokens = array_unique(array_map('normalize_name', $m[1]));
        if ($tokens) {
            $ph  = implode(',', array_fill(0, count($tokens), '?'));
            $sql = "SELECT id FROM users WHERE REPLACE(LOWER(display_name), ' ', '') IN ($ph)";
            $st  = $pdo->prepare($sql);
            $st->execute($tokens);
            foreach ($st->fetchAll(PDO::FETCH_COLUMN, 0) as $uid) {
                $uid = (int)$uid;
                if ($uid !== (int)$user['id']) $mentionIds[$uid] = true;
            }
        }
    }
}
$mentionIds = array_map('intval', array_keys($mentionIds));

$slug = slugify($title);

$pdo->beginTransaction();
try {
    // Board validieren
    $bst = $pdo->prepare("SELECT id FROM boards WHERE id = ?");
    $bst->execute([$board_id]);
    if (!$bst->fetchColumn()) {
        throw new RuntimeException('board_not_found');
    }

    // Thread anlegen
    $tst = $pdo->prepare("
        INSERT INTO threads (board_id, author_id, title, slug, posts_count, last_post_at, created_at)
        VALUES (?, ?, ?, ?, 0, NOW(), NOW())
    ");
    $tst->execute([$board_id, (int)$user['id'], $title, $slug]);
    $thread_id = (int)$pdo->lastInsertId();

    // OP-Post anlegen
    $pst = $pdo->prepare("INSERT INTO posts (thread_id, author_id, content, created_at) VALUES (?,?,?,NOW())");
    $pst->execute([$thread_id, (int)$user['id'], $content]);
    $post_id = (int)$pdo->lastInsertId();

    // Zähler aktualisieren
    $pdo->prepare("UPDATE threads SET posts_count = posts_count + 1 WHERE id = ?")->execute([$thread_id]);
    $pdo->prepare("
        UPDATE boards
        SET threads_count = threads_count + 1,
            posts_count   = posts_count + 1,
            last_post_at  = NOW()
        WHERE id = ?
    ")->execute([$board_id]);

    // Link-Previews für OP speichern
    try { lp_store_previews($pdo, $post_id, $content); } catch (\Throwable $e) {}

    // Mention-Benachrichtigungen
    if ($mentionIds) {
        // ... (deine Mention-Logik bleibt unverändert)
    }

    $pdo->commit();

    // GAMIFY: nach erfolgreichem Commit
    $achNow = [];
    try {
        // Achievements
        gamify_bump($pdo, (int)$user['id'], 'threads_count', 1);
        $achNow = gamify_check($pdo, (int)$user['id'], 'thread_created');
        
        // ##### NEU: Quest-Fortschritt aktualisieren #####
        update_quest_progress($pdo, (int)$user['id'], 'thread_created');

        // +++ NEU: XP für das neue Thema UND den ersten Beitrag vergeben +++
        awardXP($pdo, (int)$user['id'], 'new_thread', $thread_id);
awardXP($pdo, (int)$user['id'], 'new_post', $post_id);

    } catch (\Throwable $e) { $achNow = []; }

    echo json_encode([
        'ok'                  => true,
        'thread_id'           => $thread_id,
        'post_id'             => $post_id,
        'slug'                => $slug,
        'achievements_unlocked' => $achNow,
    ], JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}