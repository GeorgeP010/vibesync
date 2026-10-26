<?php
/**
 * Stage detail modal — shown when the user clicks a stage marker on the map.
 * Populated client-side via api/stage-info.php.
 */
declare(strict_types=1);
?>
<div id="stage-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-handle"></div>
        <button class="modal-close-btn" data-modal-close aria-label="Close">
            <span class="material-symbols-outlined">close</span>
        </button>

        <div class="flex items-center gap-4 mb-4 mt-2">
            <div class="w-14 h-14 rounded-2xl bg-primary-container/20 border border-primary/40 flex items-center justify-center neon-glow-primary">
                <span class="stage-modal-icon material-symbols-outlined text-primary text-[28px]" style="font-variation-settings: 'FILL' 1;">graphic_eq</span>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="stage-modal-name font-display text-headline-md text-on-surface truncate">Stage</h3>
                <p class="stage-modal-desc font-body text-body-md text-on-surface-variant truncate">&nbsp;</p>
            </div>
        </div>

        <!-- Now playing -->
        <div class="stage-modal-now mb-5" hidden>
            <p class="font-body text-label-sm text-tertiary uppercase tracking-widest mb-2 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-tertiary animate-ping"></span>
                Now Playing
            </p>
            <div class="glass-card rounded-xl p-4 border-tertiary/30 cursor-pointer stage-modal-now-card" data-artist="">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-lg bg-surface-container-highest overflow-hidden flex items-center justify-center">
                        <img class="stage-modal-now-img w-full h-full object-cover" src="" alt=""/>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="stage-modal-now-name font-display text-[18px] text-on-surface truncate">Artist</h4>
                        <p class="stage-modal-now-meta font-body text-label-sm text-on-surface-variant truncate">Genre • 21:00 - 22:30</p>
                    </div>
                    <span class="material-symbols-outlined text-tertiary">chevron_right</span>
                </div>
            </div>
        </div>

        <!-- Up next on this stage -->
        <div class="stage-modal-upcoming mb-5">
            <p class="font-body text-label-sm text-on-surface-variant uppercase tracking-widest mb-2">Up Next On Stage</p>
            <div class="stage-modal-upcoming-list space-y-2"></div>
            <p class="stage-modal-empty font-body text-body-md text-on-surface-variant" hidden>No upcoming sets scheduled on this stage.</p>
        </div>

        <a class="stage-modal-schedule-link btn-secondary w-full justify-center" href="schedule.php">
            <span class="material-symbols-outlined text-[18px]">event_note</span>
            View full schedule
        </a>
    </div>
</div>
