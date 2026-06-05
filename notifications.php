<?php

declare(strict_types=1);

/** @var callable(string): string $url */
/** @var list<array<string, mixed>> $notificationItems */
/** @var int $currentUserId */

$notificationItems = $notificationItems ?? [];
?>
                    <div class="notifications-list" id="notifications-list">
<?php if ($notificationItems === []) : ?>
                        <div class="notifications-empty" role="status">
                            <div class="notifications-empty-icon" aria-hidden="true">
                                <i data-lucide="bell"></i>
                            </div>
                            <h2 class="notifications-empty-title"><?php echo __e('notifications.empty_title'); ?></h2>
                            <p class="notifications-empty-hint"><?php echo __e('notifications.empty_hint'); ?></p>
                        </div>
<?php else :
    foreach ($notificationItems as $notificationItem) :
        $notificationId = (int) ($notificationItem['id'] ?? 0);
        $isUnread = empty($notificationItem['is_read']);
        $actorName = (string) ($notificationItem['display_name'] ?? 'User');
        $actorAvatar = userMediaUrl($notificationItem, 'avatar_url', $url);
        $targetUrl = notificationTargetUrl($notificationItem, $url);
        $message = notificationMessage($notificationItem);
        $timeLabel = formatPostTimeLabel((string) ($notificationItem['created_at'] ?? ''));
        $itemClass = 'notifications-item' . ($isUnread ? ' is-unread' : '');
        ?>
                        <a
                            class="<?php echo htmlspecialchars($itemClass, ENT_QUOTES, 'UTF-8'); ?>"
                            href="<?php echo htmlspecialchars($targetUrl, ENT_QUOTES, 'UTF-8'); ?>"
                            data-notification-id="<?php echo $notificationId; ?>"
                        >
                            <img
                                class="notifications-item-avatar"
                                src="<?php echo htmlspecialchars($actorAvatar, ENT_QUOTES, 'UTF-8'); ?>"
                                alt="<?php echo __e('sidebar.avatar_alt', ['name' => $actorName]); ?>"
                                width="48"
                                height="48"
                                loading="lazy"
                                decoding="async"
                            >
                            <div class="notifications-item-body">
                                <p class="notifications-item-message"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
                                <time class="notifications-item-time" datetime="<?php echo htmlspecialchars((string) ($notificationItem['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($timeLabel, ENT_QUOTES, 'UTF-8'); ?>
                                </time>
                            </div>
<?php if ($isUnread) : ?>
                            <span class="notifications-item-dot" aria-hidden="true"></span>
<?php endif; ?>
                        </a>
<?php
    endforeach;
endif; ?>
                    </div>
