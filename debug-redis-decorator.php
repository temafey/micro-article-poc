<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv('.env');

$kernel = new Micro\Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();

// Check if TracingRedis service exists
echo "=== Checking TracingRedis service ===" . PHP_EOL;
$tracingRedisClass = 'Micro\Component\Common\Infrastructure\Observability\Redis\TracingRedis';
$tracingFactoryClass = 'Micro\Component\Common\Infrastructure\Observability\Redis\TracingRedisFactory';

echo "Has TracingRedis: " . ($container->has($tracingRedisClass) ? "yes" : "no") . PHP_EOL;
echo "Has TracingRedisFactory: " . ($container->has($tracingFactoryClass) ? "yes" : "no") . PHP_EOL;
echo "Has TracingRedis.inner: " . ($container->has($tracingRedisClass . '.inner') ? "yes" : "no") . PHP_EOL;
echo "Has Redis: " . ($container->has('Redis') ? "yes" : "no") . PHP_EOL;

// Check the decorated service aliases
echo PHP_EOL . "=== Redis-related services in container ===" . PHP_EOL;
$refl = new ReflectionObject($container);
if ($refl->hasMethod('getServiceIds')) {
    $method = $refl->getMethod('getServiceIds');
    $method->setAccessible(true);
    $ids = $method->invoke($container);
    foreach ($ids as $id) {
        if (stripos($id, 'redis') !== false || stripos($id, 'Tracing') !== false) {
            echo "- $id" . PHP_EOL;
        }
    }
}

// Try to get the factory and create a TracingRedis manually
echo PHP_EOL . "=== Testing TracingRedisFactory directly ===" . PHP_EOL;
try {
    $factory = $container->get($tracingFactoryClass);
    echo "Got TracingRedisFactory: " . get_class($factory) . PHP_EOL;

    // Create a test Redis and wrap it
    $testRedis = new Redis();
    $testRedis->connect('test-micro-article-system-redis', 6379);
    $tracingRedis = $factory->create($testRedis);
    echo "Created TracingRedis: " . get_class($tracingRedis) . PHP_EOL;
    echo "TracingRedis->ping(): " . var_export($tracingRedis->ping(), true) . PHP_EOL;
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}

// Now check what the NonceStore actually received
echo PHP_EOL . "=== Checking NonceStore Redis instance ===" . PHP_EOL;
try {
    $nonceStore = $container->get('message_nonce_store');
    echo 'NonceStore class: ' . get_class($nonceStore) . PHP_EOL;

    $reflection = new ReflectionClass($nonceStore);
    $prop = $reflection->getProperty('redis');
    $prop->setAccessible(true);
    $redis = $prop->getValue($nonceStore);
    echo 'Redis instance class: ' . get_class($redis) . PHP_EOL;
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
