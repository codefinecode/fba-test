<?php

declare(strict_types=1);

namespace App\Service;

use App\Data\AbstractOrder;
use App\Data\BuyerInterface;
use App\Service\Exception\ShippingException;
use App\Service\Exception\TrackingNotFoundException;
use App\ShippingServiceInterface;
use Psr\Log\LoggerInterface;

class AmazonFbaShippingService implements ShippingServiceInterface
{
    private FbaClientInterface $client;
    private ?LoggerInterface $logger;

    public function __construct(FbaClientInterface $client, ?LoggerInterface $logger = null)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function ship(AbstractOrder $order, BuyerInterface $buyer): string
    {
        // Ensure order data is loaded
        $orderData = $order->getData();
        if (empty($orderData)) {
            $order->load();
            $orderData = $order->getData();
        }

        // convert buyer (ArrayAccess) to array
        $buyerData = [];
        foreach ([
            'country_id','country_code','country_code3','shop_username','email','phone','address','data'
        ] as $k) {
            $buyerData[$k] = isset($buyer[$k]) ? $buyer[$k] : null;
        }

        if (empty($orderData)) {
            throw new ShippingException('Order or buyer data is empty');
        }

        // Basic pre-validation to fail fast before client call
        $requiredBuyer = ['country_code','address','email'];
        $missingBuyer = [];
        foreach ($requiredBuyer as $k) {
            if (!isset($buyerData[$k]) || $buyerData[$k] === '') {
                $missingBuyer[] = $k;
            }
        }
        if ($missingBuyer) {
            throw new ShippingException('Buyer data missing required fields: ' . implode(',', $missingBuyer));
        }

        $orderId = $orderData['order_id'] ?? null;
        $productsCount = is_array($orderData['products'] ?? null) ? count($orderData['products']) : 0;

        if ($this->logger) {
            $this->logger->info('FBA ship request prepared', [
                'order_id' => $orderId,
                'products_count' => $productsCount,
                'country_code' => $buyerData['country_code'] ?? null,
            ]);
        }

        try {
            $resp = $this->client->fulfillOrderFromData($orderData, $buyerData);
        } catch (\Throwable $e) {
            if ($this->logger) {
                $this->logger->error('FBA call failed', [
                    'order_id' => $orderId,
                    'error' => $e->getMessage(),
                ]);
            }
            throw new ShippingException('FBA call failed: ' . $e->getMessage(), 0, $e);
        }

        if (empty($resp['trackingNumber'])) {
            if ($this->logger) {
                $this->logger->warning('FBA did not return tracking number', [
                    'order_id' => $orderId,
                ]);
            }
            throw new TrackingNotFoundException('Tracking not returned by FBA');
        }

        if ($this->logger) {
            $this->logger->info('FBA ship succeeded', [
                'order_id' => $orderId,
                'tracking_prefix' => substr((string)$resp['trackingNumber'], 0, 4),
            ]);
        }

        return (string)$resp['trackingNumber'];
    }
}
