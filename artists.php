<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';

requireLogin();

$userId      = currentUserId();
$artists     = fetchArtists();
$favoriteIds = fetchUserFavoriteIds($userId);

$user        = currentUser();
$avatarUrl   = $user['avatar_url'] ?? 'https://lh3.googleusercontent.com/aida-public/AB6AXuCZGGpKymmFjsTWBBhEo1m_qEKaThQn-Adw8py8GO4s75UqQ0wVTMAzJM0fka_sY4GEck3HaQV2VcyuxRJC95v6kkJ_mkwhFAwRXH_Z7rFXWZguOIkkCOUnxbJVp8lEP5DEyCdY_ca1wbUTxAg8zbFydoK2H02VnqbluK6potBhWIEqNYYxc13acQB-nlezAHIoKpuN4dR_umneEZRpy_YFGJb-LMRpYcG5q0rxkqTYkya8ju3-CUP_KY97bKDSHHz_yS882HLRP48';

$pageTitle  = 'Artists';
$topBarLeft = '<a href="home.php" class="text-primary hover:bg-white/5 transition-colors p-2 rounded-full">'
            . '<span class="material-symbols-outlined">arrow_back</span></a>';
$topBarRight = '<a href="profile.php" class="hover:bg-white/5 transition-colors p-2 rounded-full w-10 h-10 overflow-hidden">'
             . '<img alt="Profile" class="w-full h-full rounded-full object-cover" src="' . e($avatarUrl) . '"/></a>';
require __DIR__ . '/includes/header.php';

