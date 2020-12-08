<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGroup\Shipping\Model\Pipeline\DeleteShipments\Stage;

use GlsGroup\Sdk\ParcelProcessing\Exception\ServiceException;
use GlsGroup\Shipping\Model\Pipeline\DeleteShipments\ArtifactsContainer;
use GlsGroup\Shipping\Model\Webservice\ParcelProcessingServiceFactory;
use Netresearch\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\TrackRequest\TrackRequestInterface;
use Netresearch\ShippingCore\Api\Pipeline\RequestTracksStageInterface;

class SendRequestStage implements RequestTracksStageInterface
{
    /**
     * @var ParcelProcessingServiceFactory
     */
    private $serviceFactory;

    public function __construct(ParcelProcessingServiceFactory $serviceFactory)
    {
        $this->serviceFactory = $serviceFactory;
    }

    /**
     * Send request data to shipment service.
     *
     * @param TrackRequestInterface[] $requests
     * @param ArtifactsContainerInterface|ArtifactsContainer $artifactsContainer
     * @return TrackRequestInterface[]
     */
    public function execute(array $requests, ArtifactsContainerInterface $artifactsContainer): array
    {
        $apiRequests = $artifactsContainer->getApiRequests();
        if (empty($apiRequests)) {
            return [];
        }

        try {
            $cancellationService = $this->serviceFactory->createCancellationService($artifactsContainer->getStoreId());
        } catch (ServiceException $exception) {
            // service discovery error, abort immediately.
            throw new \RuntimeException($exception->getMessage(), $exception->getCode(), $exception);
        }

        try {
            $shipmentNumbers = array_values($apiRequests);
            $cancelledShipments = $cancellationService->cancelParcels($shipmentNumbers);
            // add shipment number as response index
            foreach ($cancelledShipments as $parcelNumber) {
                $artifactsContainer->addApiResponse($parcelNumber, $parcelNumber);
            }

            return $requests;
        } catch (ServiceException $exception) {
            // mark all requests as failed
            foreach ($requests as $cancelRequest) {
                $artifactsContainer->addError(
                    $cancelRequest->getTrackNumber(),
                    $cancelRequest->getSalesShipment(),
                    $cancelRequest->getSalesTrack(),
                    $exception->getMessage()
                );
            }
            return [];
        }
    }
}
