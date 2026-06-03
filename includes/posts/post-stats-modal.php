<?php

declare(strict_types=1);
?>
<div class="post-stats-modal-overlay" id="post-stats-modal-overlay" hidden>
    <div
        class="post-stats-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="post-stats-modal-title"
    >
        <button type="button" class="post-stats-modal-close" id="post-stats-modal-close" aria-label="Close stats">
            <i data-lucide="x" aria-hidden="true"></i>
        </button>
        <h2 class="post-stats-modal-title" id="post-stats-modal-title">Stats</h2>
        <p class="post-stats-modal-subtitle" id="post-stats-modal-subtitle" hidden></p>
        <div class="post-stats-modal-body" id="post-stats-modal-body">
            <p class="post-stats-modal-status" id="post-stats-modal-status">Loading…</p>
        </div>
    </div>
</div>
