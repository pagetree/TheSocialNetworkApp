<?php

declare(strict_types=1);

/** @var callable(string): string $url */
/** @var array<string, mixed>|null $profileUser */
/** @var string $profileCsrfToken */

$modalCoverUrl = userMediaUrl($profileUser ?? null, 'cover_url', $url);
$modalAvatarUrl = userMediaUrl($profileUser ?? null, 'avatar_url', $url);
?>
<div class="profile-edit-overlay" id="profile-edit-overlay" hidden>
    <div
        class="profile-edit-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="profile-edit-title"
    >
        <span class="profile-edit-sr-only" id="profile-edit-title">Edit profile</span>
        <button type="button" class="profile-edit-close" id="profile-edit-close" aria-label="Close edit profile">
            <i data-lucide="x" aria-hidden="true"></i>
        </button>

        <form class="profile-edit-form" id="profile-edit-form" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($profileCsrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="auth-honeypot" aria-hidden="true">
                <label>
                    <span>Website</span>
                    <input type="text" name="_hp_url" tabindex="-1" autocomplete="off">
                </label>
            </div>

            <section class="profile-hero profile-hero--edit" aria-label="Edit profile preview">
                <div class="profile-cover profile-cover--editable">
                    <img
                        class="profile-cover-image"
                        id="profile-edit-cover-preview"
                        src="<?php echo htmlspecialchars($modalCoverUrl, ENT_QUOTES, 'UTF-8'); ?>"
                        alt=""
                    >
                    <label class="profile-media-upload profile-media-upload--cover" aria-label="Upload cover image">
                        <input
                            type="file"
                            id="profile-edit-cover-input"
                            name="cover_image"
                            accept="image/jpeg,image/png,image/webp,image/gif"
                            hidden
                        >
                        <i data-lucide="upload" aria-hidden="true"></i>
                    </label>
                </div>
                <div class="profile-hero-body">
                    <div class="profile-hero-top">
                        <div class="profile-hero-avatar-wrap">
                            <img
                                class="profile-hero-avatar"
                                id="profile-edit-avatar-preview"
                                src="<?php echo htmlspecialchars($modalAvatarUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                alt="Avatar preview"
                            >
                            <label class="profile-media-upload profile-media-upload--avatar" aria-label="Upload avatar">
                                <input
                                    type="file"
                                    id="profile-edit-avatar-input"
                                    name="avatar"
                                    accept="image/jpeg,image/png,image/webp,image/gif"
                                    hidden
                                >
                                <i data-lucide="upload" aria-hidden="true"></i>
                            </label>
                        </div>
                    </div>
                </div>
            </section>

            <div class="profile-edit-modal-scroll">
            <div class="profile-edit-fields">
                <label class="auth-field">
                    <span>Name</span>
                    <div class="auth-input-wrap">
                        <span class="auth-input-leading-icon" aria-hidden="true">
                            <i data-lucide="user-round"></i>
                        </span>
                        <input type="text" name="display_name" id="profile-edit-display-name" maxlength="100" required>
                    </div>
                </label>
                <label class="auth-field">
                    <span>Bio</span>
                    <div class="auth-input-wrap auth-input-wrap--textarea">
                        <span class="auth-input-leading-icon" aria-hidden="true">
                            <i data-lucide="align-left"></i>
                        </span>
                        <textarea
                            name="bio"
                            id="profile-edit-bio"
                            rows="3"
                            maxlength="300"
                        ></textarea>
                    </div>
                </label>
                <label class="auth-field">
                    <span>Location</span>
                    <div class="auth-input-wrap">
                        <span class="auth-input-leading-icon" aria-hidden="true">
                            <i data-lucide="map-pin"></i>
                        </span>
                        <input type="text" name="location" id="profile-edit-location" maxlength="100">
                    </div>
                </label>
                <label class="auth-field">
                    <span>Website</span>
                    <div class="auth-input-wrap auth-input-wrap--website">
                        <span class="auth-input-leading-icon" aria-hidden="true">
                            <i data-lucide="link"></i>
                        </span>
                        <span class="auth-input-prefix auth-input-prefix--website" aria-hidden="true">https://</span>
                        <input
                            type="text"
                            name="website"
                            id="profile-edit-website"
                            maxlength="255"
                            inputmode="url"
                            autocapitalize="off"
                            autocorrect="off"
                            spellcheck="false"
                        >
                    </div>
                </label>
                <label class="auth-field">
                    <span>Date of birth</span>
                    <div class="auth-input-wrap">
                        <span class="auth-input-leading-icon" aria-hidden="true">
                            <i data-lucide="cake"></i>
                        </span>
                        <input type="date" name="date_of_birth" id="profile-edit-dob">
                    </div>
                </label>
            </div>

            <p class="profile-edit-form-error" id="profile-edit-form-error" hidden></p>
            </div>

            <footer class="profile-edit-actions">
                <button type="button" class="profile-edit-cancel" id="profile-edit-cancel">Cancel</button>
                <button type="submit" class="profile-edit-save" id="profile-edit-save">
                    <span class="profile-edit-save-spinner" aria-hidden="true" hidden></span>
                    <span class="profile-edit-save-label">Save</span>
                </button>
            </footer>
        </form>
    </div>
</div>
