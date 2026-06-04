<?php

declare(strict_types=1);

/** @var callable(string): string $url */
$registerCsrfToken = createCsrfToken('register');
$appTheme = resolveAppTheme();
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars(appHtmlLang(), ENT_QUOTES, 'UTF-8'); ?>" data-theme="<?php echo htmlspecialchars($appTheme, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
<?php renderThemeHeadScript(); ?>
    <title><?php echo __e('meta.register_title'); ?></title>
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
                    <?php require __DIR__ . '/../includes/layout/lang-switcher.php'; ?>
                    <?php require __DIR__ . '/../includes/layout/theme-toggle.php'; ?>
                </div>
                <h1 class="register-title"><?php echo __e('auth.join_today'); ?></h1>
                <form class="auth-form register-form" id="register-form" novalidate>
                    <div class="auth-honeypot" aria-hidden="true">
                        <label>
                            <span><?php echo __e('auth.website_honeypot'); ?></span>
                            <input type="text" name="_hp_url" tabindex="-1" autocomplete="off">
                        </label>
                    </div>
                    <label class="auth-field">
                        <span><?php echo __e('auth.username'); ?></span>
                        <div class="auth-input-wrap auth-input-wrap--username">
                            <span class="auth-input-prefix" aria-hidden="true">@</span>
                            <input
                                type="text"
                                name="username"
                                id="register-username"
                                autocomplete="username"
                                autocapitalize="off"
                                autocorrect="off"
                                spellcheck="false"
                                required
                            >
                        </div>
                        <p class="auth-field-hint" id="username-hint" hidden></p>
                    </label>
                    <div class="register-name-row">
                        <label class="auth-field">
                            <span><?php echo __e('auth.first_name'); ?></span>
                            <div class="auth-input-wrap">
                            <span class="auth-input-leading-icon" aria-hidden="true">
                                <i data-lucide="user-round"></i>
                            </span>
                                <input type="text" name="first_name" autocomplete="given-name" required>
                            </div>
                        </label>
                        <label class="auth-field">
                            <span><?php echo __e('auth.last_name'); ?></span>
                            <div class="auth-input-wrap">
                            <span class="auth-input-leading-icon" aria-hidden="true">
                                <i data-lucide="user-round"></i>
                            </span>
                                <input type="text" name="last_name" autocomplete="family-name" required>
                            </div>
                        </label>
                    </div>
                    <label class="auth-field">
                        <span><?php echo __e('auth.email'); ?></span>
                        <div class="auth-input-wrap">
                            <span class="auth-input-leading-icon" aria-hidden="true">
                                <i data-lucide="mail"></i>
                            </span>
                            <input type="email" name="email" autocomplete="email" required>
                        </div>
                    </label>
                    <label class="auth-field">
                        <span><?php echo __e('auth.password'); ?></span>
                        <div class="auth-input-wrap auth-input-wrap--password">
                            <span class="auth-input-leading-icon" aria-hidden="true">
                                <i data-lucide="lock-keyhole"></i>
                            </span>
                            <input type="password" name="password" id="register-password" autocomplete="new-password" required>
                            <button type="button" class="auth-input-toggle" id="register-password-toggle" aria-label="<?php echo __e('auth.password_toggle.show'); ?>" aria-pressed="false">
                                <i data-lucide="eye"></i>
                            </button>
                        </div>
                    </label>
                    <p class="auth-form-error" id="register-form-error" hidden></p>
                    <button type="submit" class="auth-submit-btn"><?php echo __e('auth.create_account'); ?></button>
                </form>
                <p class="register-footer">
                    <?php echo __e('auth.have_account'); ?>
                    <a href="<?php echo htmlspecialchars($url('/login'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo __e('auth.sign_in'); ?></a>
                </p>
            </div>
        </section>
<?php require __DIR__ . '/../includes/auth/register-brand-column.php'; ?>
    </div>
    <script>
        window.APP_REGISTER_URL = <?php echo json_encode($url('/auth/register'), JSON_THROW_ON_ERROR); ?>;
        window.APP_CHECK_USERNAME_URL = <?php echo json_encode($url('/auth/check-username'), JSON_THROW_ON_ERROR); ?>;
        window.APP_HOME_URL = <?php echo json_encode($url('/onboarding/welcome'), JSON_THROW_ON_ERROR); ?>;
        window.APP_CSRF_TOKEN = <?php echo json_encode($registerCsrfToken, JSON_THROW_ON_ERROR); ?>;
    </script>
    <script src="https://unpkg.com/lucide@0.544.0/dist/umd/lucide.min.js"></script>
    <script src="<?php echo htmlspecialchars($url('/assets/js/theme.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script>
        lucide.createIcons();
    </script>
    <script src="<?php echo htmlspecialchars($url('/assets/js/i18n.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="<?php echo htmlspecialchars($url('/assets/js/register.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>
