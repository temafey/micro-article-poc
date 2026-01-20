<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

// Ensure test environment is set before Dotenv loads
// PHPUnit <server> tags may not be processed before bootstrap in some configurations
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'test';
$_SERVER['KERNEL_CLASS'] = $_ENV['KERNEL_CLASS'] = $_SERVER['KERNEL_CLASS'] ?? $_ENV['KERNEL_CLASS'] ?? 'Micro\\Kernel';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
}

if ($_SERVER['APP_DEBUG'] ?? false) {
    umask(0o000);
}
