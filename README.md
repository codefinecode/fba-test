# Amazon FBA Shipping Service (mock) - matching original interfaces

Implements `App\ShippingServiceInterface` and the original method signature:
`ship(AbstractOrder $order, BuyerInterface $buyer): string`.

## Project layout
- `src/` - source code (PSR-4 namespace `App\`)
- `mock/` - mock JSON files used by the FBA client (provided original mocks)
- `tests/` - PHPUnit tests that use TestOrder and TestBuyer to satisfy interfaces

## Architecture
- `App\Service\AmazonFbaShippingService` implements `ShippingServiceInterface` (domain service, orchestrates fulfilling).
- `App\Service\FbaApiClient` is a mock adapter (ports & adapters); in real life this would call SP-API.
- `App\Data\AbstractOrder` encapsulates order data and exposes `getData()` after `load()` (no public mutable state).
- `App\Data\BuyerInterface` (given) is treated as `ArrayAccess`; service whitelists required fields into an array DTO.

Key decisions
- Input validation (order & buyer) before API call (fail fast).
- Idempotency-ready design: fulfillment identified by `order_id` (mock client chooses mock file by `order_id` with fallback).
- Optional PSR-3 logging (no PII in logs).

## Requirements
- PHP 8.2+ (works with PHP 8.4)
- Composer

## Run tests (local)
```bash
composer install
vendor/bin/phpunit
```

## Run with Docker
```bash
docker build -t valigara-fba-test .
docker compose run --rm app
```

## Kubernetes (example)
See `k8s-deployment.yaml` for a minimal demo manifest that runs tests via the container command. This is for demonstration only (no hostPath in real clusters).

## Validation & Error handling
- Order validation: requires `order_id` and non-empty `products`.
- Buyer validation: requires `email`, `country_code`, `address`.
- Errors:
  - `ShippingException` for orchestration/validation errors.
  - `TrackingNotFoundException` when no tracking returned.
  - `ApiException` for client-level issues (e.g., missing mock file).

## Logging
- `AmazonFbaShippingService` accepts optional `Psr\Log\LoggerInterface`.
- Logs:
  - `info` on request prepared (order_id, products_count, country_code)
  - `error` on client failure (message only)
  - `warning` when tracking is missing
- No PII is logged.

## Code quality & CI
- Static analysis: PHPStan level 8 (`phpstan.neon`).
- Coding standards: PHP-CS-Fixer (`.php-cs-fixer.php`, PSR-12 + strict types).
- CI: GitHub Actions `.github/workflows/ci.yml` runs validate → install → cs (dry-run) → phpstan → phpunit.

### Run QA tools locally
```bash
vendor/bin/phpstan analyse --no-progress
vendor/bin/php-cs-fixer fix --dry-run --diff
```

## Notes on SP-API integration
- In a real-world scenario, `FbaApiClient` would be replaced with an implementation that calls the Amazon SP-API.
- The `AmazonFbaShippingService` would remain unchanged, as it only depends on the `FbaClientInterface` (port).
- High-level plan:
  - Auth: Login With Amazon (LWA) + AWS STS role assumption, AWS SigV4 signing.
  - Outbound flow: `createFulfillmentOrder` then `getPackageTrackingDetails`.
  - Network: timeouts, retries with exponential backoff, idempotency keys by `order_id`.

## Time spent
~3 hours (including adapting to original interfaces, Dockerfiles, Kubernetes manifest, coding, and tests)

## Author
Yuriy
