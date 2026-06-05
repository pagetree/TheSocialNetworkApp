<?php

declare(strict_types=1);
?>
                <aside class="app-sidebar app-sidebar--contacts" aria-label="<?php echo __e('chat.contacts_sidebar'); ?>">
                    <header class="app-shell-header app-sidebar-header--contacts">
                        <h1 class="app-content-header-title">/<?php echo __e('nav.page_chat'); ?></h1>
                    </header>
                    <header class="app-shell-header app-sidebar-header--contacts-search">
                        <label class="app-sidebar-search">
                            <i data-lucide="search" class="app-sidebar-search-icon" aria-hidden="true"></i>
                            <input
                                type="search"
                                class="app-sidebar-search-input"
                                placeholder="<?php echo __e('chat.search_placeholder'); ?>"
                                aria-label="<?php echo __e('chat.search_label'); ?>"
                                autocomplete="off"
                                data-chat-contacts-search
                            >
                        </label>
                    </header>
                    <div class="app-sidebar-body app-sidebar-body--contacts">
                        <ul class="chat-contacts-list" id="chat-contacts-list"></ul>
                    </div>
                </aside>
