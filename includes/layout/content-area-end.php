<?php

declare(strict_types=1);

/** @var bool $isLoggedIn */
/** @var callable(string): string $url */
/** @var string $loginCsrfToken */
/** @var list<string> $pageScripts */

$pageScripts = $pageScripts ?? [];
$onboardingLayout = !empty($onboardingLayout);
?>
                    </div>
<?php if (!$onboardingLayout) : ?>
                    </div>
<?php endif; ?>
                </main>
<?php
if (!$onboardingLayout && empty($chatLayout)) {
    require __DIR__ . '/sidebar-right.php';
}
if (!$onboardingLayout) : ?>
            </div>
<?php endif; ?>
        </div>
    </div>
    <?php if (!$onboardingLayout && !empty($showProfileEditModal)) {
        require dirname(__DIR__) . '/profile/edit-profile-modal.php';
    } ?>
    <?php if (!$onboardingLayout && !empty($showFeedReplyModal)) {
        require dirname(__DIR__) . '/posts/feed-reply-modal.php';
    } ?>
    <?php if (!$onboardingLayout && !empty($showQuoteModal)) {
        require dirname(__DIR__) . '/posts/post-quote-modal.php';
    } ?>
    <?php if (!$onboardingLayout && !empty($postStatsCsrfToken)) {
        require dirname(__DIR__) . '/posts/post-stats-modal.php';
    } ?>
    <?php if ($isLoggedIn && !$onboardingLayout) {
        require dirname(__DIR__) . '/report-modal.php';
    } ?>
    <?php if ($isLoggedIn && !$onboardingLayout) {
        require __DIR__ . '/media-lightbox.php';
    } ?>
    <?php
    if ($isLoggedIn && !$onboardingLayout && empty($showFeedComposerInline)) {
        if (empty($composerAvatarUrl)) {
            $composerUser = getCurrentUser();
            $composerAvatarUrl = is_array($composerUser)
                ? userMediaUrl($composerUser, 'avatar_url', $url)
                : '';
        }
        if (empty($postCsrfToken)) {
            $postCsrfToken = createCsrfToken('post_create');
        }
        $postComposerStandalone = true;
        require dirname(__DIR__) . '/posts/post-composer-modal.php';
    }
    ?>
    <?php if ($isLoggedIn && $onboardingLayout) : ?>
    <script>
        window.APP_ONBOARDING = <?php echo json_encode([
            'step' => $onboardingStep ?? 'welcome',
            'csrfToken' => $onboardingCsrfToken ?? '',
            'maxInterests' => ONBOARDING_MAX_INTERESTS,
            'bioMaxLength' => PROFILE_BIO_MAX_LENGTH,
            'urls' => [
                'avatar' => $url('/auth/onboarding/avatar'),
                'bio' => $url('/auth/onboarding/bio'),
                'interests' => $url('/auth/onboarding/interests'),
                'follow' => $url('/auth/onboarding/follow'),
                'complete' => $url('/auth/onboarding/complete'),
                'steps' => [
                    'welcome' => $url('/onboarding/welcome'),
                    'avatar' => $url('/onboarding/avatar'),
                    'bio' => $url('/onboarding/bio'),
                    'interests' => $url('/onboarding/interests'),
                    'suggestions' => $url('/onboarding/suggestions'),
                ],
            ],
        ], JSON_THROW_ON_ERROR); ?>;
    </script>
    <?php elseif ($isLoggedIn) : ?>
    <?php if (!empty($showProfileEditModal)) : ?>
    <script>
        window.APP_PROFILE_UPDATE_URL = <?php echo json_encode($url('/auth/profile'), JSON_THROW_ON_ERROR); ?>;
        window.APP_PROFILE_CSRF_TOKEN = <?php echo json_encode($profileCsrfToken ?? '', JSON_THROW_ON_ERROR); ?>;
    </script>
    <?php endif; ?>
    <?php if ($isLoggedIn) :
        if (empty($profileFollowCsrfToken)) {
            $profileFollowCsrfToken = createCsrfToken('profile_follow');
        }
    ?>
    <script>
        window.APP_PROFILE_FOLLOW_URL = <?php echo json_encode($url('/users/follow'), JSON_THROW_ON_ERROR); ?>;
        window.APP_PROFILE_FOLLOW_CSRF_TOKEN = <?php echo json_encode($profileFollowCsrfToken, JSON_THROW_ON_ERROR); ?>;
    </script>
    <?php endif; ?>
    <?php if (!empty($postStatsCsrfToken)) : ?>
    <script>
        window.APP_POST_STATS_URL = <?php echo json_encode($url('/posts/stats'), JSON_THROW_ON_ERROR); ?>;
        window.APP_POST_STATS_DETAIL_URL = <?php echo json_encode($url('/posts/stats/detail'), JSON_THROW_ON_ERROR); ?>;
        window.APP_POST_STATS_CSRF_TOKEN = <?php echo json_encode($postStatsCsrfToken, JSON_THROW_ON_ERROR); ?>;
        window.APP_CURRENT_USER_ID = <?php echo json_encode($currentUserId ?? 0, JSON_THROW_ON_ERROR); ?>;
    </script>
    <?php endif; ?>
    <?php
    if ($isLoggedIn && empty($postRepostCsrfToken)) {
        $postRepostCsrfToken = createCsrfToken('post_repost');
    }
    ?>
    <?php if (!empty($postLikeCsrfToken)) : ?>
    <script>
        window.APP_POST_LIKE_URL = <?php echo json_encode($url('/posts/like'), JSON_THROW_ON_ERROR); ?>;
        window.APP_POST_LIKE_CSRF_TOKEN = <?php echo json_encode($postLikeCsrfToken, JSON_THROW_ON_ERROR); ?>;
    </script>
    <?php endif; ?>
    <?php if (!empty($postRepostCsrfToken)) : ?>
    <script>
        window.APP_POST_REPOST_URL = <?php echo json_encode($url('/posts/repost'), JSON_THROW_ON_ERROR); ?>;
        window.APP_POST_REPOST_CSRF_TOKEN = <?php echo json_encode($postRepostCsrfToken, JSON_THROW_ON_ERROR); ?>;
    </script>
    <?php endif; ?>
    <?php if ($isLoggedIn && !$onboardingLayout) : ?>
    <script>
        window.APP_CONTENT_REPORT_URL = <?php echo json_encode($url('/content/report'), JSON_THROW_ON_ERROR); ?>;
        window.APP_CONTENT_REPORT_CSRF_TOKEN = <?php echo json_encode(createCsrfToken('content_report'), JSON_THROW_ON_ERROR); ?>;
        window.APP_CONTENT_REPORT_DETAILS_MAX_LENGTH = <?php echo json_encode(CONTENT_REPORT_DETAILS_MAX_LENGTH, JSON_THROW_ON_ERROR); ?>;
    </script>
    <script>
        window.APP_POST_REMOVE_URL = <?php echo json_encode($url('/posts/remove'), JSON_THROW_ON_ERROR); ?>;
        window.APP_POST_REMOVE_CSRF_TOKEN = <?php echo json_encode(createCsrfToken('post_remove'), JSON_THROW_ON_ERROR); ?>;
        window.APP_CURRENT_USER_ID = <?php echo json_encode((int) ($currentUserId ?? 0), JSON_THROW_ON_ERROR); ?>;
    </script>
    <?php
    if (empty($notificationsReadCsrfToken)) {
        $notificationsReadCsrfToken = createCsrfToken('notifications_read');
    }
    ?>
    <script>
        window.APP_NOTIFICATIONS_UNREAD_URL = <?php echo json_encode($url('/notifications/unread'), JSON_THROW_ON_ERROR); ?>;
        window.APP_NOTIFICATIONS_READ_URL = <?php echo json_encode($url('/notifications/read'), JSON_THROW_ON_ERROR); ?>;
        window.APP_NOTIFICATIONS_READ_CSRF_TOKEN = <?php echo json_encode($notificationsReadCsrfToken, JSON_THROW_ON_ERROR); ?>;
        window.APP_NOTIFICATIONS_PAGE = <?php echo json_encode(!empty($showNotificationsPage), JSON_THROW_ON_ERROR); ?>;
    </script>
    <?php endif; ?>
    <?php if (!empty($replyCsrfToken)) : ?>
    <script>
        window.APP_POST_REPLY_URL = <?php echo json_encode($url('/posts/reply'), JSON_THROW_ON_ERROR); ?>;
        window.APP_POST_REPLY_CSRF_TOKEN = <?php echo json_encode($replyCsrfToken, JSON_THROW_ON_ERROR); ?>;
        window.APP_POST_MEDIA_LIMITS = <?php echo json_encode([
            'imageMaxBytes' => POST_IMAGE_MAX_BYTES,
            'videoMaxBytes' => POST_VIDEO_MAX_BYTES,
            'maxImages' => POST_MAX_IMAGES,
            'maxVideos' => POST_MAX_VIDEOS,
        ], JSON_THROW_ON_ERROR); ?>;
        <?php if (empty($showFeedReplyModal)) : ?>
        window.APP_POST_REPLY_POST_ID = <?php echo json_encode((int) ($post['id'] ?? 0), JSON_THROW_ON_ERROR); ?>;
        <?php endif; ?>
    </script>
    <?php endif; ?>
    <?php
    if ($isLoggedIn && !empty($showQuoteModal) && empty($postCsrfToken)) {
        $postCsrfToken = createCsrfToken('post_create');
    }
    ?>
    <?php if (!empty($postCsrfToken)) : ?>
    <script>
        window.APP_POST_CREATE_URL = <?php echo json_encode($url('/posts/create'), JSON_THROW_ON_ERROR); ?>;
        window.APP_POST_CSRF_TOKEN = <?php echo json_encode($postCsrfToken, JSON_THROW_ON_ERROR); ?>;
        window.APP_POST_MEDIA_LIMITS = <?php echo json_encode([
            'imageMaxBytes' => POST_IMAGE_MAX_BYTES,
            'videoMaxBytes' => POST_VIDEO_MAX_BYTES,
            'maxImages' => POST_MAX_IMAGES,
            'maxVideos' => POST_MAX_VIDEOS,
        ], JSON_THROW_ON_ERROR); ?>;
    </script>
    <?php endif; ?>
    <?php else : ?>
    <script>
        window.APP_LOGIN_URL = <?php echo json_encode($url('/auth/login'), JSON_THROW_ON_ERROR); ?>;
        window.APP_CSRF_TOKEN = <?php echo json_encode($loginCsrfToken, JSON_THROW_ON_ERROR); ?>;
    </script>
    <?php endif; ?>
    <script src="https://unpkg.com/lucide@0.544.0/dist/umd/lucide.min.js"></script>
    <script src="<?php echo htmlspecialchars($url('/assets/js/i18n.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="<?php echo htmlspecialchars($url('/assets/js/theme.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="<?php echo htmlspecialchars($url('/assets/js/icons.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <?php if (!$onboardingLayout) : ?>
    <script src="<?php echo htmlspecialchars($url('/assets/js/mobile-nav.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <?php if ($isLoggedIn) : ?>
    <script src="<?php echo htmlspecialchars($url('/assets/js/sidebar-footer-menu.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <?php endif; ?>
    <?php endif; ?>
    <?php if ($isLoggedIn && !$onboardingLayout) : ?>
    <script>
        window.APP_POST_COMPOSER_MENTION = <?php echo json_encode($postComposerMentionHandle ?? '', JSON_THROW_ON_ERROR); ?>;
    </script>
    <?php endif; ?>
    <?php if ($isLoggedIn && !$onboardingLayout) : ?>
    <script src="<?php echo htmlspecialchars($url('/assets/js/post-composer-modal.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <?php if (!empty($postCsrfToken)) : ?>
    <script src="<?php echo htmlspecialchars($url('/assets/js/post-composer.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <?php endif; ?>
    <?php endif; ?>
    <?php if ($isLoggedIn && $onboardingLayout) : ?>
    <?php foreach ($pageScripts as $scriptPath) : ?>
    <script src="<?php echo htmlspecialchars($url($scriptPath), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <?php endforeach; ?>
    <?php elseif ($isLoggedIn) : ?>
    <?php if (!empty($showAnalyticsPage)) : ?>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.49.0/dist/apexcharts.min.js"></script>
    <script>
        window.APP_ANALYTICS = <?php echo json_encode([
            'url' => $url('/analytics/impressions'),
            'statsUrl' => $url('/analytics/stats'),
            'defaultPeriod' => $analyticsDefaultPeriod ?? '7d',
            'initialData' => $analyticsInitialData ?? ['ok' => false],
            'initialStats' => $analyticsInitialStats ?? ['ok' => false],
            'preloadedData' => $analyticsPreloadedData ?? [],
            'preloadedStats' => $analyticsPreloadedStats ?? [],
            'impressionsLabel' => __('analytics.impressions_series'),
            'sessionExpiredMessage' => __('api.session_expired_short'),
        ], JSON_THROW_ON_ERROR); ?>;
    </script>
    <?php endif; ?>
    <?php foreach ($pageScripts as $scriptPath) : ?>
    <script src="<?php echo htmlspecialchars($url($scriptPath), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <?php endforeach; ?>
    <script src="<?php echo htmlspecialchars($url('/assets/js/media-lightbox.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="<?php echo htmlspecialchars($url('/assets/js/post-stats.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <?php if (!empty($postStatsCsrfToken)) : ?>
    <script src="<?php echo htmlspecialchars($url('/assets/js/post-stats-modal.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <?php endif; ?>
    <script src="<?php echo htmlspecialchars($url('/assets/js/post-likes.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="<?php echo htmlspecialchars($url('/assets/js/post-reposts.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <?php if (!empty($showQuoteModal)) : ?>
    <script src="<?php echo htmlspecialchars($url('/assets/js/post-quote-modal.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <?php endif; ?>
    <script src="<?php echo htmlspecialchars($url('/assets/js/content-report.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="<?php echo htmlspecialchars($url('/assets/js/post-remove.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="<?php echo htmlspecialchars($url('/assets/js/profile-follow.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="<?php echo htmlspecialchars($url('/assets/js/notifications.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <?php else : ?>
    <script src="<?php echo htmlspecialchars($url('/assets/js/login.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <?php endif; ?>
    <script>
        if (typeof window.refreshLucideIcons === "function") {
            window.refreshLucideIcons();
        }
    </script>
</body>
</html>
