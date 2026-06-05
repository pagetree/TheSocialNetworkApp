<?php

declare(strict_types=1);

const MENTION_HANDLE_MAX_LENGTH = 50;
const MENTION_MAX_PER_CONTENT = 10;

/** @ handle slug: same charset as username, without the leading @. */
const MENTION_HANDLE_REGEX = '[a-z0-9_]{1,' . MENTION_HANDLE_MAX_LENGTH . '}';

function normalizeMentionHandle(string $raw): string
{
    $raw = strtolower(rtrim(ltrim(trim($raw), '@'), '.,!?;:'));
    if ($raw === '') {
        return '';
    }

    $username = normalizeUsername($raw);
    if ($username === '' || strlen($username) > MENTION_HANDLE_MAX_LENGTH) {
        return '';
    }

    return '@' . $username;
}

/**
 * @return list<string>
 */
function extractMentionHandles(string $body): array
{
    if ($body === '') {
        return [];
    }

    $pattern = '/(?<![a-z0-9_])@(' . MENTION_HANDLE_REGEX . ')(?![a-z0-9_])/i';
    if (!preg_match_all($pattern, $body, $matches)) {
        return [];
    }

    $handles = [];
    foreach ($matches[1] as $raw) {
        $handle = normalizeMentionHandle((string) $raw);
        if ($handle === '' || isset($handles[$handle])) {
            continue;
        }

        $handles[$handle] = true;
        if (count($handles) >= MENTION_MAX_PER_CONTENT) {
            break;
        }
    }

    return array_keys($handles);
}

/**
 * @param list<string> $handles
 * @return array<string, array<string, mixed>>
 */
function fetchUsersByMentionHandles(array $handles): array
{
    $handles = array_values(array_unique(array_filter(array_map(
        static fn (string $handle): string => normalizeMentionHandle($handle),
        $handles
    ), static fn (string $handle): bool => $handle !== '')));

    if ($handles === []) {
        return [];
    }

    $placeholders = implode(', ', array_fill(0, count($handles), '?'));
    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT id, username, display_name, handle, avatar_url
         FROM users
         WHERE handle IN (' . $placeholders . ')'
    );
    $stmt->execute($handles);

    $usersByHandle = [];
    while ($row = $stmt->fetch()) {
        $handle = normalizeMentionHandle((string) ($row['handle'] ?? ''));
        if ($handle === '') {
            continue;
        }

        $usersByHandle[$handle] = $row;
    }

    return $usersByHandle;
}

function notifyPostMentions(int $postId, int $actorUserId, ?string $body): void
{
    if ($postId < 1 || $actorUserId < 1 || $body === null || $body === '') {
        return;
    }

    $handles = extractMentionHandles($body);
    if ($handles === []) {
        return;
    }

    $usersByHandle = fetchUsersByMentionHandles($handles);
    $notifiedUserIds = [];

    foreach ($handles as $handle) {
        $mentionedUser = $usersByHandle[$handle] ?? null;
        if ($mentionedUser === null) {
            continue;
        }

        $mentionedUserId = (int) ($mentionedUser['id'] ?? 0);
        if ($mentionedUserId < 1 || isset($notifiedUserIds[$mentionedUserId])) {
            continue;
        }

        $notifiedUserIds[$mentionedUserId] = true;
        createNotificationIfEligible($mentionedUserId, $actorUserId, 'mention', $postId);
    }
}

function notifyReplyMentions(int $conversationId, int $replyId, int $actorUserId, string $body): void
{
    if ($conversationId < 1 || $replyId < 1 || $actorUserId < 1 || $body === '') {
        return;
    }

    $handles = extractMentionHandles($body);
    if ($handles === []) {
        return;
    }

    $usersByHandle = fetchUsersByMentionHandles($handles);
    $notifiedUserIds = [];

    foreach ($handles as $handle) {
        $mentionedUser = $usersByHandle[$handle] ?? null;
        if ($mentionedUser === null) {
            continue;
        }

        $mentionedUserId = (int) ($mentionedUser['id'] ?? 0);
        if ($mentionedUserId < 1 || isset($notifiedUserIds[$mentionedUserId])) {
            continue;
        }

        $notifiedUserIds[$mentionedUserId] = true;
        createNotificationIfEligible($mentionedUserId, $actorUserId, 'mention', $conversationId, $replyId);
    }
}
