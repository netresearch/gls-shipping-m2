<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGermany\Shipping\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Netresearch\ShippingCore\Api\InfoBox\VersionInterface;

class ModuleConfig implements VersionInterface
{
    // Defaults
    private const CONFIG_PATH_VERSION = 'carriers/glsgermany/version';

    // 100_general_settings.xml
    private const CONFIG_PATH_ENABLE_LOGGING = 'carriers/glsgermany/general/logging';
    private const CONFIG_PATH_LOGLEVEL = 'carriers/glsgermany/general/logging_group/loglevel';

    // 200_gls_account.xml
    private const CONFIG_PATH_SANDBOX_MODE = 'carriers/glsgermany/account/sandboxmode';
    private const CONFIG_PATH_USER_NAME = 'carriers/glsgermany/account/api_username';
    private const CONFIG_PATH_PASSWORD = 'carriers/glsgermany/account/api_password';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
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
}
