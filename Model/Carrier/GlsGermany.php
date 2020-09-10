<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGermany\Shipping\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrierOnline;
use Magento\Shipping\Model\Carrier\CarrierInterface;

class GlsGermany extends AbstractCarrierOnline implements CarrierInterface
{
    const CARRIER_CODE = 'glsgermany';

    /**
     * @var string
     */
    protected $_code = self::CARRIER_CODE;

    public function collectRates(RateRequest $request)
    {
        // TODO: Implement collectRates() method.
        return new \LogicException('Not implemented yet.');
    }

    protected function _doShipmentRequest(\Magento\Framework\DataObject $request)
    {
        // TODO: Implement _doShipmentRequest() method.
        return new \LogicException('Not implemented yet.');
    }

    public function getAllowedMethods()
    {
        // TODO: Implement getAllowedMethods() method.
        return new \LogicException('Not implemented yet.');
    }
}
