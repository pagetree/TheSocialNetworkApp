<?php

declare(strict_types=1);

/** @var callable(string): string $url */
$loginCsrfToken = createCsrfToken('login');
$appTheme = resolveAppTheme();
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars(appHtmlLang(), ENT_QUOTES, 'UTF-8'); ?>" data-theme="<?php echo htmlspecialchars($appTheme, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
<?php renderThemeHeadScript(); ?>
    <title><?php echo __e('meta.sign_in_title'); ?></title>
<?php renderPageSeoHeadTags(__('meta.sign_in_title'), seoNoindexPage('/login')); ?>
<?php renderAppI18nScript(); ?>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($url('/assets/img/logo.png'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;300;400;500;600;700;800;900;1000&family=Nunito+Sans:opsz,wght@6..12,200;6..12,300;6..12,400;6..12,500;6..12,600;6..12,700;6..12,800;6..12,900;6..12,1000&display=swap">
<?php
require __DIR__ . '/../includes/layout/stylesheets.php';
renderAppStylesheets($url);
?>
</head>
<body class="register-page">
    <div class="register-layout">
        <section class="register-form-column">
            <div class="register-form-panel">
                <img
                    class="register-form-logo"
                    src="<?php echo htmlspecialchars($url('/assets/img/logo.png'), ENT_QUOTES, 'UTF-8'); ?>"
                    alt=""
                    width="72"
                    height="72"
                >
                <div class="register-form-header">
                    <?php require __DIR__ . '/../includes/layout/theme-toggle.php'; ?>
                </div>
                <h1 class="register-title"><?php echo __e('auth.sign_in'); ?></h1>
                <form class="auth-form register-form" id="login-form" novalidate>
                    <div class="auth-honeypot" aria-hidden="true">
                        <label>
                            <span><?php echo __e('auth.website_honeypot'); ?></span>
                            <input type="text" name="_hp_url" tabindex="-1" autocomplete="off">
                        </label>
                    </div>
                    <label class="auth-field">
                        <span><?php echo __e('auth.email_or_username'); ?></span>
                        <div class="auth-input-wrap">
                            <span class="auth-input-leading-icon" aria-hidden="true">
                                <i data-lucide="user-round"></i>
                            </span>
                            <input type="text" name="identifier" autocomplete="username" required>
                        </div>
                    </label>
                    <label class="auth-field">
                        <span><?php echo __e('auth.password'); ?></span>
                        <div class="auth-input-wrap auth-input-wrap--password">
                            <span class="auth-input-leading-icon" aria-hidden="true">
                                <i data-lucide="lock-keyhole"></i>
                            </span>
                            <input type="password" name="password" autocomplete="current-password" required>
                        </div>
                    </label>
                    <p class="auth-form-error" id="login-form-error" hidden></p>
                    <button type="submit" class="auth-submit-btn"><?php echo __e('auth.sign_in'); ?></button>
                </form>
                <p class="register-footer">
                    <?php echo __e('auth.no_account'); ?>
                    <a href="<?php echo htmlspecialchars($url('/register'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo __e('auth.create_one'); ?></a>
                </p>
            </div>
        </section>
<?php require __DIR__ . '/../includes/auth/register-brand-column.php'; ?>
    </div>
    <script>
        window.APP_LOGIN_URL = <?php echo json_encode($url('/auth/login'), JSON_THROW_ON_ERROR); ?>;
        window.APP_HOME_URL = <?php echo json_encode($url('/'), JSON_THROW_ON_ERROR); ?>;
        window.APP_CSRF_TOKEN = <?php echo json_encode($loginCsrfToken, JSON_THROW_ON_ERROR); ?>;
    </script>
    <script src="https://unpkg.com/lucide@0.544.0/dist/umd/lucide.min.js"></script>
    <script src="<?php echo htmlspecialchars($url('/assets/js/theme.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script>
        lucide.createIcons();
    </script>
    <script src="<?php echo htmlspecialchars($url('/assets/js/i18n.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="<?php echo htmlspecialchars($url('/assets/js/login.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>
