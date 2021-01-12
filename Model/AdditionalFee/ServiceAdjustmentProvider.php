<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGroup\Shipping\Model\AdditionalFee;

use GlsGroup\Shipping\Model\Config\ModuleConfig;
use GlsGroup\Shipping\Model\ShippingSettings\ShippingOption\Codes;
use Netresearch\ShippingCore\Api\AdditionalFee\AdditionalFeeProviderInterface;

class ServiceAdjustmentProvider implements AdditionalFeeProviderInterface
{
    /**
     * @var ModuleConfig
     */
    private $config;

    public function __construct(ModuleConfig $config)
    {
        $this->config = $config;
    }

    public function getAmounts(int $storeId): array
    {
        $amounts = [
            Codes::SERVICE_OPTION_FLEX_DELIVERY => $this->config->getFlexDeliveryAdjustment($storeId),
            Codes::SERVICE_OPTION_DEPOSIT => $this->config->getDepositAdjustment($storeId),
            Codes::SERVICE_OPTION_GUARANTEED24 => $this->config->getG24Adjustment($storeId),
        ];

        return array_filter($amounts);
    }
}
