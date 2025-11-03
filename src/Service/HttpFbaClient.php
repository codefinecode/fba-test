<?php
declare(strict_types=1);

namespace App\Service;

use App\Service\Exception\ApiException;
use GuzzleHttp\ClientInterface;

/**
 * Demonstration HTTP-based FBA client adapter using Guzzle.
 * Note: This is a showcase for how a real SP-API call could be prepared.
 * Actual SP-API auth (LWA/STS/SigV4) is deliberately omitted in this test task.
 */
class HttpFbaClient implements FbaClientInterface
{
    private ClientInterface $http;
    private string $baseUri;

    public function __construct(ClientInterface $http, string $baseUri)
    {
        $this->http = $http;
        $this->baseUri = rtrim($baseUri, '/');
    }

    /**
     * @param array<string, mixed> $orderData
     * @param array<string, mixed> $buyerData
     * @return array<string, mixed>
     */
    public function fulfillOrderFromData(array $orderData, array $buyerData): array
    {
        $orderId = (string)($orderData['order_id'] ?? '');
        if ($orderId === '') {
            throw new ApiException('Order payload is missing required field: order_id');
        }
        if (empty($orderData['products']) || !is_array($orderData['products'])) {
            throw new ApiException('Order payload is missing products');
        }
        foreach (['country_code','address','email'] as $f) {
            if (!isset($buyerData[$f]) || $buyerData[$f] === '') {
                throw new ApiException('Buyer payload is missing required field: ' . $f);
            }
        }

        // Build minimal payload resembling SP-API createFulfillmentOrder
        $items = [];
        foreach ($orderData['products'] as $p) {
            $sku = (string)($p['sku'] ?? $p['product_code'] ?? '');
            $qty = (int)($p['ammount'] ?? 0);
            if ($sku === '' || $qty <= 0) {
                continue;
            }
            $items[] = [
                'sellerSku' => $sku,
                'quantity' => $qty,
            ];
        }
        if (!$items) {
            throw new ApiException('Order has no shippable items');
        }

        $payload = [
            'sellerFulfillmentOrderId' => $orderId,
            'displayableOrderId' => $orderId,
            'destinationAddress' => [
                'name' => (string)($orderData['buyer_name'] ?? 'Buyer'),
                'addressLine1' => (string)($orderData['shipping_street'] ?? ''),
                'countryCode' => (string)($orderData['shipping_country'] ?? $buyerData['country_code']),
                'stateOrRegion' => (string)($orderData['shipping_state'] ?? ''),
                'city' => (string)($orderData['shipping_city'] ?? ''),
                'postalCode' => (string)($orderData['shipping_zip'] ?? ''),
                'phone' => (string)($buyerData['phone'] ?? ''),
            ],
            'items' => $items,
            // Other fields like shipping speed/category would go here
        ];

        // Showcase: how a request would be prepared (NOT executed here)
        $requestOptions = [
            'json' => $payload,
            'headers' => [
                'accept' => 'application/json',
                'content-type' => 'application/json',
                // 'authorization' => 'Bearer <LWA-ACCESS-TOKEN>', // TODO: LWA/STS + SigV4
            ],
            'timeout' => 5.0,
        ];

        // Example endpoints for SP-API Outbound v2020-07-01 (for illustration only)
        $createEndpoint = $this->baseUri . '/fba/outbound/2020-07-01/fulfillmentOrders';
        $trackingEndpoint = $this->buildTrackingEndpoint($orderId);

        // Intentionally NOT performing the HTTP call in test task
        // $response = $this->http->request('POST', $endpoint, $requestOptions);
        // Parse response and extract tracking with a subsequent call to package tracking endpoint.

        // Return prepared endpoints to make two-step flow explicit; tracking is mocked
        return [
            'status' => 'MOCKED_HTTP',
            'trackingNumber' => 'AMZ-' . strtoupper(bin2hex(random_bytes(4))),
            'orderId' => $orderId,
            'preparedCreateEndpoint' => $createEndpoint,
            'preparedTrackingEndpoint' => $trackingEndpoint,
            'requestOptions' => [
                'headers' => $requestOptions['headers'],
            ],
        ];
    }

    /**
     * Build example package tracking endpoint URL for the given order id.
     */
    private function buildTrackingEndpoint(string $orderId): string
    {
        // In real SP-API, you would call getPackageTrackingDetails with a package id; for demo we key by order id
        return $this->baseUri . '/fba/outbound/2020-07-01/tracking/' . rawurlencode($orderId);
    }
}
