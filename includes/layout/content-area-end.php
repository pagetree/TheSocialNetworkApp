<?php

declare(strict_types=1);

/** @var bool $isLoggedIn */
/** @var callable(string): string $url */
/** @var string $loginCsrfToken */
/** @var list<string> $pageScripts */

$pageScripts = $pageScripts ?? [];
?>
                </main>
            </div>
        </div>
    </div>
    <?php if (!empty($showProfileEditModal)) {
        require dirname(__DIR__) . '/profile/edit-profile-modal.php';
    } ?>
    <?php if ($isLoggedIn) : ?>
    <?php if (!empty($showProfileEditModal)) : ?>
    <script>
        window.APP_PROFILE_UPDATE_URL = <?php echo json_encode($url('/auth/profile'), JSON_THROW_ON_ERROR); ?>;
        window.APP_PROFILE_CSRF_TOKEN = <?php echo json_encode($profileCsrfToken ?? '', JSON_THROW_ON_ERROR); ?>;
    </script>
    <?php endif; ?>
    <script src="https://unpkg.com/lucide@0.544.0/dist/umd/lucide.min.js"></script>
    <?php foreach ($pageScripts as $scriptPath) : ?>
    <script src="<?php echo htmlspecialchars($url($scriptPath), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <?php endforeach; ?>
    <script>
        lucide.createIcons();
    </script>
    <?php else : ?>
    <script>
        window.APP_LOGIN_URL = <?php echo json_encode($url('/auth/login'), JSON_THROW_ON_ERROR); ?>;
        window.APP_CSRF_TOKEN = <?php echo json_encode($loginCsrfToken, JSON_THROW_ON_ERROR); ?>;
    </script>
    <script src="<?php echo htmlspecialchars($url('/assets/js/login.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <?php endif; ?>
</body>
</html>
