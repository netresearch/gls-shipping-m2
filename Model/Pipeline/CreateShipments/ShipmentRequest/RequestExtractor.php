<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGermany\Shipping\Model\Pipeline\CreateShipments\ShipmentRequest;

use GlsGermany\Shipping\Model\Config\ModuleConfig;
use GlsGermany\Shipping\Model\ShippingSettings\ShippingOption\Codes;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;
use Magento\Shipping\Model\Shipment\Request;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentRequest\RecipientInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentRequest\ShipperInterface;
use Netresearch\ShippingCore\Api\Pipeline\ShipmentRequest\RequestExtractorInterface;
use Netresearch\ShippingCore\Api\Pipeline\ShipmentRequest\RequestExtractorInterfaceFactory;

/**
 * Class RequestExtractor
 *
 * The original shipment request is a rather limited DTO with unstructured data (DataObject, array).
 * The extractor and its subtypes offer a well-defined interface to extract the request data and
 * isolates the toxic part of extracting unstructured array data from the shipment request.
 */
class RequestExtractor implements RequestExtractorInterface
{
    /**
     * @var RequestExtractorInterfaceFactory
     */
    private $requestExtractorFactory;

    /**
     * @var Request
     */
    private $shipmentRequest;

    /**
     * @var RequestExtractorInterface
     */
    private $coreExtractor;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    public function __construct(
        RequestExtractorInterfaceFactory $requestExtractorFactory,
        Request $shipmentRequest,
        ModuleConfig $moduleConfig
    ) {
        $this->requestExtractorFactory = $requestExtractorFactory;
        $this->shipmentRequest = $shipmentRequest;
        $this->moduleConfig = $moduleConfig;
    }

    /**
     * Obtain core extractor for forwarding generic shipment data calls.
     *
     * @return RequestExtractorInterface
     */
    private function getCoreExtractor(): RequestExtractorInterface
    {
        if (empty($this->coreExtractor)) {
            $this->coreExtractor = $this->requestExtractorFactory->create(
                ['shipmentRequest' => $this->shipmentRequest]
            );
        }

        return $this->coreExtractor;
    }

    public function isReturnShipmentRequest(): bool
    {
        return $this->getCoreExtractor()->isReturnShipmentRequest();
    }

    public function getStoreId(): int
    {
        return $this->getCoreExtractor()->getStoreId();
    }

    public function getBaseCurrencyCode(): string
    {
        return $this->getCoreExtractor()->getBaseCurrencyCode();
    }

    public function getOrder(): Order
    {
        return $this->getCoreExtractor()->getOrder();
    }

    public function getShipment(): Shipment
    {
        return $this->getCoreExtractor()->getShipment();
    }

    public function getShipper(): ShipperInterface
    {
        return $this->getCoreExtractor()->getShipper();
    }

    public function getRecipient(): RecipientInterface
    {
        return $this->getCoreExtractor()->getRecipient();
    }

    public function getPackageWeight(): float
    {
        return $this->getCoreExtractor()->getPackageWeight();
    }

    public function getPackages(): array
    {
        return $this->getCoreExtractor()->getPackages();
    }

    public function getAllItems(): array
    {
        return $this->getCoreExtractor()->getAllItems();
    }

    public function getPackageItems(): array
    {
        return $this->getCoreExtractor()->getPackageItems();
    }

    public function getShipmentDate(): \DateTime
    {
        return $this->getCoreExtractor()->getShipmentDate();
    }

    /**
     * Obtain the service data array.
     *
     * @param string $serviceName
     * @return string[]
     */
    private function getServiceData(string $serviceName): array
    {
        $packages = $this->shipmentRequest->getData('packages');
        $packageId = $this->shipmentRequest->getData('package_id');
        return $packages[$packageId]['params']['services'][$serviceName] ?? [];
    }

    /**
     * Check if recipient email must be set.
     *
     * By default, recipient email address is not included with the request.
     * There are some services though that require an email address.
     *
     * @todo(nr): apply proper rules
     *
     * @return bool
     */
    public function isRecipientEmailRequired(): bool
    {
        if ($this->isParcelAnnouncement()) {
            // parcel announcement services requires email address
            return true;
        }

        return false;
    }

    /**
     * Check if "cash on delivery" was chosen for the current shipment request.
     *
     * @return bool
     */
    public function isCashOnDelivery(): bool
    {
        return (bool) ($this->getServiceData(Codes::CHECKOUT_SERVICE_CASH_ON_DELIVERY)['enabled'] ?? false);
    }

    /**
     * Obtain the "parcelAnnouncement" flag for the current package.
     *
     * @todo(nr): service is called "FlexDeliveryService"
     * @return bool
     */
    public function isParcelAnnouncement(): bool
    {
        return (bool) ($this->getServiceData(Codes::CHECKOUT_PARCEL_ANNOUNCEMENT)['enabled'] ?? false);
    }

    /**
     * Obtain the "reasonForPayment" value for the current package.
     *
     * @todo(nr): add config setting, parse template
     * @return string[] Array of maximum two lines.
     */
    public function getCodReasonForPayment(): array
    {
        throw new LocalizedException(__('Not implemented yet.'));
    }

    /**
     * Obtain the shipper id.
     *
     * @todo(nr): read from shipping settings
     * @return string
     */
    public function getShipperId(): string
    {
        return $this->moduleConfig->getShipperId();
    }

    /**
     * Obtain all broker reference.
     *
     * @todo(nr): read from shipping settings
     * @return string
     */
    public function getBrokerReference(): string
    {
        return $this->moduleConfig->getBrokerReference();
    }
}
