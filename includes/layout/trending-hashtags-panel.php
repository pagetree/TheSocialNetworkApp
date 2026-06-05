<?php

declare(strict_types=1);

/** @var callable(string): string $url */
/** @var list<array{tag: string, post_count: int}> $trendingHashtags */
?>
                    <article class="trending-hashtags-card">
                        <h2 class="trending-hashtags-card-title"><?php echo __e('sidebar.trending_hashtags'); ?></h2>
                        <?php if ($trendingHashtags === []) : ?>
                        <p class="trending-hashtags-empty"><?php echo __e('sidebar.no_trending_hashtags'); ?></p>
                        <?php else : ?>
                        <ul class="trending-hashtags-list">
                            <?php foreach ($trendingHashtags as $hashtagRow) :
                                $hashtagTag = (string) ($hashtagRow['tag'] ?? '');
                                $hashtagPostCount = max(0, (int) ($hashtagRow['post_count'] ?? 0));
                                $hashtagUrl = $url(hashtagUrlPath($hashtagTag));
                                ?>
                            <li class="trending-hashtags-item">
                                <a class="trending-hashtags-link" href="<?php echo htmlspecialchars($hashtagUrl, ENT_QUOTES, 'UTF-8'); ?>">
                                    <span class="trending-hashtags-name">#<?php echo htmlspecialchars($hashtagTag, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="trending-hashtags-count"><?php echo __e('sidebar.hashtag_post_count', ['count' => formatEngagementCount($hashtagPostCount)]); ?></span>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </article>
