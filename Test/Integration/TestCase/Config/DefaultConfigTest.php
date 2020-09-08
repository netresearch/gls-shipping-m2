<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGermany\Shipping\Test\Integration\TestCase\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Shipping\Model\Carrier\AbstractCarrierOnline;
use Magento\Shipping\Model\CarrierFactoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class DefaultConfigTest extends TestCase
{
    /**
     * @var ScopeConfigInterface
     */
    private $configReader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configReader = Bootstrap::getObjectManager()->create(ScopeConfigInterface::class);
    }

    /**
     * Assert carrier model is configured and exists
     *
     * @test
     */
    public function carrierModel()
    {
        /** @var CarrierFactoryInterface $carrierFactory */
        $carrierFactory = Bootstrap::getObjectManager()->create(CarrierFactoryInterface::class);
        self::assertInstanceOf(AbstractCarrierOnline::class, $carrierFactory->get('glsgermany'));
    }

    /**
     * Assert generic carrier settings exist in config defaults and are set to valid values.
     *
     * @test
     */
    public function carrierDefaults()
    {
        self::assertTrue($this->configReader->isSetFlag('carriers/glsgermany/is_online'));
        self::assertGreaterThan(0, (int) $this->configReader->getValue('carriers/glsgermany/max_package_weight'));
        self::assertFalse($this->configReader->isSetFlag('carriers/glsgermany/active'));
        self::assertNotEmpty($this->configReader->getValue('carriers/glsgermany/title'));
        self::assertIsNumeric($this->configReader->getValue('carriers/glsgermany/sort_order'));
        self::assertTrue($this->configReader->isSetFlag('carriers/glsgermany/showmethod'));
        self::assertNotEmpty($this->configReader->getValue('carriers/glsgermany/specificerrmsg'));
        self::assertFalse($this->configReader->isSetFlag('carriers/glsgermany/sallowspecific'));
    }
}
