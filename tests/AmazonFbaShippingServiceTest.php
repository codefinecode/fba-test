<?php

declare(strict_types=1);

namespace Tests;

use App\Data\AbstractOrder;
use App\Data\BuyerInterface;
use App\Service\AmazonFbaShippingService;
use App\Service\Exception\ApiException;
use App\Service\Exception\ShippingException;
use App\Service\FbaApiClient;
use PHPUnit\Framework\TestCase;

class TestOrder extends AbstractOrder
{
    protected function loadOrderData(int $id): array
    {
        $file = __DIR__ . '/../mock/order.16400.json';
        if (!file_exists($file)) {
            return [];
        }
        return json_decode(file_get_contents($file), true);
    }
}

class TestBuyer implements BuyerInterface
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists((string)$offset, $this->data);
    }

    public function offsetGet(mixed $offset): mixed
    {
        $key = (string)$offset;
        return $this->data[$key] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $key = (string)$offset;
        $this->data[$key] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        $key = (string)$offset;
        unset($this->data[$key]);
    }
}

class AmazonFbaShippingServiceTest extends TestCase
{
    public function testShipReturnsTrackingNumber()
    {
        $mockPath = __DIR__ . '/../mock';
        $client = new FbaApiClient($mockPath);
        $service = new AmazonFbaShippingService($client);

        $order = new TestOrder(16400);
        $buyerRaw = json_decode(file_get_contents($mockPath . '/buyer.29664.json'), true);
        $buyer = new TestBuyer($buyerRaw);

        $tracking = $service->ship($order, $buyer);

        $this->assertIsString($tracking);
        $this->assertStringStartsWith('AMZ-', $tracking);
    }

    public function testShipFailsWhenMockNotFound()
    {
        $this->expectException(ApiException::class);

        // Point client to a non-existent mock directory to trigger missing mock
        $mockPath = __DIR__ . '/../mock_missing';
        $client = new FbaApiClient($mockPath);
        $service = new AmazonFbaShippingService($client);

        $order = new TestOrder(99999); // any id, directory is missing
        $buyerRaw = [
            'country_code' => 'US',
            'address' => '123 Test St',
            'email' => 't@example.com',
        ];
        $buyer = new TestBuyer($buyerRaw);

        $service->ship($order, $buyer);
    }

    public function testShipFailsOnMissingBuyerFields()
    {
        $this->expectException(ShippingException::class);

        $mockPath = __DIR__ . '/../mock';
        $client = new FbaApiClient($mockPath);
        $service = new AmazonFbaShippingService($client);

        $order = new TestOrder(16400);
        $buyerRaw = json_decode(file_get_contents($mockPath . '/buyer.29664.json'), true);
        unset($buyerRaw['email']); // remove required field
        $buyer = new TestBuyer($buyerRaw);

        $service->ship($order, $buyer);
    }

    public function testShipFailsOnEmptyProducts()
    {
        $this->expectException(ApiException::class);

        // Custom order with empty products via subclass override
        $order = new class (16400) extends TestOrder {
            protected function loadOrderData(int $id): array
            {
                $file = __DIR__ . '/../mock/order.16400.json';
                $data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
                $data['products'] = [];
                return $data;
            }
        };

        $mockPath = __DIR__ . '/../mock';
        $client = new FbaApiClient($mockPath);
        $service = new AmazonFbaShippingService($client);

        $buyerRaw = json_decode(file_get_contents($mockPath . '/buyer.29664.json'), true);
        $buyer = new TestBuyer($buyerRaw);

        $service->ship($order, $buyer);
    }
}
