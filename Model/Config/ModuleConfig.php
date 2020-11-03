<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGermany\Shipping\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\ScopeInterface;
use Netresearch\ShippingCore\Api\InfoBox\VersionInterface;
use Netresearch\ShippingCore\Api\Rate\ProxyCarrierConfigInterface;

class ModuleConfig implements VersionInterface, ProxyCarrierConfigInterface
{
    // Defaults
    private const CONFIG_PATH_VERSION = 'carriers/glsgermany/version';

    // 100_general_settings.xml
    private const CONFIG_PATH_CUT_OFF_TIMES = 'carriers/glsgermany/general/cut_off_times';

    public const CONFIG_PATH_ENABLE_LOGGING = 'carriers/glsgermany/general/logging';
    public const CONFIG_PATH_LOGLEVEL = 'carriers/glsgermany/general/logging_group/loglevel';

    // 200_account_settings.xml
    private const CONFIG_PATH_SANDBOX_MODE = 'carriers/glsgermany/account/sandboxmode';
    private const CONFIG_PATH_USER_NAME = 'carriers/glsgermany/account/api_username';
    private const CONFIG_PATH_PASSWORD = 'carriers/glsgermany/account/api_password';
    private const CONFIG_PATH_CUSTOMER_ID = 'carriers/glsgermany/account/customer_id';
    private const CONFIG_PATH_CONTACT_ID = 'carriers/glsgermany/account/contact_id';
    private const CONFIG_PATH_BROKER_REFERENCE = 'carriers/glsgermany/account/broker_reference';

    // 400_checkout_settings.xml
    private const CONFIG_PATH_PROXY_CARRIER = 'carriers/glsgermany/checkout/emulated_carrier';
    private const CONFIG_PATH_SHIPPING_METHOD_TITLE = 'carriers/glsgermany/checkout/method_title';

    // 500_shipment_defaults.xml
    private const CONFIG_PATH_LABEL_SIZE = 'carriers/glsgermany/shipment_defaults/label_size';
    private const CONFIG_PATH_PACKAGE_DEFAULT_WEIGHT = 'carriers/glsgermany/shipment_defaults/package_default_weight';
    private const CONFIG_PATH_TERMS_OF_TRADE = 'carriers/glsgermany/shipment_defaults/terms_of_trade';
    private const CONFIG_PATH_SEND_SHIPPER = 'carriers/glsgermany/shipment_defaults/send_shipper';
    private const CONFIG_PATH_USE_SHOPRETURN = 'carriers/glsgermany/shipment_defaults/shopreturn';
    private const CONFIG_PATH_USE_LETTERBOX = 'carriers/glsgermany/shipment_defaults/letterbox';
    private const CONFIG_PATH_ENABLE_ALT_RETURN_ADDRESS = 'carriers/glsgermany/shipment_defaults/return_address';
    private const CONFIG_PATH_ALT_RETURN_ADDRESS = 'carriers/glsgermany/shipment_defaults/alt_return_address';

    // 600_additional_services.xml
    private const CONFIG_PATH_FLEXDELIVERY_REVOCATION_EMAIL = 'carriers/glsgermany/additional_services/flexdelivery_identity';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        TimezoneInterface $timezone
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->timezone = $timezone;
    }

    /**
     * @return string
     */
    public function getModuleVersion(): string
    {
        return (string) $this->scopeConfig->getValue(self::CONFIG_PATH_VERSION);
    }

    /**
     * Returns true if sandbox mode is enabled.
     *
     * @param mixed $store
     * @return bool
     */
    public function isSandboxMode($store = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_SANDBOX_MODE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the user's name (API user credentials).
     *
     * @param mixed $store
     * @return string
     */
    public function getUserName($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_USER_NAME,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the password (API user credentials).
     *
     * @param mixed $store
     * @return string
     */
    public function getPassword($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_PASSWORD,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the shipper id.
     *
     * @param mixed $store
     * @return string
     */
    public function getShipperId($store = null): string
    {
        $customerId = (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_CUSTOMER_ID,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        $contactId = (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_CONTACT_ID,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return $customerId . ' ' . $contactId;
    }

    /**
     * Get the shipper id.
     *
     * @return string
     */
    public function getBrokerReference(): string
    {
        return (string) $this->scopeConfig->getValue(self::CONFIG_PATH_BROKER_REFERENCE);
    }

    /**
     * Get the logging status.
     *
     * @return bool
     */
    public function isLoggingEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::CONFIG_PATH_ENABLE_LOGGING);
    }

    /**
     * Get the log level.
     *
     * @return int
     */
    public function getLogLevel(): int
    {
        return (int) $this->scopeConfig->getValue(self::CONFIG_PATH_LOGLEVEL);
    }

    /**
     * Get the code of the carrier to forward rate requests to.
     *
     * @param mixed $store
     * @return string
     */
    public function getProxyCarrierCode($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_PROXY_CARRIER,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Obtain the preferred document size for shipping labels.
     *
     * @param mixed $store
     * @return string
     */
    public function getLabelSize($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_LABEL_SIZE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Determine whether the shipping origin setting should be used as shipper address or not.
     *
     * @param mixed $store
     * @return bool
     */
    public function isSendFromStoreShippingOrigin($store = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::CONFIG_PATH_SEND_SHIPPER, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * Obtain email address used for revoking consent of transmitting the consumer email.
     *
     * @param mixed $store
     * @return string
     */
    public function getFlexDeliveryRevocationEmail($store = null): string
    {
        $ident = $this->scopeConfig->getValue(
            self::CONFIG_PATH_FLEXDELIVERY_REVOCATION_EMAIL,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return $this->scopeConfig->getValue(
            'trans_email/ident_' . $ident . '/email',
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Obtain the list of cut-off times, applied to the upcoming days (max. seven entries).
     *
     * @param mixed $store
     * @return \DateTime[]
     */
    public function getCutOffTimes($store = null): array
    {
        $cutOffTimes = $this->scopeConfig->getValue(
            self::CONFIG_PATH_CUT_OFF_TIMES,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        $cutOffTimes = array_column($cutOffTimes, 'time', 'day');

        $days = [];
        for ($i = 0; $i <= 6; $i++) {
            $day = $this->timezone->scopeDate($store)->modify("+$i day");
            $weekDay = $day->format('N');
            if (!isset($cutOffTimes[$weekDay])) {
                // no cut-off configured for the given day, next.
                continue;
            }

            $cutOffTime =  explode(':', $cutOffTimes[$weekDay]);
            list($hours, $minutes) = array_map('intval', $cutOffTime);
            $day->setTime($hours, $minutes);
            $days[$weekDay] = $day;
        }

        return $days;
    }

    /**
     * Obtain the carrier method title for checkout presentation.
     *
     * @param mixed $store
     * @return string
     */
    public function getMethodTitle($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_SHIPPING_METHOD_TITLE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Obtain configured alternative return address.
     *
     * - company
     * - country_id
     * - postcode
     * - city
     * - street
     *
     * @param mixed $store
     * @return string[] Empty array if alternative address should not be used, address details otherwise.
     */
    public function getAlternativeReturnAddress($store = null): array
    {
        $useAlternativeAddress = $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_ENABLE_ALT_RETURN_ADDRESS,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        if (!$useAlternativeAddress) {
            return [];
        }

        return (array)$this->scopeConfig->getValue(
            self::CONFIG_PATH_ALT_RETURN_ADDRESS,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}
