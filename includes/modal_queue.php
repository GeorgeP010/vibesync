<?php
/**
 * Queue detail modal — clicking a queue card opens this with full info.
 * Populated client-side from the card's data attributes.
 */
declare(strict_types=1);
?>
<div id="queue-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-handle"></div>
        <button class="modal-close-btn" data-modal-close aria-label="Close">
            <span class="material-symbols-outlined">close</span>
        </button>

        <div class="flex items-center gap-4 mb-5 mt-2">
            <div class="w-14 h-14 rounded-2xl bg-surface-container flex items-center justify-center border border-white/10">
                <span class="queue-modal-icon material-symbols-outlined text-primary text-[28px]">local_bar</span>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="queue-modal-name font-display text-headline-md text-on-surface truncate">Queue</h3>
                <p class="queue-modal-location font-body text-label-sm text-on-surface-variant uppercase tracking-wider truncate">Location</p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 mb-5">
            <div class="glass-card rounded-xl p-4">
                <p class="font-body text-label-sm text-on-surface-variant uppercase tracking-wider">Wait</p>
                <p class="queue-modal-wait font-display text-headline-md text-primary mt-1">0 min</p>
            </div>
            <div class="glass-card rounded-xl p-4">
                <p class="font-body text-label-sm text-on-surface-variant uppercase tracking-wider">Capacity</p>
                <p class="queue-modal-capacity font-display text-headline-md text-secondary mt-1">0%</p>
            </div>
        </div>

        <div class="mb-5">
            <p class="font-body text-label-sm text-on-surface-variant mb-2">Crowd level</p>
            <div class="progress-bar">
                <div class="queue-modal-bar progress-bar-fill bg-primary" style="width: 0%"></div>
            </div>
        </div>

        <p class="queue-modal-updated font-body text-label-sm text-on-surface-variant text-center">Last updated just now</p>
    </div>
</div>
