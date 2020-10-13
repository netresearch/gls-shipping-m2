<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGermany\Shipping\Model\Pipeline\CreateShipments\ShipmentRequest;

use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Shipping\Model\Shipment\Request;
use Netresearch\ShippingCore\Api\Pipeline\ShipmentRequest\RequestModifierInterface;

class RequestModifier implements RequestModifierInterface
{
    /**
     * @var RequestModifierInterface
     */
    private $coreModifier;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    public function __construct(
        RequestModifierInterface $coreModifier,
        DataObjectFactory $dataObjectFactory
    ) {
        $this->coreModifier = $coreModifier;
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * Add shipment request data using given shipment.
     *
     * The request modifier collects all additional data from defaults (config, product attributes)
     * during bulk label creation where no user input (packaging popup) is involved.
     *
     * @param Request $shipmentRequest
     */
    public function modify(Request $shipmentRequest): void
    {
        $this->coreModifier->modify($shipmentRequest);
    }
}
