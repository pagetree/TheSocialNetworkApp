<?php

declare(strict_types=1);

/** @var callable(string): string $url */
/** @var string $loginCsrfToken */
?>
<div class="auth-overlay" role="dialog" aria-modal="true" aria-labelledby="auth-login-title">
    <div class="auth-modal">
        <img
            class="auth-modal-logo"
            src="<?php echo htmlspecialchars($url('/assets/img/logo.png'), ENT_QUOTES, 'UTF-8'); ?>"
            alt="<?php echo __e('nav.home'); ?>"
        >
        <h1 class="auth-modal-title" id="auth-login-title"><?php echo __e('auth.sign_in'); ?></h1>
        <p class="auth-modal-subtitle"><?php echo __e('auth.sign_in_subtitle'); ?></p>
        <form class="auth-form" id="login-form" novalidate>
            <div class="auth-honeypot" aria-hidden="true">
                <label>
                    <span><?php echo __e('auth.website_honeypot'); ?></span>
                    <input type="text" name="_hp_url" tabindex="-1" autocomplete="off">
                </label>
            </div>
            <label class="auth-field">
                <span><?php echo __e('auth.email_or_username'); ?></span>
                <input type="text" name="identifier" autocomplete="username" required>
            </label>
            <label class="auth-field">
                <span><?php echo __e('auth.password'); ?></span>
                <input type="password" name="password" autocomplete="current-password" required>
            </label>
            <p class="auth-form-error" id="login-form-error" hidden></p>
            <button type="submit" class="auth-submit-btn"><?php echo __e('auth.sign_in'); ?></button>
        </form>
        <p class="auth-modal-footer">
            <?php echo __e('auth.no_account'); ?>
            <a href="<?php echo htmlspecialchars($url('/register'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo __e('auth.create_one'); ?></a>
        </p>
    </div>
</div>
