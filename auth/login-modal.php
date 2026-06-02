<?php

declare(strict_types=1);

/** @var callable(string): string $url */
?>
<div class="auth-overlay" role="dialog" aria-modal="true" aria-labelledby="auth-login-title">
    <div class="auth-modal">
        <img
            class="auth-modal-logo"
            src="<?php echo htmlspecialchars($url('/assets/img/logo.png'), ENT_QUOTES, 'UTF-8'); ?>"
            alt="TheSocialNetworkApp logo"
        >
        <h1 class="auth-modal-title" id="auth-login-title">Sign in</h1>
        <p class="auth-modal-subtitle">Welcome back. Sign in to continue.</p>
        <form class="auth-form" id="login-form" novalidate>
            <label class="auth-field">
                <span>Email or username</span>
                <input type="text" name="identifier" autocomplete="username" required>
            </label>
            <label class="auth-field">
                <span>Password</span>
                <input type="password" name="password" autocomplete="current-password" required>
            </label>
            <p class="auth-form-error" id="login-form-error" hidden></p>
            <button type="submit" class="auth-submit-btn">Sign in</button>
        </form>
        <p class="auth-modal-footer">
            No account yet?
            <a href="<?php echo htmlspecialchars($url('/register'), ENT_QUOTES, 'UTF-8'); ?>">Create one</a>
        </p>
    </div>
</div>
