<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGroup\Shipping\Model\ShippingSettings\TypeProcessor\ShippingOptions;

use GlsGroup\Shipping\Model\ShippingSettings\ShippingOption\Codes;
use Magento\Sales\Api\Data\ShipmentInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOptionInterface;
use Netresearch\ShippingCore\Api\ShippingSettings\TypeProcessor\ShippingOptionsProcessorInterface;
use Netresearch\ShippingCore\Api\Util\DeliveryAreaInterface;

class ServiceAvailabilityProcessor implements ShippingOptionsProcessorInterface
{
    /**
     * @var DeliveryAreaInterface
     */
    private $deliveryArea;

    public function __construct(DeliveryAreaInterface $deliveryArea)
    {
        $this->deliveryArea = $deliveryArea;
    }

    /**
     * In addition to the shipping routes, perform area-based availability checks.
     *
     * Some services may not be available in certain areas. Addresses on islands
     * for instance do not apply for express delivery etc.
     *
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
        if (!$this->deliveryArea->isIsland($countryCode, $postalCode)) {
            return $shippingOptions;
        }

        // GLS services unavailable on islands
        $services = [
            Codes::CHECKOUT_SERVICE_DEPOSIT,
            Codes::CHECKOUT_SERVICE_FLEX_DELIVERY,
            Codes::CHECKOUT_SERVICE_GUARANTEED24,
            Codes::PACKAGING_SERVICE_LETTERBOX,
            Codes::PACKAGING_SERVICE_SHOP_RETURN
        ];

        return array_filter(
            $shippingOptions,
            function (ShippingOptionInterface $shippingOption) use ($services) {
                return !in_array($shippingOption->getCode(), $services, true);
            }
        );
    }
}
