<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth/db.php';

$pdo = db();

/* ---- Input ---- */
$uid    = isset($_GET['id'])   ? (int)$_GET['id'] : 0;
$slug   = isset($_GET['slug']) ? trim((string)$_GET['slug']) : '';
$w      = isset($_GET['w'])    ? max(200, min(1600, (int)$_GET['w'])) : 0;
$debug  = (isset($_GET['debug']) && $_GET['debug'] == '1');

function fail($msg, $code=500, $debug=false){
  http_response_code($code);
  if ($debug){
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
  }
  exit;
}
if ($uid <= 0 && $slug === '') fail('need id or slug', 400, $debug);
if (!extension_loaded('gd'))   fail('PHP GD extension missing', 500, $debug);

/* ---- User ---- */
if ($slug !== '') {
  $st = $pdo->prepare("SELECT id, display_name, slug, avatar_path, created_at FROM users WHERE slug=? LIMIT 1");
  $st->execute([$slug]);
} else {
  $st = $pdo->prepare("SELECT id, display_name, slug, avatar_path, created_at FROM users WHERE id=? LIMIT 1");
  $st->execute([$uid]);
}
$user = $st->fetch(PDO::FETCH_ASSOC);
if (!$user) fail('user not found', 404, $debug);
$uid = (int)$user['id'];

/* ---- Stats ---- */
$threadsCount = (int)$pdo->prepare("SELECT COUNT(*) FROM threads WHERE author_id=?")
  ->execute([$uid]) ?: 0;
$threadsCount = (int)$pdo->query("SELECT COUNT(*) FROM threads WHERE author_id={$uid}")->fetchColumn();

$postsCount   = (int)$pdo->query("SELECT COUNT(*) FROM posts   WHERE author_id={$uid}")->fetchColumn();
$likesThreads = (int)$pdo->query("SELECT COALESCE(SUM(likes_count),0) FROM threads WHERE author_id={$uid}")->fetchColumn();
$likesPosts   = (int)$pdo->query("SELECT COALESCE(SUM(likes_count),0) FROM posts   WHERE author_id={$uid}")->fetchColumn();
$likesTotal   = $likesThreads + $likesPosts;

/* ---- Canvas ---- */
$W=1200; $H=360;            // wie zuvor
$PAD=32;

$im = imagecreatetruecolor($W,$H);
imagesavealpha($im,true);
imagealphablending($im,true);
$clear = imagecolorallocatealpha($im,0,0,0,127);
imagefill($im,0,0,$clear);

/* ---- Colors ---- */
$white    = imagecolorallocate($im,238,238,238);
$muted    = imagecolorallocate($im,156,163,175);
$accent   = imagecolorallocate($im,242,150,32);

/* ---- Assets (dist-Style) ---- */
$APP = dirname(__DIR__); // eine Ebene über /gamercard
$BG_FILE   = $APP . '/assets/gamercard/bg.png';
$OVL_FILE  = $APP . '/assets/gamercard/overlay.png';
$FRAME_FILE= $APP . '/assets/gamercard/frame.png';

