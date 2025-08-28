<?php
declare(strict_types=1);
require_once __DIR__ . '/auth/guards.php';
require_once __DIR__ . '/lib/layout.php';

$me = current_user();

ob_start();
if ($me) {
    ?>
    <div class="bg-white/5 border border-white/10 rounded-2xl p-6 text-center">
        <h1 class="heading-2 text-w-neutral-1 mb-4">
            Willkommen, <?= htmlspecialchars($me['display_name']) ?>
        </h1>
        <p class="mb-6">Rolle: <span class="px-2 py-1 bg-white/10 rounded"><?= htmlspecialchars($me['role']) ?></span></p>
        <a href="/cms/profile.php" class="btn">Profil bearbeiten</a>
        <a href="/cms/u/<?= htmlspecialchars($me['slug']) ?>" class="btn">Öffentliches Profil</a>
        <a id="logout" href="#" class="btn bg-red-600 hover:bg-red-700">Logout</a>
    </div>
    <?php
} else {
    ?>
    <div class="grid md:grid-cols-2 gap-6">
      <form id="loginForm" class="bg-white/5 border border-white/10 rounded-2xl p-6 grid gap-4">
        <h2 class="heading-3 mb-2">Anmelden</h2>
        <input name="email" type="email" placeholder="E-Mail" class="input">
        <input name="password" type="password" placeholder="Passwort" class="input">
        <button class="btn">Login</button>
        <p id="loginMsg" class="text-sm text-red-400"></p>
      </form>

      <form id="regForm" class="bg-white/5 border border-white/10 rounded-2xl p-6 grid gap-4">
        <h2 class="heading-3 mb-2">Registrieren</h2>
        <input name="display_name" placeholder="Anzeigename" class="input">
        <input name="email" type="email" placeholder="E-Mail" class="input">
        <input name="password" type="password" placeholder="Passwort" class="input">
        <button class="btn">Registrieren</button>
        <p id="regMsg" class="text-sm text-red-400"></p>
      </form>
    </div>
    <?php
}
$content = ob_get_clean();

render_theme_page($content, $me ? 'Dashboard' : 'Login / Registrieren');
