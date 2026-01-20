<?php

declare(strict_types=1);

use Micro\Component\Common\Infrastructure\Cache\AdaptiveMsgPackMarshaller;
use Micro\Component\Common\Infrastructure\Cache\MsgPackMarshaller;
use Micro\Component\Common\Infrastructure\EventListener\CsrfTokenValidationListener;
use Micro\Component\Common\Infrastructure\Security\StatelessCsrfTokenService;
use MicroModule\Base\Application\Tracing\TraceableInterface;
use Nelmio\ApiDocBundle\Controller\DocumentationController;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

/**
 * Services configuration migrated from YAML to PHP for OPcache optimization.
 *
 * Benefits:
 * - Type safety with IDE autocomplete
 * - OPcache compatible (no YAML parsing at runtime)
 * - 10-20% faster config loading
 *
 * @see docs/updates/phase-5-advanced/TASK-015-php-array-config.md
 */
return static function (ContainerConfigurator $container): void {
    // Import parameters from YAML (simple key-value parameters kept in YAML for readability)
    $container->import('parameters.yaml');

    $services = $container->services();

    // Default service configuration
    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->private();

    // Configure TraceableInterface implementations with tracing client
    $services->instanceof(TraceableInterface::class)
        ->call('setTracer', [service('tracing.client')])
        ->call('setIsTracingEnabled', ['%tracing.is_enabled%']);

    // Register Common components as services
    // Excludes controllers and services that require explicit configuration
    $services->load('Micro\\Component\\Common\\', '../src/Common')
        ->exclude([
            '../src/Common/Presentation/Rest/V1/UserCommandsController.php',
            '../src/Common/Presentation/Rest/V1/UserQueriesController.php',
            '../src/Common/Presentation/Rest/V2/UserCommandsController.php',
            '../src/Common/Presentation/Rest/V2/UserQueriesController.php',
            // Message signing services - configured in config/packages/message_signing.yaml
            '../src/Common/Infrastructure/Security/MessageSignerService.php',
            '../src/Common/Infrastructure/Security/RedisNonceStore.php',
            '../src/Common/Infrastructure/Security/SignedMessageMiddleware.php',
            // Profiling services - configured in config/packages/profiling.yaml (ADR-014 Phase 4.2)
            '../src/Common/Infrastructure/Observability/Profiling/',
        ]);

    // Alias for Nelmio ApiDocBundle DocumentationController
    $services->alias(DocumentationController::class, 'nelmio_api_doc.controller.swagger_json');

    // Stateless CSRF Protection Service (Symfony 8+)
    // Uses cryptographically signed tokens - no session required
    $services->set(StatelessCsrfTokenService::class);

    // CSRF Validation Listener - disabled by default for pure API services
    $services->set(CsrfTokenValidationListener::class)
        ->arg('$enabled', '%env(bool:CSRF_PROTECTION_ENABLED)%');

    // MsgPack Cache Marshaller - provides ~40% faster serialization when msgpack extension is available
    // Falls back to PHP native serialization otherwise
    $services->set(AdaptiveMsgPackMarshaller::class);
    $services->set(MsgPackMarshaller::class);

    // Replace default cache marshaller with our adaptive MsgPack marshaller
    $services->alias('cache.default_marshaller', AdaptiveMsgPackMarshaller::class);

    // Register Article module services
    // Makes classes in src/Article available to be used as services
    // Creates a service per class whose id is the fully-qualified class name
    $services->load('Micro\\Article\\', '../src/Article');

    // Register Identity module services (JWT + User subdomains)
    // Makes classes in src/Identity available to be used as services
    $services->load('Micro\\Identity\\', '../src/Identity');
};
