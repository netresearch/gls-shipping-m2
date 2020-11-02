<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGermany\Shipping\Test\Integration\TestCase\Controller\Adminhtml\Order\Shipment;

use GlsGermany\Shipping\Model\Carrier\GlsGermany;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Collection;
use Netresearch\ShippingCore\Api\LabelStatus\LabelStatusManagementInterface;
use Netresearch\ShippingCore\Model\LabelStatus\LabelStatusProvider;
use TddWizard\Fixtures\Catalog\ProductBuilder;
use TddWizard\Fixtures\Customer\AddressBuilder;
use TddWizard\Fixtures\Customer\CustomerBuilder;
use TddWizard\Fixtures\Sales\OrderBuilder;

/**
 * Test basic shipment creation for DE-DE route with no value-added services.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class CreateDomesticLabelTest extends SaveShipmentTest
{
    /**
     * Scenario: Two products are contained in an order, both are valid.
     *
     * - Assert that one shipment is created
     * - Assert that one tracking number is created per package
     * - Assert that label status is set to "Processed"
     *
     * @test
     * @dataProvider postDataProviderDomestic
     *
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
     * @magentoConfigFixture current_store carriers/glsgermany/account/customer_id foo
     * @magentoConfigFixture current_store carriers/glsgermany/account/contact_id bar
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     *
     * @param callable $getPostData
     * @throws \Exception
     */
    public function saveShipment(callable $getPostData)
    {
        $addressBuilder = AddressBuilder::anAddress('de_DE')->asDefaultBilling()->asDefaultShipping();

        /** @var \Magento\Sales\Model\Order $order */
        $order = OrderBuilder::anOrder()
            ->withShippingMethod(GlsGermany::CARRIER_CODE . '_flatrate')
            ->withProducts(
                ProductBuilder::aSimpleProduct()->withWeight(0.65)->withSku('foo'),
                ProductBuilder::aSimpleProduct()->withWeight(0.99)->withSku('bar')
            )
            ->withCustomer(CustomerBuilder::aCustomer()->withAddresses($addressBuilder))
            ->build();
        $this->orderFixtures->add($order);

        // create packaging post data from order fixture
        $data = $getPostData($order);

        // dispatch
        $this->getRequest()->setMethod($this->httpMethod);
        $this->getRequest()->setPostValue('data', \json_encode($data));
        $this->getRequest()->setParam('order_id', $order->getEntityId());
        $this->dispatch($this->uri);

        /** @var Collection $shipmentCollection */
        $shipmentCollection = $this->_objectManager->create(Collection::class);
        $shipments = $shipmentCollection->setOrderFilter($order)->getItems();
        $shipments = array_values($shipments);

        // assert that exactly one shipment was created for the order
        self::assertCount(1, $shipments);
        $shipment = $shipments[0];

        // assert shipping label was persisted with shipment
        self::assertStringStartsWith('%PDF-1', $shipment->getShippingLabel());

        // assert that one track was created per package
        $tracks = $shipment->getTracks();
        self::assertCount(count($data['packages']), $tracks);

        // assert that the order's label status is "Processed"
        /** @var LabelStatusProvider $labelStatusProvider */
        $labelStatusProvider = $this->_objectManager->create(LabelStatusProvider::class);
        $labelStatus = $labelStatusProvider->getLabelStatus([$order->getEntityId()]);
        self::assertSame(LabelStatusManagementInterface::LABEL_STATUS_PROCESSED, $labelStatus[$order->getEntityId()]);
    }
}
