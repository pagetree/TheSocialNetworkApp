<?php

declare(strict_types=1);

/** @var bool $sidebarShowFooter */
/** @var string $sidebarName */
/** @var string $sidebarHandle */
/** @var string $sidebarAvatar */
/** @var bool $sidebarFooterUseProfileIds */

$sidebarFooterUseProfileIds = $sidebarFooterUseProfileIds ?? true;
$sidebarAvatarId = $sidebarFooterUseProfileIds ? 'profile-sidebar-avatar' : '';
$sidebarNameId = $sidebarFooterUseProfileIds ? 'profile-sidebar-name' : '';
?>
                    <footer class="app-sidebar-footer">
                        <div class="app-sidebar-footer-row">
                            <div class="app-sidebar-user">
                                <img
                                    <?php if ($sidebarAvatarId !== '') : ?>id="<?php echo $sidebarAvatarId; ?>" <?php endif; ?>
                                    class="app-sidebar-user-avatar"
                                    src="<?php echo htmlspecialchars($sidebarAvatar, ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="<?php echo __e('sidebar.avatar_alt', ['name' => $sidebarName]); ?>"
                                    width="48"
                                    height="48"
                                >
                                <span class="app-sidebar-user-meta">
                                    <span <?php if ($sidebarNameId !== '') : ?>id="<?php echo $sidebarNameId; ?>" <?php endif; ?>class="app-sidebar-user-name"><?php echo htmlspecialchars($sidebarName, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="app-sidebar-user-handle"><?php echo htmlspecialchars($sidebarHandle, ENT_QUOTES, 'UTF-8'); ?></span>
                                </span>
                            </div>
                            <button
                                type="button"
                                class="app-sidebar-footer-menu-btn"
                                aria-haspopup="menu"
                                aria-expanded="false"
                                aria-label="<?php echo __e('profile.options'); ?>"
                            >
                                <i data-lucide="ellipsis" aria-hidden="true"></i>
                            </button>
                        </div>
                    </footer>
