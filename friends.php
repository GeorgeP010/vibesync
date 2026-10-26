<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';

requireLogin();

$userId = currentUserId();

// Accepted friends (both rows exist; pick mine).
$friends = dbQuery(
    'SELECT u.id, u.name, u.email, u.avatar_url, u.location
       FROM friends f
       JOIN users u ON u.id = f.friend_id
      WHERE f.user_id = ? AND f.status = ?
      ORDER BY u.name ASC',
    [$userId, 'accepted']
)->fetchAll();

// Incoming pending — others invited me.
$incoming = dbQuery(
    'SELECT u.id, u.name, u.email, u.avatar_url
       FROM friends f
       JOIN users u ON u.id = f.user_id
      WHERE f.friend_id = ? AND f.status = ?
      ORDER BY u.name ASC',
    [$userId, 'pending']
)->fetchAll();

// Outgoing pending — I invited others, awaiting reply.
$outgoing = dbQuery(
    'SELECT u.id, u.name, u.email, u.avatar_url
       FROM friends f
       JOIN users u ON u.id = f.friend_id
      WHERE f.user_id = ? AND f.status = ?
      ORDER BY u.name ASC',
    [$userId, 'pending']
)->fetchAll();

$pageTitle  = 'Friends';
$topBarLeft = '<a href="profile.php" class="text-primary hover:bg-white/5 transition-colors p-2 rounded-full">'
            . '<span class="material-symbols-outlined">arrow_back</span></a>';
$topBarTitle = 'Friends';
$topBarRight = '<span class="font-display text-headline-md text-primary px-2">' . count($friends) . '</span>';
require __DIR__ . '/includes/header.php';

$avatarFallback = static function (?string $url): string {
    return $url && $url !== '' ? $url : '';
};
?>
<main class="content-padding max-w-3xl mx-auto space-y-8">

    <!-- FIND FRIENDS -->
    <section class="space-y-4 animate-on-scroll">
        <h2 class="font-display text-headline-lg text-on-surface">Find friends</h2>
        <p class="font-body text-body-md text-on-surface-variant">Search by name or email to send a friend request.</p>

        <div class="glass-panel rounded-full flex items-center px-4 py-3">
            <span class="material-symbols-outlined text-outline mr-3">search</span>
            <input id="friend-search" type="text" autocomplete="off"
                   placeholder="Search people…"
                   class="bg-transparent border-none outline-none w-full text-on-surface placeholder-on-surface-variant"/>
            <span id="friend-search-spinner" class="material-symbols-outlined text-outline animate-spin" hidden>progress_activity</span>
        </div>

        <div id="friend-search-results" class="space-y-2"></div>
        <p id="friend-search-empty" class="font-body text-label-sm text-on-surface-variant" hidden>
            No matches. Try a different name or email.
        </p>
    </section>

    <!-- PENDING -->
    <?php if (!empty($incoming) || !empty($outgoing)): ?>
    <section class="space-y-4 animate-on-scroll">
        <h3 class="font-display text-headline-md text-on-surface">Pending</h3>

        <?php foreach ($incoming as $u): ?>
        <div class="glass-card rounded-xl p-4 flex items-center gap-3 friend-row friend-incoming"
             data-user-id="<?= (int) $u['id'] ?>"
             data-user-name="<?= e($u['name']) ?>">
            <div class="w-12 h-12 rounded-full overflow-hidden bg-surface-container-highest border border-white/10 flex-shrink-0">
                <?php if (!empty($u['avatar_url'])): ?>
                    <img alt="" class="w-full h-full object-cover" src="<?= e($u['avatar_url']) ?>"/>
                <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center text-on-surface-variant">
                        <span class="material-symbols-outlined">person</span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-display text-[16px] text-on-surface truncate"><?= e($u['name']) ?></p>
                <p class="font-body text-label-sm text-on-surface-variant truncate flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px] text-tertiary">call_received</span>
                    Sent you a request
                </p>
            </div>
            <button type="button" class="friend-accept-btn btn-primary px-3 py-1.5 text-label-sm" aria-label="Accept">
                <span class="material-symbols-outlined text-[16px]">check</span>
            </button>
            <button type="button" class="friend-decline-btn btn-secondary px-3 py-1.5 text-label-sm" aria-label="Decline">
                <span class="material-symbols-outlined text-[16px]">close</span>
            </button>
        </div>
        <?php endforeach; ?>

        <?php foreach ($outgoing as $u): ?>
        <div class="glass-card rounded-xl p-4 flex items-center gap-3 opacity-70 friend-row friend-outgoing"
             data-user-id="<?= (int) $u['id'] ?>"
             data-user-name="<?= e($u['name']) ?>">
            <div class="w-12 h-12 rounded-full overflow-hidden bg-surface-container-highest border border-white/10 flex-shrink-0">
                <?php if (!empty($u['avatar_url'])): ?>
                    <img alt="" class="w-full h-full object-cover" src="<?= e($u['avatar_url']) ?>"/>
                <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center text-on-surface-variant">
                        <span class="material-symbols-outlined">person</span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-display text-[16px] text-on-surface truncate"><?= e($u['name']) ?></p>
                <p class="font-body text-label-sm text-on-surface-variant truncate flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">schedule</span>
                    Awaiting reply
                </p>
            </div>
            <button type="button" class="friend-cancel-btn font-body text-label-sm text-on-surface-variant hover:text-error transition-colors px-3 py-1.5">
                Cancel
            </button>
        </div>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <!-- YOUR FRIENDS -->
    <section class="space-y-4 animate-on-scroll">
        <h3 class="font-display text-headline-md text-on-surface">
            Your festival crew
            <span class="font-body text-label-sm text-on-surface-variant ml-2">(<?= count($friends) ?>)</span>
        </h3>

        <?php if (empty($friends)): ?>
        <div class="empty-state glass-card rounded-xl">
            <span class="material-symbols-outlined">group_off</span>
            <p class="font-body text-body-md mb-1">No friends yet</p>
            <p class="font-body text-label-sm text-on-surface-variant">Use the search above to add people.</p>
        </div>
        <?php else: ?>
        <div id="friend-list" class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <?php foreach ($friends as $u): ?>
            <div class="friend-row glass-card rounded-xl p-4 flex items-center gap-3"
                 data-user-id="<?= (int) $u['id'] ?>"
                 data-user-name="<?= e($u['name']) ?>">
                <div class="w-12 h-12 rounded-full overflow-hidden border-2 border-secondary/40 flex-shrink-0 bg-surface-container-highest">
                    <?php if (!empty($u['avatar_url'])): ?>
                        <img alt="" class="w-full h-full object-cover" src="<?= e($u['avatar_url']) ?>"/>
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center text-on-surface-variant">
                            <span class="material-symbols-outlined">person</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-display text-[16px] text-on-surface truncate"><?= e($u['name']) ?></p>
                    <p class="font-body text-label-sm text-on-surface-variant truncate">
                        <?= e($u['location'] ?: $u['email']) ?>
                    </p>
                </div>
                <button type="button" class="friend-remove-btn text-on-surface-variant hover:text-error transition-colors p-2 rounded-full" aria-label="Remove">
                    <span class="material-symbols-outlined">person_remove</span>
                </button>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
</main>

<?php require __DIR__ . '/includes/nav.php'; ?>
