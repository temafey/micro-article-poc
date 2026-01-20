<?php

declare(strict_types=1);

namespace Micro;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    protected const SERVICE_NAME = 'article';

    protected const DOMAINS = ['article', 'identity'];

    #[\Override]
    public function getCacheDir(): string
    {
        if ($this->environment === 'prod') {
            // return '/dev/shm/symfony-app/cache/' . $this->environment;
        }

        return CACHE_PATH . $this->environment;
    }

    #[\Override]
    public function getLogDir(): string
    {
        return LOG_PATH;
    }

    public function registerBundles(): iterable
    {
        $contents = require CONFIG_PATH . 'bundles.php';

        if (file_exists(CONFIG_PATH . 'bundles_' . $this->environment . '.php')) {
            $specContents = require CONFIG_PATH . 'bundles_' . $this->environment . '.php';
            $contents = array_merge_recursive($contents, $specContents);
        }

        foreach ($contents as $class => $envs) {
            if ((isset($envs['all']) || isset($envs[$this->environment])) && class_exists($class)) {
                yield new $class();
            }
        }
    }

    /**
     * Symfony 8.0 compatible container configuration.
     *
     * @SuppressWarnings(PHPMD)
     */
    private function configureContainer(ContainerConfigurator $container): void
    {
        $container->parameters()
            ->set('container.autowiring.strict_mode', true);
        $container->parameters()
            ->set('container.dumper.inline_class_loader', true);

        $container->import(CONFIG_PATH . '{packages}/*' . self::CONFIG_EXTS, 'glob');

        if (is_dir(CONFIG_PATH . 'packages/' . $this->environment)) {
            $container->import(CONFIG_PATH . '{packages}/' . $this->environment . '/*' . self::CONFIG_EXTS, 'glob');
            $container->import(CONFIG_PATH . '{domains}/*/{packages}/*' . self::CONFIG_EXTS, 'glob');
        }

        $container->import(CONFIG_PATH . 'parameters' . self::CONFIG_EXTS, 'glob');
        $container->import(CONFIG_PATH . '{services}' . self::CONFIG_EXTS, 'glob');
        $container->import(CONFIG_PATH . '{services}/*' . self::CONFIG_EXTS, 'glob');
        $container->import(CONFIG_PATH . '{services}_' . $this->environment . self::CONFIG_EXTS, 'glob');

        if (is_dir(CONFIG_PATH . 'services/' . $this->environment)) {
            $container->import(CONFIG_PATH . '{services}/' . $this->environment . '/*' . self::CONFIG_EXTS, 'glob');
        }

        $container->import(CONFIG_PATH . '{domains}/*/{parameters}' . self::CONFIG_EXTS, 'glob');
        $container->import(CONFIG_PATH . '{domains}/*/{services}' . self::CONFIG_EXTS, 'glob');
        $container->import(CONFIG_PATH . '{domains}/*/{services}/*' . self::CONFIG_EXTS, 'glob');

        foreach (self::DOMAINS as $domainName) {
            $servicesEnvDirName = sprintf('%sdomains/%s/services/%s', CONFIG_PATH, $domainName, $this->environment);
            $servicesEnvFileName = sprintf(
                '%sdomains/%s/services_%s',
                CONFIG_PATH,
                $domainName,
                $this->environment
            );
            if (is_dir($servicesEnvDirName)) {
                $container->import(sprintf('%s/*%s', $servicesEnvDirName, self::CONFIG_EXTS), 'glob');
            }

            if (file_exists($servicesEnvFileName)) {
                $container->import(sprintf('%s%s', $servicesEnvFileName, self::CONFIG_EXTS), 'glob');
            }
        }
    }

    /**
     * @SuppressWarnings(PHPMD)
     */
    private function configureRoutes(RoutingConfigurator $routes): void
    {
        if (is_dir(CONFIG_PATH . 'routes/')) {
            $routes->import(CONFIG_PATH . 'routes/*' . self::CONFIG_EXTS, 'glob');
        }

        if (is_dir(CONFIG_PATH . 'routes/' . $this->environment)) {
            $routes->import(CONFIG_PATH . 'routes/' . $this->environment . '/**/*' . self::CONFIG_EXTS, 'glob');
        }

        $routes->import(CONFIG_PATH . '{domains}/*/{routes}/*' . self::CONFIG_EXTS, 'glob');
    }
}
