<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGermany\Shipping\Test\Integration\TestCase\Controller\Adminhtml\Shipment;

use GlsGermany\Shipping\Model\Pipeline\CreateShipments\Stage\SendRequestStage as CreationStage;
use GlsGermany\Shipping\Test\Integration\TestCase\Controller\Adminhtml\ControllerTest;
use GlsGermany\Shipping\Test\Integration\TestDouble\Pipeline\CreateShipments\Stage\SendRequestStageStub;
use Magento\Framework\Exception\AuthenticationException;
use TddWizard\Fixtures\Sales\OrderFixturePool;

/**
 * Class AutoCreateTest
 *
 * Base controller test for the auto-create route.
 *
 * @method \Magento\Framework\App\Request\Http getRequest()
 */
abstract class AutoCreateTest extends ControllerTest
{
    /**
     * The resource used to authorize action
     *
     * @var string
     */
    protected $resource = 'Magento_Sales::ship';

    /**
     * The uri at which to access the controller
     *
     * @var string
     */
    protected $uri = 'backend/nrshipping/shipment/autocreate';

    /**
     * @var OrderFixturePool
     */
    protected $orderFixtures;

    /**
     * Configure pipeline stage for shipment creations.
     *
     * @throws AuthenticationException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->orderFixtures = new OrderFixturePool();

        // configure web service response
        $this->_objectManager->configure(['preferences' => [CreationStage::class => SendRequestStageStub::class]]);
    }

    protected function tearDown(): void
    {
        try {
            $this->orderFixtures->rollback();
        } catch (\Exception $exception) {
            $argv = $_SERVER['argv'] ?? [];
            if (in_array('--verbose', $argv, true)) {
                $message = sprintf("Error during rollback: %s%s", $exception->getMessage(), PHP_EOL);
                register_shutdown_function('fwrite', STDERR, $message);
            }
        }

        parent::tearDown();
    }

    /**
     * The actual test to be implemented.
     */
    abstract public function createLabels();

    /**
     * @magentoConfigFixture default_store general/store_information/name NR-Test-Store
     * @magentoConfigFixture default_store general/store_information/region_id 86
     * @magentoConfigFixture default_store general/store_information/phone 000
     * @magentoConfigFixture default_store general/store_information/country_id DE
     * @magentoConfigFixture default_store general/store_information/postcode 36286
     * @magentoConfigFixture default_store general/store_information/city Neuenstein
     * @magentoConfigFixture default_store general/store_information/street_line1 GLS Germany-Straße 1 - 7
     *
     * @magentoConfigFixture default_store shipping/origin/country_id DE
     * @magentoConfigFixture default_store shipping/origin/region_id 86
     * @magentoConfigFixture default_store shipping/origin/postcode 36286
     * @magentoConfigFixture default_store shipping/origin/city Neuenstein
     * @magentoConfigFixture default_store shipping/origin/street_line1 GLS Germany-Straße 1 - 7
     *
     * @magentoConfigFixture default_store catalog/price/scope 0
     * @magentoConfigFixture default_store currency/options/base EUR
     * @magentoConfigFixture default_store shipping/batch_processing/retry_failed_shipments_shipments 0
     *
     * @magentoConfigFixture current_store carriers/glsgermany/active 1
     * @magentoConfigFixture current_store  carriers/glsgermany/checkout/emulated_carrier flatrate
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     */
    public function testAclHasAccess()
    {
        $postData = [
            'selected' => ['123456789', '987654321'],
            'namespace' => 'sales_order_grid'
        ];
        $this->getRequest()->setPostValue($postData);

        parent::testAclHasAccess();
    }
}
