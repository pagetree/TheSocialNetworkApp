<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run from CLI: php workers/compute-post-scores.php\n");
    exit(1);
}

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$updated = recomputePostScores();

echo 'Post scores updated: ' . $updated . " rows\n";
