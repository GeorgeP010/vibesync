<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';

requireLogin();

$pageTitle  = 'Festival Map';
$bodyClass  = 'text-on-background font-body text-body-md h-screen w-screen overflow-hidden flex flex-col relative';
$topBarLeft = '<a href="home.php" class="text-primary hover:bg-white/5 transition-colors p-2 rounded-full">'
            . '<span class="material-symbols-outlined">arrow_back</span></a>';
$topBarTitle = 'Festival Map';
$topBarRight = '<a href="profile.php" class="hover:bg-white/5 transition-colors p-2 rounded-full">'
             . '<span class="material-symbols-outlined text-primary">account_circle</span></a>';

require __DIR__ . '/includes/header.php';

$stages = dbQuery('SELECT * FROM stages ORDER BY id ASC')->fetchAll();
$queues = dbQuery('SELECT * FROM queues ORDER BY id ASC')->fetchAll();

/**
 * Pseudo-stable position for queue markers (we don't have map_x/map_y for queues).
 * Returns [x%, y%] so the same queue always lands at the same spot.
 */
$facilityPos = static function (int $id): array {
    $positions = [
        [62, 65], [76, 78], [60, 35], [82, 40], [38, 78], [18, 75],
    ];
    return $positions[($id - 1) % count($positions)] ?? [50, 50];
};

