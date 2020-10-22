<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGermany\Shipping\Test\Integration\TestDouble;

use GlsGermany\Sdk\ParcelProcessing\Api\Data\ShipmentInterface;
use GlsGermany\Sdk\ParcelProcessing\Api\ShipmentServiceInterface;
use GlsGermany\Sdk\ParcelProcessing\Exception\ServiceException;
use GlsGermany\Shipping\Test\Integration\TestDouble\Pipeline\CreateShipments\Stage\SendRequestStageStub as CreationStage;
use GlsGermany\Shipping\Test\Integration\TestDouble\Pipeline\DeleteShipments\Stage\SendRequestStageStub as CancellationStage;

/**
 * Return responses on webservice calls which can be predefined via artifacts containers.
 */
class ShipmentServiceStub implements ShipmentServiceInterface
{
    /**
     * @var CreationStage
     */
    private $createShipmentsStage;

    /**
     * @var CancellationStage
     */
    private $deleteShipmentsStage;

    /**
     * ShipmentServiceStub constructor.
     *
     * @param CreationStage $createShipmentsStage
     * @param CancellationStage $deleteShipmentsStage
     */
    public function __construct(
        CreationStage $createShipmentsStage,
        CancellationStage $deleteShipmentsStage
    ) {
        $this->createShipmentsStage = $createShipmentsStage;
        $this->deleteShipmentsStage = $deleteShipmentsStage;
    }

    /**
     * Return a fake web service response pre-defined via CreateShipmentsStageInterface
     *
     * @param \JsonSerializable $shipmentRequest
     * @return ShipmentInterface
     * @throws ServiceException
     * @see \GlsGermany\Shipping\Test\Integration\TestDouble\Pipeline\CreateShipments\Stage\SendRequestStageStub
     */
    public function createShipment(\JsonSerializable $shipmentRequest): ShipmentInterface
    {
        $callback = $this->createShipmentsStage->responseCallback;
        if (is_callable($callback)) {
            // created shipments or exception
            $response = $callback($this->createShipmentsStage);
            if ($response instanceof ServiceException) {
                throw $response;
            }

            if (is_array($response)) {
                return $response[0];
            }
        }

        // response callback not defined or empty, return default response.
        return array_shift($this->createShipmentsStage->apiResponses);
    }
}
