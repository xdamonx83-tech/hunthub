<?php
declare(strict_types=1);
require_once __DIR__ . '/auth/guards.php';
require_once __DIR__ . '/auth/db.php';
require_once __DIR__ . '/auth/csrf.php';
require_once __DIR__ . '/lib/layout.php';

$me = require_auth();
$pdo = db();
$cfg = require __DIR__ . '/auth/config.php';
$csrf = issue_csrf($pdo, $_COOKIE[$cfg['cookies']['session_name']] ?? '');

ob_start(); ?>
<section class="grid lg:grid-cols-[240px_1fr] gap-8">
  <aside class="bg-white/5 border border-white/10 rounded-2xl p-6">
    <img class="w-44 h-44 rounded-full object-cover border border-white/10"
         src="<?= htmlspecialchars($me['avatar_path'] ?: '/cms/assets/images/avatars/placeholder.png') ?>" alt="Avatar">
    <p class="mt-3 text-white/70 text-sm">Eingeloggt als <strong><?= htmlspecialchars($me['display_name']) ?></strong></p>
    <a class="inline-block mt-3 px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20" href="/cms/u/<?= htmlspecialchars($me['slug']) ?>">Öffentliches Profil</a>
  </aside>

  <form id="profileForm" class="bg-white/5 border border-white/10 rounded-2xl p-6 grid gap-4"
        action="/cms/api/auth/update_profile.php" method="post" enctype="multipart/form-data">
    <h1 class="text-2xl font-semibold">Profil bearbeiten</h1>
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

    <label class="text-sm text-white/70">Anzeigename</label>
    <input class="bg-black/40 border border-white/10 rounded-lg px-3 py-2" name="display_name" value="<?= htmlspecialchars($me['display_name']) ?>" required>

    <label class="text-sm text-white/70">Bio</label>
    <textarea class="bg-black/40 border border-white/10 rounded-lg px-3 py-2" name="bio" rows="4"><?= htmlspecialchars($me['bio'] ?? '') ?></textarea>

    <label class="text-sm text-white/70">Avatar (PNG/JPG/WebP)</label>
    <input class="bg-black/40 border border-white/10 rounded-lg px-3 py-2" type="file" name="avatar" accept="image/png,image/jpeg,image/webp">

    <div class="mt-2">
      <button class="px-4 py-2 rounded-lg bg-white/10 hover:bg-white/20">Speichern</button>
    </div>
  </form>
</section>








<main>

            <!-- profile settiongs section start -->
            <section class="section-py">

                <div class="container pt-30p">

                    <div class="relative rounded-32 overflow-hidden">
                        <div class="glitch-effect">
                            <div class="glitch-thumb">
                                <img class="w-full xl:h-[472px] lg:h-[400px] md:h-[340px] sm:h-[300px] h-[240px] hover:scale-110 object-cover"
                                    src="assets/images/photos/profileCover2.png" alt="image">
                            </div>
                            <div class="glitch-thumb">
                                <img class="w-full xl:h-[472px] lg:h-[400px] md:h-[340px] sm:h-[300px] h-[240px] hover:scale-110 object-cover"
                                    src="assets/images/photos/profileCover2.png" alt="image">
                            </div>
                        </div>
                        <label for="coverPhoto"
                            class="cursor-pointer absolute xl:top-[30px] md:top-5 top-4 xl:right-[30px] md:right-5 right-4 z-[5]">
                            <span class="flex-c size-60p rounded-full bg-b-neutral-3 text-w-neutral-1 icon-32">
                                <i class="ti ti-camera"></i>
                            </span>
                        </label>
                        <input type="file" name="coverPhoto" id="coverPhoto" class="hidden" />
                    </div>

                    <div
                        class="relative flex 3xl:items-end max-3xl:items-center 3xl:justify-between max-3xl:flex-col gap-30p 3xl:mt-[90px] xl:-mt-52 lg:-mt-44 md:-mt-36 sm:-mt-30 -mt-20 4xl:mb-[70px] mb-60p">
                        <div
                            class=" 3xl:absolute 3xl:bottom-0 3xl:left-1/2 3xl:-translate-x-1/2 max-3xl:flex-col-c z-[4]">
                            <img class="avatar xl:size-60 lg:size-52 md:size-44 sm:size-40 size-28 border-2 border-secondary"
                                src="<?= htmlspecialchars($me['avatar_path'] ?: '/cms/assets/images/avatars/placeholder.png') ?>" alt="profile" />
                            <label for="profilePhoto"
                                class="cursor-pointer absolute lg:-bottom-6 md:-bottom-5 -bottom-4 left-1/2 -translate-x-1/2">
                                <span class=" flex-c size-60p rounded-full bg-primary text-b-neutral-4 icon-32">
                                    <i class="ti ti-camera"></i>
                                </span>
                            </label>
                            <input type="file" name="profilePhoto" id="profilePhoto" class="hidden" />
                        </div>

                    </div>

                    <div class="grid grid-cols-12 gap-30p">
                        <div class="xxl:col-start-3 xxl:col-end-11 col-span-12 ">
                            <div class="bg-b-neutral-3 rounded-12 p-40p">
                                <h4 class="heading-4 text-w-neutral-1 mb-60p">
                                    General Information
                                </h4>
                                <form>
                                    <div class="grid grid-cols-8 gap-30p">
                                        <div class="sm:col-span-4 col-span-8">
                                            <label for="first_name" class="label label-lg mb-3">First Name</label>
                                            <input type="text" name="display_name" value="<?= htmlspecialchars($me['display_name']) ?>" required class="box-input-3" />
                                        </div>
                                        <div class="sm:col-span-4 col-span-8">
                                            <label for="last_name" class="label label-lg mb-3">Last Name</label>
                                            <input type="text" name="last_name" id="last_name" class="box-input-3" />
                                        </div>
                                        <div class="col-span-8">
                                            <label for="email" class="label label-lg mb-3">Email</label>
                                            <input type="email" name="email" id="email" class="box-input-3" />
                                        </div>
                                        <div class="col-span-8">
                                            <label for="about" class="label label-lg mb-3">About me</label>
                                   
											<textarea class="box-input-3 h-[142px]" name="bio" rows="4"><?= htmlspecialchars($me['bio'] ?? '') ?></textarea>
                                        </div>
                                        <div class="col-span-8">
                                            <label for="location" class="label label-lg mb-3">Location</label>
                                            <input type="text" name="location" id="location" class="box-input-3" />
                                        </div>
                                        <div class="sm:col-span-4 col-span-8">
                                            <label for="working" class="label label-lg mb-3">Working at</label>
                                            <input type="text" name="working" id="working" class="box-input-3" />
                                        </div>
                                        <div class="sm:col-span-4 col-span-8">
                                            <label for="relationship" class="label label-lg mb-3">Relationship</label>
                                            <input type="text" name="relationship" id="relationship"
                                                class="box-input-3" />
                                        </div>
                                    </div>
                                    <div class="flex items-center md:justify-end justify-center">
                                        <button class="btn btn-md btn-primary rounded-12 mt-60p">
                                            Saved Change
                                        </button>
                                    </div>
                                </form>
                                <div>

                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </section>
            <!-- profile settiongs section end -->

        </main>

<script>
document.getElementById('profileForm')?.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const res = await fetch(e.target.action, {method:'POST', body:new FormData(e.target)});
  const json = await res.json();
  if (json.ok) location.reload(); else alert(json.error||'Fehler');
});
</script>
<?php
$content = ob_get_clean();
render_theme_page($content, 'Profil bearbeiten – HTDA');
