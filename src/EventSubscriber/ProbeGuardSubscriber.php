<?php

/**
 * Answers verified MockCard production probes to the configured webhook paths with 200 +
 * `MockCard-Probe-Ack: skipped` before any controller runs. Forged probes continue to your normal
 * handling, so the header can never make your app process anything it otherwise wouldn't.
 *
 * Author: Arixeva Labs <hello@arixevalabs.com>
 */

declare(strict_types=1);

namespace MockCard\Symfony\EventSubscriber;

use MockCard\Probe;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ProbeGuardSubscriber implements EventSubscriberInterface
{
    /**
     * @param string   $probeSecret  The project's probe secret.
     * @param string[] $webhookPaths Guarded path prefixes.
     */
    public function __construct(private readonly string $probeSecret, private readonly array $webhookPaths)
    {
    }

    /**
     * @return array<string, array{string, int}> Runs before routing (priority 256).
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onRequest', 256]];
    }

    /**
     * Acknowledges a verified probe.
     *
     * @param RequestEvent $event The request event.
     * @return void
     */
    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest() || !$request->isMethod('POST')) {
            return;
        }
        $path = $request->getPathInfo();
        if (!array_filter($this->webhookPaths, static fn (string $p) => str_starts_with($path, $p))) {
            return;
        }
        $headers = array_map(static fn ($v) => $v[0] ?? '', $request->headers->all());
        if (Probe::isProbe($headers) && Probe::verify($headers, $request->getContent(), $this->probeSecret)) {
            $event->setResponse(new JsonResponse(['received' => true, 'probe' => true], 200, [Probe::ACK_HEADER => Probe::ACK_VALUE]));
        }
    }
}
