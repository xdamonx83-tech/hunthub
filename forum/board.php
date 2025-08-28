<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth/db.php';
require_once __DIR__ . '/../auth/guards.php';
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../lib/layout.php';
$lang = function_exists('detect_lang') ? detect_lang() : 'de';
$GLOBALS['L'] = load_lang($lang);
$q = trim($_GET['q'] ?? $_GET['search'] ?? '');
if ($q !== '') {
  header('Location: /forum/search.php?q=' . urlencode($q));
  exit;
}
$pdo = db();
$me  = function_exists('optional_auth') ? optional_auth() : null;

$cfg       = require __DIR__ . '/../auth/config.php';
$APP_BASE  = rtrim($cfg['app_base'] ?? '', '/');
$csrf      = issue_csrf($pdo, $_COOKIE[$cfg['cookies']['session_name']] ?? '');


$boardId = isset($_GET['b']) ? (int)$_GET['b'] : 0;
$cursor  = isset($_GET['cursor']) ? (string)$_GET['cursor'] : null;

$limit = 20;
$limitPlusOne = $limit + 1;
$q = trim($_GET['q'] ?? $_GET['search'] ?? '');
if ($q !== '') {
  header('Location: /forum/search.php?q=' . urlencode($q));
  exit;
}
/* ---------- Board ---------- */
$bst = $pdo->prepare("SELECT id, name, slug, description FROM boards WHERE id = ?");
$bst->execute([$boardId]);
$board = $bst->fetch(PDO::FETCH_ASSOC);
if (!$board) {
  http_response_code(404);
  echo "Board nicht gefunden";
  exit;
}

/* ---------- Cursor-Bedingung ---------- */
$cond   = '';
$params = [$boardId];
if ($cursor) {
  $parts = explode('|', $cursor, 2);
  if (count($parts) === 2) {
    $cond     = "AND (t.last_post_at < ? OR (t.last_post_at = ? AND t.id < ?))";
    $params[] = $parts[0];
    $params[] = $parts[0];
    $params[] = (int)$parts[1];
  }
}

/* ---------- Threads inkl. letztem Antwortenden ---------- */
/* nutzt posts(thread_id,id)-Index optimal */
$sql = "
SELECT
  t.id,
  t.title,
  t.slug,
  t.is_locked,
  t.is_pinned,
  t.posts_count,
  t.last_post_at,

  -- Ersteller (Fallback, falls noch keine Antwort existiert)
  ua.display_name AS author_name,
  ua.avatar_path  AS author_avatar,
  ua.id           AS author_id,

  -- Letzter Post & dessen Autor
  ul.display_name AS last_user_name,
  ul.avatar_path  AS last_user_avatar,
  ul.id           AS last_user_id

FROM threads t
JOIN users ua ON ua.id = t.author_id

LEFT JOIN (
  SELECT thread_id, MAX(id) AS last_post_id
  FROM posts
  WHERE deleted_at IS NULL
  GROUP BY thread_id
) lp          ON lp.thread_id = t.id
LEFT JOIN posts  pl ON pl.id = lp.last_post_id
LEFT JOIN users  ul ON ul.id = pl.author_id

WHERE t.board_id = ? AND t.deleted_at IS NULL
$cond
ORDER BY t.is_pinned DESC, t.last_post_at DESC, t.id DESC
LIMIT $limitPlusOne";
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

/* ---------- Pagination-Cursor ---------- */
$nextCursor = null;
if (count($rows) > $limit) {
  $last = array_pop($rows);
  $nextCursor = ($last['last_post_at'] ?? '') . '|' . $last['id'];
}

$title = 'Board – ' . $board['name'];

