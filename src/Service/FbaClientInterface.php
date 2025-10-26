<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Client port for FBA operations (mock or real implementation).
 */
interface FbaClientInterface
{
    /**
     * @param array<string, mixed> $orderData
     * @param array<string, mixed> $buyerData
     * @return array<string, mixed>
     */
    public function fulfillOrderFromData(array $orderData, array $buyerData): array;
}
