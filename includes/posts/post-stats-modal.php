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
        <header class="post-stats-modal-header">
            <h2 class="post-stats-modal-title" id="post-stats-modal-title">Stats</h2>
            <p class="post-stats-modal-subtitle" id="post-stats-modal-subtitle" hidden></p>
        </header>
        <div class="post-stats-modal-body" id="post-stats-modal-body">
            <div class="post-stats-modal-content" id="post-stats-modal-content" hidden>
                <div class="post-stats-hero-card" id="post-stats-modal-hero"></div>
                <section class="post-stats-insights" id="post-stats-modal-insights" hidden>
                    <header class="post-stats-insights-header">
                        <h3 class="post-stats-insights-title">Audience insights</h3>
                        <p class="post-stats-insights-lead">Reach, clicks, and momentum behind this content.</p>
                    </header>
                    <div class="post-stats-insights-grid" id="post-stats-modal-insights-grid"></div>
                </section>
            </div>
            <p class="post-stats-modal-status" id="post-stats-modal-status">Loading…</p>
        </div>
    </div>
</div>
