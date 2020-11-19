<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGroup\Shipping\Model\AdditionalFee;

use GlsGroup\Shipping\Model\Carrier\GlsGroup;
use GlsGroup\Shipping\Model\Config\ModuleConfig;
use GlsGroup\Shipping\Model\ShippingSettings\ShippingOption\Codes;
use Magento\Framework\Phrase;
use Magento\Quote\Model\Quote;
use Netresearch\ShippingCore\Api\AdditionalFee\AdditionalFeeConfigurationInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\Selection\AssignedSelectionInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\Selection\SelectionInterface;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Selection\QuoteSelectionManager;

class ServiceAdjustmentConfiguration implements AdditionalFeeConfigurationInterface
{
    /**
     * @var QuoteSelectionManager
     */
    private $quoteSelectionManager;

    /**
     * @var ModuleConfig
     */
    private $config;

    /**
     * @var float
     */
    private $serviceAdjustment;

    public function __construct(QuoteSelectionManager $quoteSelectionManager, ModuleConfig $config)
    {
        $this->quoteSelectionManager = $quoteSelectionManager;
        $this->config = $config;
    }

    private function calculateAdjustmentAmount(Quote $quote): float
    {
        if (is_float($this->serviceAdjustment)) {
            return $this->serviceAdjustment;
        }

        $this->serviceAdjustment = 0;
        $fees = [
            Codes::CHECKOUT_SERVICE_FLEX_DELIVERY => $this->config->getFlexDeliveryAdjustment($quote->getStoreId()),
            Codes::CHECKOUT_SERVICE_DEPOSIT => $this->config->getDepositAdjustment($quote->getStoreId()),
            Codes::CHECKOUT_SERVICE_GUARANTEED24 => $this->config->getG24Adjustment($quote->getStoreId()),
        ];

        $selections = $this->quoteSelectionManager->load((int) $quote->getShippingAddress()->getId());
        $serviceCodes = array_unique(
            array_map(
                function (SelectionInterface $selection) {
                    return $selection->getShippingOptionCode();
                },
                $selections
            )
        );

        foreach ($serviceCodes as $serviceCode) {
            $this->serviceAdjustment += $fees[$serviceCode] ?? 0;
        }

        return $this->serviceAdjustment;
    }

    /**
     * @return string
     */
    public function getCarrierCode(): string
    {
        return GlsGroup::CARRIER_CODE;
    }

    /**
     * @return Phrase
     */
    public function getLabel(): Phrase
    {
        return __('Shipping Service Adjustment');
    }

    /**
     * @param Quote $quote
     * @return bool
     */
    public function isActive(Quote $quote): bool
    {
        $adjustment = $this->calculateAdjustmentAmount($quote);
        return !empty($adjustment);
    }

    /**
     * @param Quote $quote
     * @return float
     */
    public function getServiceCharge(Quote $quote): float
    {
        return $this->calculateAdjustmentAmount($quote);
    }
}
