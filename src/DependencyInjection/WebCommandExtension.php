<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\DependencyInjection;

use Danilovl\WebCommandBundle\Admin\DashboardController;
use Danilovl\WebCommandBundle\Dto\ConfigurationModel;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

/**
 * @phpstan-import-type ConfigurationArray from ConfigurationModel
 */
class WebCommandExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration;
        /** @var ConfigurationArray $config */
        $config = $this->processConfiguration($configuration, $configs);
        $configurationModel = ConfigurationModel::fromArray($config);

        $fileLocator = new FileLocator(__DIR__ . '/../Resources/config');
        $loader = new YamlFileLoader($container, $fileLocator);
        $loader->load('services.yaml');

        $consolePath = $configurationModel->consolePath;
        if ($consolePath === null) {
            /** @var string $projectDir */
            $projectDir = $container->getParameter('kernel.project_dir');
            $consolePath = $projectDir . DIRECTORY_SEPARATOR . 'bin/console';
        }

        $container->setParameter('web_command.api_prefix', $configurationModel->apiPrefix);
        $container->setParameter('web_command.console_path', $consolePath);
        $container->setParameter('web_command.enable_async', $configurationModel->enableAsync);
        $container->setParameter('web_command.default_timeout', $configurationModel->defaultTimeout);
        $container->setParameter('web_command.default_time_limit', $configurationModel->defaultTimeLimit);
        $container->setParameter('web_command.default_memory_limit', $configurationModel->defaultMemoryLimit);

        if (!$configurationModel->enabledDashboardController) {
            $container->removeDefinition(DashboardController::class);
        }
    }

    public function getAlias(): string
    {
        return Configuration::ALIAS;
    }
}
