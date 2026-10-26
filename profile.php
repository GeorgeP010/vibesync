<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';

requireLogin();

$user     = currentUser();
$settings = fetchUserSettings((int) $user['id']);
$genres   = dbQuery('SELECT id, genre_name FROM genres WHERE user_id = ? ORDER BY id ASC', [(int) $user['id']])->fetchAll();

// Real, computed numbers — no fake hard-coded counts.
$favoriteCount = (int) dbQuery('SELECT COUNT(*) FROM favorites    WHERE user_id = ?', [(int) $user['id']])->fetchColumn();
$scheduleCount = (int) dbQuery('SELECT COUNT(*) FROM user_schedule WHERE user_id = ?', [(int) $user['id']])->fetchColumn();
$friendCount   = (int) dbQuery(
    'SELECT COUNT(*) FROM friends WHERE user_id = ? AND status = ?',
    [(int) $user['id'], 'accepted']
)->fetchColumn();
$pendingFriendCount = (int) dbQuery(
    'SELECT COUNT(*) FROM friends WHERE friend_id = ? AND status = ?',
    [(int) $user['id'], 'pending']
)->fetchColumn();

$pageTitle  = 'Profile';
$topBarLeft = '<a href="home.php" class="text-primary hover:bg-white/5 transition-colors p-2 rounded-full">'
            . '<span class="material-symbols-outlined">arrow_back</span></a>';
$topBarRight = '<a href="logout.php" class="text-error hover:bg-white/5 transition-colors p-2 rounded-full" aria-label="Log out">'
             . '<span class="material-symbols-outlined">logout</span></a>';
require __DIR__ . '/includes/header.php';

$avatarUrl = $user['avatar_url'] ?: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDpTVwtWq_ezrNi9iyw_VYBwVcaebRtRYzcW1m1UHSm6PBVnrr9FeMgMp9x39sxmEuLe-zrJX5rcxDO_wSl-QikdhzI7KOyfkptXHplp-j1F97ixMddaJu95yfWHfNgCgLJjX6NJSu-CJXoK_D7Vm762hhCx55MgMm3FGS7mQp2IbnusyqI4FqygGmFLExZ1gT3uztsK96Mk-3FTkk5BdmW4fuK2KY3EfR8mrHVkr7TWW3LhZyxD-39B4_vL7b5xazceLLHIfvZwBE';

