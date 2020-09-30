<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGermany\Shipping\Model\Pipeline\CreateShipments\ShipmentRequest;

use GlsGermany\Shipping\Model\Config\ModuleConfig;
use GlsGermany\Shipping\Model\ShippingSettings\ShippingOption\Codes;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;
use Magento\Shipping\Model\Shipment\Request;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentRequest\RecipientInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentRequest\ShipperInterface;
use Netresearch\ShippingCore\Api\Pipeline\ShipmentRequest\RequestExtractor\ServiceOptionReaderInterface;
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
     * @var Request
     */
    private $shipmentRequest;

    /**
     * @var RequestExtractorInterfaceFactory
     */
    private $requestExtractorFactory;

    /**
     * @var RequestExtractorInterface
     */
    private $coreExtractor;

    /**
     * @var ServiceOptionReaderInterface
     */
    private $serviceOptionReader;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    public function __construct(
        Request $shipmentRequest,
        RequestExtractorInterfaceFactory $requestExtractorFactory,
        ServiceOptionReaderInterface $serviceOptionReader,
        ModuleConfig $moduleConfig
    ) {
        $this->shipmentRequest = $shipmentRequest;
        $this->requestExtractorFactory = $requestExtractorFactory;
        $this->serviceOptionReader = $serviceOptionReader;
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

    public function isCashOnDelivery(): bool
    {
        return $this->coreExtractor->isCashOnDelivery();
    }

    public function getCodReasonForPayment(): string
    {
        return $this->coreExtractor->getCodReasonForPayment();
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
        if ($this->isFlexDeliveryEnabled()) {
            // flex delivery service requires email address
            return true;
        }

        return false;
    }

    /**
     * Check whether FlexDeliveryService was chosen or not.
     *
     * @return bool
     */
    public function isFlexDeliveryEnabled(): bool
    {
        return $this->serviceOptionReader->isServiceEnabled(Codes::CHECKOUT_SERVICE_FLEX_DELIVERY);
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
