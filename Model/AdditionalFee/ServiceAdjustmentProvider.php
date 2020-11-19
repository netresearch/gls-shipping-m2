<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGroup\Shipping\Model\AdditionalFee;

use GlsGroup\Shipping\Model\Config\ModuleConfig;
use GlsGroup\Shipping\Model\ShippingSettings\ShippingOption\Codes;

class ServiceAdjustmentProvider
{
    /**
     * @var ModuleConfig
     */
    private $config;

    public function __construct(ModuleConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Obtain all configured service adjustment amounts, indexed by shipping option code.
     *
     * @param mixed $store
     * @return float[]
     */
    public function getAmounts($store = null): array
    {
        $amounts = [
            Codes::CHECKOUT_SERVICE_FLEX_DELIVERY => $this->config->getFlexDeliveryAdjustment($store),
            Codes::CHECKOUT_SERVICE_DEPOSIT => $this->config->getDepositAdjustment($store),
            Codes::CHECKOUT_SERVICE_GUARANTEED24 => $this->config->getG24Adjustment($store),
        ];

        return array_filter($amounts);
    }
}
