<?php

declare(strict_types=1);

namespace App\Data;

use ArrayAccess;

/**
 * @property int $country_id
 * @property string $country_code
 * @property string $country_code3
 * @property string $name
 * @property string $shop_username
 * @property string $email
 * @property string $phone
 * @property string $address
 * @property array $data
 *
 * @extends ArrayAccess<string, mixed>
 */
interface BuyerInterface extends ArrayAccess
{
}
