<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGermany\Shipping\Model\Pipeline\CreateShipments\ShipmentRequest\Data;

use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentRequest\PackageAdditionalInterface;

class PackageAdditional implements PackageAdditionalInterface
{
    /**
     * @var int
     */
    private $termsOfTrade;

    public function __construct(int $termsOfTrade)
    {
        $this->termsOfTrade = $termsOfTrade;
    }

    /**
     * Obtain customs terms of trade.
     *
     * @return int
     */
    public function getTermsOfTrade(): int
    {
        return $this->termsOfTrade;
    }

    public function getData(): array
    {
        return get_object_vars($this);
    }
}
