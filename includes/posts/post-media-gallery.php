<?php

declare(strict_types=1);

/** @var array<string, mixed> $post */

$postMediaItems = is_array($post['media'] ?? null) ? $post['media'] : [];
$mediaCount = count($postMediaItems);
$galleryClass = 'post-media-gallery';

if ($mediaCount === 1) {
    $galleryClass .= ' post-media-gallery--1';
} elseif ($mediaCount === 2) {
    $galleryClass .= ' post-media-gallery--2';
} elseif ($mediaCount === 3) {
    $galleryClass .= ' post-media-gallery--3';
} elseif ($mediaCount >= 4) {
    $galleryClass .= ' post-media-gallery--4';
}

if ($mediaCount === 0) {
    return;
}
?>
                        <div class="<?php echo htmlspecialchars($galleryClass, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php foreach ($postMediaItems as $mediaItem) :
                                $mediaUrl = (string) ($mediaItem['url'] ?? '');
                                $mediaType = (string) ($mediaItem['type'] ?? '');
                                if ($mediaUrl === '') {
                                    continue;
                                }
                                ?>
                                <?php if ($mediaType === 'video') : ?>
                            <video
                                class="post-media post-media--video"
                                controls
                                preload="metadata"
                                playsinline
                                src="<?php echo htmlspecialchars($mediaUrl, ENT_QUOTES, 'UTF-8'); ?>"
                            ></video>
                                <?php else : ?>
                            <img
                                class="post-media post-media--zoomable"
                                src="<?php echo htmlspecialchars($mediaUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                alt=""
                                role="button"
                                tabindex="0"
                            >
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
