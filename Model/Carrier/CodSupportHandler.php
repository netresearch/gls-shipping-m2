<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGermany\Shipping\Model\Carrier;

use Magento\Quote\Model\Quote;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\Selection\AssignedSelectionInterface;
use Netresearch\ShippingCore\Api\PaymentMethod\MethodAvailabilityInterface;
use Netresearch\ShippingCore\Api\ShippingSettings\CodSelectorInterface;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Codes;

class CodSupportHandler implements MethodAvailabilityInterface, CodSelectorInterface
{
    public function isAvailable(Quote $quote): bool
    {
        if (!\in_array($quote->getShippingAddress()->getCountryId(), ['DE', 'AT'])) {
            return false;
        }

        if ($quote->getBaseGrandTotal() > 2500) {
            return false;
        }

        return true;
    }

    public function assignCodSelection(AssignedSelectionInterface $selection)
    {
        $selection->setShippingOptionCode(Codes::SERVICE_OPTION_CASH_ON_DELIVERY);
        $selection->setInputCode('enabled');
        $selection->setInputValue((string) true);
    }
}
