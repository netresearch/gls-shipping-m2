<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGroup\Shipping\Test\Integration\TestCase\Controller\Adminhtml\Order\Shipment;

use GlsGroup\Shipping\Model\Pipeline\CreateShipments\Stage\SendRequestStage as CreationStage;
use GlsGroup\Shipping\Model\Pipeline\DeleteShipments\Stage\SendRequestStage as CancellationStage;
use GlsGroup\Shipping\Test\Integration\Provider\Controller\SaveShipment\PostDataProviderCrossBorder;
use GlsGroup\Shipping\Test\Integration\Provider\Controller\SaveShipment\PostDataProviderDomestic;
use GlsGroup\Shipping\Test\Integration\TestCase\Controller\Adminhtml\ControllerTest;
use GlsGroup\Shipping\Test\Integration\TestDouble\Pipeline\CreateShipments\Stage\SendRequestStageStub as CreationStageStub;
use GlsGroup\Shipping\Test\Integration\TestDouble\Pipeline\DeleteShipments\Stage\SendRequestStageStub as CancellationStageStub;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Sales\Api\Data\OrderInterface;
use TddWizard\Fixtures\Sales\OrderFixturePool;

/**
 * Base test to build various shipment creation scenarios on.
 *
 * @method \Magento\Framework\App\Request\Http getRequest()
 * @method \Magento\Framework\App\Response\Http getResponse()
 */
abstract class SaveShipmentTest extends ControllerTest
{
    /**
     * The resource used to authorize action
     *
     * @var string
     */
    protected $resource = 'Magento_Sales::shipment';

    /**
     * The uri at which to access the controller
     *
     * @var string
     */
    protected $uri = 'backend/nrshipping/order_shipment/save';

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

        // configure positive web service response
        $this->_objectManager->configure(
            [
                'preferences' => [
                    CreationStage::class => CreationStageStub::class,
                    CancellationStage::class => CancellationStageStub::class,
                ],
            ]
        );
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

    public function postDataProviderDomestic()
    {
        return [
            'single_package' => [
                function (OrderInterface $order) {
                    return PostDataProviderDomestic::singlePackageDomestic($order);
                },
            ],
            'multi_package' => [
                function (OrderInterface $order) {
                    return PostDataProviderDomestic::multiPackageDomestic($order);
                },
            ],
        ];
    }

    public function postDataProviderCod()
    {
        return [
            'single_package' => [
                function (OrderInterface $order) {
                    return PostDataProviderDomestic::singlePackageDomesticWithCod($order);
                },
            ],
            'multi_package' => [
                function (OrderInterface $order) {
                    return PostDataProviderDomestic::multiPackageDomesticWithCod($order);
                },
            ],
        ];
    }

    public function postDataProviderCrossBorder()
    {
        return [
            'single_package_xb' => [
                function (OrderInterface $order) {
                    return PostDataProviderCrossBorder::singlePackageCrossBorder($order);
                },
            ],
            'multi_package_xb' => [
                function (OrderInterface $order) {
                    return PostDataProviderCrossBorder::multiPackageCrossBorder($order);
                },
            ]
        ];
    }

    /**
     * The actual test to be implemented.
     *
     * @param callable $getPostData
     */
    abstract public function saveShipment(callable $getPostData);

    /**
     * @magentoConfigFixture default_store general/store_information/name NR-Test-Store
     * @magentoConfigFixture default_store general/store_information/region_id 86
     * @magentoConfigFixture default_store general/store_information/phone 000
     * @magentoConfigFixture default_store general/store_information/country_id DE
     * @magentoConfigFixture default_store general/store_information/postcode 36286
     * @magentoConfigFixture default_store general/store_information/city Neuenstein
     * @magentoConfigFixture default_store general/store_information/street_line1 GLS-Germany-Straße 1 - 7
     *
     * @magentoConfigFixture default_store shipping/origin/country_id DE
     * @magentoConfigFixture default_store shipping/origin/region_id 86
     * @magentoConfigFixture default_store shipping/origin/postcode 36286
     * @magentoConfigFixture default_store shipping/origin/city Neuenstein
     * @magentoConfigFixture default_store shipping/origin/street_line1 GLS-Germany-Straße 1 - 7
     *
     * @magentoConfigFixture default_store catalog/price/scope 0
     * @magentoConfigFixture default_store currency/options/base EUR
     *
     * @magentoConfigFixture current_store carriers/glsgroup/active 1
     * @magentoConfigFixture current_store carriers/glsgroup/checkout_settings/emulated_carrier flatrate
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     */
    public function testAclHasAccess()
    {
        $this->getRequest()->setParam('order_id', '123456789');
        $this->getRequest()->setParam('data', '[]');

        parent::testAclHasAccess();
    }
}
