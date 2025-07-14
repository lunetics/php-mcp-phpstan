#!/usr/bin/env php
<?php

declare(strict_types=1);

use PhpMcp\PhpStan\McpServer;

// Autoload dependencies
$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../autoload.php',
];

$autoloadPath = null;
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        $autoloadPath = $path;
        break;
    }
}

if ($autoloadPath === null) {
    fwrite(STDERR, "Error: Could not find vendor/autoload.php. Please run 'composer install'.\n");
    exit(1);
}

require_once $autoloadPath;

try {
    $server = new McpServer();
    $server->run();
} catch (\Throwable $e) {
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}