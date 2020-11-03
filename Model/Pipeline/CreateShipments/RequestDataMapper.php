<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGermany\Shipping\Model\Pipeline\CreateShipments;

use GlsGermany\Sdk\ParcelProcessing\Api\ShipmentRequestBuilderInterface;
use GlsGermany\Sdk\ParcelProcessing\Exception\RequestValidatorException;
use GlsGermany\Shipping\Model\Config\ModuleConfig;
use GlsGermany\Shipping\Model\Config\Source\LabelSize;
use GlsGermany\Shipping\Model\Pipeline\CreateShipments\ShipmentRequest\Data\PackageAdditional;
use GlsGermany\Shipping\Model\Pipeline\CreateShipments\ShipmentRequest\RequestExtractorFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Shipping\Model\Shipment\Request;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentRequest\PackageInterface;
use Netresearch\ShippingCore\Api\Util\UnitConverterInterface;

class RequestDataMapper
{
    /**
     * @var RequestExtractorFactory
     */
    private $requestExtractorFactory;

    /**
     * @var ShipmentRequestBuilderInterface
     */
    private $requestBuilder;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var UnitConverterInterface
     */
    private $unitConverter;

    public function __construct(
        ShipmentRequestBuilderInterface $requestBuilder,
        RequestExtractorFactory $requestExtractorFactory,
        ModuleConfig $moduleConfig,
        UnitConverterInterface $unitConverter
    ) {
        $this->requestBuilder = $requestBuilder;
        $this->requestExtractorFactory = $requestExtractorFactory;
        $this->moduleConfig = $moduleConfig;
        $this->unitConverter = $unitConverter;
    }

    /**
     * Map the Magento shipment request to an SDK request object using the SDK request builder.
     *
     * @param Request $request The shipment request
     *
     * @return \JsonSerializable
     * @throws LocalizedException
     */
    public function mapRequest(Request $request): \JsonSerializable
    {
        $implode = function (array $parts) {
            $parts = array_filter($parts);
            return implode(' ', $parts);
        };

        $requestExtractor = $this->requestExtractorFactory->create(['shipmentRequest' => $request]);

        $this->requestBuilder->setShipperAccount(
            $this->moduleConfig->getShipperId($requestExtractor->getStoreId()),
            $this->moduleConfig->getBrokerReference()
        );

        if ($this->moduleConfig->isSendFromStoreShippingOrigin($requestExtractor->getStoreId())) {
            // include shipping origin with label request.
            $this->requestBuilder->setShipperAddress(
                $requestExtractor->getShipper()->getCountryCode(),
                $requestExtractor->getShipper()->getPostalCode(),
                $requestExtractor->getShipper()->getCity(),
                $implode($requestExtractor->getShipper()->getStreet()),
                $requestExtractor->getShipper()->getContactCompanyName(),
                null,
                null,
                null,
                $requestExtractor->getShipper()->getState(),
                $requestExtractor->getShipper()->getContactPersonName(),
                null,
                null,
                null
            );
        }

        if ($requestExtractor->isRecipientEmailRequired()) {
            $recipientEmail = $requestExtractor->getRecipient()->getContactEmail();
        } else {
            $recipientEmail = null;
        }

        $this->requestBuilder->setRecipientAddress(
            $requestExtractor->getRecipient()->getCountryCode(),
            $requestExtractor->getRecipient()->getPostalCode(),
            $requestExtractor->getRecipient()->getCity(),
            $implode($requestExtractor->getRecipient()->getStreet()),
            $requestExtractor->getRecipient()->getContactPersonName(),
            $requestExtractor->getRecipient()->getContactCompanyName(),
            $recipientEmail,
            null,
            null,
            null,
            $requestExtractor->getRecipient()->getState(),
            null
        );

        if ($requestExtractor->isFlexDeliveryEnabled()) {
            $this->requestBuilder->requestFlexDeliveryService();
        }

        if ($requestExtractor->isNextDayDeliveryEnabled()) {
            $this->requestBuilder->requestNextDayDelivery();
        }

        $depositLocation = $requestExtractor->getPlaceOfDeposit();
        if ($depositLocation) {
            $this->requestBuilder->setPlaceOfDeposit($depositLocation);
        }

        if ($requestExtractor->isShopReturnBooked()) {
            $this->requestBuilder->setReturnAddress(
                $requestExtractor->getReturnRecipient()->getCountryCode(),
                $requestExtractor->getReturnRecipient()->getPostalCode(),
                $requestExtractor->getReturnRecipient()->getCity(),
                $implode($requestExtractor->getReturnRecipient()->getStreet()),
                $requestExtractor->getReturnRecipient()->getContactCompanyName()
            );
        }

        /** @var PackageInterface $package */
        foreach ($requestExtractor->getPackages() as $packageId => $package) {
            $weight = $package->getWeight();
            $weightUom = $package->getWeightUom();
            $weightInKg = $this->unitConverter->convertWeight($weight, $weightUom, \Zend_Measure_Weight::KILOGRAM);

            $codAmount = null;
            $reasonForPayment = null;
            if ($requestExtractor->isCashOnDelivery()) {
                $codAmount = ((float)($requestExtractor->getOrder()->getBaseGrandTotal()) * 100) / 100;
                $reasonForPayment = $requestExtractor->getCodReasonForPayment();
            }

            $this->requestBuilder->addParcel(
                $weightInKg,
                $requestExtractor->getOrder()->getIncrementId(),
                null,
                $codAmount,
                $reasonForPayment
            );

            $packageAdditional = $package->getPackageAdditional();
            if ($packageAdditional instanceof PackageAdditional && !empty($packageAdditional->getTermsOfTrade())) {
                $this->requestBuilder->setCustomsDetails((int) $packageAdditional->getTermsOfTrade());
            }
        }

        $this->requestBuilder->setShipmentDate($requestExtractor->getShipmentDate());
        $this->requestBuilder->setLabelFormat(ShipmentRequestBuilderInterface::LABEL_FORMAT_PDF);

        $labelSize = $this->moduleConfig->getLabelSize($requestExtractor->getStoreId());
        if ($labelSize === LabelSize::LABEL_SIZE_A6) {
            $this->requestBuilder->setLabelSize(ShipmentRequestBuilderInterface::LABEL_SIZE_A6);
        } elseif ($labelSize === LabelSize::LABEL_SIZE_A5) {
            $this->requestBuilder->setLabelSize(ShipmentRequestBuilderInterface::LABEL_SIZE_A5);
        } elseif ($labelSize === LabelSize::LABEL_SIZE_A4) {
            $this->requestBuilder->setLabelSize(ShipmentRequestBuilderInterface::LABEL_SIZE_A4);
        }

        try {
            return $this->requestBuilder->create();
        } catch (RequestValidatorException $exception) {
            $message = __('Web service request could not be created: %1', $exception->getMessage());
            throw new LocalizedException($message);
        }
    }
}
