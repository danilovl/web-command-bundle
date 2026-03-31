<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const string ALIAS = 'danilovl_web_command';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ALIAS);
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('api_prefix')
                    ->defaultValue('/danilovl/web-command/api')
                ->end()
                ->scalarNode('console_path')
                    ->defaultNull()
                ->end()
                ->booleanNode('enable_async')
                    ->defaultFalse()
                ->end()
                ->integerNode('default_timeout')
                    ->defaultNull()
                ->end()
                ->integerNode('default_time_limit')
                    ->defaultNull()
                ->end()
                ->scalarNode('default_memory_limit')
                    ->defaultNull()
                ->end()
                ->booleanNode('enabled_admin_dashboard')
                    ->defaultFalse()
                ->end()
                ->booleanNode('enabled_dashboard_live_status')
                    ->defaultTrue()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
