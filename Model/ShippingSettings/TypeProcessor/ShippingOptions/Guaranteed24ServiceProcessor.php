<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGermany\Shipping\Model\ShippingSettings\TypeProcessor\ShippingOptions;

use GlsGermany\Shipping\Model\Config\ModuleConfig;
use GlsGermany\Shipping\Model\ShippingSettings\ShippingOption\Codes;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOptionInterface;
use Netresearch\ShippingCore\Api\ShippingSettings\TypeProcessor\ShippingOptionsProcessorInterface;

class Guaranteed24ServiceProcessor implements ShippingOptionsProcessorInterface
{
    /**
     * @var ModuleConfig
     */
    private $config;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    public function __construct(
        ModuleConfig $config,
        TimezoneInterface $timezone
    ) {
        $this->config = $config;
        $this->timezone = $timezone;
    }

    /**
     * Remove the G24 service after cut-off time expired.
     *
     * If no handover to the carrier happens anymore at the day of checkout, the service must not be booked.
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
        $guaranteed24Option = $shippingOptions[Codes::CHECKOUT_SERVICE_GUARANTEED24] ?? false;
        if (!$guaranteed24Option) {
            // service not available for selection, nothing to modify.
            return $shippingOptions;
        }

        $currentDateTime =  $this->timezone->scopeDate($storeId, null, true);
        $weekDay = $currentDateTime->format('N');
        $shipmentDates = $this->config->getCutOffTimes($storeId);

        if (!isset($shipmentDates[$weekDay]) || $currentDateTime > $shipmentDates[$weekDay]) {
            unset($shippingOptions[Codes::CHECKOUT_SERVICE_GUARANTEED24]);
        }

        return $shippingOptions;
    }
}
