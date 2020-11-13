<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGroup\Shipping\Model\ShippingSettings\TypeProcessor\ShippingOptions;

use GlsGroup\Shipping\Model\Config\Source\TermsOfTrade;
use GlsGroup\Shipping\Model\ShippingSettings\ShippingOption\Codes;
use Magento\Sales\Api\Data\ShipmentInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\OptionInterfaceFactory;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOptionInterface;
use Netresearch\ShippingCore\Api\ShippingSettings\TypeProcessor\ShippingOptionsProcessorInterface;

class InputDataProcessor implements ShippingOptionsProcessorInterface
{
    /**
     * @var TermsOfTrade
     */
    private $termsOfTrade;

    /**
     * @var OptionInterfaceFactory
     */
    private $optionFactory;

    public function __construct(TermsOfTrade $termsOfTrade, OptionInterfaceFactory $optionFactory)
    {
        $this->termsOfTrade = $termsOfTrade;
        $this->optionFactory = $optionFactory;
    }

    /**
     * Set options and values to inputs on package level.
     *
     * @param ShippingOptionInterface $shippingOption
     * @param ShipmentInterface $shipment
     */
    private function processInputs(ShippingOptionInterface $shippingOption, ShipmentInterface $shipment)
    {
        foreach ($shippingOption->getInputs() as $input) {
            switch ($input->getCode()) {
                case Codes::PACKAGING_INPUT_TERMS_OF_TRADE:
                    $fnCreateOptions = function (array $optionArray) {
                        $option = $this->optionFactory->create();
                        $option->setValue((string) $optionArray['value']);
                        $option->setLabel((string) $optionArray['label']);
                        return $option;
                    };

                    $input->setOptions(array_map($fnCreateOptions, $this->termsOfTrade->toOptionArray()));
                    break;
            }
        }
    }

    /**
     * @param ShippingOptionInterface[] $shippingOptions
     * @param int $storeId
     * @param string $countryCode
     * @param string $postalCode
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
        if (!$shipment) {
            return $shippingOptions;
        }

        foreach ($shippingOptions as $shippingOption) {
            $this->processInputs($shippingOption, $shipment);
        }

        return $shippingOptions;
    }
}