ob_start();
?>

        <!-- main start -->
        <main>
            <section class="pt-30p">
                <div class="section-pt">
                    <div
                        class="relative bg-[url('../images/photos/breadcrumbImg.png')] bg-cover bg-no-repeat rounded-24 overflow-hidden">
                        <div class="container">
                            <div class="grid grid-cols-12 gap-30p relative xl:py-[130px] md:py-30 sm:py-25 py-20 z-[2]">
                                <div class="lg:col-start-2 lg:col-end-12 col-span-12">
                                    <h2 class="heading-2 text-w-neutral-1 mb-3">
                                        <?= htmlspecialchars($board['name']) ?>
                                    </h2>
                                    <ul class="breadcrumb">
                                        <li class="breadcrumb-item">
                                            <a href="/" class="breadcrumb-link">
                                                Home
                                            </a>
                                        </li>
                                        <li class="breadcrumb-item">
                                            <span class="breadcrumb-icon">
                                                <i class="ti ti-chevrons-right"></i>
                                            </span>
                                        </li>
										    <li class="breadcrumb-item">
                                            <a href="/forum/boards.php" class="breadcrumb-link">
                                                Forum
                                            </a>
                                        </li>
										     <li class="breadcrumb-item">
                                            <span class="breadcrumb-icon">
                                                <i class="ti ti-chevrons-right"></i>
                                            </span>
                                        </li>
                                        <li class="breadcrumb-item">
                                            <span class="breadcrumb-current"><?= htmlspecialchars($board['name']) ?></span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="overlay-11"></div>
                    </div>
                </div>
            </section>
            <!-- teams section start -->
            <section class="section-pb pt-30">
                <div class="container">
               
                    <div class="flex items-center justify-between flex-wrap gap-24p mb-30p">


                        <form class="flex items-center sm:flex-row flex-col gap-28p shrink-0 sm:w-fit w-full">
                            <div
                                class="sm:w-[230px] w-full shrink-0 px-16p py-3 flex items-center justify-between sm:gap-3 gap-2 rounded-12 border border-shap">
                                <input autocomplete="off" class="bg-transparent text-w-neutral-1 w-full" type="text"
                                    name="search" id="search" placeholder="Search...">
                                <button type="submit" class="flex-c icon-24 text-w-neutral-4">
                                    <i class="ti ti-search"></i>
                                </button>
                            </div>
							</form>
                    <?php if ($me): ?>
        <button id="openThreadModal" class="btn btn-md btn-primary rounded-12" >
       <?php echo $L['thread_create']; ?>
        </button>
      <?php endif; ?>
                        

	  <?php if ($me): ?>
  <!-- Modal: Thread erstellen -->
  <style>
    .modal-mask{position:fixed;inset:0;background:rgba(0,0,0,.65);display:none;z-index:9999}
    .modal-wrap{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:20px}
    .modal-card{background:#0b0b0b;border:1px solid rgba(255,255,255,.1);border-radius:16px;max-width:720px;width:100%}
    .modal-hd{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;border-bottom:1px solid rgba(255,255,255,.08)}
    .modal-bd{padding:18px}
    .btnx{display:inline-flex;align-items:center;gap:8px;border-radius:10px;padding:10px 14px;border:1px solid rgba(255,255,255,.15);background:#1b1b1b;color:#fff}
    .btnx.primary{background:#f29620;color:#111;border-color:#f29620}
  </style>

  <div id="threadModal" class="modal-mask" aria-hidden="true">
    <div class="modal-wrap" role="dialog" aria-modal="true" aria-labelledby="threadModalTitle">
      <div class="modal-card">
<div class="modal-hd">
  <strong id="threadModalTitle"><?= t('thread_new') ?></strong>
  <button type="button" class="btnx" id="closeThreadModal"><?= t('close') ?></button>
</div>
<div class="modal-bd">
  <form id="newThreadForm" action="<?= $APP_BASE ?>/api/forum/create_thread.php" method="post">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="board_id" value="<?= (int)$board['id'] ?>">

    <label class="label label-lg mb-2"><?= t('title') ?></label>
    <input class="box-input-3 mb-3" type="text" name="title" id="threadTitle"
           placeholder="<?= t('title') ?>" required maxlength="200">

    <label class="label label-lg mb-2"><?= t('first_post') ?></label>
    <textarea class="box-input-3 h-[140px] mb-4" name="content"
              placeholder="<?= t('first_post') ?>" required></textarea>

    <div class="flex justify-end gap-2">
      <button type="button" class="btnx" id="cancelThreadModal"><?= t('cancel') ?></button>
      <button class="btnx primary" type="submit"><?= t('create') ?></button>
    </div>
  </form>
</div>

        </div>
      </div>
    </div>
  </div>

  <script>
  (() => {
    const openBtn  = document.getElementById('openThreadModal');
    const modal    = document.getElementById('threadModal');
    const closeBtn = document.getElementById('closeThreadModal');
    const cancelBtn= document.getElementById('cancelThreadModal');
    const form     = document.getElementById('newThreadForm');
    const titleEl  = document.getElementById('threadTitle');

    function openModal(){ modal.style.display='block'; setTimeout(()=>titleEl?.focus(),30); }
    function closeModal(){ modal.style.display='none'; }

    openBtn?.addEventListener('click', openModal);
    closeBtn?.addEventListener('click', closeModal);
    cancelBtn?.addEventListener('click', closeModal);
    modal?.addEventListener('click', (e)=>{ if(e.target===modal) closeModal(); });
    window.addEventListener('keydown',(e)=>{ if(e.key==='Escape' && modal.style.display==='block') closeModal(); });

    form?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(form);
      try {
        const res = await fetch(form.action, {
          method: 'POST',
          body: fd,
          credentials: 'include',
          headers: { 'Accept': 'application/json' }
        });
        const out = await res.json().catch(()=>({}));
        if (!res.ok || !out?.ok) throw new Error(out?.error || 'Fehler');

        const base = '<?= $APP_BASE ?>';
        const slug = out.slug ? '&slug=' + encodeURIComponent(out.slug) : '';
        window.location.href = `${base}/forum/thread.php?t=${out.thread_id}${slug}`;
      } catch (err) {
        alert(err.message || 'Fehler beim Erstellen des Threads');
      }
    });
  })();
  </script>
  <?php endif; ?>
                    </div>
                    <div class="grid 4xl:grid-cols-4 xxl:grid-cols-3 md:grid-cols-2 grid-cols-1 gap-30p">


        <?php foreach ($rows as $t): ?>
                <?php
                  // Fallback auf Ersteller, wenn noch niemand geantwortet hat
                  $lastName   = $t['last_user_name']   ?: $t['author_name'];
                  $lastAvatar = $t['last_user_avatar'] ?: ($t['author_avatar'] ?: $avatarFallback);
                  $lastUserId = $t['last_user_id']     ?: (int)$t['author_id'];

                  $threadUrl  = $APP_BASE . '/forum/thread.php?t=' . (int)$t['id']
                              . (!empty($t['slug']) ? '&slug=' . urlencode($t['slug']) : '');
                  $authorUrl  = $APP_BASE . '/user.php?id=' . (int)$t['author_id'];
                  $lastUrl    = $APP_BASE . '/user.php?id=' . (int)$lastUserId;

                  $authorAvatar = !empty($t['author_avatar']) ? $t['author_avatar'] : $avatarFallback;
                ?>


                        <!-- Team 1 -->
                        <div class="bg-b-neutral-3 rounded-12 p-32p border border-transparent hover:border-accent-7 group transition-1"
                            data-aos="zoom-in">
                            <div class="flex items-start justify-between gap-24p mb-24p">
                                <div class="flex-y flex-wrap gap-3">
                                    <img class="avatar size-60p" src="<?= htmlspecialchars($authorAvatar) ?>" onerror="this.onerror=null;this.src='<?= $avatarFallback ?>'" alt="<?= htmlspecialchars($t['author_name']) ?>" />
                                    <div>
                                        <a href="<?= $threadUrl ?>"
                                            class="text-xl-medium text-w-neutral-1 link-1"><?= htmlspecialchars($t['title']) ?></a>
                                        <span class="text-m-medium text-w-neutral-3"><?= htmlspecialchars($t['author_name']) ?></span>
                                    </div>
                                </div>
                          
                            </div>
                            <div class="flex-y flex-wrap gap-20p whitespace-nowrap mb-32p">
               
                                <div>
                                    <span class="text-m-medium text-w-neutral-4 mb-1"><?php echo $L['posts']; ?></span>
                                    <div class="text-l-medium text-w-neutral-1"><?= (int)$t['posts_count'] ?></div>
                                </div>
                                <div>
                                    <span class="text-m-medium text-w-neutral-4 mb-1"><?php echo $L['date']; ?></span>
                                    <span class="text-l-medium text-w-neutral-1"><?= !empty($t['last_post_at'])
     ? htmlspecialchars(date('d.m.Y - H:i', strtotime((string)$t['last_post_at'])))
     : '' ?></span>
                                </div>
                            </div>
                            <div class="flex-y flex-wrap justify-between gap-24p pt-32p border-t border-t-shap">
                                <div
                                    class="flex items-center *:size-40p *:shrink-0 *:size-40p *:border *:border-white *:-ml-3 ml-3">
                                    <img class="avatar" src="<?= $lastAvatar ?>" alt="user" />
                               
                                    <span
                                        class="flex-c rounded-full bg-[#333333] text-s-medium text-w-neutral-1">+<?= (int)$t['posts_count'] ?></span>
                                </div>
                                <a href="<?= $threadUrl ?>"
                                    class="btn px-16p py-2 btn-outline-secondary group-hover:bg-secondary group-hover:text-b-neutral-4">
                                    <?php echo $L['go_to_thread']; ?>
                                </a>
                            </div>
                        </div>
 <?php endforeach; ?>
                        <!-- Team 2 -->
                        

                        <!-- Team 3 -->
                        

                        <!-- Team 4 -->
                        

                        <!-- Team 5 -->
                        

                        <!-- Team 6 -->
                        

                        <!-- Team 7 -->
                        

                        <!-- Team 8 -->
                        

                        <!-- Team 9 -->
                        

                        <!-- Team 10 -->
                        

                        <!-- Team 11 -->
                        

                        <!-- Team 12 -->
                        

                    </div>
               <?php if ($nextCursor): ?>
                <div class="p-16 text-center">
                  <a class="btn btn-sm btn-secondary rounded-12"
                     href="?b=<?= (int)$board['id'] ?>&cursor=<?= urlencode($nextCursor) ?>">
                    <?php echo $L['load_more_threads']; ?>
                  </a>
                </div>
              <?php endif; ?>
                </div>
            </section>
            <!-- teams section end -->

        </main>
        <!-- main end -->

        <!-- footer start -->
<?php
$content = ob_get_clean();
render_theme_page($content, $title);