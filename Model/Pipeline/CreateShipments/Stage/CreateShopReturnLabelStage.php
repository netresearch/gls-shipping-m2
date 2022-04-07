<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGroup\Shipping\Model\Pipeline\CreateShipments\Stage;

use GlsGroup\Sdk\ParcelProcessing\Exception\ServiceException;
use GlsGroup\Shipping\Model\Pipeline\CreateShipments\ArtifactsContainer;
use GlsGroup\Shipping\Model\Pipeline\CreateShipments\ReturnRequestDataMapper;
use GlsGroup\Shipping\Model\Webservice\ParcelProcessingServiceFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Shipping\Model\Shipment\Request;
use Magento\Shipping\Model\Shipping\LabelGenerator;
use Netresearch\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ReturnShipmentDocumentInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ReturnShipmentDocumentInterfaceFactory;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ShipmentDocumentInterface;
use Netresearch\ShippingCore\Api\Pipeline\CreateShipmentsStageInterface;
use Psr\Log\LoggerInterface;

class CreateShopReturnLabelStage implements CreateShipmentsStageInterface
{
    /**
     * @var ReturnRequestDataMapper
     */
    private $requestDataMapper;

    /**
     * @var ParcelProcessingServiceFactory
     */
    private $serviceFactory;

    /**
     * @var ReturnShipmentDocumentInterfaceFactory
     */
    private $returnDocumentFactory;

    /**
     * @var LabelGenerator
     */
    private $labelGenerator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ReturnRequestDataMapper $requestDataMapper,
        ParcelProcessingServiceFactory $shipmentServiceFactory,
        ReturnShipmentDocumentInterfaceFactory $returnDocumentFactory,
        LabelGenerator $labelGenerator,
        LoggerInterface $logger
    ) {
        $this->requestDataMapper = $requestDataMapper;
        $this->serviceFactory = $shipmentServiceFactory;
        $this->returnDocumentFactory = $returnDocumentFactory;
        $this->labelGenerator = $labelGenerator;
        $this->logger = $logger;
    }

    /**
     * Send return label request objects to shipment service.
     *
     * @param Request[] $requests
     * @param ArtifactsContainerInterface|ArtifactsContainer $artifactsContainer
     * @return Request[]
     */
    public function execute(array $requests, ArtifactsContainerInterface $artifactsContainer): array
    {
        foreach ($artifactsContainer->getLabelResponses() as $requestIndex => $labelResponse) {
            $request = $requests[$requestIndex];
            try {
                $shipmentRequest = $this->requestDataMapper->mapRequest($request);
            } catch (LocalizedException $exception) {
                $this->logger->error($exception->getMessage(), ['exception' => $exception]);
                $shipmentRequest = null;
            }

            if (!$shipmentRequest) {
                continue;
            }

            try {
                $shipmentService = $this->serviceFactory->createShipmentService($artifactsContainer->getStoreId());
                $shipment = $shipmentService->createShipment($shipmentRequest);

                $packages = $shipment->getParcels();
                $package = array_shift($packages);
                $trackingNumber = $package->getParcelNumber();

                $labels = $shipment->getLabels();
                $returnDocuments = array_map(
                    function (string $labelData) use ($trackingNumber) {
                        return $this->returnDocumentFactory->create([
                            'data' => [
                                ShipmentDocumentInterface::TITLE => 'Retoure-Paketschein',
                                ShipmentDocumentInterface::MIME_TYPE => 'application/pdf',
                                ShipmentDocumentInterface::LABEL_DATA => base64_encode($labelData),
                                ReturnShipmentDocumentInterface::TRACKING_NUMBER => $trackingNumber,
                            ]
                        ]);
                    },
                    $labels
                );

                $labelResponse->setData('documents', array_merge($labelResponse->getDocuments(), $returnDocuments));

                array_unshift($labels, $labelResponse->getShippingLabelContent());
                $labelContent = $this->labelGenerator->combineLabelsPdf($labels)->render();
                $labelResponse->setData('shipping_label_content', $labelContent);
            } catch (ServiceException | \Zend_Pdf_Exception $exception) {
                $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            }
        }

        return $requests;
    }
}
