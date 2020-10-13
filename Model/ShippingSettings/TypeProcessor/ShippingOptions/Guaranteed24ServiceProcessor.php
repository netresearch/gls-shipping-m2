<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGermany\Shipping\Model\ShippingSettings\TypeProcessor\ShippingOptions;

use GlsGermany\Shipping\Model\ShippingSettings\ShippingOption\Codes;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOptionInterface;
use Netresearch\ShippingCore\Api\ShippingSettings\TypeProcessor\ShippingOptionsProcessorInterface;
use Netresearch\ShippingCore\Model\Config\ParcelProcessingConfig;

class Guaranteed24ServiceProcessor implements ShippingOptionsProcessorInterface
{
    /**
     * @var ParcelProcessingConfig
     */
    private $config;

    /**
     * @var TimezoneInterface
     */
    protected $localeDate;

    public function __construct(ParcelProcessingConfig $config, TimezoneInterface $localeDate)
    {
        $this->config = $config;
        $this->localeDate = $localeDate;
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
        $guaranteed24Option = $shippingOptions[Codes::CHECKOUT_SERVICE_GUARANTEED24] ?? false;
        if (!$guaranteed24Option) {
            return $shippingOptions;
        }

        $isEnabledInput = $guaranteed24Option->getInputs()['enabled'] ?? false;
        if (!$isEnabledInput) {
            return $shippingOptions;
        }

        $comment = $isEnabledInput->getComment();
        if (!$comment) {
            return $shippingOptions;
        }

        $commentText = $comment->getContent();
        $formattedTime = $this->localeDate->formatDateTime(
            $this->config->getCutOffTime($storeId),
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::SHORT
        );
        $comment->setContent(__($commentText, $formattedTime)->render());

        return $shippingOptions;
    }
}
