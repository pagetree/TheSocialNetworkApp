<?php

declare(strict_types=1);

const CONTENT_REPORT_DETAILS_MAX_LENGTH = 500;

/**
 * @return list<array{code: string, label: string}>
 */
function contentReportReasonOptions(): array
{
    return [
        ['code' => 'spam', 'label' => 'Spam'],
        ['code' => 'harassment', 'label' => 'Harassment or bullying'],
        ['code' => 'hate', 'label' => 'Hate speech'],
        ['code' => 'misinformation', 'label' => 'Misinformation'],
        ['code' => 'nudity', 'label' => 'Nudity or sexual content'],
        ['code' => 'impersonation', 'label' => 'Impersonation'],
        ['code' => 'other', 'label' => 'Something else'],
    ];
}

function normalizeContentReportTargetType(string $targetType): ?string
{
    $targetType = strtolower(trim($targetType));

    return in_array($targetType, ['post', 'reply', 'user'], true) ? $targetType : null;
}

function normalizeContentReportReasonCode(string $reasonCode): ?string
{
    $reasonCode = strtolower(trim($reasonCode));
    foreach (contentReportReasonOptions() as $option) {
        if ($option['code'] === $reasonCode) {
            return $reasonCode;
        }
    }

    return null;
}

/**
 * @return array{ok: true, report_id: int}|array{ok: false, error: string, status: int}
 */
function submitContentReport(
    int $reporterUserId,
    string $targetType,
    int $targetId,
    string $reasonCode,
    ?string $details
): array {
    if ($reporterUserId < 1) {
        return ['ok' => false, 'error' => 'You must be signed in.', 'status' => 401];
    }

    $targetType = normalizeContentReportTargetType($targetType) ?? '';
    if ($targetType === '') {
        return ['ok' => false, 'error' => 'Invalid report target.', 'status' => 422];
    }

    if ($targetId < 1) {
        return ['ok' => false, 'error' => 'Invalid report target.', 'status' => 422];
    }

    $reasonCode = normalizeContentReportReasonCode($reasonCode) ?? '';
    if ($reasonCode === '') {
        return ['ok' => false, 'error' => 'Choose a reason for your report.', 'status' => 422];
    }

    $details = $details !== null ? trim($details) : '';
    if ($details !== '' && mb_strlen($details) > CONTENT_REPORT_DETAILS_MAX_LENGTH) {
        return [
            'ok' => false,
            'error' => 'Additional details must be ' . CONTENT_REPORT_DETAILS_MAX_LENGTH . ' characters or fewer.',
            'status' => 422,
        ];
    }

    if ($reasonCode === 'other' && $details === '') {
        return ['ok' => false, 'error' => 'Please describe the issue.', 'status' => 422];
    }

    $pdo = createPdoConnection();
    $ownerUserId = resolveContentReportTargetOwnerUserId($pdo, $targetType, $targetId);
    if ($ownerUserId === null) {
        return ['ok' => false, 'error' => 'This content is no longer available.', 'status' => 404];
    }

    if ($ownerUserId === $reporterUserId) {
        return ['ok' => false, 'error' => 'You cannot report your own content.', 'status' => 422];
    }

    $duplicateCheck = $pdo->prepare(
        'SELECT 1
         FROM content_reports
         WHERE reporter_user_id = :reporter_user_id
           AND target_type = :target_type
           AND target_id = :target_id
         LIMIT 1'
    );
    $duplicateCheck->execute([
        'reporter_user_id' => $reporterUserId,
        'target_type' => $targetType,
        'target_id' => $targetId,
    ]);

    if ($duplicateCheck->fetch() !== false) {
        return ['ok' => false, 'error' => 'You already submitted a report for this.', 'status' => 422];
    }

    $insert = $pdo->prepare(
        'INSERT INTO content_reports (
            reporter_user_id,
            target_type,
            target_id,
            reason_code,
            details
        ) VALUES (
            :reporter_user_id,
            :target_type,
            :target_id,
            :reason_code,
            :details
        )
        RETURNING id'
    );
    $insert->execute([
        'reporter_user_id' => $reporterUserId,
        'target_type' => $targetType,
        'target_id' => $targetId,
        'reason_code' => $reasonCode,
        'details' => $details !== '' ? $details : null,
    ]);

    $reportId = (int) $insert->fetchColumn();

    return ['ok' => true, 'report_id' => $reportId];
}

function resolveContentReportTargetOwnerUserId(PDO $pdo, string $targetType, int $targetId): ?int
{
    if ($targetType === 'post') {
        $stmt = $pdo->prepare(
            'SELECT user_id
             FROM posts
             WHERE id = :id
               AND is_deleted = FALSE
             LIMIT 1'
        );
        $stmt->execute(['id' => $targetId]);
        $userId = $stmt->fetchColumn();

        return $userId !== false ? (int) $userId : null;
    }

    if ($targetType === 'reply') {
        $stmt = $pdo->prepare(
            'SELECT user_id
             FROM post_replies
             WHERE id = :id
               AND is_deleted = FALSE
             LIMIT 1'
        );
        $stmt->execute(['id' => $targetId]);
        $userId = $stmt->fetchColumn();

        return $userId !== false ? (int) $userId : null;
    }

    $stmt = $pdo->prepare(
        'SELECT id
         FROM users
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => $targetId]);
    $userId = $stmt->fetchColumn();

    return $userId !== false ? (int) $userId : null;
}
