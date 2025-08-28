<?php
declare(strict_types=1);
session_start();

// --- Pfade sicher laden
require_once __DIR__ . '/auth/auth.php';

$LAYOUT_OK = false;
$layoutPath = __DIR__ . '/lib/layout.php';
if (file_exists($layoutPath)) {
    require_once $layoutPath;
    if (function_exists('start_layout') && function_exists('end_layout')) {
        $LAYOUT_OK = true;
    }
}

// CSRF holen (defensiv)
$csrfToken = null;
if (function_exists('csrf_token')) {
    $csrfToken = csrf_token();
} elseif (function_exists('csrf_get_token')) {
    $csrfToken = csrf_get_token();
}
if (!$csrfToken) {
    $_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
    $csrfToken = $_SESSION['csrf_token'];
}

// Eingeloggt? -> Startseite
if (!empty($_SESSION['user_id'] ?? null)) {
    header('Location: /');
    exit;
}

$pageTitle = 'Registrieren';

// --- Header rendern
if ($LAYOUT_OK) {
    start_layout($pageTitle);
} else {
    // Fallback: direktes Theme-Header/Footerset
    $TITLE = $pageTitle; // viele Themes lesen $TITLE
    @include __DIR__ . '/theme/header.php';
}
?>
<body>

    <!-- preloader start -->
    <div class="preloader">
    <div class="loader"></div>
</div>
    <!-- preloader end -->

    <!-- scroll to top button start -->
    <button class="scroll-to-top show" id="scrollToTop">
    <i class="ti ti-arrow-up"></i>
