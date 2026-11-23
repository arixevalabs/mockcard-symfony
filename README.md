# mockcard/symfony-bundle

MockCard for Symfony: guard your payment webhook routes against MockCard's production probes and run
payment chaos scenarios from the console. Built on `mockcard/mockcard-php`.

Author: Arixeva Labs <hello@arixevalabs.com> · [mockcard.io](https://www.mockcard.io) · MIT

```sh
composer require mockcard/symfony-bundle
```

```yaml
# config/packages/mockcard.yaml
mockcard:
  api_key: '%env(MOCKCARD_API_KEY)%'
  project_id: '%env(MOCKCARD_PROJECT_ID)%'
  probe_secret: '%env(MOCKCARD_PROBE_SECRET)%'
  webhook_paths: ['/webhooks/stripe']
```

Verified MockCard probes to `webhook_paths` get `200` + `MockCard-Probe-Ack: skipped` before routing.

CI:

```sh
bin/console mockcard:chaos http://127.0.0.1:8000/webhooks/stripe --min-score=100
```

In PHPUnit, inject `MockCard\ChaosRunner` and use `MockCard\Testing\AssertsChaosRun` (see mockcard-php).