$genreClasses = [
    'bg-primary-container/20 text-primary border border-primary/30',
    'bg-secondary-container/20 text-secondary border border-secondary/30',
    'bg-tertiary-container/20 text-tertiary border border-tertiary/30',
];
?>
<main class="content-padding max-w-3xl mx-auto space-y-8">
    <!-- Profile header -->
    <section class="grid grid-cols-1 md:grid-cols-3 gap-4 animate-on-scroll">
        <div class="glass-card rounded-xl p-5 flex flex-col items-center text-center col-span-1 md:col-span-2 relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-tr from-primary-container/15 to-transparent opacity-50"></div>
            <div class="relative z-10 flex flex-col items-center w-full">
                <div class="w-24 h-24 rounded-full overflow-hidden border-2 border-primary neon-glow-primary mb-3">
                    <img id="profile-avatar" alt="User avatar" class="w-full h-full object-cover" src="<?= e($avatarUrl) ?>"/>
                </div>
                <h2 id="profile-name" class="font-display text-headline-lg text-on-surface mb-1"><?= e($user['name']) ?></h2>
                <p class="font-body text-body-md text-on-surface-variant flex items-center gap-1">
                    <span class="material-symbols-outlined text-primary text-[16px]">location_on</span>
                    <span id="profile-location"><?= e($user['location'] ?: 'Add your location') ?></span>
                </p>
                <div class="flex gap-3 mt-4 w-full max-w-xs">
                    <button id="edit-profile-btn" class="btn-primary flex-1 justify-center">
                        <span class="material-symbols-outlined text-[18px]">edit</span>
                        Edit Profile
                    </button>
                </div>
            </div>
        </div>

        <!-- Real stats -->
        <div class="grid grid-rows-3 gap-4 col-span-1">
            <a href="artists.php" class="glass-card rounded-xl p-4 flex items-center justify-between hover:bg-white/5 transition-colors">
                <div>
                    <p class="font-body text-label-sm text-on-surface-variant uppercase tracking-wider">Favorites</p>
                    <p class="font-display text-headline-md text-primary"><?= $favoriteCount ?></p>
                </div>
                <div class="w-10 h-10 rounded-full bg-primary-container/20 flex items-center justify-center">
                    <span class="material-symbols-outlined text-primary">favorite</span>
                </div>
            </a>
            <a href="schedule.php" class="glass-card rounded-xl p-4 flex items-center justify-between hover:bg-white/5 transition-colors">
                <div>
                    <p class="font-body text-label-sm text-on-surface-variant uppercase tracking-wider">In schedule</p>
                    <p class="font-display text-headline-md text-tertiary"><?= $scheduleCount ?></p>
                </div>
                <div class="w-10 h-10 rounded-full bg-tertiary-container/20 flex items-center justify-center">
                    <span class="material-symbols-outlined text-tertiary">event_note</span>
                </div>
            </a>
            <a href="friends.php" class="glass-card rounded-xl p-4 flex items-center justify-between hover:bg-white/5 transition-colors relative">
                <div>
                    <p class="font-body text-label-sm text-on-surface-variant uppercase tracking-wider">Friends</p>
                    <p class="font-display text-headline-md text-secondary"><?= $friendCount ?></p>
                </div>
                <div class="w-10 h-10 rounded-full bg-secondary-container/20 flex items-center justify-center relative">
                    <span class="material-symbols-outlined text-secondary">group</span>
                    <?php if ($pendingFriendCount > 0): ?>
                    <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] rounded-full bg-tertiary text-on-primary font-display text-[11px] font-bold flex items-center justify-center px-1">
                        <?= $pendingFriendCount ?>
                    </span>
                    <?php endif; ?>
                </div>
            </a>
        </div>
    </section>

    <!-- Vibe Preferences -->
    <section class="space-y-4 animate-on-scroll">
        <h3 class="font-display text-headline-md text-on-surface">Vibe Preferences</h3>
        <div class="glass-card rounded-xl p-5 space-y-5">
            <!-- Genres -->
            <div>
                <p class="font-body text-label-sm text-on-surface-variant mb-3 uppercase tracking-wider">Favorite Genres</p>
                <div id="genre-list" class="flex flex-wrap gap-2">
                    <?php foreach ($genres as $i => $g): ?>
                        <span class="genre-chip <?= e($genreClasses[$i % 3]) ?> px-3 py-1.5 rounded-full font-body text-label-sm flex items-center gap-1"
                              data-genre-id="<?= (int) $g['id'] ?>">
                            <span><?= e($g['genre_name']) ?></span>
                            <button type="button" class="genre-remove ml-1 -mr-1 hover:text-error transition-colors" aria-label="Remove">
                                <span class="material-symbols-outlined text-[16px]">close</span>
                            </button>
                        </span>
                    <?php endforeach; ?>
                </div>

                <form id="add-genre-form" class="mt-3 flex gap-2 items-center">
                    <input type="text" name="name" required maxlength="80"
                           placeholder="Add a genre (e.g. Trance)"
                           class="flex-1 bg-surface-container-low border border-white/10 rounded-full px-4 py-2 text-on-surface placeholder-on-surface-variant focus:outline-none focus:border-primary/60 transition-colors text-label-sm"/>
                    <button type="submit" class="btn-primary justify-center px-4 py-2">
                        <span class="material-symbols-outlined text-[16px]">add</span>
                        Add
                    </button>
                </form>
            </div>

            <hr class="border-white/10"/>

            <!-- Set Alerts -->
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-body text-body-lg text-on-surface">Set Alerts</p>
                    <p class="font-body text-label-sm text-on-surface-variant">Notify me before favorite artists start</p>
                </div>
                <input type="checkbox" class="toggle" data-setting="set_alerts" <?= ((int) $settings['set_alerts'] === 1) ? 'checked' : '' ?>/>
            </div>
            <hr class="border-white/10"/>
            <!-- Crowd Alerts -->
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-body text-body-lg text-on-surface">Crowd Alerts</p>
                    <p class="font-body text-label-sm text-on-surface-variant">Get notified about high-density areas</p>
                </div>
                <input type="checkbox" class="toggle" data-setting="crowd_alerts" <?= ((int) $settings['crowd_alerts'] === 1) ? 'checked' : '' ?>/>
            </div>
        </div>
    </section>

    <!-- Safety -->
    <section class="space-y-4 animate-on-scroll">
        <h3 class="font-display text-headline-md text-error">Safety &amp; Privacy</h3>
        <div class="glass-card rounded-xl p-5 space-y-5" style="border-color: rgba(255,180,171,0.15);">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-full bg-error/10 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-error" style="font-variation-settings: 'FILL' 1;">emergency</span>
                </div>
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-1">
                        <p class="font-body text-body-lg text-on-surface font-semibold">Safety Mode</p>
                        <input type="checkbox" class="toggle" data-setting="safety_mode" <?= ((int) $settings['safety_mode'] === 1) ? 'checked' : '' ?>/>
                    </div>
                    <p class="font-body text-label-sm text-on-surface-variant">Quick access to emergency contacts on lock screen.</p>
                </div>
            </div>
            <hr class="border-white/10"/>
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-full bg-secondary-container/20 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-secondary" style="font-variation-settings: 'FILL' 1;">my_location</span>
                </div>
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-1">
                        <p class="font-body text-body-lg text-on-surface font-semibold">Friend Tracker</p>
                        <input type="checkbox" class="toggle" data-setting="friend_tracker" <?= ((int) $settings['friend_tracker'] === 1) ? 'checked' : '' ?>/>
                    </div>
                    <p class="font-body text-label-sm text-on-surface-variant">Allow approved friends to see your location.</p>
                    <a href="friends.php" class="mt-3 bg-surface-container-low p-3 rounded-lg border border-white/5 flex items-center justify-between hover:bg-white/5 transition-colors">
                        <span class="font-body text-label-sm text-on-surface-variant flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px] text-secondary">group</span>
                            Approved Friends: <?= $friendCount ?>
                            <?php if ($pendingFriendCount > 0): ?>
                                <span class="font-body text-label-sm text-tertiary">· <?= $pendingFriendCount ?> pending</span>
                            <?php endif; ?>
                        </span>
                        <span class="font-body text-label-sm text-secondary flex items-center gap-1">
                            Manage
                            <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Sign out -->
    <section class="space-y-4 animate-on-scroll pb-8">
        <a href="logout.php" class="glass-card rounded-xl p-4 flex items-center justify-between hover:bg-white/5 transition-colors">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-error">logout</span>
                <span class="font-body text-body-md text-error">Log Out</span>
            </div>
            <span class="material-symbols-outlined text-on-surface-variant">chevron_right</span>
        </a>
    </section>
</main>

<?php
$editName     = (string) $user['name'];
$editLocation = (string) ($user['location'] ?? '');
$editAvatar   = (string) ($user['avatar_url'] ?? '');
require __DIR__ . '/includes/modal_edit_profile.php';
require __DIR__ . '/includes/nav.php';
?>
