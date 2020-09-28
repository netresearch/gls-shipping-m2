<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGermany\Shipping\Model\Pipeline\CreateShipments\Stage;

use GlsGermany\Shipping\Model\Pipeline\CreateShipments\ArtifactsContainer;
use GlsGermany\Shipping\Model\Pipeline\CreateShipments\ResponseDataMapper;
use Magento\Shipping\Model\Shipment\Request;
use Netresearch\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Netresearch\ShippingCore\Api\Pipeline\CreateShipmentsStageInterface;

class MapResponseStage implements CreateShipmentsStageInterface
{
    /**
     * @var ResponseDataMapper
     */
    private $responseDataMapper;

    public function __construct(ResponseDataMapper $responseDataMapper)
    {
        $this->responseDataMapper = $responseDataMapper;
    }

    /**
     * Transform collected results into response objects suitable for processing by the core.
     *
     * @param Request[] $requests
     * @param ArtifactsContainerInterface|ArtifactsContainer $artifactsContainer
     * @return Request[]
     */
    public function execute(array $requests, ArtifactsContainerInterface $artifactsContainer): array
    {
        foreach ($artifactsContainer->getErrors() as $requestIndex => $error) {
            // error occurred during previous stage: validation error or negative response received from webservice.
            $message = __('Label could not be created: %1', $error['message']);
            $shipment = $error['shipment'];
            $response = $this->responseDataMapper->createErrorResponse((string)$requestIndex, $message, $shipment);
            $artifactsContainer->addErrorResponse((string) $requestIndex, $response);
        }

        foreach ($artifactsContainer->getApiResponses() as $requestIndex => $response) {
            // positive response received from webservice
            $shipment = $requests[$requestIndex]->getOrderShipment();
            $response = $this->responseDataMapper->createShipmentResponse($response, $shipment);
            $artifactsContainer->addLabelResponse((string)$requestIndex, $response);
        }

        return $requests;
    }
}
