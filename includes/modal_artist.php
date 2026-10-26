<?php
/**
 * Reusable artist detail modal — included once per page that has artist cards.
 */
declare(strict_types=1);
?>
<div id="artist-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-handle"></div>
        <button class="modal-close-btn" data-modal-close aria-label="Close">
            <span class="material-symbols-outlined">close</span>
        </button>
        <div class="flex items-center gap-4 mb-4 mt-2">
            <div class="w-16 h-16 rounded-xl bg-surface-container-highest overflow-hidden">
                <img class="modal-artist-img w-full h-full object-cover" src="" alt="Artist"/>
            </div>
            <div class="flex-1">
                <h3 class="modal-artist-name font-display text-headline-md text-on-surface">Artist</h3>
                <p class="modal-artist-genre font-body text-body-md text-on-surface-variant">Genre</p>
            </div>
            <button class="modal-fav-btn favorite-btn text-outline" data-artist-id="">
                <span class="material-symbols-outlined text-[28px]">favorite_border</span>
            </button>
        </div>
        <div class="space-y-3 mb-6">
            <div class="flex items-center gap-3 text-on-surface-variant">
                <span class="material-symbols-outlined text-secondary">location_on</span>
                <span class="modal-artist-stage font-body text-body-md">Stage</span>
            </div>
            <div class="flex items-center gap-3 text-on-surface-variant">
                <span class="material-symbols-outlined text-primary">schedule</span>
                <span class="modal-artist-time font-body text-body-md">Time</span>
            </div>
        </div>
        <div class="flex gap-3">
            <button class="modal-schedule-btn btn-primary flex-1 justify-center" data-artist="">Add to Schedule</button>
            <a href="map.php" class="btn-secondary flex-1 justify-center">
                <span class="material-symbols-outlined text-[18px]">map</span>
                Navigate
            </a>
        </div>
    </div>
</div>
