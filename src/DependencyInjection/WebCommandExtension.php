<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\DependencyInjection;

use Danilovl\WebCommandBundle\Admin\DashboardController;
use Danilovl\WebCommandBundle\Service\ConfigurationProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

class WebCommandExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration;
        $config = $this->processConfiguration($configuration, $configs);

        $fileLocator = new FileLocator(__DIR__ . '/../Resources/config');
        $loader = new YamlFileLoader($container, $fileLocator);
        $loader->load('services.yaml');

        $consolePath = $config['console_path'] ?? null;
        if ($consolePath === null) {
            /** @var string $projectDir */
            $projectDir = $container->getParameter('kernel.project_dir');
            $consolePath = $projectDir . DIRECTORY_SEPARATOR . 'bin/console';
        }

        $container->setParameter('web_command.api_prefix', $config['api_prefix']);

        $container->register(ConfigurationProvider::class, ConfigurationProvider::class)
            ->setArguments([
                $config['api_prefix'],
                $consolePath,
                $config['enable_async'],
                $config['default_timeout'],
                $config['default_time_limit'],
                $config['default_memory_limit'],
                $config['enabled_admin_dashboard'],
                $config['enabled_dashboard_live_status']
            ]);

        if (!$config['enabled_admin_dashboard']) {
            $container->removeDefinition(DashboardController::class);
        }
    }

    public function getAlias(): string
    {
        return Configuration::ALIAS;
    }
}
