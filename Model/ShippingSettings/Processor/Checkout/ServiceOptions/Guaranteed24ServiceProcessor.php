<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGermany\Shipping\Model\ShippingSettings\Processor\Checkout\ServiceOptions;

use GlsGermany\Shipping\Model\ShippingSettings\ShippingOption\Codes;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOptionInterface;
use Netresearch\ShippingCore\Api\ShippingSettings\Processor\Checkout\ShippingOptionsProcessorInterface;
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
        $guaranteed24Option = $optionsData[Codes::CHECKOUT_SERVICE_GUARANTEED24] ?? false;
        if (!$guaranteed24Option) {
            return $optionsData;
        }

        $isEnabledInput = $guaranteed24Option->getInputs()['enabled'] ?? false;
        if (!$isEnabledInput) {
            return $optionsData;
        }

        $comment = $isEnabledInput->getComment();
        if (!$comment) {
            return $optionsData;
        }

        $commentText = $comment->getContent();
        $formattedTime = $this->localeDate->formatDateTime(
            $this->config->getCutOffTime($storeId),
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::SHORT
        );
        $comment->setContent(__($commentText, $formattedTime)->render());

        return $optionsData;
    }
}
