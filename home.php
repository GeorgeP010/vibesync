<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';

requireLogin();

$userId       = currentUserId();
$favoriteIds  = fetchUserFavoriteIds($userId);
$scheduledIds = fetchUserScheduledPerformanceIds($userId);
$nowPlaying   = fetchNowPlaying();
$upNext       = fetchUpNext(3);

// Crowd heatmap: take the busiest and the quietest queue right now.
$busiest = dbQuery(
    'SELECT id, name, location, capacity_percent, wait_minutes, type
       FROM queues
      ORDER BY capacity_percent DESC
      LIMIT 1'
)->fetch();

$quietest = dbQuery(
    'SELECT id, name, location, capacity_percent, wait_minutes, type
       FROM queues
      WHERE id <> ?
      ORDER BY capacity_percent ASC
      LIMIT 1',
    [(int) ($busiest['id'] ?? 0)]
)->fetch();

// "For You": the user's top vibe-match favorite (or, if none, the highest match overall),
// plus the queue with the shortest wait so they know where to grab a drink.
if (!empty($favoriteIds)) {
    $placeholders = implode(',', array_fill(0, count($favoriteIds), '?'));
    $forYouArtist = dbQuery(
        "SELECT id, slug, name, genre, image_url, vibe_match
           FROM artists
          WHERE id IN ($placeholders)
          ORDER BY vibe_match DESC
          LIMIT 1",
        $favoriteIds
    )->fetch();
} else {
    $forYouArtist = dbQuery(
        'SELECT id, slug, name, genre, image_url, vibe_match
           FROM artists
          ORDER BY vibe_match DESC
          LIMIT 1'
    )->fetch();
}

$shortestQueue = dbQuery(
    'SELECT name, location, wait_minutes, capacity_percent, icon, type
       FROM queues
      ORDER BY wait_minutes ASC, capacity_percent ASC
      LIMIT 1'
)->fetch();

// Notifications = number of saved performances that haven't started yet.
$upcomingCount = (int) dbQuery(
    'SELECT COUNT(*)
       FROM user_schedule us
       JOIN performances p ON p.id = us.performance_id
      WHERE us.user_id = ? AND p.start_time >= NOW()',
    [$userId]
)->fetchColumn();

$pageTitle = 'Home';

$bellHtml = '<a href="schedule.php" class="hover:bg-white/5 transition-colors p-2 rounded-full relative" aria-label="Upcoming sets">'
          . '<span class="material-symbols-outlined text-primary">notifications</span>'
          . ($upcomingCount > 0
                ? '<span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] rounded-full bg-tertiary text-on-primary font-display text-[11px] font-bold flex items-center justify-center px-1">' . $upcomingCount . '</span>'
                : '')
          . '</a>';
$topBarRight = $bellHtml;

