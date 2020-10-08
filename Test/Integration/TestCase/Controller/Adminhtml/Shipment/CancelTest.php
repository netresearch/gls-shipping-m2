<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGermany\Shipping\Test\Integration\TestCase\Controller\Adminhtml\Shipment;

use GlsGermany\Shipping\Model\Pipeline\DeleteShipments\Stage\SendRequestStage;
use GlsGermany\Shipping\Model\Webservice\CancellationService;
use GlsGermany\Shipping\Test\Integration\TestDouble\CancellationServiceStub;
use GlsGermany\Shipping\Test\Integration\TestDouble\Pipeline\DeleteShipments\Stage\SendRequestStageStub;
use Magento\Sales\Api\Data\TrackInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractBackendController;
use Netresearch\ShippingCore\Api\LabelStatus\LabelStatusManagementInterface;
use Netresearch\ShippingCore\Model\LabelStatus\LabelStatusProvider;
use TddWizard\Fixtures\Sales\OrderBuilder;
use TddWizard\Fixtures\Sales\OrderFixturePool;
use TddWizard\Fixtures\Sales\ShipmentBuilder;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 * @method \Magento\Framework\App\Request\Http getRequest()
 */
class CancelTest extends AbstractBackendController
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
    protected $uri = 'backend/nrshipping/shipment/cancel';

    /**
     * The HTTP method to use when calling the controller
     *
     * @var string
     */
    protected $httpMethod = 'GET';

    /**
     * @var OrderFixturePool
     */
    private $orderFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderFixtures = new OrderFixturePool();

        Bootstrap::getObjectManager()->configure(
            [
                'preferences' => [
                    SendRequestStage::class => SendRequestStageStub::class,
                    CancellationService::class => CancellationServiceStub::class,
                ]
            ]
        );
    }

    protected function tearDown(): void
    {
        $this->orderFixtures->rollback();

        parent::tearDown();
    }

    /**
     * Cancellation is requested and web service returns positive result.
     *
     * - Assert tracking numbers are removed
     * - Assert shipping label is removed
     * - Assert label status remains is set back to pending
     *
     * @test
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
     * @magentoConfigFixture current_store carriers/glsgermany/active 1
     * @magentoConfigFixture current_store carriers/glsgermany/checkout/emulated_carrier flatrate
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     * @throws \Exception
     */
    public function cancellationSucceeds()
    {
        $trackingNumbers = ['GLS123', 'GLS456'];

        /** @var Order $order */
        $order = OrderBuilder::anOrder()->withShippingMethod('glsgermany_flatrate')->build();
        $this->orderFixtures->add($order);

        $fixtureShipment = ShipmentBuilder::forOrder($order)
            ->withTrackingNumbers(...$trackingNumbers)
            ->build();

        /** @var LabelStatusManagementInterface $labelStatusManagement */
        $labelStatusManagement = Bootstrap::getObjectManager()->create(LabelStatusManagementInterface::class);
        $labelStatusManagement->setLabelStatusProcessed($order);

        /** @var ShipmentRepositoryInterface $shipmentRepository */
        $shipmentRepository = Bootstrap::getObjectManager()->create(ShipmentRepositoryInterface::class);
        /** @var LabelStatusProvider $labelStatusProvider */
        $labelStatusProvider = Bootstrap::getObjectManager()->create(LabelStatusProvider::class);
        $labelStatus = $labelStatusProvider->getLabelStatus([$fixtureShipment->getOrderId()]);
        $labelStatusBefore = $labelStatus[$fixtureShipment->getOrderId()];

        // dispatch
        $this->getRequest()->setMethod($this->httpMethod);
        $this->getRequest()->setParam('shipment_id', $fixtureShipment->getEntityId());
        $this->dispatch($this->uri);

        $shipment = $shipmentRepository->get($fixtureShipment->getEntityId());
        $labelStatus = $labelStatusProvider->getLabelStatus([$shipment->getOrderId()]);
        $labelStatusAfter = $labelStatus[$fixtureShipment->getOrderId()];
        $trackingNumbersAfter = array_map(
            function (TrackInterface $track) {
                return $track->getTrackNumber();
            },
            array_values($shipment->getTracks())
        );

        // assert shipment is not modified
        self::assertEmpty($shipment->getShippingLabel());
        self::assertEmpty($trackingNumbersAfter);
        self::assertSame(LabelStatusManagementInterface::LABEL_STATUS_PENDING, $labelStatusAfter);
    }

    /**
     * Cancellation is requested but web service returns negative result.
     *
     * - Assert tracking numbers are still there
     * - Assert shipping label is still there
     * - Assert label status remains the same as before
     *
     * @test
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
     * @magentoConfigFixture current_store carriers/glsgermany/active 1
     * @magentoConfigFixture current_store carriers/glsgermany/checkout/emulated_carrier flatrate
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     * @throws \Exception
     */
    public function cancellationFails()
    {
        $trackingNumbers = ['GLS123', 'GLS456'];

        /** @var Order $order */
        $order = OrderBuilder::anOrder()->withShippingMethod('glsgermany_flatrate')->build();
        $this->orderFixtures->add($order);

        $fixtureShipment = ShipmentBuilder::forOrder($order)
            ->withTrackingNumbers(...$trackingNumbers)
            ->build();

        /** @var LabelStatusManagementInterface $labelStatusManagement */
        $labelStatusManagement = Bootstrap::getObjectManager()->create(LabelStatusManagementInterface::class);
        $labelStatusManagement->setLabelStatusProcessed($order);

        /** @var SendRequestStageStub $stage */
        $stage = Bootstrap::getObjectManager()->get(SendRequestStage::class);
        $stage->responseCallback = function () {
            // return empty response (no tracks successfully cancelled)
            return [];
        };

        /** @var ShipmentRepositoryInterface $shipmentRepository */
        $shipmentRepository = Bootstrap::getObjectManager()->create(ShipmentRepositoryInterface::class);
        /** @var LabelStatusProvider $labelStatusProvider */
        $labelStatusProvider = Bootstrap::getObjectManager()->create(LabelStatusProvider::class);
        $labelStatus = $labelStatusProvider->getLabelStatus([$fixtureShipment->getOrderId()]);
        $labelStatusBefore = $labelStatus[$fixtureShipment->getOrderId()];

        // dispatch
        $this->getRequest()->setMethod($this->httpMethod);
        $this->getRequest()->setParam('shipment_id', $fixtureShipment->getEntityId());
        $this->dispatch($this->uri);

        $shipment = $shipmentRepository->get($fixtureShipment->getEntityId());
        $labelStatus = $labelStatusProvider->getLabelStatus([$shipment->getOrderId()]);
        $labelStatusAfter = $labelStatus[$fixtureShipment->getOrderId()];
        $trackingNumbersAfter = array_map(
            function (TrackInterface $track) {
                return $track->getTrackNumber();
            },
            array_values($shipment->getTracks())
        );

        // assert shipment is not modified
        self::assertSame($fixtureShipment->getShippingLabel(), $shipment->getShippingLabel());
        self::assertSame($trackingNumbers, $trackingNumbersAfter);
        self::assertSame($labelStatusBefore, $labelStatusAfter);
    }
}
