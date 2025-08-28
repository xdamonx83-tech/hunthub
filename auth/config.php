<?php
declare(strict_types=1);

return [
  'db' => [
    'dsn'  => 'mysql:host=localhost;dbname=hunthub_db1;charset=utf8mb4',
    'user' => 'hunthub_db1',
    'pass' => 'T2M25sfeLZ@j#_bH%_',
    'options' => [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
    ],
  ],

  // Basis-URL-Pfad relativ zur Domain (leer = Root)
  'app_base' => '',

  // Sessions
  'sessions_table'     => 'auth_sessions',
  'sessions_touch_col' => 'last_seen',

  // ---- App Keys (später in .env auslagern) ----
  'app_key'       => 'CHANGE_ME_LONG_RANDOM',
  'ws_url'        => 'wss://hunthub.online',
  'ws_jwt_secret' => 'a37f92c41d68b02e5acf13897be456d20a9cf1673b8d24ea7519bd406cf82a91',

  // ---- Cookie-Settings (Top-Level: für Helper/Legacy-Code) ----
  // Diese Keys werden von meinen Helfern gelesen (session_cookie_name_from_config usw.)
  'session_cookie'  => 'sess_id',   // Name des Session-Cookies
  'cookie_path'     => '/',         // überall gültig (wichtig für /api/*)
  'cookie_domain'   => null,        // optional: z.B. '.hunthub.online' für www + non-www
  'cookie_secure'   => true,        // bei HTTPS true
  'cookie_httponly' => true,
  'cookie_samesite' => 'Lax',

  // ---- Optional: bestehender Block für andere Stellen im Code ----
  // Bleibt erhalten und wird mit den gleichen Werten befüllt.
  'cookies' => [
    'session_name' => 'sess_id',
    'lifetime'     => 60*60*24*14, // 14 Tage
    'secure'       => true,
    'httponly'     => true,
    'samesite'     => 'Lax',
    'path'         => '/',
    'domain'       => null,        // ggf. '.hunthub.online'
  ],
'gifs' => ['provider'=>'giphy','giphy_key'=>'E2VjWjIwY7seuWxgFrpjS3DPDBrmcY51','limit'=>24],

  // Sticker Ordner (für Sticker-Tab)
  'stickers' => [
    'dir'      => __DIR__ . '/../uploads/stickers', // lokaler Pfad
    'base_url' => '/uploads/stickers',              // URL-Pfad
  ],
  // Uploads
  'uploads' => [
    'avatars_dir' => __DIR__ . '/../uploads/avatars',
    'covers_dir'  => __DIR__ . '/../uploads/covers',
    'max_size'    => 2 * 1024 * 1024,
    'allowed'     => ['image/jpeg','image/png','image/webp'],

    // ▼ Videos
    'videos_dir'       => __DIR__ . '/../uploads/videos',
    'videos_max_size'  => 300 * 1024 * 1024, // 300 MB
    'videos_allowed'   => ['video/mp4','video/quicktime','video/webm','video/x-matroska'],
    'ffmpeg_bin'       => '/usr/bin/ffmpeg',
    'ffprobe_bin'      => '/usr/bin/ffprobe',
  ],
];
