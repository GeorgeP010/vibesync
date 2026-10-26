<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';

requireLogin();

$userId      = currentUserId();
$days        = fetchAllDays();
if (empty($days)) {
    $days = [['day_label' => 'Friday Mar 12'], ['day_label' => 'Saturday Mar 13'], ['day_label' => 'Sunday Mar 14']];
}

$selectedDay = $_GET['day'] ?? ($days[0]['day_label'] ?? '');
$savedItems  = fetchUserScheduledPerformances($userId, $selectedDay);
$fullLineup  = fetchPerformancesByDay($selectedDay);
$favoriteIds = fetchUserFavoriteIds($userId);
$scheduledIds = fetchUserScheduledPerformanceIds($userId);

$pageTitle  = 'Schedule';
$topBarLeft = '<a href="home.php" class="text-primary hover:bg-white/5 transition-colors p-2 rounded-full">'
            . '<span class="material-symbols-outlined">arrow_back</span></a>';
$topBarRight = '<a href="profile.php" class="hover:bg-white/5 transition-colors p-2 rounded-full">'
             . '<span class="material-symbols-outlined text-primary">account_circle</span></a>';
require __DIR__ . '/includes/header.php';
?>
<main class="content-padding max-w-3xl mx-auto space-y-8"
      data-favorites='<?= htmlspecialchars(json_encode(array_map("strval", $favoriteIds)), ENT_QUOTES, "UTF-8") ?>'
      data-scheduled='<?= htmlspecialchars(json_encode(array_map("intval", $scheduledIds)), ENT_QUOTES, "UTF-8") ?>'>

    <section class="flex flex-col items-center space-y-4 animate-on-scroll">
        <h1 class="font-display text-headline-lg text-on-surface text-center">Your Lineup</h1>

        <div class="glass-card rounded-full p-1 inline-flex" data-tab-group="schedule">
            <button class="tab-button tab-active" data-tab="my">My Schedule</button>
            <button class="tab-button" data-tab="full">Full Lineup</button>
        </div>

        <div class="day-selector flex gap-3 overflow-x-auto w-full pb-2 no-scrollbar snap-x">
            <?php foreach ($days as $day):
                $label   = (string) $day['day_label'];
                [$dow, $num] = formatDayBadge($label);
                $isActive = $label === $selectedDay;
            ?>
            <a href="?day=<?= urlencode($label) ?>"
               class="day-option <?= $isActive ? 'day-active' : '' ?>"
               data-day-label="<?= e($label) ?>">
                <span class="font-body text-label-sm uppercase opacity-70"><?= e($dow) ?></span>
                <span class="font-display text-headline-md"><?= e($num) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- My Schedule -->
    <section class="animate-on-scroll" data-tab-content="schedule" data-tab-id="my">
        <h2 class="font-display text-headline-md text-on-surface mb-4">Your Picks</h2>
        <div id="saved-schedule-list" class="space-y-3">
            <?php foreach ($savedItems as $item): ?>
            <div class="glass-card rounded-xl p-5 relative overflow-hidden cursor-pointer"
                 data-artist='<?= artistDataAttribute($item) ?>'
                 data-performance-id="<?= (int) $item['performance_id'] ?>">
                <div class="absolute top-0 left-0 w-1 h-full bg-primary"></div>
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-display text-headline-md text-on-surface"><?= e($item['artist_name']) ?></h3>
                        <div class="flex items-center text-secondary mt-1">
                            <span class="material-symbols-outlined text-[16px] mr-1" style="font-variation-settings: 'FILL' 1;">location_on</span>
                            <span class="font-body text-label-sm"><?= e($item['stage_name']) ?></span>
                        </div>
                    </div>
                    <button class="favorite-btn <?= in_array((int) $item['artist_id'], $favoriteIds, true) ? 'favorited text-primary' : 'text-primary' ?>"
                            data-artist-id="<?= (int) $item['artist_id'] ?>">
                        <span class="material-symbols-outlined" <?= in_array((int) $item['artist_id'], $favoriteIds, true) ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>
                            <?= in_array((int) $item['artist_id'], $favoriteIds, true) ? 'favorite' : 'favorite_border' ?>
                        </span>
                    </button>
                </div>
                <div class="flex items-center gap-2 mt-3">
                    <span class="bg-primary-container/20 text-primary border border-primary/30 px-2 py-1 rounded-full font-body text-label-sm"><?= e($item['genre']) ?></span>
                    <span class="font-body text-label-sm text-on-surface-variant"><?= e(formatTimeRange($item['start_time'], $item['end_time'])) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div id="schedule-empty-state" class="empty-state glass-card rounded-xl" <?= !empty($savedItems) ? 'style="display:none;"' : '' ?>>
            <span class="material-symbols-outlined">event_busy</span>
            <p class="font-body text-body-md mb-2">No artists scheduled yet</p>
            <p class="font-body text-label-sm mb-4">Browse the lineup and tap the heart or "Schedule" button to save artists.</p>
            <a href="artists.php" class="btn-primary inline-flex">Discover Artists</a>
        </div>
    </section>

    <!-- Full Lineup -->
    <section class="relative animate-on-scroll" data-tab-content="schedule" data-tab-id="full" style="display:none;">
        <div class="absolute left-10 top-0 bottom-0 w-px bg-white/10 z-0"></div>
        <div class="space-y-10 relative z-10">
            <?php if (empty($fullLineup)): ?>
                <p class="font-body text-body-md text-on-surface-variant ml-24">No performances on this day.</p>
            <?php else: foreach ($fullLineup as $perf):
                $isFav       = in_array((int) $perf['artist_id'], $favoriteIds, true);
                $isScheduled = in_array((int) $perf['performance_id'], $scheduledIds, true);
                $hasClash    = (int) $perf['has_clash'] === 1;
                $isLive      = strtotime($perf['start_time']) <= time() && strtotime($perf['end_time']) >= time();
                $startLabel  = date('H:i', strtotime($perf['start_time']));
            ?>
            <div class="relative flex items-start">
                <div class="w-20 shrink-0 font-body text-label-sm <?= $isLive ? 'text-tertiary animate-pulse' : 'text-on-surface-variant' ?> pt-4 text-right pr-4">
                    <?= $isLive ? 'LIVE' : e($startLabel) ?>
                </div>
                <div class="absolute left-10 -ml-1.5 mt-5 w-3 h-3 rounded-full bg-surface border-2 <?= $isLive ? 'border-tertiary neon-glow-tertiary' : ($hasClash ? 'border-error' : 'border-primary') ?> z-20"></div>
                <div class="flex-grow pl-3 pt-2 space-y-3">
                    <?php if ($hasClash): ?>
                    <div class="flex items-center text-error bg-error/10 px-3 py-1.5 rounded-full w-fit border border-error/20">
                        <span class="material-symbols-outlined text-[16px] mr-1">warning</span>
                        <span class="font-body text-label-sm">Schedule Clash</span>
                    </div>
                    <?php endif; ?>
                    <div class="glass-card rounded-xl p-5 relative overflow-hidden group cursor-pointer <?= $isLive ? 'bg-white/[0.03] border-tertiary/30 neon-glow-tertiary' : '' ?>"
                         data-artist='<?= artistDataAttribute($perf) ?>'
                         data-performance-id="<?= (int) $perf['performance_id'] ?>">
                        <div class="absolute top-0 left-0 w-1 h-full <?= $isLive ? 'bg-tertiary' : 'bg-secondary-container' ?>"></div>
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <?php if ($isLive): ?>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="w-2 h-2 rounded-full bg-tertiary animate-ping"></span>
                                    <span class="font-body text-label-sm text-tertiary uppercase tracking-wider">Now Playing</span>
                                </div>
                                <?php endif; ?>
                                <h3 class="font-display text-headline-md text-on-surface"><?= e($perf['artist_name']) ?></h3>
                                <div class="flex items-center text-secondary mt-1">
                                    <span class="material-symbols-outlined text-[16px] mr-1" style="font-variation-settings: 'FILL' 1;">location_on</span>
                                    <span class="font-body text-label-sm"><?= e($perf['stage_name']) ?></span>
                                </div>
                            </div>
                            <button class="favorite-btn <?= $isFav ? 'favorited text-primary' : 'text-primary hover:text-primary-container transition-colors' ?>"
                                    data-artist-id="<?= (int) $perf['artist_id'] ?>">
                                <span class="material-symbols-outlined" <?= $isFav ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>
                                    <?= $isFav ? 'favorite' : 'favorite_border' ?>
                                </span>
                            </button>
                        </div>
                        <div class="flex items-center gap-2 mt-3">
                            <span class="bg-secondary-container/20 text-secondary border border-secondary-container/50 px-2 py-1 rounded-full font-body text-label-sm"><?= e($perf['genre']) ?></span>
                            <span class="font-body text-label-sm text-on-surface-variant"><?= e(formatTimeRange($perf['start_time'], $perf['end_time'])) ?></span>
                            <button class="ml-auto schedule-toggle-btn font-body text-label-sm <?= $isScheduled ? 'text-primary' : 'text-on-surface-variant hover:text-primary' ?> transition-colors flex items-center gap-1"
                                    data-performance-id="<?= (int) $perf['performance_id'] ?>"
                                    data-artist-name="<?= e($perf['artist_name']) ?>">
                                <span class="material-symbols-outlined text-[16px]"><?= $isScheduled ? 'event_available' : 'calendar_add_on' ?></span>
                                <span class="schedule-label"><?= $isScheduled ? 'Scheduled' : 'Schedule' ?></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/modal_artist.php'; ?>
<?php require __DIR__ . '/includes/nav.php'; ?>
