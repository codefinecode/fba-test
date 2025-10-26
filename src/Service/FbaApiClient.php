<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Exception\ApiException;

class FbaApiClient implements FbaClientInterface
{
    private string $mockPath;
    /**
     * @var array<string, string> orderId => trackingNumber
     */
    private static array $idempotencyCache = [];

    public function __construct(string $mockPath)
    {
        $this->mockPath = rtrim($mockPath, DIRECTORY_SEPARATOR);
    }

    /**
     * Emulate fulfill order call using arrays of order and buyer data.
     *
     * @param array<string, mixed> $orderData
     * @param array<string, mixed> $buyerData
     * @return array<string, mixed>
     * @throws ApiException
     */
    public function fulfillOrderFromData(array $orderData, array $buyerData): array
    {
        // Basic order validation
        $orderId = (string)($orderData['order_id'] ?? '');
        if ($orderId === '') {
            throw new ApiException('Order payload is missing required field: order_id');
        }
        if (empty($orderData['products']) || !is_array($orderData['products'])) {
            throw new ApiException('Order payload is missing products');
        }

        // Basic buyer validation (minimal and non-PII leaking)
        $requiredBuyerFields = ['country_code', 'address', 'email'];
        $missing = [];
        foreach ($requiredBuyerFields as $field) {
            if (!isset($buyerData[$field]) || $buyerData[$field] === '') {
                $missing[] = $field;
            }
        }
        if ($missing) {
            throw new ApiException('Buyer payload is missing required fields: ' . implode(',', $missing));
        }

        // Choose mock file by order id when available; fallback to legacy file name for compatibility
        $candidate = $this->mockPath . DIRECTORY_SEPARATOR . 'order.' . $orderId . '.json';
        $mockFile = file_exists($candidate)
            ? $candidate
            : ($this->mockPath . DIRECTORY_SEPARATOR . 'order.16400.json');

        if (!file_exists($mockFile)) {
            throw new ApiException('Mock file not found: ' . $mockFile);
        }

        // idempotency: same orderId returns same tracking within process
        if (!isset(self::$idempotencyCache[$orderId])) {
            self::$idempotencyCache[$orderId] = 'AMZ-' . strtoupper(bin2hex(random_bytes(4)));
        }
        $tracking = self::$idempotencyCache[$orderId];

        return [
            'status' => 'SUCCESS',
            'trackingNumber' => $tracking,
            'orderId' => $orderId,
        ];
    }
}
