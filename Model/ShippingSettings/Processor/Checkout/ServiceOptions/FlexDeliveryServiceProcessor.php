<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGermany\Shipping\Model\ShippingSettings\Processor\Checkout\ServiceOptions;

use GlsGermany\Shipping\Model\Config\ModuleConfig;
use GlsGermany\Shipping\Model\ShippingSettings\ShippingOption\Codes;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOptionInterface;
use Netresearch\ShippingCore\Api\ShippingSettings\Processor\Checkout\ShippingOptionsProcessorInterface;

class FlexDeliveryServiceProcessor implements ShippingOptionsProcessorInterface
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
     * @param ShippingOptionInterface[] $optionsData
     * @param string $countryCode Destination country code
     * @param string $postalCode Destination postal code
     * @param int|null $storeId
     *
     * @return ShippingOptionInterface[]
     */
    public function process(
        array $optionsData,
        string $countryCode,
        string $postalCode,
        int $storeId = null
    ): array {
        $flexDeliveryOption = $optionsData[Codes::CHECKOUT_SERVICE_FLEX_DELIVERY] ?? false;
        if (!$flexDeliveryOption) {
            return $optionsData;
        }

        $isEnabledInput = $flexDeliveryOption->getInputs()['enabled'] ?? false;
        if (!$isEnabledInput) {
            return $optionsData;
        }

        $comment = $isEnabledInput->getComment();
        if (!$comment) {
            return $optionsData;
        }

        $commentText = $comment->getContent();
        $email = $this->config->getFlexDeliveryRevocationEmail($storeId);
        $comment->setContent(__($commentText, $email)->render());

        return $optionsData;
    }
}