/* ---- Helper ---- */
function loadImageFlexible(string $pathOrAbs){
  if (!$pathOrAbs) return null;
  // Remote?
  if (preg_match('~^https?://~i',$pathOrAbs)) {
    $data=@file_get_contents($pathOrAbs);
    return ($data===false) ? null : @imagecreatefromstring($data);
  }
  // Bereits absoluter Pfad?
  $abs = $pathOrAbs;
  if (!is_file($abs)) {
    $abs = $_SERVER['DOCUMENT_ROOT'].'/'.ltrim($pathOrAbs,'/');
  }
  if (!is_file($abs)) return null;
  $ext=strtolower(pathinfo($abs,PATHINFO_EXTENSION));
  return match($ext){
    'jpg','jpeg' => @imagecreatefromjpeg($abs),
    'png'        => @imagecreatefrompng($abs),
    'gif'        => @imagecreatefromgif($abs),
    default      => @imagecreatefromstring(@file_get_contents($abs)),
  };
}
function drawCover(&$dst, $src){
  $W = imagesx($dst); $H = imagesy($dst);
  $sw = imagesx($src); $sh = imagesy($src);
  if ($sw<=0 || $sh<=0) return;
  $scale = max($W/$sw, $H/$sh);
  $tw = (int)round($sw*$scale);
  $th = (int)round($sh*$scale);
  $tx = (int)round(($W-$tw)/2);
  $ty = (int)round(($H-$th)/2);
  imagecopyresampled($dst,$src,$tx,$ty,0,0,$tw,$th,$sw,$sh);
}
function drawGradientLeft(&$img, int $width, array $rgbaFrom=[0,0,0,100], array $rgbaTo=[0,0,0,127]){
  // vertikale Höhe = Canvas-H; horizontal von links -> rechts
  $H = imagesy($img);
  $W = min($width, imagesx($img));
  for ($x=0; $x<$W; $x++){
    $t = $W>1 ? ($x/($W-1)) : 1.0;
    $a = (int)round($rgbaFrom[3]*(1-$t) + $rgbaTo[3]*$t);
    $r = (int)round($rgbaFrom[0]*(1-$t) + $rgbaTo[0]*$t);
    $g = (int)round($rgbaFrom[1]*(1-$t) + $rgbaTo[1]*$t);
    $b = (int)round($rgbaFrom[2]*(1-$t) + $rgbaTo[2]*$t);
    $col = imagecolorallocatealpha($img,$r,$g,$b,$a);
    imageline($img,$x,0,$x,$H,$col);
  }
}
function circleThumbGD($src,int $size,int $ring=0,$ringCol=null){
  $dst=imagecreatetruecolor($size,$size);
  imagesavealpha($dst,true);
  $t=imagecolorallocatealpha($dst,0,0,0,127);
  imagefill($dst,0,0,$t);
  $w=imagesx($src); $h=imagesy($src); $side=min($w,$h);
  $sx=(int)(($w-$side)/2); $sy=(int)(($h-$side)/2);
  imagecopyresampled($dst,$src,0,0,$sx,$sy,$size,$size,$side,$side);
  // kreisförmige Maske
  $mask=imagecreatetruecolor($size,$size);
  imagesavealpha($mask,true);
  $clear=imagecolorallocatealpha($mask,0,0,0,127);
  $solid=imagecolorallocatealpha($mask,0,0,0,0);
  imagefill($mask,0,0,$clear);
  imagefilledellipse($mask,$size/2,$size/2,$size,$size,$solid);
  for($x=0;$x<$size;$x++){
    for($y=0;$y<$size;$y++){
      $a=(imagecolorat($mask,$x,$y)&0x7F000000)>>24;
      $rgba=imagecolorat($dst,$x,$y);
      $a2=($rgba&0x7F000000)>>24;
      $newA=max($a,$a2);
      imagesetpixel($dst,$x,$y,($rgba&0x00FFFFFF)|($newA<<24));
    }
  }
  imagedestroy($mask);
  if ($ring>0 && $ringCol!==null){
    imagesetthickness($dst, max(1,$ring));
    imagearc($dst,$size/2,$size/2,$size-$ring,$size-$ring,0,360,$ringCol);
    imagesetthickness($dst,1);
  }
  return $dst;
}

/* ---- 1) Background (Cover) ---- */
$bg = is_file($BG_FILE) ? loadImageFlexible($BG_FILE) : null;
if ($bg) {
  drawCover($im, $bg);
  imagedestroy($bg);
} else {
  // Fallback: dunkler Verlauf
  $bgCol = imagecolorallocate($im,18,18,18);
  imagefilledrectangle($im,0,0,$W,$H,$bgCol);
}

/* ---- 2) Linke Vignette/Gradient für Textlesbarkeit ---- */
drawGradientLeft($im, 520, [0,0,0,75], [0,0,0,112]); // leicht → dunkler

/* ---- 3) Overlay/Frame (optional) ---- */
$ovl = is_file($OVL_FILE) ? loadImageFlexible($OVL_FILE) : null;
if ($ovl){
  imagesavealpha($ovl,true);
  imagecopyresampled($im,$ovl,0,0,0,0,$W,$H,imagesx($ovl),imagesy($ovl));
  imagedestroy($ovl);
}
$frame = is_file($FRAME_FILE) ? loadImageFlexible($FRAME_FILE) : null;
if ($frame){
  imagesavealpha($frame,true);
  imagecopyresampled($im,$frame,0,0,0,0,$W,$H,imagesx($frame),imagesy($frame));
  imagedestroy($frame);
}

