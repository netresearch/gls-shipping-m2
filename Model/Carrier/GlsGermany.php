<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGermany\Shipping\Model\Carrier;

use GlsGermany\Shipping\Model\BulkShipment\ShipmentManagement;
use GlsGermany\Shipping\Model\Config\ModuleConfig;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Directory\Helper\Data;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Xml\Security;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory as RateErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrierInterface;
use Magento\Shipping\Model\Carrier\AbstractCarrierOnline;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\ResultFactory as RateResultFactory;
use Magento\Shipping\Model\Shipment\Request;
use Magento\Shipping\Model\Simplexml\ElementFactory;
use Magento\Shipping\Model\Tracking\Result\ErrorFactory as TrackErrorFactory;
use Magento\Shipping\Model\Tracking\Result\StatusFactory;
use Magento\Shipping\Model\Tracking\ResultFactory as TrackResultFactory;
use Netresearch\ShippingCore\Model\Rate\Emulation\ProxyCarrierFactory;
use Netresearch\ShippingCore\Model\Rate\Emulation\RatesManagement;
use Psr\Log\LoggerInterface;

class GlsGermany extends AbstractCarrierOnline implements CarrierInterface
{
    public const CARRIER_CODE = 'glsgermany';

    /**
     * @var string
     */
    protected $_code = self::CARRIER_CODE;

    /**
     * @var RatesManagement
     */
    private $ratesManagement;

    /**
     * @var ShipmentManagement
     */
    private $shipmentManagement;

    /**
     * @var ProxyCarrierFactory
     */
    private $proxyCarrierFactory;

    /**
     * @var AbstractCarrierInterface
     */
    private $proxyCarrier;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        RateErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        Security $xmlSecurity,
        ElementFactory $xmlElFactory,
        RateResultFactory $rateFactory,
        MethodFactory $rateMethodFactory,
        TrackResultFactory $trackFactory,
        TrackErrorFactory $trackErrorFactory,
        StatusFactory $trackStatusFactory,
        RegionFactory $regionFactory,
        CountryFactory $countryFactory,
        CurrencyFactory $currencyFactory,
        Data $directoryData,
        StockRegistryInterface $stockRegistry,
        RatesManagement $ratesManagement,
        ModuleConfig $moduleConfig,
        ProxyCarrierFactory $proxyCarrierFactory,
        ShipmentManagement $shipmentManagement,
        array $data = []
    ) {
        $this->ratesManagement = $ratesManagement;
        $this->moduleConfig = $moduleConfig;
        $this->proxyCarrierFactory = $proxyCarrierFactory;
        $this->shipmentManagement = $shipmentManagement;

        parent::__construct(
            $scopeConfig,
            $rateErrorFactory,
            $logger,
            $xmlSecurity,
            $xmlElFactory,
            $rateFactory,
            $rateMethodFactory,
            $trackFactory,
            $trackErrorFactory,
            $trackStatusFactory,
            $regionFactory,
            $countryFactory,
            $currencyFactory,
            $directoryData,
            $stockRegistry,
            $data
        );
    }

    /**
     * Returns the configured carrier instance.
     *
     * @return AbstractCarrierInterface
     * @throws NotFoundException
     */
    private function getProxyCarrier()
    {
        if (!$this->proxyCarrier) {
            $storeId = $this->getData('store');
            $carrierCode = $this->moduleConfig->getProxyCarrierCode($storeId);

            $this->proxyCarrier = $this->proxyCarrierFactory->create($carrierCode);
        }

        return $this->proxyCarrier;
    }

    /**
     * Check if the carrier can handle the given rate request.
     *
     * GLS Germany carrier ships only from DE.
     *
     * @todo(nr): check destination country
     * @param DataObject $request
     * @return bool|DataObject|AbstractCarrierOnline
     */
    public function processAdditionalValidation(DataObject $request)
    {
        $shippingOrigin = (string) $request->getData('country_id');
        if ($shippingOrigin !== 'DE') {
            return false;
        }

        return parent::processAdditionalValidation($request);
    }

    public function collectRates(RateRequest $request)
    {
        $result = $this->_rateFactory->create();

        if ($this->_activeFlag && !$this->getConfigFlag($this->_activeFlag)) {
            return $result;
        }
        // set carrier details for rate post-processing
        $request->setData('carrier_code', $this->getCarrierCode());
        $request->setData('carrier_title', $this->getConfigData('title'));

        $proxyResult = $this->ratesManagement->collectRates($request);
        if (!$proxyResult) {
            $result->append($this->getErrorMessage());

            return $result;
        }

        return $proxyResult;
    }

    /**
     * Obtain shipping methods offered by the carrier.
     *
     * The GLS Germany carrier does not offer own methods. The call gets
     * forwarded to another carrier as configured via module settings.
     *
     * @return string[] Associative array of method names with method code as key.
     */
    public function getAllowedMethods(): array
    {
        try {
            $carrier = $this->getProxyCarrier();
        } catch (LocalizedException $exception) {
            return [];
        }

        if (!$carrier instanceof CarrierInterface) {
            return [];
        }

        return $carrier->getAllowedMethods();
    }

    /**
     * Perform a shipment request to the GLS web service.
     *
     * Return either tracking number and label data or a shipment error.
     * Note that Magento triggers one web service request per package in multi-package shipments.
     *
     * @param DataObject|Request $request
     * @return DataObject
     * @see \Magento\Shipping\Model\Carrier\AbstractCarrierOnline::requestToShipment
     * @see \Magento\Shipping\Model\Carrier\AbstractCarrierOnline::returnOfShipment
     */
    protected function _doShipmentRequest(DataObject $request): DataObject
    {
        /** @var DataObject[] $apiResult */
        $apiResult = $this->shipmentManagement->createLabels([$request->getData('package_id') => $request]);

        // one request, one response.
        return $apiResult[0];
    }

    public function isCityRequired(): bool
    {
        try {
            return $this->getProxyCarrier()->isCityRequired();
        } catch (LocalizedException $exception) {
            return parent::isCityRequired();
        }
    }

    public function isZipCodeRequired($countryId = null): bool
    {
        try {
            return $this->getProxyCarrier()->isZipCodeRequired($countryId);
        } catch (LocalizedException $exception) {
            return parent::isZipCodeRequired($countryId);
        }
    }
}