</button>
    <!-- scroll to top button end -->

    <!-- header start -->
    <header id="header" class="absolute w-full z-[999]">
  <div class="mx-auto relative">
    <div id="header-nav" class="w-full px-24p bg-b-neutral-3 relative">
      <div class="flex items-center justify-between gap-x-2 mx-auto py-20p">
        <nav class="relative xl:grid xl:grid-cols-12 flex justify-between items-center gap-24p text-semibold w-full">
          <div class="3xl:col-span-6 xl:col-span-5 flex items-center 3xl:gap-x-10 gap-x-5">
            <a href="index-2.html" class="shrink-0">
              <img class="xl:w-[170px] sm:w-36 w-30 h-auto shrink-0" src="assets/images/icons/logo.png" alt="brand" />
            </a>
            <form
              class="hidden lg:flex items-center sm:gap-3 gap-2 min-w-[300px] max-w-[670px] w-full px-20p py-16p bg-b-neutral-4 rounded-full">
              <span class="flex-c icon-20 text-white">
                <i class="ti ti-search"></i>
              </span>
              <input autocomplete="off" class="bg-transparent w-full" type="text" name="search" id="search"
                placeholder="Search..." />
            </form>
          </div>
          <div class="3xl:col-span-6 xl:col-span-7 flex items-center xl:justify-between justify-end w-full">
            <a href="#"
              class="hidden xl:inline-flex items-center gap-3 pl-1 py-1 pr-6  rounded-full bg-[rgba(242,150,32,0.10)] text-w-neutral-1 text-base">
              <span class="size-48p flex-c text-b-neutral-4 bg-primary rounded-full icon-32">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                  class="icon icon-tabler icons-tabler-outline icon-tabler-speakerphone">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                  <path d="M18 8a3 3 0 0 1 0 6" />
                  <path d="M10 8v11a1 1 0 0 1 -1 1h-1a1 1 0 0 1 -1 -1v-5" />
                  <path
                    d="M12 8h0l4.524 -3.77a.9 .9 0 0 1 1.476 .692v12.156a.9 .9 0 0 1 -1.476 .692l-4.524 -3.77h-8a1 1 0 0 1 -1 -1v-4a1 1 0 0 1 1 -1h8" />
                </svg>
              </span>
              News For You
            </a>
            <div class="flex items-center lg:gap-x-32p gap-x-2">
              <div class="hidden lg:flex items-center gap-1 shrink-0">
                <a href="shopping-cart.html" class="btn-c btn-c-lg btn-c-dark-outline">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                    class="icon icon-tabler icons-tabler-outline icon-tabler-shopping-cart">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                    <path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                    <path d="M17 17h-11v-14h-2" />
                    <path d="M6 5l14 1l-1 7h-13" />
                  </svg>
                </a>
                <div class="relative hidden lg:block">
                  <a href="chat.html" class="btn-c btn-c-lg btn-c-dark-outline">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                      stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                      class="icon icon-tabler icons-tabler-outline icon-tabler-bell">
                      <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                      <path
                        d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6" />
                      <path d="M9 17v1a3 3 0 0 0 6 0v-1" />
                    </svg>
                  </a>
                </div>
              </div>
              <div x-data="dropdown" class="dropdown relative shrink-0 lg:block hidden">
                <button @click="toggle()" class="dropdown-toggle gap-24p">
                  <span class="flex items-center gap-3">
                    <img class="size-48p rounded-full shrink-0" src="assets/images/users/user1.png" alt="profile" />
                    <span class="">
                      <span class="text-m-medium text-w-neutral-1 mb-1">
                        David Malan
                      </span>
                      <span class="text-sm text-w-neutral-4 block">
                        270 Followars
                      </span>
                    </span>
                  </span>
                  <span :class="isOpen ? '-rotate-180' : ''"
                    class="btn-c btn-c-lg text-w-neutral-4 icon-32 transition-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                      stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                      class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-down">
                      <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                      <path d="M6 9l6 6l6 -6" />
                    </svg>
                  </span>
                </button>

                <div x-show="isOpen" x-transition @click.away="close()" class="dropdown-content">
                  <a href="profile.html" class="dropdown-item">Profile</a>
                  <a href="user-settings.html" class="dropdown-item">Settings</a>
                  <button type="button" @click="close()" class="dropdown-item">Logout</button>
                  <a href="contact-us.html" class="dropdown-item">Help</a>
                </div>
              </div>

              <button class="lg:hidden btn-c btn-c-lg btn-c-dark-outline nav-toggole shrink-0">
                <i class="ti ti-menu-2"></i>
              </button>
            </div>
          </div>
        </nav>
      </div>
    </div>
    <nav class="w-full flex justify-between items-center">
      <div
        class="small-nav fixed top-0 left-0 h-screen w-full shadow-lg z-[999] transform transition-transform ease-in-out invisible md:translate-y-full max-md:-translate-x-full duration-500">
        <div class="absolute z-[5] inset-0 bg-b-neutral-3 flex-col-c min-h-screen max-md:max-w-[400px]">
          <div class="container max-md:p-0 md:overflow-y-hidden overflow-y-scroll scrollbar-sm lg:max-h-screen">
            <div class="p-40p">
              <div class="flex justify-between items-center mb-10">
                <a href="index-2.html">
                  <img class="w-[142px]" src="assets/images/icons/logo.png" alt="GameCo" />
                </a>
                <button class="nav-close btn-c btn-c-md btn-c-primary">
                  <i class="ti ti-x"></i>
                </button>
              </div>
              <div class="grid grid-cols-12 gap-x-24p gap-y-10 sm:p-y-48p">
                <div class="xl:col-span-8 md:col-span-7 col-span-12">
                  <div
                    class="overflow-y-scroll overflow-x-hidden scrollbar scrollbar-sm xl:max-h-[532px] md:max-h-[400px] md:pr-4">
                    <ul class="flex flex-col justify-center items-start gap-20p text-w-neutral-1">
                      <li class="sub-menu mobail-submenu">
                        <span class="mobail-submenu-btn">
                          <span class="submenu-btn">Home</span>
                          <span class="collapse-icon mobail-submenu-icon">
                            <i class="ti ti-chevron-down "></i>
                          </span>
                        </span>
                        <ul class="grid gap-y-2 px-16p">
                          <li class="pt-2">
                            <a aria-label="item" class="text-base hover:text-primary transition-1" href="index-2.html">
                              - Home One
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1" href="home-two.html">
                              - Home Two
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="home-three.html">
                              - Home Three
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="home-four.html">
                              - Home Four
                            </a>
                          </li>
                        </ul>
                      </li>
                      <li class="mobail-menu">
                        <a href="trending.html">Trending</a>
                      </li>
                      <li class="mobail-menu">
                        <a href="community.html">Community</a>
                      </li>
                      <li class="mobail-menu">
                        <a href="saved.html">Saved</a>
                      </li>
                      <li class="mobail-menu">
                        <a href="live-stream.html">Live Stream</a>
                      </li>
                      <li class="sub-menu mobail-submenu">
                        <span class="mobail-submenu-btn">
                          <span class="submenu-btn">Library</span>
                          <span class="collapse-icon mobail-submenu-icon">
                            <i class="ti ti-chevron-down"></i>
                          </span>
                        </span>
                        <ul class="grid gap-y-2 px-16p">
                          <li class="pt-2">
                            <a aria-label="item" class="text-base hover:text-primary transition-1" href="library.html">
                              - Library
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="library-details.html">
                              - Library Details
                            </a>
                          </li>
                        </ul>
                      </li>
                      <li class="sub-menu mobail-submenu">
                        <span class="mobail-submenu-btn">
                          <span class="submenu-btn">Games</span>
                          <span class="collapse-icon mobail-submenu-icon">
                            <i class="ti ti-chevron-down"></i>
                          </span>
                        </span>
                        <ul class="grid gap-y-2 px-16p">
                          <li class="pt-2">
                            <a aria-label="item" class="text-base hover:text-primary transition-1" href="games.html">
                              - Games
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="game-details.html">
                              - Game Details One
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="game-details-two.html">
                              - Game Details Two
                            </a>
                          </li>
                        </ul>
                      </li>
                      <li class="sub-menu mobail-submenu">
                        <span class="mobail-submenu-btn">
                          <span class="submenu-btn">Groups</span>
                          <span class="collapse-icon mobail-submenu-icon">
                            <i class="ti ti-chevron-down"></i>
                          </span>
                        </span>
                        <ul class="grid gap-y-2 px-16p">
                          <li class="pt-2">
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="groups-one.html">
                              - Groups One
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="groups-two.html">
                              - Group Two
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="group-home.html">
                              - Group Home
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="group-related-groups.html">
                              - Related Groups
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="group-forum.html">
                              - Group Forum
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="group-members.html">
                              - Group Members
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="group-media.html">
                              - Group Media
                            </a>
                          </li>
                        </ul>
                      </li>
                      <li class="sub-menu mobail-submenu">
                        <span class="mobail-submenu-btn">
                          <span class="submenu-btn">Teams</span>
                          <span class="collapse-icon mobail-submenu-icon">
                            <i class="ti ti-chevron-down"></i>
                          </span>
                        </span>
                        <ul class="grid gap-y-2 px-16p">
                          <li class="pt-2">
                            <a aria-label="item" class="text-base hover:text-primary transition-1" href="teams.html">
                              - Teams
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="team-home.html">
                              - Team Members
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="team-games.html">
                              - Team Games
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="team-ranks.html">
                              - Team Ranks
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="team-tournament.html">
                              - Team Tournament
                            </a>
                          </li>
                        </ul>
                      </li>
                      <li class="sub-menu mobail-submenu">
                        <span class="mobail-submenu-btn">
                          <span class="submenu-btn">Tournaments</span>
                          <span class="collapse-icon mobail-submenu-icon">
                            <i class="ti ti-chevron-down"></i>
                          </span>
                        </span>
                        <ul class="grid gap-y-2 px-16p">
                          <li class="pt-2">
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="tournaments.html">
                              - Tournaments
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="tournament-overview.html">
                              - Tournament Overview
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="tournament-prizes.html">
                              - Tournament Prizes
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="tournament-participants.html">
                              - Tournament Participants
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="tournament-matches.html">
                              - Tournament Matches
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="tournament-brackets.html">
                              - Tournament Brackets
                            </a>
                          </li>
                        </ul>
                      </li>
                      <li class="mobail-menu">
                        <a href="leaderboard.html">Leaderboard</a>
                      </li>
                      <li class="sub-menu mobail-submenu">
                        <span class="mobail-submenu-btn">
                          <span class="submenu-btn">Marketplace</span>
                          <span class="collapse-icon mobail-submenu-icon">
                            <i class="ti ti-chevron-down"></i>
                          </span>
                        </span>
                        <ul class="grid gap-y-2 px-16p">
                          <li class="pt-2">
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="marketplace-one.html">
                              - Marketplace One
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="marketplace-two.html">
                              - Marketplace Two
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="marketplace-details.html">
                              - Marketplace Details
                            </a>
                          </li>
                        </ul>
                      </li>
                      <li class="sub-menu mobail-submenu">
                        <span class="mobail-submenu-btn">
                          <span class="submenu-btn">Profile</span>
                          <span class="collapse-icon mobail-submenu-icon">
                            <i class="ti ti-chevron-down "></i>
                          </span>
                        </span>
                        <ul class="grid gap-y-2 px-16p">
                          <li class="pt-2">
                            <a aria-label="item" class="text-base hover:text-primary transition-1" href="profile.html">
                              - Post Item
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="user-game-stats.html">
                              - Game Stats
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="user-about.html">
                              - About
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="user-team.html">
                              - My Team
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="user-groups.html">
                              - My Group
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="user-forums.html">
                              - Forums
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="user-videos.html">
                              - Videos
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="user-achievements.html">
                              - Achievements
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1" href="chat.html">
                              - Chat
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="user-settings.html">
                              - Settings
                            </a>
                          </li>
                        </ul>
                      </li>
                      <li class="sub-menu mobail-submenu">
                        <span class="mobail-submenu-btn">
                          <span class="submenu-btn">Shop</span>
                          <span class="collapse-icon mobail-submenu-icon">
                            <i class="ti ti-chevron-down "></i>
                          </span>
                        </span>
                        <ul class="grid gap-y-2 px-16p">
                          <li class="pt-2">
                            <a aria-label="item" class="text-base hover:text-primary transition-1" href="shop.html">
                              - Shop
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="shop-details.html">
                              - Shop Details
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="shopping-cart.html">
                              - Shopping Cart
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1" href="shipping.html">
                              - Shipping
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1" href="checkout.html">
                              - checkout
                            </a>
                          </li>
                        </ul>
                      </li>
                      <li class="sub-menu mobail-submenu">
                        <span class="mobail-submenu-btn">
                          <span class="submenu-btn">Blogs</span>
                          <span class="collapse-icon mobail-submenu-icon">
                            <i class="ti ti-chevron-down "></i>
                          </span>
                        </span>
                        <ul class="grid gap-y-2 px-16p">
                          <li class="pt-2">
                            <a aria-label="item" class="text-base hover:text-primary transition-1" href="blogs.html">
                              - Blogs
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="blog-details.html">
                              - Blog Details
                            </a>
                          </li>
                        </ul>
                      </li>
                      <li class="sub-menu mobail-submenu">
                        <span class="mobail-submenu-btn">
                          <span class="submenu-btn">Pages</span>
                          <span class="collapse-icon mobail-submenu-icon">
                            <i class="ti ti-chevron-down "></i>
                          </span>
                        </span>
                        <ul class="grid gap-y-2 px-16p">
                          <li class="pt-2">
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="pricing-plan.html">
                              - Pricing Plan
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="terms-conditions.html">
                              - Terms Conditions
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1" href="faqs.html">
                              - Faq's
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1"
                              href="not-found.html">
                              - Not Found
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1" href="login.html">
                              - Login
                            </a>
                          </li>
                          <li>
                            <a aria-label="item" class="text-base hover:text-primary transition-1" href="sign-up.html">
                              - Sign Up
                            </a>
                          </li>
                        </ul>
                      </li>
                      <li class="mobail-menu">
                        <a href="contact-us.html">Contact Us</a>
                      </li>
                    </ul>
                  </div>
                </div>
                <div class="xl:col-span-4 md:col-span-5 col-span-12">
                  <div class="flex flex-col items-baseline justify-between h-full">
                    <form
                      class="w-full flex items-center justify-between px-16p py-2 pr-1 border border-w-neutral-4/60 rounded-full">
                      <input class="placeholder:text-w-neutral-4 bg-transparent w-full" type="text" name="search-media"
                        placeholder="Search Media" id="search-media" />
                      <button type="submit" class="btn-c btn-c-md text-w-neutral-4">
                        <i class="ti ti-search"></i>
                      </button>
                    </form>
                    <div class="mt-40p">
                      <img class="mb-16p" src="assets/images/icons/logo.png" alt="logo" />
                      <p class="text-base text-w-neutral-3 mb-32p">
                        Become visionary behind a sprawling metropolis in Metropolis Tycoon Plan
                        empire
                        progress.
                      </p>
                      <div class="flex items-center flex-wrap gap-3">
                        <a href="#" class="btn-socal-primary">
                          <i class="ti ti-brand-facebook"></i>
                        </a>
                        <a href="#" class="btn-socal-primary">
                          <i class="ti ti-brand-twitch"></i>
                        </a>
                        <a href="#" class="btn-socal-primary">
                          <i class="ti ti-brand-instagram"></i>
                        </a>
                        <a href="#" class="btn-socal-primary">
                          <i class="ti ti-brand-discord"></i>
                        </a>
                        <a href="#" class="btn-socal-primary">
                          <i class="ti ti-brand-youtube"></i>
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="nav-close min-h-[200vh] navbar-overly"></div>
      </div>
    </nav>
  </div>
