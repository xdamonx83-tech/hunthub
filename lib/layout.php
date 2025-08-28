<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth/db.php';
require_once __DIR__ . '/../auth/csrf.php';

$pdo = $pdo ?? db();
$cfg = require __DIR__ . '/../auth/config.php';

$APP_BASE     = rtrim($cfg['app_base'] ?? '', '/');
$sessionName  = $cfg['cookies']['session_name'] ?? '';
$csrf         = issue_csrf($pdo, $_COOKIE[$sessionName] ?? '');

/**
 * Erlaubte Sprachen & Defaults
 */
const LANGS_ALLOWED = ['de','en'];
const LANG_DEFAULT  = 'de';

/**
 * Ermittelt aktuelle Sprache aus ?lang=…, Cookie oder Default.
 * Setzt bei ?lang=… zusätzlich ein Cookie (1 Jahr).
 */
function detect_lang(): string {
    $get = isset($_GET['lang']) ? strtolower(trim((string)$_GET['lang'])) : null;
    $cookie = isset($_COOKIE['lang']) ? strtolower(trim((string)$_COOKIE['lang'])) : null;

    $lang = $get ?: $cookie ?: LANG_DEFAULT;
    if (!in_array($lang, LANGS_ALLOWED, true)) {
        $lang = LANG_DEFAULT;
    }

    if ($get) {
        // Cookie ein Jahr, sicher & httponly
        $secure   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $sameSite = 'Lax';
        setcookie('lang', $lang, [
            'expires'  => time() + 60*60*24*365,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => $sameSite,
        ]);
        $_COOKIE['lang'] = $lang; // für diesen Request direkt verfügbar
    }

    return $lang;
}

/**
 * Lädt Sprachdatei als Array $L. Fallback auf Default, falls Datei fehlt.
 */
function load_lang(string $lang): array {
    $path = __DIR__ . "/../lang/{$lang}.php";
    if (is_file($path)) {
        /** @var array $L */
        $L = require $path;
        if (is_array($L)) {
            return $L;
        }
    }
    // Fallback
    $fallback = __DIR__ . "/../lang/" . LANG_DEFAULT . ".php";
    return is_file($fallback) ? (require $fallback) : [];
}

/**
 * Übersetzer: t('key', ...$args) → vsprintf-Unterstützung: "Hi %s"
 */
if (!function_exists('t')) {
    function t(string $key, mixed ...$args): string {
        global $L;
        $val = $L[$key] ?? $key;
        if ($args) {
            // Versuche vsprintf, falls Platzhalter vorhanden
            try {
                $val = @vsprintf($val, $args) ?: $val;
            } catch (\Throwable) {}
        }
        return $val;
    }
}

/**
 * Baut die aktuelle URL mit geänderter Sprache (?lang=en/de) und
 * behält andere Query-Parameter bei.
 */
function build_lang_url(string $toLang): string {
    $uri  = $_SERVER['REQUEST_URI'] ?? '/';
    $u    = parse_url($uri);
    $path = $u['path'] ?? '/';
    parse_str($u['query'] ?? '', $qs);

    unset($qs['lang']);
    $qs['lang'] = $toLang;

    $query = http_build_query($qs);
    return $path . ($query ? ('?' . $query) : '');
}

/**
 * Generiert einfache HTML-Buttons für den Sprachwechsel.
 * Kann im Template direkt ausgegeben werden: <?= $LANG_SWITCH_HTML ?>
 */
function render_lang_switch_html(string $current): string {
    $urlDe = build_lang_url('de');
    $urlEn = build_lang_url('en');

    return <<<HTML
<div class="lang-switch flex items-center gap-2">
  <a href="{$urlDe}" class="px-2 py-1 rounded border text-sm hover:opacity-80 ".($current==='de'?'aria-current="true"':'').">🇩🇪 Deutsch</a>
  <a href="{$urlEn}" class="px-2 py-1 rounded border text-sm hover:opacity-80 ".($current==='en'?'aria-current="true"':'').">🇬🇧 English</a>
</div>
HTML;
}

/**
 * Optional: hreflang-Tags für SEO in <head> injizieren.
 * Kann im Template per <?= $HREFLANG_LINKS ?> ausgegeben werden.
 */
function build_hreflang_links(): string {
    $uri  = $_SERVER['REQUEST_URI'] ?? '/';
    $u    = parse_url($uri);
    $path = $u['path'] ?? '/';
    parse_str($u['query'] ?? '', $qs);
    unset($qs['lang']);

    // Basis-URL bestimmen (Host + Proto)
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';

    $links = [];
    foreach (LANGS_ALLOWED as $code) {
        $qs2 = $qs;
        $qs2['lang'] = $code;
        $url = $proto . '://' . $host . $path . '?' . http_build_query($qs2);
        $links[] = '<link rel="alternate" hreflang="' . htmlspecialchars($code) . '" href="' . htmlspecialchars($url) . '">';
    }
    // x-default auf Default
    $qsDefault = $qs;
    $qsDefault['lang'] = LANG_DEFAULT;
    $urlDefault = $proto . '://' . $host . $path . '?' . http_build_query($qsDefault);
    $links[] = '<link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($urlDefault) . '">';

    return implode("\n", $links);
}

/**
 * Rendert eine Seite im Theme mit i18n-Unterstützung.
 *
 * @param string $contentHtml HTML-Inhalt für den Content-Slot.
 * @param string $title       <title>-Text.
 */
function render_theme_page(string $contentHtml, string $title = 'HTDA CMS'): void {
    $templatePath = __DIR__ . '/../theme/page-template.php';
    if (!is_file($templatePath)) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Template fehlt: $templatePath";
        return;
    }

    // i18n vorbereiten
    $LANG               = detect_lang();          // 'de' | 'en'
    $GLOBALS['L']       = load_lang($LANG);       // global $L für t()
    $L                  = $GLOBALS['L'];          // auch als lokale Variable fürs Template
    $LANG_SWITCH_HTML   = render_lang_switch_html($LANG);
    $HREFLANG_LINKS     = build_hreflang_links();

    // Variablen, die das Template direkt nutzen kann
    $TITLE   = $title;
    $CONTENT = $contentHtml;

    // Optional nützlich im Template:
    $APP_BASE = $GLOBALS['APP_BASE'] ?? '';
    $csrf     = $GLOBALS['csrf']     ?? '';

    // Template als PHP ausführen (damit z. B. der Header-Login funktioniert)
    ob_start();
    include $templatePath;
    $html = (string)ob_get_clean();

    // Fallback 1: Falls das Template weiterhin [CONTENT] enthält, ersetze es robust
    if (strpos($html, '[CONTENT]') !== false) {
        $html = preg_replace('/\[CONTENT\]/', $contentHtml, $html, 1);
    }

    // Fallback 2: <title> setzen, wenn im Template ein statischer <title> steht
    if (preg_match('#<title>.*?</title>#i', $html)) {
        $html = preg_replace(
            '#<title>.*?</title>#i',
            '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>',
            $html,
            1
        );
    } else {
        // Optionaler alternativer Platzhalter
        $html = str_replace('{{TITLE}}', htmlspecialchars($title, ENT_QUOTES, 'UTF-8'), $html);
    }

    header('Content-Type: text/html; charset=utf-8');
    echo $html;
}
