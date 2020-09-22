<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGermany\Shipping\Model\BulkShipment;

use GlsGermany\Shipping\Model\Pipeline\ApiGateway;
use GlsGermany\Shipping\Model\Pipeline\ApiGatewayFactory;
use Magento\Shipping\Model\Shipment\Request;
use Netresearch\ShippingCore\Api\BulkShipment\BulkLabelCancellationInterface;
use Netresearch\ShippingCore\Api\BulkShipment\BulkLabelCreationInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ShipmentResponseInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\TrackRequest\TrackRequestInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\TrackResponse\TrackResponseInterface;
use Netresearch\ShippingCore\Api\Pipeline\ShipmentResponseProcessorInterface;
use Netresearch\ShippingCore\Api\Pipeline\TrackResponseProcessorInterface;

/**
 * Class ShipmentManagement
 *
 * Central entrypoint for creating and deleting shipments.
 */
class ShipmentManagement implements BulkLabelCreationInterface, BulkLabelCancellationInterface
{
    /**
     * @var ApiGatewayFactory
     */
    private $apiGatewayFactory;

    /**
     * @var ShipmentResponseProcessorInterface
     */
    private $createResponseProcessor;

    /**
     * @var TrackResponseProcessorInterface
     */
    private $deleteResponseProcessor;

    /**
     * @var ApiGateway[]
     */
    private $apiGateways;

    public function __construct(
        ApiGatewayFactory $apiGatewayFactory,
        ShipmentResponseProcessorInterface $createResponseProcessor,
        TrackResponseProcessorInterface $deleteResponseProcessor
    ) {
        $this->apiGatewayFactory = $apiGatewayFactory;
        $this->createResponseProcessor = $createResponseProcessor;
        $this->deleteResponseProcessor = $deleteResponseProcessor;
    }

    /**
     * Create api gateway.
     *
     * API gateways are created with store specific configuration and configured post-processors (bulk or popup).
     *
     * @param int $storeId
     * @return ApiGateway
     */
    private function getApiGateway(int $storeId): ApiGateway
    {
        if (!isset($this->apiGateways[$storeId])) {
            $api = $this->apiGatewayFactory->create(
                [
                    'storeId' => $storeId,
                    'createResponseProcessor' => $this->createResponseProcessor,
                    'deleteResponseProcessor' => $this->deleteResponseProcessor,
                ]
            );

            $this->apiGateways[$storeId] = $api;
        }

        return $this->apiGateways[$storeId];
    }

    /**
     * Create shipment labels at GLS API
     *
     * Shipment requests are divided by store for multi-store support (different GLS account configurations).
     *
     * @param Request[] $shipmentRequests
     * @return ShipmentResponseInterface[]
     */
    public function createLabels(array $shipmentRequests): array
    {
        if (empty($shipmentRequests)) {
            return [];
        }

        $apiRequests = [];
        $apiResults = [];

        foreach ($shipmentRequests as $shipmentRequest) {
            $storeId = (int) $shipmentRequest->getOrderShipment()->getStoreId();
            $apiRequests[$storeId][] = $shipmentRequest;
        }

        foreach ($apiRequests as $storeId => $storeApiRequests) {
            $api = $this->getApiGateway($storeId);
            $apiResults[$storeId] = $api->createShipments($storeApiRequests);
        }

        if (!empty($apiResults)) {
            // convert results per store to flat response
            $apiResults = array_reduce($apiResults, 'array_merge', []);
        }

        return $apiResults;
    }

    /**
     * Cancel shipment orders at the GLS API alongside associated tracks and shipping labels.
     *
     * Cancellation requests are divided by store for multi-store support (different GLS account configurations).
     *
     * @param TrackRequestInterface[] $cancelRequests
     * @return TrackResponseInterface[]
     */
    public function cancelLabels(array $cancelRequests): array
    {
        if (empty($cancelRequests)) {
            return [];
        }

        $apiRequests = [];
        $apiResults = [];

        // divide cancel requests by store as they may use different api configurations
        foreach ($cancelRequests as $shipmentNumber => $cancelRequest) {
            $storeId = $cancelRequest->getStoreId();
            $apiRequests[$storeId][$shipmentNumber] = $cancelRequest;
        }

        foreach ($apiRequests as $storeId => $storeApiRequests) {
            $api = $this->getApiGateway($storeId);
            $apiResults[$storeId] = $api->cancelShipments($storeApiRequests);
        }

        if (!empty($apiResults)) {
            // convert results per store to flat response
            $apiResults = array_reduce($apiResults, 'array_merge', []);
        }

        return $apiResults;
    }
}
