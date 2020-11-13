<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGroup\Shipping\Model\Pipeline\DeleteShipments\Stage;

use GlsGroup\Sdk\ParcelProcessing\Exception\ServiceException;
use GlsGroup\Shipping\Model\Pipeline\DeleteShipments\ArtifactsContainer;
use GlsGroup\Shipping\Model\Webservice\CancellationServiceFactory;
use Netresearch\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\TrackRequest\TrackRequestInterface;
use Netresearch\ShippingCore\Api\Pipeline\RequestTracksStageInterface;

class SendRequestStage implements RequestTracksStageInterface
{
    /**
     * @var CancellationServiceFactory
     */
    private $cancellationServiceFactory;

    public function __construct(CancellationServiceFactory $cancellationServiceFactory)
    {
        $this->cancellationServiceFactory = $cancellationServiceFactory;
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
        if (!empty($apiRequests)) {
            $cancellationService = $this->cancellationServiceFactory->create(
                ['storeId' => $artifactsContainer->getStoreId()]
            );

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
            }
        }

        // no requests passed the stage
        return [];
    }
}
