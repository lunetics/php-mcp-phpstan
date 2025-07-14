#!/usr/bin/env php
<?php

declare(strict_types=1);

use PhpMcp\Server\Defaults\StreamLogger;
use PhpMcp\Server\Server;
use PhpMcp\PhpStan\PhpStanMcpService;

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
    $logFile = __DIR__ . '/../mcp.log';
    $logger = new StreamLogger($logFile, 'debug');

    $server = Server::make()
        ->withBasePath(__DIR__ . '/..')
        ->withLogger($logger)
        ->withTool([PhpStanMcpService::class, 'analyzeCode'], 'phpstan_analyze')
        ->discover();

    $exitCode = $server->run('stdio');
    exit($exitCode);
    
} catch (\Throwable $e) {
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}