/* ---- Avatar ---- */
$AV_SIZE = 140;
$av = loadImageFlexible((string)$user['avatar_path']);
if ($av){
  $ringCol = imagecolorallocate($im,242,150,32); // accent
  $circ = circleThumbGD($av,$AV_SIZE,4,$ringCol);
  imagecopy($im,$circ,$PAD,($H-$AV_SIZE)/2,0,0,$AV_SIZE,$AV_SIZE);
  imagedestroy($circ);
  imagedestroy($av);
} else {
  $ph=imagecolorallocatealpha($im,255,255,255,90);
  imagefilledellipse($im,$PAD+$AV_SIZE/2,$H/2,$AV_SIZE,$AV_SIZE,$ph);
}

/* ---- Fonts ---- */
$FONT_SEMI = $APP . '/assets/fonts/Inter-SemiBold.ttf';
$FONT_REG  = $APP . '/assets/fonts/Inter-Regular.ttf';
if (!is_file($FONT_SEMI) || !is_file($FONT_REG)) {
  $fallback = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
  if (is_file($fallback)) $FONT_SEMI = $FONT_REG = $fallback;
  else { $FONT_SEMI = $FONT_REG = null; }
}
$drawText = function($img,$size,$x,$y,$color,$font,$text){
  if ($font && function_exists('imagettftext')) {
    imagettftext($img,$size,0,$x,$y,$color,$font,$text);
  } else {
    imagestring($img, 5, $x, $y-14, $text, $color);
  }
};

/* ---- Texte (dist-Look: Name größer, zweite Zeile schmaler) ---- */
$name  = (string)$user['display_name'];
$since = 'Mitglied seit '.date('d.m.Y', strtotime((string)$user['created_at']));
$line2 = "Threads: {$threadsCount}   ·   Beiträge: {$postsCount}   ·   Likes: {$likesTotal}";

$tx = $PAD + $AV_SIZE + 24;
$drawText($im, 36, $tx, 132, $white, $FONT_SEMI, $name);
$drawText($im, 20, $tx, 174, $muted, $FONT_REG,  $line2);
$drawText($im, 18, $tx, 206, $muted, $FONT_REG,  $since);

/* ---- Badge rechts oben („HuntHub GamerCard“) ---- */
$badgeW=280; $badgeH=58; $bx=$W-$badgeW-$PAD; $by=$PAD+8;
$badgeBg=imagecolorallocatealpha($im,12,12,12,70);
imagefilledroundedrectangle:
if (!function_exists('imagefilledroundedrectangle')) {
  // mini helper:
  $rr = function($img,$x1,$y1,$x2,$y2,$r,$col){
    imagefilledrectangle($img,$x1+$r,$y1,$x2-$r,$y2,$col);
    imagefilledrectangle($img,$x1,$y1+$r,$x2,$y2-$r,$col);
    imagefilledellipse($img,$x1+$r,$y1+$r,2*$r,2*$r,$col);
    imagefilledellipse($img,$x2-$r,$y1+$r,2*$r,2*$r,$col);
    imagefilledellipse($img,$x1+$r,$y2-$r,2*$r,2*$r,$col);
    imagefilledellipse($img,$x2-$r,$y2-$r,2*$r,2*$r,$col);
  };
  $rr($im,$bx,$by,$bx+$badgeW,$by+$badgeH,24,$badgeBg);
}
$drawText($im, 20, $bx+20, $by+36, $accent, $FONT_SEMI, 'HuntHub GamerCard');

/* ---- Optionales Downscale (wie gehabt) ---- */
$out=$im;
if ($w>0 && $w<$W){
  $h=(int)round(($w/$W)*$H);
  $thumb=imagecreatetruecolor($w,$h);
  imagesavealpha($thumb,true); imagealphablending($thumb,false);
  $t=imagecolorallocatealpha($thumb,0,0,0,127); imagefill($thumb,0,0,$t);
  imagecopyresampled($thumb,$im,0,0,0,0,$w,$h,$W,$H);
  imagedestroy($im); $out=$thumb;
}

/* ---- Cache ---- */
$etag = '"' . md5($uid.$threadsCount.$postsCount.$likesTotal.($user['avatar_path']??'').($user['display_name']??'')) . '"';
header('Content-Type: image/png');
header('Cache-Control: public, max-age=300');
header('ETag: '.$etag);
if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) { http_response_code(304); exit; }

/* ---- Ausgabe ---- */
imagepng($out);
imagedestroy($out);