require __DIR__ . '/includes/header.php';
?>
<main class="content-padding max-w-4xl mx-auto space-y-10"
      data-favorites='<?= htmlspecialchars(json_encode(array_map("strval", $favoriteIds)), ENT_QUOTES, "UTF-8") ?>'
      data-scheduled='<?= htmlspecialchars(json_encode(array_map("intval", $scheduledIds)), ENT_QUOTES, "UTF-8") ?>'>

    <!-- Now Playing Hero -->
    <section class="animate-on-scroll">
        <div class="glass-card rounded-2xl overflow-hidden relative min-h-[380px] flex flex-col justify-end p-6 md:p-8">
            <?php if ($nowPlaying && !empty($nowPlaying['image_url'])): ?>
                <img alt="<?= e($nowPlaying['artist_name']) ?>" class="absolute inset-0 w-full h-full object-cover opacity-50 mix-blend-screen" src="<?= e($nowPlaying['image_url']) ?>"/>
            <?php else: ?>
                <img alt="Festival Stage" class="absolute inset-0 w-full h-full object-cover opacity-50 mix-blend-screen" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDpyjyqGgcr6I-6nQzewlcwI9KRk9wfWkbgWsPMA4IavB-3s3xHqk4_qS3KW3Qo8Py4A3TsM0vk_XvCpB1SGOOq7MvKpls8-atPQ_VUZzOOPpurYuJXihaLs_E150MuIoLK_8Jv5oRInSkovIQe-r8nYcx22mLBmnp0jDIaSW7kzXgafyG8aH5FKQKIzjeqBt1WsnJT38-MRWTMGbwd8bkzAilT9r1YxKIQE8ovIdUXm1rNM_QRdKuqYqbngTiZ7tixjb5gCzwsBs4"/>
            <?php endif; ?>
            <div class="absolute inset-0 bg-gradient-to-t from-surface-container-lowest via-surface-container-lowest/80 to-transparent"></div>
            <div class="relative z-10 space-y-4">
                <div class="flex items-center gap-2">
                    <?php if ($nowPlaying): ?>
                    <span class="w-3 h-3 rounded-full bg-tertiary neon-glow-tertiary animate-pulse"></span>
                    <span class="font-body text-label-sm text-tertiary uppercase tracking-widest">
                        Live Now • <?= e($nowPlaying['stage_name']) ?>
                    </span>
                    <?php else: ?>
                    <span class="w-3 h-3 rounded-full bg-secondary"></span>
                    <span class="font-body text-label-sm text-secondary uppercase tracking-widest">Festival idle</span>
                    <?php endif; ?>
                </div>
                <div>
                    <h2 class="font-display text-display-xl text-on-surface mb-1">
                        <?= e($nowPlaying['artist_name'] ?? 'Stand by') ?>
                    </h2>
                    <p class="font-body text-body-lg text-on-surface-variant">
                        <?= e($nowPlaying['genre'] ?? 'Next set starts soon') ?>
                    </p>
                </div>
                <div class="flex gap-3 pt-2">
                    <a href="map.php" class="btn-primary">
                        <?= $nowPlaying ? 'Find the stage' : 'Open map' ?>
                    </a>
                    <a href="schedule.php" class="btn-secondary">
                        <span class="material-symbols-outlined text-[18px]">event_note</span>
                        Lineup
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Up Next -->
    <section class="animate-on-scroll">
        <div class="flex justify-between items-end mb-4">
            <h3 class="font-display text-headline-lg text-on-surface">Up Next</h3>
            <a class="font-body text-label-sm text-primary hover:text-[#f0dbff] transition-colors" href="schedule.php">View Schedule</a>
        </div>
        <div class="flex overflow-x-auto gap-4 pb-3 no-scrollbar snap-x">
            <?php if (empty($upNext)): ?>
                <div class="glass-card rounded-xl p-5 min-w-[280px] flex items-center justify-center text-on-surface-variant text-body-md">
                    No upcoming performances.
                </div>
            <?php else: foreach ($upNext as $i => $perf):
                $accentClasses = [
                    'bg-secondary-container/20 group-hover:bg-secondary-container/40',
                    'bg-primary-container/20 group-hover:bg-primary-container/40',
                    'bg-tertiary-container/20 group-hover:bg-tertiary-container/40',
                ];
                $accent = $accentClasses[$i % 3];
                $isFav  = in_array((int) $perf['artist_id'], $favoriteIds, true);
            ?>
            <div class="glass-card rounded-xl p-5 min-w-[280px] snap-start flex-shrink-0 relative overflow-hidden group cursor-pointer"
                 data-artist='<?= artistDataAttribute($perf) ?>'>
                <div class="absolute top-0 right-0 w-24 h-24 <?= $accent ?> rounded-bl-full blur-2xl transition-colors"></div>
                <div class="flex justify-between items-start mb-8">
                    <div class="bg-surface-container-highest text-on-surface-variant font-body text-label-sm px-3 py-1 rounded-full">
                        <?= e(formatTimeRange($perf['start_time'], $perf['end_time'])) ?>
                    </div>
                    <button class="favorite-btn <?= $isFav ? 'favorited text-primary' : 'text-outline' ?> hover:text-primary transition-colors"
                            data-artist-id="<?= (int) $perf['artist_id'] ?>">
                        <span class="material-symbols-outlined" <?= $isFav ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>
                            <?= $isFav ? 'favorite' : 'favorite_border' ?>
                        </span>
                    </button>
                </div>
                <h4 class="font-display text-headline-md text-on-surface"><?= e($perf['artist_name']) ?></h4>
                <p class="font-body text-body-md text-on-surface-variant mt-1"><?= e($perf['stage_name']) ?></p>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </section>

    <!-- Bento Grid: Heatmap + For You -->
    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 animate-on-scroll">
        <section class="md:col-span-5">
            <h3 class="font-display text-headline-lg text-on-surface mb-4">Crowd Heatmap</h3>
            <a href="queues.php" class="glass-card rounded-xl p-5 h-[220px] relative overflow-hidden flex flex-col justify-between block">
                <div class="absolute inset-0 bg-gradient-to-tr from-tertiary-container/10 via-primary-container/10 to-secondary-container/10"></div>

                <?php if ($busiest): ?>
                <div class="relative z-10 flex justify-between items-center bg-surface-container/60 backdrop-blur-md rounded-lg p-3 border border-white/5">
                    <span class="font-body text-label-sm text-on-surface"><?= e($busiest['name']) ?></span>
                    <span class="flex items-center gap-1 text-error">
                        <span class="material-symbols-outlined text-[16px]">local_fire_department</span>
                        <span class="font-body text-label-sm"><?= (int) $busiest['capacity_percent'] ?>% busy</span>
                    </span>
                </div>
                <?php endif; ?>

                <?php if ($quietest): ?>
                <div class="relative z-10 flex justify-between items-center bg-surface-container/60 backdrop-blur-md rounded-lg p-3 border border-white/5">
                    <span class="font-body text-label-sm text-on-surface"><?= e($quietest['name']) ?></span>
                    <span class="flex items-center gap-1 text-secondary">
                        <span class="material-symbols-outlined text-[16px]">eco</span>
                        <span class="font-body text-label-sm"><?= (int) $quietest['wait_minutes'] ?> min wait</span>
                    </span>
                </div>
                <?php endif; ?>
            </a>
        </section>

        <section class="md:col-span-7">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-primary">auto_awesome</span>
                <h3 class="font-display text-headline-lg text-on-surface">For You</h3>
            </div>
            <div class="space-y-3">

                <?php if ($forYouArtist): ?>
                <a href="artists.php" class="glass-card rounded-xl p-4 flex items-center gap-4 group cursor-pointer hover:bg-white/5 transition-colors no-underline">
                    <div class="w-14 h-14 rounded-lg overflow-hidden flex-shrink-0 bg-surface-container-highest flex items-center justify-center">
                        <?php if (!empty($forYouArtist['image_url'])): ?>
                            <img alt="<?= e($forYouArtist['name']) ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" src="<?= e($forYouArtist['image_url']) ?>"/>
                        <?php else: ?>
                            <span class="material-symbols-outlined text-primary text-2xl" style="font-variation-settings: 'FILL' 1;">graphic_eq</span>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow">
                        <h4 class="font-display text-[18px] text-on-surface"><?= e($forYouArtist['name']) ?></h4>
                        <p class="font-body text-[14px] text-on-surface-variant mt-0.5"><?= e($forYouArtist['genre']) ?> • <?= (int) $forYouArtist['vibe_match'] ?>% match</p>
                    </div>
                    <span class="material-symbols-outlined text-outline group-hover:text-primary transition-colors">arrow_forward</span>
                </a>
                <?php endif; ?>

                <?php if ($shortestQueue): ?>
                <a href="queues.php" class="glass-card rounded-xl p-4 flex items-center gap-4 group cursor-pointer hover:bg-white/5 transition-colors no-underline">
                    <div class="w-14 h-14 rounded-lg bg-surface-container-highest flex items-center justify-center flex-shrink-0 border border-white/5">
                        <span class="material-symbols-outlined text-secondary text-2xl"><?= e($shortestQueue['icon'] ?: 'eco') ?></span>
                    </div>
                    <div class="flex-grow">
                        <h4 class="font-display text-[18px] text-on-surface"><?= e($shortestQueue['name']) ?></h4>
                        <p class="font-body text-[14px] text-on-surface-variant mt-0.5">
                            <?= (int) $shortestQueue['wait_minutes'] ?> min wait • <?= e($shortestQueue['location']) ?>
                        </p>
                    </div>
                    <span class="material-symbols-outlined text-outline group-hover:text-primary transition-colors">arrow_forward</span>
                </a>
                <?php endif; ?>

            </div>
        </section>
    </div>
</main>

<?php require __DIR__ . '/includes/modal_artist.php'; ?>
<?php require __DIR__ . '/includes/nav.php'; ?>
