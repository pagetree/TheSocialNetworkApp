<?php

declare(strict_types=1);
?>
<div class="content-report-modal-overlay" id="content-report-modal-overlay" hidden>
    <div
        class="content-report-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="content-report-modal-title"
    >
        <button type="button" class="content-report-modal-close" id="content-report-modal-close" aria-label="Close report form">
            <i data-lucide="x" aria-hidden="true"></i>
        </button>
        <header class="content-report-modal-header">
            <h2 class="content-report-modal-title" id="content-report-modal-title">Report</h2>
            <p class="content-report-modal-subtitle" id="content-report-modal-subtitle"></p>
        </header>
        <form class="content-report-modal-form" id="content-report-modal-form" novalidate>
            <fieldset class="content-report-reasons">
                <legend class="content-report-sr-only">Reason for report</legend>
<?php foreach (contentReportReasonOptions() as $index => $reasonOption) : ?>
                <label class="content-report-reason">
                    <input
                        type="radio"
                        name="content_report_reason"
                        value="<?php echo htmlspecialchars($reasonOption['code'], ENT_QUOTES, 'UTF-8'); ?>"
                        <?php echo $index === 0 ? '' : ''; ?>
                    >
                    <span class="content-report-reason-label"><?php echo htmlspecialchars($reasonOption['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                </label>
<?php endforeach; ?>
            </fieldset>
            <div class="content-report-details-wrap">
                <label class="content-report-details-label" for="content-report-details">
                    Additional details <span class="content-report-details-optional">(optional)</span>
                </label>
                <textarea
                    class="content-report-details"
                    id="content-report-details"
                    name="details"
                    rows="4"
                    maxlength="<?php echo CONTENT_REPORT_DETAILS_MAX_LENGTH; ?>"
                    placeholder="Share any context that will help us review this report."
                    aria-describedby="content-report-details-hint content-report-error content-report-success"
                ></textarea>
                <p class="content-report-details-hint" id="content-report-details-hint">
                    Required when you choose “Something else”. Max <?php echo CONTENT_REPORT_DETAILS_MAX_LENGTH; ?> characters.
                </p>
            </div>
            <p class="content-report-error" id="content-report-error" hidden></p>
            <p class="content-report-success" id="content-report-success" hidden></p>
            <div class="content-report-actions">
                <button type="button" class="profile-edit-cancel content-report-cancel" id="content-report-cancel">Cancel</button>
                <button type="submit" class="profile-edit-save content-report-submit" id="content-report-submit">Submit report</button>
            </div>
        </form>
    </div>
</div>
