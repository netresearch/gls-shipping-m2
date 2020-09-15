<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGermany\Shipping\Model\Carrier;

use GlsGermany\Shipping\Model\Config\ModuleConfig;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Directory\Helper\Data;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
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
     * @var ProxyCarrierFactory
     */
    private $proxyCarrierFactory;

    /**
     * @var AbstractCarrierInterface
     */
    private $proxyCarrier;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * GLS Germany carrier constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param RateErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param Security $xmlSecurity
     * @param ElementFactory $xmlElFactory
     * @param RateResultFactory $rateFactory
     * @param MethodFactory $rateMethodFactory
     * @param TrackResultFactory $trackFactory
     * @param TrackErrorFactory $trackErrorFactory
     * @param StatusFactory $trackStatusFactory
     * @param RegionFactory $regionFactory
     * @param CountryFactory $countryFactory
     * @param CurrencyFactory $currencyFactory
     * @param Data $directoryData
     * @param StockRegistryInterface $stockRegistry
     * @param RatesManagement $ratesManagement
     * @param ModuleConfig $moduleConfig
     * @param ProxyCarrierFactory $proxyCarrierFactory
     * @param mixed[] $data
     */
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
        array $data = []
    ) {
        $this->ratesManagement = $ratesManagement;
        $this->moduleConfig = $moduleConfig;
        $this->proxyCarrierFactory = $proxyCarrierFactory;

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
     * Returns the configured proxied carrier instance.
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
     * @param DataObject $request
     * @return DataObject
     */
    protected function _doShipmentRequest(DataObject $request)
    {
        return $this->dataObjectFactory->create();
    }

    /**
     * Check if city option required.
     *
     * @return boolean
     */
    public function isCityRequired(): bool
    {
        try {
            return $this->getProxyCarrier()->isCityRequired();
        } catch (LocalizedException $exception) {
            return parent::isCityRequired();
        }
    }

    /**
     * Determine whether zip-code is required for the country of destination.
     *
     * @param string|null $countryId
     * @return bool
     */
    public function isZipCodeRequired($countryId = null): bool
    {
        try {
            return $this->getProxyCarrier()->isZipCodeRequired($countryId);
        } catch (LocalizedException $exception) {
            return parent::isZipCodeRequired($countryId);
        }
    }
}
