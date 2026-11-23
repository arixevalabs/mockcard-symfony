<?php

/**
 * The `mockcard` configuration tree.
 *
 * Author: Arixeva Labs <hello@arixevalabs.com>
 */

declare(strict_types=1);

namespace MockCard\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * @return TreeBuilder The configuration tree.
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $tree = new TreeBuilder('mockcard');
        $tree->getRootNode()
            ->children()
                ->scalarNode('api_key')->defaultNull()->info('API key for chaos runs (a CI key is enough).')->end()
                ->scalarNode('project_id')->defaultNull()->end()
                ->scalarNode('probe_secret')->defaultValue('')->info('Production probe secret for the probe guard.')->end()
                ->scalarNode('base_url')->defaultValue('https://www.mockcard.io')->end()
                ->arrayNode('webhook_paths')->scalarPrototype()->end()->defaultValue([])->info('Path prefixes guarded against MockCard probes.')->end()
            ->end();
        return $tree;
    }
}
