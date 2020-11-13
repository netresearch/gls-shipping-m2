<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGroup\Shipping\Test\Integration\TestDouble\Pipeline\CreateShipments\Stage;

use GlsGroup\Sdk\ParcelProcessing\Api\Data\ShipmentInterface;
use GlsGroup\Sdk\ParcelProcessing\Service\ShipmentService\Parcel;
use GlsGroup\Sdk\ParcelProcessing\Service\ShipmentService\Shipment;
use GlsGroup\Shipping\Model\Pipeline\CreateShipments\ArtifactsContainer;
use GlsGroup\Shipping\Model\Pipeline\CreateShipments\Stage\SendRequestStage;
use Magento\Shipping\Model\Shipment\Request;
use Netresearch\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;

class SendRequestStageStub extends SendRequestStage
{
    /**
     * Magento shipment request objects passed to the stage. Can be used for assertions.
     *
     * @var Request[]
     */
    public $shipmentRequests = [];

    /**
     * API request objects sent to the web service. Can be used for assertions.
     *
     * @var \JsonSerializable[]
     */
    public $apiRequests = [];

    /**
     * Regular API responses. Built during runtime from the given shipment requests.
     *
     * @var ShipmentInterface[]
     */
    public $apiResponses = [];

    /**
     * API response callback. Can be used to alter the default response during runtime, e.g. throw an exception.
     *
     * @var callable|null
     */
    public $responseCallback;

    /**
     * @return string
     */
    private function getLabelPdf(): string
    {
        return <<<'B64'
JVBERi0xLjUKJbXtrvsKMyAwIG9iago8PCAvTGVuZ3RoIDQgMCBSCiAgIC9GaWx0ZXIgL0ZsYXRl
RGVjb2RlCj4+CnN0cmVhbQp4nCvkCuQCAAKSANcKZW5kc3RyZWFtCmVuZG9iago0IDAgb2JqCiAg
IDEyCmVuZG9iagoyIDAgb2JqCjw8Cj4+CmVuZG9iago1IDAgb2JqCjw8IC9UeXBlIC9QYWdlCiAg
IC9QYXJlbnQgMSAwIFIKICAgL01lZGlhQm94IFsgMCAwIDEwNCAxNDcgXQogICAvQ29udGVudHMg
MyAwIFIKICAgL0dyb3VwIDw8CiAgICAgIC9UeXBlIC9Hcm91cAogICAgICAvUyAvVHJhbnNwYXJl
bmN5CiAgICAgIC9DUyAvRGV2aWNlUkdCCiAgID4+CiAgIC9SZXNvdXJjZXMgMiAwIFIKPj4KZW5k
b2JqCjEgMCBvYmoKPDwgL1R5cGUgL1BhZ2VzCiAgIC9LaWRzIFsgNSAwIFIgXQogICAvQ291bnQg
MQo+PgplbmRvYmoKNiAwIG9iago8PCAvQ3JlYXRvciAoY2Fpcm8gMS45LjUgKGh0dHA6Ly9jYWly
b2dyYXBoaWNzLm9yZykpCiAgIC9Qcm9kdWNlciAoY2Fpcm8gMS45LjUgKGh0dHA6Ly9jYWlyb2dy
YXBoaWNzLm9yZykpCj4+CmVuZG9iago3IDAgb2JqCjw8IC9UeXBlIC9DYXRhbG9nCiAgIC9QYWdl
cyAxIDAgUgo+PgplbmRvYmoKeHJlZgowIDgKMDAwMDAwMDAwMCA2NTUzNSBmIAowMDAwMDAwMzQ2
IDAwMDAwIG4gCjAwMDAwMDAxMjUgMDAwMDAgbiAKMDAwMDAwMDAxNSAwMDAwMCBuIAowMDAwMDAw
MTA0IDAwMDAwIG4gCjAwMDAwMDAxNDYgMDAwMDAgbiAKMDAwMDAwMDQxMSAwMDAwMCBuIAowMDAw
MDAwNTM2IDAwMDAwIG4gCnRyYWlsZXIKPDwgL1NpemUgOAogICAvUm9vdCA3IDAgUgogICAvSW5m
byA2IDAgUgo+PgpzdGFydHhyZWYKNTg4CiUlRU9GCg==
B64;
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
        $this->shipmentRequests = $requests;
        $this->apiRequests = $artifactsContainer->getApiRequests();
        $this->apiResponses = [];

        $pdf = $this->getLabelPdf();

        foreach ($requests as $index => $shipmentRequest) {
            $parcels = [];
            $orderId = (string) $shipmentRequest->getOrderShipment()->getOrderId();
            foreach ($shipmentRequest->getData('packages') as $package) {
                $parcels[] = new Parcel(
                    "https://qs.gls-group.eu/track/YY99XXX0",
                    $orderId,
                    'YY99XXX0'
                );
            }
            $shipment = new Shipment(
                "https://qs.gls-group.eu/track/YY99XXX0",
                "12345678900",
                $parcels
            );

            $shipment->setLabels([$pdf]);

            $this->apiResponses[$index] = $shipment;
        }

        return parent::execute($requests, $artifactsContainer);
    }
}
