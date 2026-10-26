<?php
/**
 * Edit-profile modal — populated from the page's currentUser() data.
 *
 * Expects these PHP vars to be set by the caller before include:
 *   $editName, $editLocation, $editAvatar
 */
declare(strict_types=1);

$editName     = $editName     ?? '';
$editLocation = $editLocation ?? '';
$editAvatar   = $editAvatar   ?? '';
?>
<div id="edit-profile-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-handle"></div>
        <button class="modal-close-btn" data-modal-close aria-label="Close">
            <span class="material-symbols-outlined">close</span>
        </button>

        <h3 class="font-display text-headline-md text-on-surface mb-1">Edit profile</h3>
        <p class="font-body text-body-md text-on-surface-variant mb-5">Update your name, location and avatar.</p>

        <form id="edit-profile-form" class="space-y-4" novalidate>
            <label class="block">
                <span class="font-body text-label-sm text-on-surface-variant uppercase tracking-wider">Name</span>
                <input type="text" name="name" required maxlength="120"
                       value="<?= e($editName) ?>"
                       class="mt-2 w-full bg-surface-container-low border border-white/10 rounded-lg px-4 py-3 text-on-surface placeholder-on-surface-variant focus:outline-none focus:border-primary/60 transition-colors"/>
            </label>
            <label class="block">
                <span class="font-body text-label-sm text-on-surface-variant uppercase tracking-wider">Location</span>
                <input type="text" name="location" maxlength="120"
                       value="<?= e($editLocation) ?>"
                       placeholder="City, country"
                       class="mt-2 w-full bg-surface-container-low border border-white/10 rounded-lg px-4 py-3 text-on-surface placeholder-on-surface-variant focus:outline-none focus:border-primary/60 transition-colors"/>
            </label>
            <label class="block">
                <span class="font-body text-label-sm text-on-surface-variant uppercase tracking-wider">Avatar URL</span>
                <input type="url" name="avatar_url" maxlength="500"
                       value="<?= e($editAvatar) ?>"
                       placeholder="https://..."
                       class="mt-2 w-full bg-surface-container-low border border-white/10 rounded-lg px-4 py-3 text-on-surface placeholder-on-surface-variant focus:outline-none focus:border-primary/60 transition-colors"/>
            </label>

            <div class="flex gap-3 pt-2">
                <button type="button" class="btn-secondary flex-1 justify-center" data-modal-close>Cancel</button>
                <button type="submit" class="btn-primary flex-1 justify-center">
                    <span class="material-symbols-outlined text-[18px]">save</span>
                    Save
                </button>
            </div>
        </form>
    </div>
</div>
