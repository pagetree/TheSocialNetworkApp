<?php

declare(strict_types=1);

/** @var callable(string): string $url */

$sidebarFooterLocationUrl = 'https://www.google.com/maps/place/Municipality+of+Arganda+del+Rey,+28500,+Madrid/@40.3096412,-3.4488183,3a,75y,136.1h,82.46t/data=!3m7!1e1!3m5!1sFemft0_dK0EQ121lwBi7LA!2e0!6shttps:%2F%2Fstreetviewpixels-pa.googleapis.com%2Fv1%2Fthumbnail%3Fcb_client%3Dmaps_sv.tactile%26w%3D900%26h%3D600%26pitch%3D7.535528365015409%26panoid%3DFemft0_dK0EQ121lwBi7LA%26yaw%3D136.09557404516727!7i16384!8i8192!4m15!1m8!3m7!1s0xd423f098a00f637:0x40340f3be4cd800!2sMunicipality+of+Arganda+del+Rey,+28500,+Madrid!3b1!8m2!3d40.3064308!4d-3.4471715!16zL20vMGgzMGtf!3m5!1s0xd423f098a00f637:0x40340f3be4cd800!8m2!3d40.3064308!4d-3.4471715!16zL20vMGgzMGtf?entry=ttu&g_ep=EgoyMDI2MDYwMS4wIKXMDSoASAFQAw%3D%3D';
$sidebarFooterLocationLink = sprintf(
    '<a href="%s" class="app-sidebar-footer-credit-link" target="_blank" rel="noopener noreferrer">%s</a>',
    htmlspecialchars($sidebarFooterLocationUrl, ENT_QUOTES, 'UTF-8'),
    __e('sidebar.footer_location')
);
?>
                    <footer class="app-sidebar-footer app-sidebar-footer--right">
                        <nav class="app-sidebar-footer-links" aria-label="<?php echo __e('sidebar.footer_nav'); ?>">
                            <a href="#" class="app-sidebar-footer-link"><?php echo __e('sidebar.footer_terms'); ?></a>
                            <a href="#" class="app-sidebar-footer-link"><?php echo __e('sidebar.footer_privacy'); ?></a>
                            <a href="#" class="app-sidebar-footer-link"><?php echo __e('sidebar.footer_archive'); ?></a>
                            <a href="#" class="app-sidebar-footer-link"><?php echo __e('sidebar.footer_about'); ?></a>
                        </nav>
                        <p class="app-sidebar-footer-credit"><?php echo applyTranslationReplacements(__('sidebar.footer_made_in'), ['location' => $sidebarFooterLocationLink]); ?></p>
                    </footer>
