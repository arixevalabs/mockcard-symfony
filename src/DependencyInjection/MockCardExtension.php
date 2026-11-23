<?php

/**
 * Registers the MockCard client, chaos runner, probe-guard subscriber and console command.
 *
 * Author: Arixeva Labs <hello@arixevalabs.com>
 */

declare(strict_types=1);

namespace MockCard\Symfony\DependencyInjection;

use MockCard\ChaosRunner;
use MockCard\Client;
use MockCard\Symfony\Command\ChaosCommand;
use MockCard\Symfony\EventSubscriber\ProbeGuardSubscriber;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

final class MockCardExtension extends Extension
{
    /**
     * @param array            $configs   Raw configuration.
     * @param ContainerBuilder $container The container.
     * @return void
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->register(Client::class, Client::class)->setArguments([$config['api_key'], $config['base_url']])->setLazy(true);
        $container->register(ChaosRunner::class, ChaosRunner::class)->setArguments([new Reference(Client::class)])->setLazy(true);
        $container->register(ProbeGuardSubscriber::class, ProbeGuardSubscriber::class)
            ->setArguments([$config['probe_secret'], $config['webhook_paths']])
            ->addTag('kernel.event_subscriber');
        $container->register(ChaosCommand::class, ChaosCommand::class)
            ->setArguments([new Reference(ChaosRunner::class), $config['project_id']])
            ->addTag('console.command');
    }

    /**
     * @return string The configuration key.
     */
    public function getAlias(): string
    {
        return 'mockcard';
    }
}
