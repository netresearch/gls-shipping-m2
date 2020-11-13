<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGroup\Shipping\Model\Pipeline\CreateShipments\Stage;

use GlsGroup\Sdk\ParcelProcessing\Exception\DetailedServiceException;
use GlsGroup\Sdk\ParcelProcessing\Exception\ServiceException;
use GlsGroup\Shipping\Model\Pipeline\CreateShipments\ArtifactsContainer;
use GlsGroup\Shipping\Model\Webservice\ShipmentServiceFactory;
use Magento\Shipping\Model\Shipment\Request;
use Netresearch\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Netresearch\ShippingCore\Api\Pipeline\CreateShipmentsStageInterface;

class SendRequestStage implements CreateShipmentsStageInterface
{
    /**
     * @var ShipmentServiceFactory
     */
    private $shipmentServiceFactory;

    public function __construct(ShipmentServiceFactory $shipmentServiceFactory)
    {
        $this->shipmentServiceFactory = $shipmentServiceFactory;
    }

    /**
     * Send label request objects to shipment service.
     *
     * @param Request[] $requests
     * @param ArtifactsContainerInterface|ArtifactsContainer $artifactsContainer
     * @return Request[]
     */
    public function execute(array $requests, ArtifactsContainerInterface $artifactsContainer): array
    {
        $apiRequests = $artifactsContainer->getApiRequests();
        if (!empty($apiRequests)) {
            $shipmentService = $this->shipmentServiceFactory->create(['storeId' => $artifactsContainer->getStoreId()]);

            foreach ($requests as $requestIndex => $request) {
                try {
                    $shipment = $shipmentService->createShipment($apiRequests[$requestIndex]);
                    $artifactsContainer->addApiResponse((string)$requestIndex, $shipment);
                } catch (DetailedServiceException $exception) {
                    $artifactsContainer->addError(
                        (string)$requestIndex,
                        $request->getOrderShipment(),
                        $exception->getMessage()
                    );
                } catch (ServiceException $exception) {
                    $artifactsContainer->addError(
                        (string)$requestIndex,
                        $request->getOrderShipment(),
                        'Web service request failed.'
                    );
                }
            }
        }

        return $requests;
    }
}
