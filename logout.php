<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$appPaths = appPaths();
header('Location: ' . $appPaths['url']('/'));
exit;