$facilityType = static function (string $type): string {
    return match ($type) {
        'bar', 'food', 'water' => 'food',
        'toilet'               => 'facility',
        'merch'                => 'facility',
        'medical'              => 'facility',
        default                => 'facility',
    };
};
?>
<main class="flex-grow relative h-full w-full pt-16 pb-20 overflow-hidden">
    <div class="absolute inset-0 z-0 bg-surface-container-lowest">
        <img alt="Festival Map" class="w-full h-full object-cover opacity-50 mix-blend-luminosity" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBcr3fX7qSxiNhH2tZHOAqFW5f86P2updErbTG2Ppi-ddPG9Qop5scun31QeB9RR-S8zKPUYScgUquW5HtLerqVytwiJ4RjTFBO_JQUkk-7CwXWQL6VF802n4p3MHiV_8AtroZ2WCPX7KNXJtwvuUWzCrlhflvqnAmJnFa_Xmi1A214NdVRTuhWawRXwonVJsp7v_Wn4agiO-17Hd15IKNGTqlJVLUnv_m_P5-HjR80CA_zt9X6shsI53bUuXVEjRujjaHoeG1jjNw"/>
        <div class="absolute inset-0 bg-gradient-to-tr from-tertiary-container/15 via-primary-container/10 to-secondary-container/15 mix-blend-screen pointer-events-none"></div>
    </div>

    <!-- Search + filters -->
    <div class="absolute top-20 left-0 right-0 px-5 z-20 pointer-events-none">
        <div class="flex gap-2 pointer-events-auto">
            <div class="glass-panel rounded-full flex-grow flex items-center px-4 py-3 shadow-[0_4px_20px_rgba(0,0,0,0.5)]">
                <span class="material-symbols-outlined text-outline mr-3">search</span>
                <input data-search-input=".map-marker-label"
                       class="bg-transparent border-none focus:ring-0 text-body-md text-on-surface placeholder-on-surface-variant w-full p-0 outline-none"
                       placeholder="Search stages or facilities..." type="text"/>
            </div>
        </div>
        <div class="chip-group flex gap-2 mt-3 overflow-x-auto no-scrollbar pb-2 pointer-events-auto"
             data-filter-target=".map-marker-label">
            <button class="chip chip-active whitespace-nowrap">All</button>
            <button class="chip chip-inactive whitespace-nowrap">Stages</button>
            <button class="chip chip-inactive whitespace-nowrap">Food</button>
            <button class="chip chip-inactive whitespace-nowrap">Facility</button>
        </div>
    </div>

    <!-- Markers -->
    <div class="absolute inset-0 z-10 w-full h-full">
        <?php foreach ($stages as $stage):
            $x = (float) ($stage['map_x'] ?? 50);
            $y = (float) ($stage['map_y'] ?? 50);
        ?>
        <button class="map-marker-label absolute flex flex-col items-center -translate-x-1/2 -translate-y-1/2 cursor-pointer group bg-transparent border-none"
                style="top: <?= e((string) $y) ?>%; left: <?= e((string) $x) ?>%;"
                data-tags="stages stage"
                data-stage-id="<?= (int) $stage['id'] ?>"
                aria-label="<?= e($stage['name']) ?>">
            <div class="w-12 h-12 bg-primary/20 backdrop-blur-md border border-primary/50 rounded-full flex items-center justify-center neon-glow-primary mb-1 group-hover:scale-110 transition-transform">
                <span class="material-symbols-outlined text-primary text-xl" style="font-variation-settings: 'FILL' 1;"><?= e($stage['icon'] ?: 'graphic_eq') ?></span>
            </div>
            <span class="font-body text-label-sm text-on-surface bg-surface-container/80 px-2 py-1 rounded-full border border-white/5 backdrop-blur-sm"><?= e($stage['name']) ?></span>
        </button>
        <?php endforeach; ?>

        <?php foreach ($queues as $q):
            [$x, $y]   = $facilityPos((int) $q['id']);
            $tagBucket = $facilityType((string) $q['type']);
            $isFood    = $tagBucket === 'food';
        ?>
        <button class="map-marker-label absolute flex flex-col items-center -translate-x-1/2 -translate-y-1/2 cursor-pointer group bg-transparent border-none"
                style="top: <?= e((string) $y) ?>%; left: <?= e((string) $x) ?>%;"
                data-tags="<?= e($tagBucket) ?> <?= e(strtolower((string) $q['name'])) ?>"
                data-queue-id="<?= (int) $q['id'] ?>"
                data-queue-name="<?= e($q['name']) ?>"
                data-queue-location="<?= e($q['location']) ?>"
                data-queue-icon="<?= e($q['icon']) ?>"
                data-queue-wait="<?= (int) $q['wait_minutes'] ?>"
                data-queue-capacity="<?= (int) $q['capacity_percent'] ?>"
                data-queue-updated="<?= e($q['updated_at']) ?>"
                aria-label="<?= e($q['name']) ?>">
            <div class="w-10 h-10 <?= $isFood ? 'bg-secondary-container/20 border-secondary/40' : 'bg-surface-container-highest/60 border-outline-variant/50' ?> backdrop-blur-md border rounded-full flex items-center justify-center mb-1 group-hover:scale-110 transition-transform">
                <span class="material-symbols-outlined <?= $isFood ? 'text-secondary' : 'text-on-surface-variant' ?> text-lg" style="font-variation-settings: 'FILL' 1;"><?= e($q['icon']) ?></span>
            </div>
            <span class="font-body text-label-sm text-on-surface bg-surface-container/80 px-2 py-1 rounded-full border border-white/5 backdrop-blur-sm">
                <?= e($q['name']) ?>
                <span class="text-on-surface-variant">· <?= (int) $q['wait_minutes'] ?>m</span>
            </span>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- Legend -->
    <div class="absolute bottom-24 left-5 z-20 glass-panel rounded-2xl px-4 py-3 max-w-[180px]">
        <p class="font-body text-label-sm text-on-surface-variant uppercase tracking-widest mb-2">Legend</p>
        <div class="space-y-2 text-on-surface text-label-sm font-body">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-primary neon-glow-primary"></span> Stages
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-secondary"></span> Food &amp; drink
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-on-surface-variant"></span> Facilities
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/includes/modal_stage.php'; ?>
<?php require __DIR__ . '/includes/modal_queue.php'; ?>
<?php require __DIR__ . '/includes/modal_artist.php'; ?>
<?php require __DIR__ . '/includes/nav.php'; ?>
