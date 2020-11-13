<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGroup\Shipping\Model\Pipeline\CreateShipments\ShipmentRequest;

use GlsGroup\Shipping\Model\Config\ModuleConfig;
use GlsGroup\Shipping\Model\Pipeline\CreateShipments\ShipmentRequest\Data\PackageAdditionalFactory;
use GlsGroup\Shipping\Model\ShippingSettings\ShippingOption\Codes;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;
use Magento\Shipping\Model\Shipment\Request;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentRequest\PackageInterfaceFactory;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentRequest\RecipientInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentRequest\ShipperInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentRequest\ShipperInterfaceFactory;
use Netresearch\ShippingCore\Api\Pipeline\ShipmentRequest\RequestExtractor\ServiceOptionReaderInterface;
use Netresearch\ShippingCore\Api\Pipeline\ShipmentRequest\RequestExtractor\ServiceOptionReaderInterfaceFactory;
use Netresearch\ShippingCore\Api\Pipeline\ShipmentRequest\RequestExtractorInterface;
use Netresearch\ShippingCore\Api\Pipeline\ShipmentRequest\RequestExtractorInterfaceFactory;
use Netresearch\ShippingCore\Api\ShipmentDate\ShipmentDateCalculatorInterface;
use Zend\Hydrator\Reflection;

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
     * @var ServiceOptionReaderInterfaceFactory
     */
    private $serviceOptionReaderFactory;

    /**
     * @var PackageAdditionalFactory
     */
    private $packageAdditionalFactory;

    /**
     * @var PackageInterfaceFactory
     */
    private $packageFactory;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var ShipmentDateCalculatorInterface
     */
    private $shipmentDate;

    /**
     * @var ShipperInterface
     */
    private $returnRecipient;

    /**
     * @var ShipperInterfaceFactory
     */
    private $shipperFactory;

    /**
     * @var Reflection
     */
    private $hydrator;

    /**
     * @var RequestExtractorInterface
     */
    private $coreExtractor;

    /**
     * @var ServiceOptionReaderInterface
     */
    private $serviceOptionReader;

    public function __construct(
        Request $shipmentRequest,
        RequestExtractorInterfaceFactory $requestExtractorFactory,
        ServiceOptionReaderInterfaceFactory $serviceOptionReaderFactory,
        PackageAdditionalFactory $packageAdditionalFactory,
        PackageInterfaceFactory $packageFactory,
        ModuleConfig $moduleConfig,
        ShipmentDateCalculatorInterface $shipmentDate,
        ShipperInterfaceFactory $shipperFactory,
        Reflection $hydrator
    ) {
        $this->shipmentRequest = $shipmentRequest;
        $this->requestExtractorFactory = $requestExtractorFactory;
        $this->serviceOptionReaderFactory = $serviceOptionReaderFactory;
        $this->packageAdditionalFactory = $packageAdditionalFactory;
        $this->packageFactory = $packageFactory;
        $this->moduleConfig = $moduleConfig;
        $this->shipmentDate = $shipmentDate;
        $this->shipperFactory = $shipperFactory;
        $this->hydrator = $hydrator;
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

    /**
     * Obtain service option reader to read carrier specific service data.
     *
     * @return ServiceOptionReaderInterface
     */
    private function getServiceOptionReader(): ServiceOptionReaderInterface
    {
        if (empty($this->serviceOptionReader)) {
            $this->serviceOptionReader = $this->serviceOptionReaderFactory->create(
                ['shipmentRequest' => $this->shipmentRequest]
            );
        }

        return $this->serviceOptionReader;
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

    public function getReturnRecipient(): ShipperInterface
    {
        if (!empty($this->returnRecipient)) {
            return $this->returnRecipient;
        }

        $alternativeAddress = $this->moduleConfig->getAlternativeReturnAddress($this->getStoreId());
        if (empty($alternativeAddress)) {
            $this->returnRecipient = $this->getShipper();
        } else {
            $this->returnRecipient = $this->shipperFactory->create(
                [
                    'contactPersonName' => '',
                    'contactPersonFirstName' => '',
                    'contactPersonLastName' => '',
                    'contactCompanyName' => $alternativeAddress['company'],
                    'contactEmail' => '',
                    'contactPhoneNumber' => '',
                    'street' => [$alternativeAddress['street']],
                    'city' => $alternativeAddress['city'],
                    'state' => '',
                    'postalCode' => $alternativeAddress['postcode'],
                    'countryCode' => $alternativeAddress['country_id'],
                    'streetName' => '',
                    'streetNumber' => '',
                    'addressAddition' => '',
                ]
            );
        }

        return $this->returnRecipient;
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
        $packages = $this->getCoreExtractor()->getPackages();
        $glsPackages = [];

        foreach ($packages as $packageId => $package) {
            // read generic export data from shipment request
            $packageParams = $this->shipmentRequest->getData('packages')[$packageId]['params'];
            $customsParams = $packageParams['customs'] ?? [];
            if (empty($customsParams)) {
                // GLS has only additional package params for customs, nothing to do.
                $glsPackages[$packageId] = $package;
                continue;
            }

            try {
                $packageData = $this->hydrator->extract($package);
                $packageData['packageAdditional'] = $this->packageAdditionalFactory->create(
                    ['termsOfTrade' => $customsParams['termsOfTrade']]
                );

                // create new extended package instance with paket-specific export data
                $glsPackages[$packageId] = $this->packageFactory->create($packageData);
            } catch (\Exception $exception) {
                throw new LocalizedException(__('An error occurred while preparing package data.'), $exception);
            }
        }

        return $glsPackages;
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
        try {
            return $this->shipmentDate->getDate(
                $this->moduleConfig->getCutOffTimes($this->getStoreId()),
                $this->getStoreId()
            );
        } catch (\RuntimeException $exception) {
            return $this->getCoreExtractor()->getShipmentDate();
        }
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
     * @return bool
     * @todo(nr): apply proper rules
     *
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
        return $this->getServiceOptionReader()->isServiceEnabled(Codes::CHECKOUT_SERVICE_FLEX_DELIVERY);
    }

    /**
     * Check whether Guaranteed24Service was chosen or not.
     *
     * @return bool
     */
    public function isNextDayDeliveryEnabled(): bool
    {
        return $this->getServiceOptionReader()->isServiceEnabled(Codes::CHECKOUT_SERVICE_GUARANTEED24);
    }

    /**
     * Check whether ShopReturnService was chosen or not.
     *
     * @return bool
     */
    public function isShopReturnBooked(): bool
    {
        return $this->getServiceOptionReader()->isServiceEnabled(Codes::PACKAGING_SERVICE_SHOP_RETURN);
    }

    /**
     * Obtain the alternative location to place the parcel.
     *
     * Note: The location selected by the consumer in checkout always
     * takes precedence over the merchant's service default setting.
     * That is, if the consumer commissions the courier to place the
     * parcel in the garage, the letterbox service will be ignored.
     *
     * @return string
     */
    public function getPlaceOfDeposit(): string
    {
        if ($this->getServiceOptionReader()->isServiceEnabled(Codes::CHECKOUT_SERVICE_DEPOSIT)) {
            return $this->getServiceOptionReader()->getServiceOptionValue(
                Codes::CHECKOUT_SERVICE_DEPOSIT,
                'details'
            );
        }

        if ($this->getServiceOptionReader()->isServiceEnabled(Codes::PACKAGING_SERVICE_LETTERBOX)) {
            return 'Briefkasten';
        }

        return '';
    }
}
