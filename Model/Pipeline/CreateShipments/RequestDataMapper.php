<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGermany\Shipping\Model\Pipeline\CreateShipments;

use GlsGermany\Sdk\ParcelProcessing\Api\ShipmentRequestBuilderInterface;
use GlsGermany\Sdk\ParcelProcessing\Exception\RequestValidatorException;
use GlsGermany\Shipping\Model\Pipeline\CreateShipments\ShipmentRequest\RequestExtractorFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Shipping\Model\Shipment\Request;
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
     * @var UnitConverterInterface
     */
    private $unitConverter;

    public function __construct(
        ShipmentRequestBuilderInterface $requestBuilder,
        RequestExtractorFactory $requestExtractorFactory,
        UnitConverterInterface $unitConverter
    ) {
        $this->requestBuilder = $requestBuilder;
        $this->requestExtractorFactory = $requestExtractorFactory;
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
        $requestExtractor = $this->requestExtractorFactory->create(['shipmentRequest' => $request]);

        $this->requestBuilder->setShipperAccount(
            $requestExtractor->getShipperId(),
            $requestExtractor->getBrokerReference()
        );

        // todo(nr): omit shipper to use gls account setting
        $this->requestBuilder->setShipperAddress(
            $requestExtractor->getShipper()->getCountryCode(),
            $requestExtractor->getShipper()->getPostalCode(),
            $requestExtractor->getShipper()->getCity(),
            implode(' ', $requestExtractor->getShipper()->getStreet()),
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

        if ($requestExtractor->isRecipientEmailRequired()) {
            $recipientEmail = $requestExtractor->getRecipient()->getContactEmail();
        } else {
            $recipientEmail = null;
        }

        $this->requestBuilder->setRecipientAddress(
            $requestExtractor->getRecipient()->getCountryCode(),
            $requestExtractor->getRecipient()->getPostalCode(),
            $requestExtractor->getRecipient()->getCity(),
            implode(' ', $requestExtractor->getRecipient()->getStreet()),
            $requestExtractor->getRecipient()->getContactPersonName(),
            $requestExtractor->getRecipient()->getContactCompanyName(),
            $recipientEmail,
            null,
            null,
            null,
            $requestExtractor->getShipper()->getState(),
            null
        );

        if ($requestExtractor->isFlexDeliveryEnabled()) {
            //todo(nr): pass in contact data here?
            $this->requestBuilder->requestFlexDeliveryService();
        }

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
        }

        try {
            return $this->requestBuilder->create();
        } catch (RequestValidatorException $exception) {
            $message = __('Web service request could not be created: %1', $exception->getMessage());
            throw new LocalizedException($message);
        }
    }
}
