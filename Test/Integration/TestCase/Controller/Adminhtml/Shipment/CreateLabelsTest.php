<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGroup\Shipping\Test\Integration\TestCase\Controller\Adminhtml\Shipment;

use GlsGroup\Shipping\Model\Carrier\GlsGroup;
use GlsGroup\Shipping\Model\Pipeline\CreateShipments\Stage\SendRequestStage;
use GlsGroup\Shipping\Test\Integration\TestDouble\Pipeline\CreateShipments\Stage\SendRequestStageStub;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentTrackInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Collection;
use Netresearch\ShippingCore\Api\LabelStatus\LabelStatusManagementInterface;
use Netresearch\ShippingCore\Test\Integration\Fixture\OrderBuilder;
use TddWizard\Fixtures\Sales\ShipmentBuilder;

/**
 * Create shipments for selected orders, do not retry failed shipments.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class CreateLabelsTest extends AutoCreateTest
{
    /**
     * @test
     *
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
     * @magentoConfigFixture default/shipping/batch_processing/retry_failed_shipments_shipments 0
     *
     * @magentoConfigFixture current_store carriers/glsgroup/active 1
     * @magentoConfigFixture current_store carriers/glsgroup/checkout/emulated_carrier flatrate
     * @magentoConfigFixture current_store carriers/glsgroup/account/customer_id foo
     * @magentoConfigFixture current_store carriers/glsgroup/account/contact_id bar
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     *
     * @throws \Exception
     */
    public function createLabels()
    {
        $orders = [];
        $shippedOrders = [];

        $shippingMethod = GlsGroup::CARRIER_CODE . '_flatrate';
        for ($i = 0; $i < 3; $i++) {
            /** @var Order $order */
            $order = OrderBuilder::anOrder()->withShippingMethod($shippingMethod)->build();
            /** @var Order $shippedOrder */
            $shippedOrder = OrderBuilder::anOrder()
                ->withShippingMethod($shippingMethod)
                ->withLabelStatus(LabelStatusManagementInterface::LABEL_STATUS_FAILED)
                ->build();
            ShipmentBuilder::forOrder($shippedOrder)->build();

            $this->orderFixtures->add($order);
            $this->orderFixtures->add($shippedOrder);

            // reset items to have them reloaded with item id as index
            $orders[] = $order->setItems([]);
            $shippedOrders[] = $shippedOrder->setItems([]);
        }

        // collect order ids selected in grid
        $selectedPendingOrderIds = [
            $orders[0]->getId(),
            $orders[2]->getId(),
        ];
        $selectedProcessedOrderIds = [
            $shippedOrders[0]->getId(),
            $shippedOrders[2]->getId(),
        ];
        $selectedOrderIds = array_merge($selectedPendingOrderIds, $selectedProcessedOrderIds);

        // prepare mass action post data from order fixtures
        $postData = [
            'selected' => $selectedOrderIds,
            'namespace' => 'sales_order_grid'
        ];

        $this->getRequest()->setMethod($this->httpMethod);
        $this->getRequest()->setPostValue($postData);
        $this->dispatch($this->uri);

        /** @var SendRequestStageStub $pipelineStage */
        $pipelineStage = $this->_objectManager->get(SendRequestStage::class);

        // assert only pending orders were sent to api
        self::assertCount(count($selectedPendingOrderIds), $pipelineStage->apiRequests);

        // load shipments for all orders placed during test setup
        $fixtureOrderIds = array_map(function (Order $order) {
            return $order->getId();
        }, array_merge($orders, $shippedOrders));

        /** @var Collection $shipmentCollection */
        $shipmentCollection = $this->_objectManager->create(Collection::class);
        $shipmentCollection->addFieldToFilter('order_id', ['in' => [$fixtureOrderIds]]);

        // assert every order now has one shipment
        $shipmentCount = count($shippedOrders) + count($selectedPendingOrderIds);
        self::assertCount($shipmentCount, $shipmentCollection);

        /** @var ShipmentInterface $shipment */
        foreach ($shipmentCollection as $shipment) {
            /** @var ShipmentTrackInterface[] $tracks */
            $tracks = array_values($shipment->getTracks());
            if (in_array($shipment->getOrderId(), $selectedPendingOrderIds)) {
                // requested orders should now have exactly one label and one track assigned
                self::assertStringStartsWith('%PDF-1', $shipment->getShippingLabel());
                self::assertCount(1, $tracks);
                self::assertStringStartsWith((string)$shipment->getOrderId(), $tracks[0]->getTrackNumber());
            } else {
                // existing orders should remain untouched
                self::assertEmpty($shipment->getShippingLabel());
                self::assertEmpty($tracks);
            }
        }
    }
}
