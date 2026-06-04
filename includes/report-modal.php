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
        <button type="button" class="content-report-modal-close" id="content-report-modal-close" aria-label="<?php echo __e('report.close'); ?>">
            <i data-lucide="x" aria-hidden="true"></i>
        </button>
        <header class="content-report-modal-header">
            <h2 class="content-report-modal-title" id="content-report-modal-title"><?php echo __e('report.title'); ?></h2>
            <p class="content-report-modal-subtitle" id="content-report-modal-subtitle"></p>
        </header>
        <form class="content-report-modal-form" id="content-report-modal-form" novalidate>
            <div class="content-report-reason-wrap">
                <label class="content-report-reason-label" for="content-report-reason"><?php echo __e('report.reason'); ?></label>
                <select
                    class="content-report-reason-select"
                    id="content-report-reason"
                    name="content_report_reason"
                    required
                    aria-describedby="content-report-error content-report-success"
                >
                    <option value="" selected disabled><?php echo __e('report.choose_reason'); ?></option>
<?php foreach (contentReportReasonOptions() as $reasonOption) : ?>
                    <option value="<?php echo htmlspecialchars($reasonOption['code'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars(contentReportReasonLabel((string) $reasonOption['code']), ENT_QUOTES, 'UTF-8'); ?>
                    </option>
<?php endforeach; ?>
                </select>
            </div>
            <div class="content-report-details-wrap">
                <label class="content-report-details-label" for="content-report-details">
                    <?php echo __e('report.details_label'); ?> <span class="content-report-details-optional"><?php echo __e('report.optional'); ?></span>
                </label>
                <textarea
                    class="content-report-details"
                    id="content-report-details"
                    name="details"
                    rows="4"
                    maxlength="<?php echo CONTENT_REPORT_DETAILS_MAX_LENGTH; ?>"
                    placeholder="<?php echo __e('report.details_placeholder'); ?>"
                    aria-describedby="content-report-details-hint content-report-error content-report-success"
                ></textarea>
                <p class="content-report-details-hint" id="content-report-details-hint">
                    <?php echo __e('report.details_hint', ['max' => CONTENT_REPORT_DETAILS_MAX_LENGTH]); ?>
                </p>
            </div>
            <p class="content-report-error" id="content-report-error" hidden></p>
            <p class="content-report-success" id="content-report-success" hidden></p>
            <div class="content-report-actions">
                <button type="button" class="profile-edit-cancel content-report-cancel" id="content-report-cancel"><?php echo __e('report.cancel'); ?></button>
                <button type="submit" class="profile-edit-save content-report-submit" id="content-report-submit"><?php echo __e('report.submit'); ?></button>
            </div>
        </form>
    </div>
</div>