// Pick top artist (highest vibe match) as the hero, others go in the grid
$topArtist  = $artists[0] ?? null;
$risingStars = array_slice($artists, 1);
?>
<main class="content-padding max-w-4xl mx-auto space-y-10"
      data-favorites='<?= htmlspecialchars(json_encode(array_map("strval", $favoriteIds)), ENT_QUOTES, "UTF-8") ?>'>

    <section class="space-y-4 animate-on-scroll">
        <h2 class="font-display text-headline-lg text-on-surface">Curated for Your <span class="text-gradient">Vibe</span></h2>
        <p class="font-body text-body-lg text-on-surface-variant">Based on your listening history, we found these matches.</p>

        <div class="glass-panel rounded-full flex items-center px-4 py-3">
            <span class="material-symbols-outlined text-outline mr-3">search</span>
            <input type="text" data-search-input=".artist-card" placeholder="Search artists..."
                   class="bg-transparent border-none outline-none w-full text-on-surface placeholder-on-surface-variant"/>
        </div>

        <div class="chip-group flex gap-2 overflow-x-auto pb-2 no-scrollbar" data-filter-target=".artist-card">
            <button class="chip chip-active whitespace-nowrap">All</button>
            <button class="chip chip-inactive whitespace-nowrap">Techno</button>
            <button class="chip chip-inactive whitespace-nowrap">House</button>
            <button class="chip chip-inactive whitespace-nowrap">Melodic</button>
            <button class="chip chip-inactive whitespace-nowrap">Electronic</button>
        </div>
    </section>

    <?php if ($topArtist):
        $artistDataAttr = htmlspecialchars(json_encode([
            'id'        => $topArtist['slug'],
            'artist_id' => (int) $topArtist['id'],
            'name'      => $topArtist['name'],
            'genre'     => $topArtist['genre'],
            'stage'     => 'Main Stage',
            'time'      => 'Tonight, 11:30 PM',
            'image'     => $topArtist['image_url'] ?? '',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
    ?>
    <section class="animate-on-scroll">
        <div class="artist-card glass-card rounded-2xl overflow-hidden relative group cursor-pointer"
             data-tags="<?= e(strtolower($topArtist['genre'])) ?>"
             data-artist='<?= $artistDataAttr ?>'>
            <div class="absolute top-4 right-4 z-10 flex items-center gap-2 bg-surface/80 backdrop-blur-md px-3 py-1.5 rounded-full border border-primary/30">
                <span class="material-symbols-outlined text-primary text-sm" style="font-variation-settings: 'FILL' 1;">local_fire_department</span>
                <span class="font-body text-label-sm text-primary"><?= (int) $topArtist['vibe_match'] ?>% Vibe Match</span>
            </div>
            <div class="h-56 md:h-80 w-full relative">
                <div class="absolute inset-0 bg-gradient-to-t from-[#0A0A0C] via-transparent to-transparent z-10"></div>
                <?php if (!empty($topArtist['image_url'])): ?>
                    <img alt="<?= e($topArtist['name']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" src="<?= e($topArtist['image_url']) ?>"/>
                <?php else: ?>
                    <div class="w-full h-full bg-gradient-to-br from-primary-container/30 to-tertiary-container/20"></div>
                <?php endif; ?>
            </div>
            <div class="p-5 relative z-20 -mt-16">
                <div class="flex justify-between items-end">
                    <div>
                        <div class="inline-block px-3 py-1 rounded-full bg-secondary-container/30 text-secondary-fixed-dim font-body text-label-sm mb-2 border border-secondary-container/50">Top Match</div>
                        <h3 class="font-display text-headline-lg text-on-surface"><?= e($topArtist['name']) ?></h3>
                        <p class="font-body text-body-md text-on-surface-variant mt-1">Main Stage • Tonight, 11:30 PM</p>
                    </div>
                    <button class="btn-primary hidden md:flex" data-favorite-btn-pivot data-artist-id="<?= (int) $topArtist['id'] ?>">
                        <span class="material-symbols-outlined text-[16px]"><?= in_array((int) $topArtist['id'], $favoriteIds, true) ? 'favorite' : 'add' ?></span>
                        <span class="schedule-label"><?= in_array((int) $topArtist['id'], $favoriteIds, true) ? 'Favorited' : 'Favorite' ?></span>
                    </button>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="space-y-4 animate-on-scroll">
        <h3 class="font-display text-headline-md text-on-surface flex items-center gap-2">
            <span class="material-symbols-outlined text-tertiary">trending_up</span>
            Rising Stars
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($risingStars as $artist):
                $isFav = in_array((int) $artist['id'], $favoriteIds, true);
                $artistDataAttr = htmlspecialchars(json_encode([
                    'id'        => $artist['slug'],
                    'artist_id' => (int) $artist['id'],
                    'name'      => $artist['name'],
                    'genre'     => $artist['genre'],
                    'stage'     => 'TBA',
                    'time'      => 'TBA',
                    'image'     => $artist['image_url'] ?? '',
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
            ?>
            <div class="artist-card glass-card rounded-xl p-4 flex flex-col gap-4 relative cursor-pointer group"
                 data-tags="<?= e(strtolower($artist['genre'])) ?>"
                 data-artist='<?= $artistDataAttr ?>'>
                <div class="absolute top-6 right-6 z-10 bg-surface/80 backdrop-blur-md px-2 py-1 rounded-full border border-white/10">
                    <span class="font-body text-label-sm text-tertiary"><?= (int) $artist['vibe_match'] ?>% Match</span>
                </div>
                <div class="h-36 w-full rounded-lg overflow-hidden relative">
                    <?php if (!empty($artist['image_url'])): ?>
                        <img alt="<?= e($artist['name']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" src="<?= e($artist['image_url']) ?>"/>
                    <?php else: ?>
                        <div class="w-full h-full bg-gradient-to-br from-primary-container/30 to-secondary-container/30 flex items-center justify-center">
                            <span class="material-symbols-outlined text-primary text-4xl" style="font-variation-settings: 'FILL' 1;">graphic_eq</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <h4 class="font-display text-[18px] text-on-surface"><?= e($artist['name']) ?></h4>
                    <p class="font-body text-[13px] text-on-surface-variant"><?= e($artist['genre']) ?></p>
                </div>
                <button class="favorite-btn-pill w-full bg-surface-container-highest hover:bg-surface-container-high text-on-surface border border-white/10 font-body text-label-sm py-2.5 rounded-lg flex items-center justify-center gap-2 transition-colors <?= $isFav ? 'favorited' : '' ?>"
                        data-favorite-btn-pivot data-artist-id="<?= (int) $artist['id'] ?>">
                    <span class="material-symbols-outlined text-[16px]" <?= $isFav ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>
                        <?= $isFav ? 'favorite' : 'favorite_border' ?>
                    </span>
                    <span class="schedule-label"><?= $isFav ? 'Favorited' : 'Favorite' ?></span>
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/modal_artist.php'; ?>
<?php require __DIR__ . '/includes/nav.php'; ?>
