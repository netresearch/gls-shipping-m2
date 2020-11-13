<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGroup\Shipping\Test\Integration\TestCase\Controller\Adminhtml\Order\Shipment;

use GlsGroup\Shipping\Model\Carrier\GlsGroup;
use GlsGroup\Shipping\Test\Integration\TestDouble\Pipeline\CreateShipments\Stage\SendRequestStageStub;
use TddWizard\Fixtures\Catalog\ProductBuilder;
use TddWizard\Fixtures\Customer\AddressBuilder;
use TddWizard\Fixtures\Customer\CustomerBuilder;
use TddWizard\Fixtures\Sales\OrderBuilder;

/**
 * Test shipment creation for DE-DE route with cash on delivery payment.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class CreateCodLabelTest extends SaveShipmentTest
{
    /**
     * Scenario: Two products are contained in an order, both are valid.
     *
     * - Assert that single-package label request contains service details
     * - Assert that multi-package label request fails (no splitting for CoD orders)
     *
     * @test
     * @dataProvider postDataProviderCod
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
     * @magentoConfigFixture current_store carriers/glsgroup/active 1
     * @magentoConfigFixture current_store carriers/glsgroup/checkout/emulated_carrier flatrate
     * @magentoConfigFixture current_store carriers/glsgroup/account/customer_id foo
     * @magentoConfigFixture current_store carriers/glsgroup/account/contact_id bar
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
            ->withShippingMethod(GlsGroup::CARRIER_CODE . '_flatrate')
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

        if (count($data['packages']) > 1) {
            // controller response should contain error message mentioning cod
            $body = \json_decode($this->getResponse()->getBody(), true);
            self::assertArrayHasKey('error', $body);
            self::assertTrue($body['error']);
            self::assertArrayHasKey('message', $body);
            self::assertNotFalse(stripos($body['message'], 'cash on delivery'));
        } else {
            // label request should contain service details
            /** @var SendRequestStageStub $pipelineStage */
            $pipelineStage = $this->_objectManager->get(SendRequestStageStub::class);
            $apiPayload = \json_encode($pipelineStage->apiRequests[0]);

            $package = array_pop($data['packages']);
            $reasonForPayment = $package['service']['cashOnDelivery']['reasonForPayment'];
            self::assertNotFalse(strpos($apiPayload, "{\"name\":\"reference\",\"value\":\"$reasonForPayment\"}"));
        }
    }
}
