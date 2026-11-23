<?php

/**
 * Tests for the Symfony bundle: container wiring from configuration and the probe-guard subscriber
 * on real HttpKernel request events.
 *
 * Author: Arixeva Labs <hello@arixevalabs.com>
 */

declare(strict_types=1);

namespace MockCard\Symfony\Tests;

use MockCard\ChaosRunner;
use MockCard\Symfony\Command\ChaosCommand;
use MockCard\Symfony\DependencyInjection\MockCardExtension;
use MockCard\Symfony\EventSubscriber\ProbeGuardSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class BundleTest extends TestCase
{
    private const SECRET = 'mcp_symfony_test';

    /**
     * Dispatches a request through the subscriber.
     *
     * @param Request $request The request.
     * @return RequestEvent The event (with a response if the probe was acknowledged).
     */
    private function guard(Request $request): RequestEvent
    {
        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
        (new ProbeGuardSubscriber(self::SECRET, ['/webhooks']))->onRequest($event);
        return $event;
    }

    /**
     * A POST with probe headers.
     *
     * @param string $path      The path.
     * @param string $body      The body.
     * @param int    $timestamp Signature timestamp.
     * @return Request The request.
     */
    private function probe(string $path, string $body, int $timestamp): Request
    {
        $sig = "t=$timestamp,v1=" . hash_hmac('sha256', "$timestamp.$body", self::SECRET);
        return Request::create($path, 'POST', server: ['HTTP_MOCKCARD_PROBE' => '1', 'HTTP_MOCKCARD_SIGNATURE' => $sig], content: $body);
    }

    public function testVerifiedProbeOnAGuardedPathIsAcknowledged(): void
    {
        $event = $this->guard($this->probe('/webhooks/stripe', '{"a":1}', time()));
        self::assertSame('skipped', $event->getResponse()?->headers->get('MockCard-Probe-Ack'));
    }

    public function testForgedProbesAndUnguardedPathsPassThrough(): void
    {
        self::assertNull($this->guard($this->probe('/webhooks/stripe', '{}', time() - 3600))->getResponse());
        self::assertNull($this->guard($this->probe('/checkout', '{}', time()))->getResponse());
        self::assertNull($this->guard(Request::create('/webhooks/stripe', 'POST', content: '{}'))->getResponse());
    }

    public function testExtensionRegistersServicesFromConfiguration(): void
    {
        $container = new ContainerBuilder();
        (new MockCardExtension())->load([['api_key' => 'mc_test', 'probe_secret' => self::SECRET, 'webhook_paths' => ['/webhooks']]], $container);
        self::assertTrue($container->hasDefinition(ChaosRunner::class));
        self::assertTrue($container->getDefinition(ProbeGuardSubscriber::class)->hasTag('kernel.event_subscriber'));
        self::assertTrue($container->getDefinition(ChaosCommand::class)->hasTag('console.command'));
        self::assertSame([self::SECRET, ['/webhooks']], $container->getDefinition(ProbeGuardSubscriber::class)->getArguments());
    }
}
