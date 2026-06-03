<?php

declare(strict_types=1);
/** @var int $profileMenuUserId */
/** @var string $profileMenuUserName */

$profileMenuUserId = (int) ($profileMenuUserId ?? 0);
$profileMenuUserName = trim((string) ($profileMenuUserName ?? ''));
?>
                                    <div
                                        class="profile-menu"
                                        data-user-id="<?php echo $profileMenuUserId; ?>"
                                        data-user-name="<?php echo htmlspecialchars($profileMenuUserName, ENT_QUOTES, 'UTF-8'); ?>"
                                    >
                                        <button
                                            type="button"
                                            class="profile-menu-btn"
                                            aria-haspopup="menu"
                                            aria-expanded="false"
                                            aria-label="Profile options"
                                        >
                                            <i data-lucide="ellipsis" aria-hidden="true"></i>
                                        </button>
                                        <div class="profile-menu-dropdown" role="menu" hidden>
                                            <button
                                                type="button"
                                                class="profile-menu-option profile-menu-option--mute"
                                                role="menuitem"
                                            >
                                                <i data-lucide="volume-x" aria-hidden="true"></i>
                                                <span>Mute</span>
                                            </button>
                                            <button
                                                type="button"
                                                class="profile-menu-option profile-menu-option--block"
                                                role="menuitem"
                                            >
                                                <i data-lucide="ban" aria-hidden="true"></i>
                                                <span>Block</span>
                                            </button>
                                            <button
                                                type="button"
                                                class="profile-menu-option profile-menu-option--report"
                                                role="menuitem"
                                            >
                                                <i data-lucide="flag" aria-hidden="true"></i>
                                                <span>Report</span>
                                            </button>
                                        </div>
                                    </div>
