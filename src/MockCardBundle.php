<?php

/**
 * MockCard bundle for Symfony. Configure in config/packages/mockcard.yaml:
 *
 *   mockcard:
 *     api_key: '%env(MOCKCARD_API_KEY)%'
 *     probe_secret: '%env(MOCKCARD_PROBE_SECRET)%'
 *     webhook_paths: ['/webhooks/stripe']
 *
 * Author: Arixeva Labs <hello@arixevalabs.com>
 */

declare(strict_types=1);

namespace MockCard\Symfony;

use Symfony\Component\HttpKernel\Bundle\Bundle;

final class MockCardBundle extends Bundle
{
}
