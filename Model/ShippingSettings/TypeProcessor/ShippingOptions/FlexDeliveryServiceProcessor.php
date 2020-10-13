<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGermany\Shipping\Model\ShippingSettings\TypeProcessor\ShippingOptions;

use GlsGermany\Shipping\Model\Config\ModuleConfig;
use GlsGermany\Shipping\Model\ShippingSettings\ShippingOption\Codes;
use Magento\Sales\Api\Data\ShipmentInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOptionInterface;
use Netresearch\ShippingCore\Api\ShippingSettings\TypeProcessor\ShippingOptionsProcessorInterface;

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
     * @param ShippingOptionInterface[] $shippingOptions
     * @param int $storeId
     * @param string $countryCode Destination country code
     * @param string $postalCode Destination postal code
     * @param ShipmentInterface|null $shipment
     *
     * @return ShippingOptionInterface[]
     */
    public function process(
        array $shippingOptions,
        int $storeId,
        string $countryCode,
        string $postalCode,
        ShipmentInterface $shipment = null
    ): array {
        $flexDeliveryOption = $shippingOptions[Codes::CHECKOUT_SERVICE_FLEX_DELIVERY] ?? false;
        if (!$flexDeliveryOption) {
            return $shippingOptions;
        }

        $isEnabledInput = $flexDeliveryOption->getInputs()['enabled'] ?? false;
        if (!$isEnabledInput) {
            return $shippingOptions;
        }

        $comment = $isEnabledInput->getComment();
        if (!$comment) {
            return $shippingOptions;
        }

        $commentText = $comment->getContent();
        $email = $this->config->getFlexDeliveryRevocationEmail($storeId);
        $comment->setContent(__($commentText, $email)->render());

        return $shippingOptions;
    }
}
