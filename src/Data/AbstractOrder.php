<?php

declare(strict_types=1);

namespace App\Data;

abstract class AbstractOrder
{
    private int $id;
    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    /**
     * @return array<string, mixed>
     */
    abstract protected function loadOrderData(int $id): array;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    final public function getOrderId(): int
    {
        return $this->id;
    }

    final public function load(): void
    {
        /** @return array<string, mixed> $data */
        $this->data = $this->loadOrderData($this->getOrderId());
    }

    /**
     * @return array<string, mixed>
     */
    final public function getData(): array
    {
        return $this->data;
    }

}