</header>
    <!-- header end -->

    <!-- sidebar start -->
    <div>
    <!-- left sidebar start-->
    <div
        class="fixed top-0 left-0 lg:translate-x-0 -translate-x-full h-screen z-30 bg-b-neutral-4 pt-30 px-[27px] transition-1">
        <div class="max-h-screen overflow-y-auto scrollbar-0">
            <div class="flex flex-col items-center xxl:gap-[30px] xl:gap-6 lg:gap-5 gap-4 h-[700px] side-navbar-one">
                <button class="nav-toggole btn-c btn-c-3xl btn-primary icon-32 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                        class="icon icon-tabler icons-tabler-outline icon-tabler-layout-grid">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M4 4m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" />
                        <path d="M14 4m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" />
                        <path d="M4 14m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" />
                        <path d="M14 14m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" />
                    </svg>
                </button>
                <div class="flex flex-col gap-2 rounded-full bg-b-neutral-1 w-fit p-2 shrink-0">
                    <a href="trending.html" class="nav-item btn-c btn-c-3xl text-white  transition-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                            class="icon icon-tabler icons-tabler-outline icon-tabler-flame">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path
                                d="M12 10.941c2.333 -3.308 .167 -7.823 -1 -8.941c0 3.395 -2.235 5.299 -3.667 6.706c-1.43 1.408 -2.333 3.621 -2.333 5.588c0 3.704 3.134 6.706 7 6.706s7 -3.002 7 -6.706c0 -1.712 -1.232 -4.403 -2.333 -5.588c-2.084 3.353 -3.257 3.353 -4.667 2.235" />
                        </svg>
                    </a>
                    <a href="groups-two.html" class="nav-item btn-c btn-c-3xl text-white  transition-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                            class="icon icon-tabler icons-tabler-outline icon-tabler-users-group">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M10 13a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                            <path d="M8 21v-1a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v1" />
                            <path d="M15 5a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                            <path d="M17 10h2a2 2 0 0 1 2 2v1" />
                            <path d="M5 5a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                            <path d="M3 13v-1a2 2 0 0 1 2 -2h2" />
                        </svg>
                    </a>
                    <a href="saved.html" class="nav-item btn-c btn-c-3xl text-white  transition-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                            class="icon icon-tabler icons-tabler-outline icon-tabler-bookmark">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M18 7v14l-6 -4l-6 4v-14a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4z" />
                        </svg>
                    </a>
                    <a href="user-achievements.html" class="nav-item btn-c btn-c-3xl text-white  transition-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                            class="icon icon-tabler icons-tabler-outline icon-tabler-star">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path
                                d="M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873z" />
                        </svg>
                    </a>
                </div>
                <div class="flex flex-col gap-2 rounded-full w-fit p-2 shrink-0">
                    <a href="marketplace-two.html" class="nav-item btn-c btn-c-3xl ">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                            class="icon icon-tabler icons-tabler-outline icon-tabler-diamond">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M6 5h12l3 5l-8.5 9.5a.7 .7 0 0 1 -1 0l-8.5 -9.5l3 -5" />
                            <path d="M10 12l-2 -2.2l.6 -1" />
                        </svg>
                    </a>
                    <a href="chat.html" class="nav-item btn-c btn-c-3xl ">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                            class="icon icon-tabler icons-tabler-outline icon-tabler-messages">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M21 14l-3 -3h-7a1 1 0 0 1 -1 -1v-6a1 1 0 0 1 1 -1h9a1 1 0 0 1 1 1v10" />
                            <path d="M14 15v2a1 1 0 0 1 -1 1h-7l-3 3v-10a1 1 0 0 1 1 -1h2" />
                        </svg>
                    </a>
                    <a href="profile.html" class="nav-item btn-c btn-c-3xl ">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                            class="icon icon-tabler icons-tabler-outline icon-tabler-user">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                            <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- left sidebar end -->
    <!-- right sidebar start -->
    <div
        class="fixed top-0 right-0 lg:translate-x-0 translate-x-full h-screen z-30 bg-b-neutral-4 pt-30 px-[27px] transition-1">
        <div class="flex flex-col items-center xxl:gap-[30px] xl:gap-6 lg:gap-5 gap-4">
            <div class="flex flex-col items-center gap-16p rounded-full w-fit p-2">
                <div class="swiper infinity-slide-vertical messenger-carousel max-h-[288px] w-full">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide">
                            <a href="chat.html" class="avatar size-60p">
                                <img src="assets/images/users/avatar1.png" alt="avatar">
                            </a>
                        </div>
                        <div class="swiper-slide">
                            <a href="chat.html" class="avatar size-60p">
                                <img src="assets/images/users/avatar2.png" alt="avatar">
                            </a>
                        </div>
                        <div class="swiper-slide">
                            <a href="chat.html" class="avatar size-60p">
                                <img src="assets/images/users/avatar3.png" alt="avatar">
                            </a>
                        </div>
                        <div class="swiper-slide">
                            <a href="chat.html" class="avatar size-60p">
                                <img src="assets/images/users/avatar4.png" alt="avatar">
                            </a>
                        </div>
                        <div class="swiper-slide">
                            <a href="chat.html" class="avatar size-60p">
                                <img src="assets/images/users/avatar1.png" alt="avatar">
                            </a>
                        </div>
                        <div class="swiper-slide">
                            <a href="chat.html" class="avatar size-60p">
                                <img src="assets/images/users/avatar2.png" alt="avatar">
                            </a>
                        </div>
                        <div class="swiper-slide">
                            <a href="chat.html" class="avatar size-60p">
                                <img src="assets/images/users/avatar3.png" alt="avatar">
                            </a>
                        </div>
                        <div class="swiper-slide">
                            <a href="chat.html" class="avatar size-60p">
                                <img src="assets/images/users/avatar4.png" alt="avatar">
                            </a>
                        </div>
                    </div>
                </div>
                <a href="#"
                    class="btn-c btn-c-xl bg-b-neutral-1 hover:bg-primary text-white hover:text-b-neutral-4 transition-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                        class="icon icon-tabler icons-tabler-outline icon-tabler-plus">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M12 5l0 14" />
                        <path d="M5 12l14 0" />
                    </svg>
                </a>
            </div>
            <div class="w-full h-1px bg-b-neutral-1"></div>
            <div class="flex flex-col items-center gap-16p rounded-full w-fit p-2">
                <div class="swiper infinity-slide-vertical messenger-carousel max-h-[136px] w-full">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide">
                            <a href="chat.html" class="avatar size-60p">
                                <img src="assets/images/users/avatar5.png" alt="avatar">
                            </a>
                        </div>
                        <div class="swiper-slide">
                            <a href="chat.html" class="avatar size-60p">
                                <img src="assets/images/users/avatar6.png" alt="avatar">
                            </a>
                        </div>
                        <div class="swiper-slide">
                            <a href="chat.html" class="avatar size-60p">
                                <img src="assets/images/users/avatar3.png" alt="avatar">
                            </a>
                        </div>
                        <div class="swiper-slide">
                            <a href="chat.html" class="avatar size-60p">
                                <img src="assets/images/users/avatar4.png" alt="avatar">
                            </a>
                        </div>
                        <div class="swiper-slide">
                            <a href="chat.html" class="avatar size-60p">
                                <img src="assets/images/users/avatar1.png" alt="avatar">
                            </a>
                        </div>
                        <div class="swiper-slide">
                            <a href="chat.html" class="avatar size-60p">
                                <img src="assets/images/users/avatar2.png" alt="avatar">
                            </a>
                        </div>
                        <div class="swiper-slide">
                            <a href="chat.html" class="avatar size-60p">
                                <img src="assets/images/users/avatar3.png" alt="avatar">
                            </a>
                        </div>
                        <div class="swiper-slide">
                            <a href="chat.html" class="avatar size-60p">
                                <img src="assets/images/users/avatar4.png" alt="avatar">
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- right sidebar end -->
</div>
    <!-- sidebar end -->

    <!-- app layout start -->
    <div class="app-layout">

        <!-- main start -->
        <main>

            <!-- breadcrumb start -->
            <section class="pt-30p">
                <div class="section-pt">
                    <div
                        class="relative bg-[url('../images/photos/breadcrumbImg.png')] bg-cover bg-no-repeat rounded-24 overflow-hidden">
                        <div class="container">
                            <div class="grid grid-cols-12 gap-30p relative xl:py-[130px] md:py-30 sm:py-25 py-20 z-[2]">
                                <div class="lg:col-start-2 lg:col-end-12 col-span-12">
                                    <h2 class="heading-2 text-w-neutral-1 mb-3">
                                        Sign Up
                                    </h2>
                                    <ul class="breadcrumb">
                                        <li class="breadcrumb-item">
                                            <a href="#" class="breadcrumb-link">
                                                Home
                                            </a>
                                        </li>
                                        <li class="breadcrumb-item">
                                            <span class="breadcrumb-icon">
                                                <i class="ti ti-chevrons-right"></i>
                                            </span>
                                        </li>
                                        <li class="breadcrumb-item">
                                            <span class="breadcrumb-current">Sign Up</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="overlay-11"></div>
                    </div>
                </div>
            </section>
            <!-- breadcrumb end -->

            <!-- sign up section start -->
            <section class="section-py">
                <div class="container">
                    <div class="flex-c">
                        <div class="max-w-[530px] w-full p-40p bg-b-neutral-3 rounded-12">
                            <h2 class="heading-2 text-w-neutral-1 mb-16p text-center">
                                Sign Up
                            </h2>
                            <p class="text-m-medium text-w-neutral-3 text-center">
                                Already have an account?
                                <a href="login.html" class="inline text-primary">
                                    Login
                                </a>
                            </p>
                            <div class="grid grid-cols-1 gap-3 py-32p text-center">
                                <button class="btn btn-md bg-[#434DE4] hover:bg-[#434DE4]/80 w-full">
                                    <i class="ti ti-brand-discord icon-24"></i>
                                    Log In With Discord
                                </button>
                                <button class="btn btn-md bg-[#6E31DF] hover:bg-[#6E31DF]/80 w-full">
                                    <i class="ti ti-brand-twitch icon-24"></i>
                                    Log In with Twitch
                                </button>
                                <button class="btn btn-md bg-[#1876F2] hover:bg-[#1876F2]/80 w-full">
                                    <i class="ti ti-brand-facebook icon-24"></i>
                                    Log In With Facebook
                                </button>
                                <div x-data="{ isOpen: false }" class="pb-20p">
                                    <button @click="isOpen = !isOpen" :aria-expanded="isOpen.toString()"
                                        class="inline-flex items-center justify-center gap-2 text-s-medium text-w-neutral-1">
                                        Show more
                                        <i :class="isOpen ? 'rotate-180' : ''" class="ti ti-chevron-down icon-20"></i>
                                    </button>
                                    <div x-show="isOpen" x-collapse @click.away="isOpen = false">
                                        <div class="grid grid-cols-1 gap-3 mt-16p">
                                            <button class="btn btn-md bg-[#6E31DF] hover:bg-[#6E31DF]/80 w-full">
                                                <i class="ti ti-brand-instagram icon-24"></i>
                                                Log In with Instagram
                                            </button>
                                            <button class="btn btn-md bg-[#1876F2] hover:bg-[#1876F2]/80 w-full">
                                                <i class="ti ti-brand-google icon-24"></i>
                                                Log In With Google
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3">
                                    <div class="w-full h-1px bg-shap"></div>
                                    <span>Or</span>
                                    <div class="w-full h-1px bg-shap"></div>
                                </div>
                            </div>
                            <form>
                                <div class="grid grid-cols-1 gap-30p mb-40p">
                                    <div>
                                        <label for="username" class="label label-xl text-w-neutral-1 font-borda mb-3">
                                            Username
                                        </label>
                                        <input class="border-input-1" type="username" name="username" id="username"
                                            placeholder="username" />
                                    </div>
                                    <div>
                                        <label for="password" class="label label-xl text-w-neutral-1 font-borda mb-3">
                                            Password
                                        </label>
                                        <input class="border-input-1" type="Password" name="Password" id="password"
                                            placeholder="Password" />
                                    </div>
                                    <div>
                                        <label for="userEmail" class="label label-xl text-w-neutral-1 font-borda mb-3">
                                            Email
                                        </label>
                                        <input class="border-input-1" type="email" name="email" id="userEmail"
                                            placeholder="Email" />
                                    </div>
                                    <div>
                                        <label for="dateOfBarth"
                                            class="label label-xl text-w-neutral-1 font-borda mb-3">
                                            Date of birth
                                        </label>
                                        <input class="border-input-1 flatpickr" type="date" name="dateObBrth"
                                            id="dateofbarth" placeholder="Month - Date - Year" />
                                    </div>
                                    <div>
                                        <label
                                            class="label label-md text-w-neutral-1 inline-flex items-center cursor-pointer gap-3">
                                            <input type="checkbox" id="togglePricing" value="" checked
                                                class="sr-only peer togglePricing">
                                            <span
                                                class="relative w-11 h-6 bg-w-neutral-1 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:bg-w-neutral-1 after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-b-neutral-3 after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary shrink-0">
                                            </span>
                                            Yes, email me offers and information about competitions and events on GameCO
                                        </label>
                                    </div>
                                </div>
                                <button class="btn btn-md btn-primary rounded-12 w-full mb-16p">
                                    Sing Up For Free
                                </button>
                                <a href="terms-conditions.html"
                                    class="text-m-medium text-primary underline text-center">
                                    Privacy Policy
                                </a>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
            <!-- sign up section end -->

        </main>
        <!-- main end -->

        <!-- footer start -->
        <footer class="relative section-pt overflow-hidden bg-b-neutral-3">
    <div class="container">
        <div class="relative z-10 lg:px-10">
            <div class="flex items-center justify-between gap-24p pb-60p">
                <div class="max-w-[530px]">
                    <h2 class="display-4 text-w-neutral-1 mb-32p text-split-left">Subscribe to our</h2>
                    <h2 class="display-lg mb-32p">
                        Newsletter
                    </h2>
                    <form class="flex items-center gap-24p pb-16p border-b-2 border-dashed border-shap">
                        <input type="email" name="subscribe" id="subscribe" required
                            placeholder="Enter your email address"
                            class="input w-full bg-transparent text-w-neutral-1 placeholder:text-w-neutral-4" />
                        <button type="submit" class="text-lg font-semibold font-poppins">Subscribe</button>
                    </form>
                </div>

            </div>
            <div
                class="grid 4xl:grid-cols-12 3xl:grid-cols-4 sm:grid-cols-2 grid-cols-1 4xl:gap-x-6 max-4xl:gap-40p border-y-2 border-dashed border-shap py-80p">
                <div class="4xl:col-start-1 4xl:col-end-4">
                    <img class="mb-16p" src="assets/images/icons/logo.png" alt="logo" />
                    <p class="text-base text-w-neutral-3 mb-32p">
                        Become visionary behind a sprawling metropolis in Metropolis Tycoon Plan empire progress.
                    </p>
                    <div class="flex items-center gap-3">
                        <a href="#" class="btn-socal-primary">
                            <i class="ti ti-brand-facebook"></i>
                        </a>
                        <a href="#" class="btn-socal-primary">
                            <i class="ti ti-brand-twitch"></i>
                        </a>
                        <a href="#" class="btn-socal-primary">
                            <i class="ti ti-brand-instagram"></i>
                        </a>
                        <a href="#" class="btn-socal-primary">
                            <i class="ti ti-brand-discord"></i>
                        </a>
                        <a href="#" class="btn-socal-primary">
                            <i class="ti ti-brand-youtube"></i>
                        </a>
                    </div>
                </div>
                <div class="4xl:col-start-5 4xl:col-end-7">
                    <div class="flex items-center gap-24p mb-24p">
                        <h4 class="heading-4 text-w-neutral-1 whitespace-nowrap ">
                            Main pages
                        </h4>
                        <span class="w-full max-w-[110px] h-0.5 bg-w-neutral-1"></span>
                    </div>
                    <ul class="grid grid-cols-2 sm:gap-y-16p gap-y-2 gap-x-32p *:flex *:items-center">
                        <li
                            class="group hover:translate-x-0 -translate-x-5 inline-flex items-center gap-1 hover:text-primary transition-1 max-w-fit">
                            <i
                                class="ti ti-chevron-right  group-hover:visible invisible text-primary group-hover:opacity-100 opacity-0 transition-1"></i>
                            <a href="library.html" class="text-m-regular text-w-neutral-3">
                                My Library
                            </a>
                        </li>
                        <li
                            class="group hover:translate-x-0 -translate-x-5 inline-flex items-center gap-1 hover:text-primary transition-1 max-w-fit">
                            <i
                                class="ti ti-chevron-right  group-hover:visible invisible text-primary group-hover:opacity-100 opacity-0 transition-1"></i>
                            <a href="trending.html" class="text-m-regular text-w-neutral-3">
                                Trending
                            </a>
                        </li>
                        <li
                            class="group hover:translate-x-0 -translate-x-5 inline-flex items-center gap-1 hover:text-primary transition-1 max-w-fit">
                            <i
                                class="ti ti-chevron-right  group-hover:visible invisible text-primary group-hover:opacity-100 opacity-0 transition-1"></i>
                            <a href="leaderboard.html" class="text-m-regular text-w-neutral-3">
                                Leaderboard
                            </a>
                        </li>
                        <li
                            class="group hover:translate-x-0 -translate-x-5 inline-flex items-center gap-1 hover:text-primary transition-1 max-w-fit">
                            <i
                                class="ti ti-chevron-right  group-hover:visible invisible text-primary group-hover:opacity-100 opacity-0 transition-1"></i>
                            <a href="chat.html" class="text-m-regular text-w-neutral-3">
                                Chat
                            </a>
                        </li>
                        <li
                            class="group hover:translate-x-0 -translate-x-5 inline-flex items-center gap-1 hover:text-primary transition-1 max-w-fit">
                            <i
                                class="ti ti-chevron-right  group-hover:visible invisible text-primary group-hover:opacity-100 opacity-0 transition-1"></i>
                            <a href="marketplace-two.html" class="text-m-regular text-w-neutral-3">
                                Marketplace
                            </a>
                        </li>
                        <li
                            class="group hover:translate-x-0 -translate-x-5 inline-flex items-center gap-1 hover:text-primary transition-1 max-w-fit">
                            <i
                                class="ti ti-chevron-right  group-hover:visible invisible text-primary group-hover:opacity-100 opacity-0 transition-1"></i>
                            <a href="shop.html" class="text-m-regular text-w-neutral-3">
                                Shop
                            </a>
                        </li>
                        <li
                            class="group hover:translate-x-0 -translate-x-5 inline-flex items-center gap-1 hover:text-primary transition-1 max-w-fit">
                            <i
                                class="ti ti-chevron-right  group-hover:visible invisible text-primary group-hover:opacity-100 opacity-0 transition-1"></i>
                            <a href="contact-us.html" class="text-m-regular text-w-neutral-3">
                                Support
                            </a>
                        </li>
                        <li
                            class="group hover:translate-x-0 -translate-x-5 inline-flex items-center gap-1 hover:text-primary transition-1 max-w-fit">
                            <i
                                class="ti ti-chevron-right  group-hover:visible invisible text-primary group-hover:opacity-100 opacity-0 transition-1"></i>
                            <a href="blogs.html" class="text-m-regular text-w-neutral-3">
                                Blogs
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="4xl:col-start-8 4xl:col-end-10">
                    <div class="flex items-center gap-24p mb-24p">
                        <h4 class="heading-4 text-w-neutral-1 whitespace-nowrap ">
                            Utility pages
                        </h4>
                        <span class="w-full max-w-[110px] h-0.5 bg-w-neutral-1"></span>
                    </div>
                    <ul class="grid grid-cols-2 sm:gap-y-16p gap-y-2 gap-x-32p *:flex *:items-center">
                        <li
                            class="group hover:translate-x-0 -translate-x-5 inline-flex items-center gap-1 hover:text-primary transition-1 max-w-fit">
                            <i
                                class="ti ti-chevron-right  group-hover:visible invisible text-primary group-hover:opacity-100 opacity-0 transition-1"></i>
                            <a href="tournaments.html" class="text-m-regular text-w-neutral-3">
                                Tournaments
                            </a>
                        </li>
                        <li
                            class="group hover:translate-x-0 -translate-x-5 inline-flex items-center gap-1 hover:text-primary transition-1 max-w-fit">
                            <i
                                class="ti ti-chevron-right  group-hover:visible invisible text-primary group-hover:opacity-100 opacity-0 transition-1"></i>
                            <a href="games.html" class="text-m-regular text-w-neutral-3">
                                Games
                            </a>
                        </li>
                        <li
                            class="group hover:translate-x-0 -translate-x-5 inline-flex items-center gap-1 hover:text-primary transition-1 max-w-fit">
                            <i
                                class="ti ti-chevron-right  group-hover:visible invisible text-primary group-hover:opacity-100 opacity-0 transition-1"></i>
                            <a href="community.html" class="text-m-regular text-w-neutral-3">
                                Community
                            </a>
                        </li>
                        <li
                            class="group hover:translate-x-0 -translate-x-5 inline-flex items-center gap-1 hover:text-primary transition-1 max-w-fit">
                            <i
                                class="ti ti-chevron-right  group-hover:visible invisible text-primary group-hover:opacity-100 opacity-0 transition-1"></i>
                            <a href="pricing-plan.html" class="text-m-regular text-w-neutral-3">
                                Pricing
                            </a>
                        </li>
                        <li
                            class="group hover:translate-x-0 -translate-x-5 inline-flex items-center gap-1 hover:text-primary transition-1 max-w-fit">
                            <i
                                class="ti ti-chevron-right  group-hover:visible invisible text-primary group-hover:opacity-100 opacity-0 transition-1"></i>
                            <a href="#" class="text-m-regular text-w-neutral-3">
                                Notifications
                            </a>
                        </li>
                        <li
                            class="group hover:translate-x-0 -translate-x-5 inline-flex items-center gap-1 hover:text-primary transition-1 max-w-fit">
                            <i
                                class="ti ti-chevron-right  group-hover:visible invisible text-primary group-hover:opacity-100 opacity-0 transition-1"></i>
                            <a href="not-found.html" class="text-m-regular text-w-neutral-3">
                                Not found
                            </a>
                        </li>
                        <li
                            class="group hover:translate-x-0 -translate-x-5 inline-flex items-center gap-1 hover:text-primary transition-1 max-w-fit">
                            <i
                                class="ti ti-chevron-right  group-hover:visible invisible text-primary group-hover:opacity-100 opacity-0 transition-1"></i>
                            <a href="contact-us.html" class="text-m-regular text-w-neutral-3">
                                Contact
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="4xl:col-start-11 4xl:col-end-13">
                    <h4 class="heading-4 text-w-neutral-1 whitespace-nowrap  mb-3">
                        Email Us
                    </h4>
                    <a href="mailto:debra.holt@example.com" class="text-base text-w-neutral-3 mb-32p">
                        debra.holt@example.com
                    </a>
                    <h4 class="heading-5 whitespace-nowrap mb-3">
                        Contact Us
                    </h4>
                    <a href="tel:207555-0119" class="text-base text-w-neutral-3">
                        (207) 555-0119
                    </a>
                </div>
            </div>
            <div class="flex items-center justify-between flex-wrap gap-24p py-30p">
                <div class="flex items-center flex-wrap">
                    <p class="text-base text-w-neutral-3">
                        Copyright ©
                        <span class="currentYear span"></span>
                    </p>
                    <div class="w-1px h-4 bg-shap mx-24p"></div>
                    <p class="text-base text-white">
                        Designed By <a href="https://themeforest.net/user/uiaxis/portfolio"
                            class="text-primary hover:underline a">UIAXIS</a>
                    </p>
                </div>
                <div class="flex items-center text-base gap-24p text-white">
                    <a href="faqs.html" class="hover:text-primary transition-1 block">
                        Privacy
                    </a>
                    <a href="terms-conditions.html" class="hover:text-primary transition-1 block">
                        Terms & Conditions
                    </a>
                </div>
            </div>
        </div>
        <div class="absolute right-0 top-0 xl:block hidden" data-aos="zoom-out-right" data-aos-duration="800">
            <img class="3xl:w-[580px] xxl:w-[500px] xl:w-[400px]" src="assets/images/photos/footerIllustration.webp"
                alt="footer" />
        </div>
    </div>
</footer>

<!-- Registrierungs-Logik -->
<script defer src="/assets/js/register.js?v=1"></script>

<?php end_layout(); // schließt Body + Footer ?>
