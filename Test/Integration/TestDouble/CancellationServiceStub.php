<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGroup\Shipping\Test\Integration\TestDouble;

use GlsGroup\Sdk\ParcelProcessing\Api\CancellationServiceInterface;
use GlsGroup\Sdk\ParcelProcessing\Exception\ServiceException;
use GlsGroup\Shipping\Test\Integration\TestDouble\Pipeline\DeleteShipments\Stage\SendRequestStageStub;

/**
 * Return responses on webservice calls which can be predefined via artifacts containers.
 */
class CancellationServiceStub implements CancellationServiceInterface
{
    /**
     * @var SendRequestStageStub
     */
    private $cancelParcelsStage;

    public function __construct(SendRequestStageStub $cancelParcelsStage)
    {
        $this->cancelParcelsStage = $cancelParcelsStage;
    }

    /**
     * Return a fake web service response pre-defined via RequestTracksStageInterface
     *
     * @param string[] $parcelIds
     * @return string[]
     * @throws ServiceException
     * @see \GlsGroup\Shipping\Test\Integration\TestDouble\Pipeline\DeleteShipments\Stage\SendRequestStageStub
     *
     */
    public function cancelParcels(array $parcelIds): array
    {
        $callback = $this->cancelParcelsStage->responseCallback;
        if (is_callable($callback)) {
            // cancelled shipment numbers or exception
            $response = $callback($this->cancelParcelsStage);
            if ($response instanceof ServiceException) {
                throw $response;
            }

            if (is_array($response)) {
                return $response;
            }
        }

        // response callback not defined or empty, return default response.
        return $this->cancelParcelsStage->apiResponses;
    }

    /**
     * Return a fake web service response pre-defined via RequestTracksStageInterface
     *
     * @param string $parcelId
     * @return string
     * @throws ServiceException
     * @see \GlsGroup\Shipping\Test\Integration\TestDouble\Pipeline\DeleteShipments\Stage\SendRequestStageStub
     *
     */
    public function cancelParcel(string $parcelId): string
    {
        $callback = $this->cancelParcelsStage->responseCallback;
        if (is_callable($callback)) {
            // cancelled shipment number or exception
            $response = $callback($this->cancelParcelsStage);
            if ($response instanceof ServiceException) {
                throw $response;
            }

            if (is_string($response)) {
                return $response;
            }
        }

        // response callback not defined or empty, return default response.
        return array_shift($this->cancelParcelsStage->apiResponses);
    }
}
