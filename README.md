# Amazon FBA Shipping Service (test task)

Implements `App\ShippingServiceInterface::ship(AbstractOrder, BuyerInterface): string` and returns a tracking number.

## How to run
```bash
composer install
vendor/bin/phpunit
```

## Flow (SP-API Outbound)
1. Validate input (order_id, products; buyer email/country_code/address)
2. Create fulfillment order: `POST /fba/outbound/2020-07-01/fulfillmentOrders`
3. Get package tracking: `GET /fba/outbound/2020-07-01/tracking/{id}`
4. Return `trackingNumber`

`HttpFbaClient` prepares both endpoints (no real call, per FAQ). `FbaApiClient` provides deterministic mock.

## Project layout
- `src/` source code (PSR-4 `App\\`)
- `mock/` sample order/buyer JSON
- `tests/` PHPUnit tests

## Notes
- PHP 8.2+, Composer
- Libraries used: `guzzlehttp/guzzle`, `psr/log`
- Exceptions follow interface contract; validation is minimal and focused

