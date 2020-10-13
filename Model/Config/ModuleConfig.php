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
    private const CONFIG_PATH_SHIPPER_ID = 'carriers/glsgermany/account/shipper_id';
    private const CONFIG_PATH_BROKER_REFERENCE = 'carriers/glsgermany/account/broker_reference';

    // 400_checkout_settings.xml
    private const CONFIG_PATH_PROXY_CARRIER = 'carriers/glsgermany/checkout/emulated_carrier';

    // 500_shipment_defaults.xml
    private const CONFIG_PATH_LABEL_SIZE = 'carriers/glsgermany/shipment_defaults/label_size';
    private const CONFIG_PATH_SEND_SHIPPER = 'carriers/glsgermany/shipment_defaults/send_shipper';

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
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_SHIPPER_ID,
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
    public function getBrokerReference($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_BROKER_REFERENCE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
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
}
