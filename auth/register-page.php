<?php

declare(strict_types=1);

/** @var callable(string): string $url */
$registerCsrfToken = createCsrfToken('register');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create account — TheSocialNetworkApp</title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($url('/assets/img/logo.png'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;300;400;500;600;700;800;900;1000&family=Nunito+Sans:opsz,wght@6..12,200;6..12,300;6..12,400;6..12,500;6..12,600;6..12,700;6..12,800;6..12,900;6..12,1000&display=swap">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url('/assets/css/main.css'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="register-page">
    <div class="register-layout">
        <section class="register-form-column">
            <div class="register-form-panel">
                <h1 class="register-title">Create account</h1>
                <p class="register-subtitle">Join TheSocialNetworkApp today.</p>
                <form class="auth-form register-form" id="register-form" novalidate>
                    <div class="auth-honeypot" aria-hidden="true">
                        <label>
                            <span>Website</span>
                            <input type="text" name="_hp_url" tabindex="-1" autocomplete="off">
                        </label>
                    </div>
                    <label class="auth-field">
                        <span>Username</span>
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
                            <span>First name</span>
                            <div class="auth-input-wrap">
                            <span class="auth-input-leading-icon" aria-hidden="true">
                                <i data-lucide="user-round"></i>
                            </span>
                                <input type="text" name="first_name" autocomplete="given-name" required>
                            </div>
                        </label>
                        <label class="auth-field">
                            <span>Last name</span>
                            <div class="auth-input-wrap">
                            <span class="auth-input-leading-icon" aria-hidden="true">
                                <i data-lucide="user-round"></i>
                            </span>
                                <input type="text" name="last_name" autocomplete="family-name" required>
                            </div>
                        </label>
                    </div>
                    <label class="auth-field">
                        <span>Email</span>
                        <div class="auth-input-wrap">
                            <span class="auth-input-leading-icon" aria-hidden="true">
                                <i data-lucide="mail"></i>
                            </span>
                            <input type="email" name="email" autocomplete="email" required>
                        </div>
                    </label>
                    <label class="auth-field">
                        <span>Password</span>
                        <div class="auth-input-wrap auth-input-wrap--password">
                            <span class="auth-input-leading-icon" aria-hidden="true">
                                <i data-lucide="lock-keyhole"></i>
                            </span>
                            <input type="password" name="password" id="register-password" autocomplete="new-password" required>
                            <button type="button" class="auth-input-toggle" id="register-password-toggle" aria-label="Show password" aria-pressed="false">
                                <i data-lucide="eye"></i>
                            </button>
                        </div>
                    </label>
                    <p class="auth-form-error" id="register-form-error" hidden></p>
                    <button type="submit" class="auth-submit-btn">Create account</button>
                </form>
                <p class="register-footer">
                    Already have an account?
                    <a href="<?php echo htmlspecialchars($url('/'), ENT_QUOTES, 'UTF-8'); ?>">Sign in</a>
                </p>
            </div>
        </section>
        <section class="register-brand-column" aria-hidden="true">
            <img
                class="register-logo"
                src="<?php echo htmlspecialchars($url('/assets/img/logo.png'), ENT_QUOTES, 'UTF-8'); ?>"
                alt=""
            >
        </section>
    </div>
    <script>
        window.APP_REGISTER_URL = <?php echo json_encode($url('/auth/register'), JSON_THROW_ON_ERROR); ?>;
        window.APP_CHECK_USERNAME_URL = <?php echo json_encode($url('/auth/check-username'), JSON_THROW_ON_ERROR); ?>;
        window.APP_HOME_URL = <?php echo json_encode($url('/'), JSON_THROW_ON_ERROR); ?>;
        window.APP_CSRF_TOKEN = <?php echo json_encode($registerCsrfToken, JSON_THROW_ON_ERROR); ?>;
    </script>
    <script src="https://unpkg.com/lucide@0.544.0/dist/umd/lucide.min.js"></script>
    <script>
        lucide.createIcons();
    </script>
    <script src="<?php echo htmlspecialchars($url('/assets/js/register.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>
