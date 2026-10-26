<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';

requireLogin();

$queues = fetchQueues();

$pageTitle  = 'Live Queues';
$bodyClass  = 'bg-background text-on-background bg-radial-alt min-h-screen font-body text-body-md overflow-x-hidden';
$topBarLeft = '<a href="home.php" class="text-primary hover:bg-white/5 transition-colors p-2 rounded-full">'
            . '<span class="material-symbols-outlined">arrow_back</span></a>';
$topBarRight = '<a href="profile.php" class="hover:bg-white/5 transition-colors p-2 rounded-full">'
             . '<span class="material-symbols-outlined text-primary">account_circle</span></a>';
require __DIR__ . '/includes/header.php';

/**
 * Queue colour theme keyed off the type so the cards match the original look.
 *
 * @return array{accent:string, badge:string, bar:string, text:string}
 */
function queueTheme(string $type, int $waitMinutes): array
{
    return match ($type) {
        'bar' => [
            'accent' => 'from-tertiary/10',
            'badge'  => 'text-tertiary bg-tertiary-container/20 neon-glow-tertiary',
            'bar'    => 'bg-tertiary neon-glow-tertiary',
            'text'   => 'text-tertiary',
        ],
        'toilet', 'water' => [
            'accent' => 'from-secondary/10',
            'badge'  => 'text-secondary bg-secondary-container/20 ' . ($waitMinutes <= 5 ? 'neon-glow-secondary' : ''),
            'bar'    => 'bg-secondary ' . ($waitMinutes <= 5 ? 'neon-glow-secondary' : ''),
            'text'   => 'text-secondary',
        ],
        'food' => [
            'accent' => 'from-primary/10',
            'badge'  => 'text-primary bg-primary-container/20 neon-glow-primary',
            'bar'    => 'bg-primary neon-glow-primary',
            'text'   => 'text-primary',
        ],
        'medical' => [
            'accent' => 'from-error/5',
            'badge'  => 'text-on-surface bg-surface-container-highest/50',
            'bar'    => 'bg-on-surface',
            'text'   => 'text-on-surface',
        ],
        default => [
            'accent' => 'from-surface-container-highest/30',
            'badge'  => 'text-on-surface bg-surface-container-highest/50',
            'bar'    => 'bg-on-surface',
            'text'   => 'text-on-surface',
        ],
    };
}
?>
<main class="content-padding max-w-4xl mx-auto space-y-8">

    <section class="space-y-3 animate-on-scroll">
        <h2 class="font-display text-headline-lg text-on-surface">Live Queues</h2>
        <p class="text-on-surface-variant font-body text-body-md">Real-time wait times across the festival grounds.</p>

        <div class="chip-group flex gap-2 overflow-x-auto pb-2 no-scrollbar" data-filter-target=".queue-card">
            <button class="chip chip-active whitespace-nowrap neon-glow-primary">All Areas</button>
            <button class="chip chip-inactive whitespace-nowrap">Food &amp; Drink</button>
            <button class="chip chip-inactive whitespace-nowrap">Restrooms</button>
            <button class="chip chip-inactive whitespace-nowrap">Merch Tents</button>
        </div>
    </section>

    <section class="grid grid-cols-1 md:grid-cols-2 gap-4 animate-on-scroll">
        <?php foreach ($queues as $q):
            $theme = queueTheme((string) $q['type'], (int) $q['wait_minutes']);
            $tagMap = [
                'bar'     => 'food & drink food drink',
                'food'    => 'food & drink food drink',
                'water'   => 'food & drink food drink',
                'toilet'  => 'restrooms',
                'merch'   => 'merch tents',
                'medical' => '',
            ];
            $tag = $tagMap[$q['type']] ?? '';
        ?>
        <button type="button"
             class="queue-card glass-card rounded-xl p-5 flex flex-col justify-between relative overflow-hidden cursor-pointer hover:bg-white/5 transition-colors text-left w-full"
             data-tags="<?= e($tag) ?>"
             data-queue-id="<?= (int) $q['id'] ?>"
             data-queue-name="<?= e($q['name']) ?>"
             data-queue-location="<?= e($q['location']) ?>"
             data-queue-icon="<?= e($q['icon']) ?>"
             data-queue-wait="<?= (int) $q['wait_minutes'] ?>"
             data-queue-capacity="<?= (int) $q['capacity_percent'] ?>"
             data-queue-updated="<?= e($q['updated_at']) ?>">
            <div class="absolute inset-0 bg-gradient-to-br <?= e($theme['accent']) ?> to-transparent opacity-50"></div>
            <div class="relative z-10 flex justify-between items-start mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center">
                        <span class="material-symbols-outlined <?= e($theme['text']) ?>"><?= e($q['icon']) ?></span>
                    </div>
                    <div>
                        <h3 class="font-display text-headline-md text-on-surface text-[18px]"><?= e($q['name']) ?></h3>
                        <p class="font-body text-label-sm text-on-surface-variant uppercase"><?= e($q['location']) ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-1 <?= e($theme['badge']) ?> px-3 py-1 rounded-full">
                    <span class="material-symbols-outlined text-sm">schedule</span>
                    <span class="font-body text-label-sm"><?= (int) $q['wait_minutes'] ?> min</span>
                </div>
            </div>
            <div class="relative z-10 space-y-2">
                <div class="flex justify-between text-label-sm font-body">
                    <span class="text-on-surface-variant">Capacity</span>
                    <span class="<?= e($theme['text']) ?>"><?= (int) $q['capacity_percent'] ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-bar-fill <?= e($theme['bar']) ?>" style="width: <?= (int) $q['capacity_percent'] ?>%"></div>
                </div>
            </div>
        </button>
        <?php endforeach; ?>
    </section>
</main>

<?php require __DIR__ . '/includes/modal_queue.php'; ?>
<?php require __DIR__ . '/includes/nav.php'; ?>
