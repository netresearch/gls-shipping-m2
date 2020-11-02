<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGermany\Shipping\Test\Integration\TestCase\Controller\Adminhtml\Order\Shipment;

use GlsGermany\Sdk\ParcelProcessing\Exception\ServiceException;
use GlsGermany\Shipping\Model\Carrier\GlsGermany;
use GlsGermany\Shipping\Model\Pipeline\CreateShipments\Stage\SendRequestStage as CreationStage;
use GlsGermany\Shipping\Model\Pipeline\DeleteShipments\Stage\SendRequestStage as CancellationStage;
use GlsGermany\Shipping\Test\Integration\TestDouble\Pipeline\CreateShipments\Stage\SendRequestStageStub as CreationStageStub;
use GlsGermany\Shipping\Test\Integration\TestDouble\Pipeline\DeleteShipments\Stage\SendRequestStageStub as CancellationStageStub;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Collection;
use Netresearch\ShippingCore\Api\LabelStatus\LabelStatusManagementInterface;
use Netresearch\ShippingCore\Model\LabelStatus\LabelStatusProvider;
use TddWizard\Fixtures\Catalog\ProductBuilder;
use TddWizard\Fixtures\Customer\AddressBuilder;
use TddWizard\Fixtures\Customer\CustomerBuilder;
use TddWizard\Fixtures\Sales\OrderBuilder;

/**
 * Make a multi-package call fail partially so that successfully created labels are rolled back.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class RollbackLabelTest extends SaveShipmentTest
{
    /**
     * Scenario: Two products are contained in an order, the second has invalid shipping options.
     *
     * - Items are packed into one package:
     * -- Assert that no shipment is created
     * -- Assert that label status is set to "Failed"
     * - Items are packed into two packages:
     * -- Assert that two packages are sent to the `create` endpoint
     * -- Assert that the first package is sent to the `cancel` endpoint (rolled back)
     * -- Assert that no shipment is created
     * -- Assert that label status is set to "Failed"
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
                ProductBuilder::aSimpleProduct()->withWeight(33.303)->withSku('bar')
            )
            ->withCustomer(CustomerBuilder::aCustomer()->withAddresses($addressBuilder))
            ->build();
        $this->orderFixtures->add($order);

        $data = $getPostData($order);

        $createShipmentsCount = 0;
        $cancelShipmentsCount = 0;

        /** @var CreationStageStub $creationStage */
        $creationStage = $this->_objectManager->get(CreationStage::class);
        // create response callback to count service invocation and throw exception on invalid shipping option
        $creationStage->responseCallback = function (CreationStageStub $stage) use (&$createShipmentsCount) {
            $createShipmentsCount++;
            if ($stage->shipmentRequests[0]->getPackageWeight() > 31.5) {
                // valid range for GLS is 0.1 to 31.5 kg
                return new ServiceException('weighty.');
            }

            return null;
        };

        /** @var CancellationStageStub $cancellationStage */
        $cancellationStage = $this->_objectManager->get(CancellationStage::class);
        // create response callback to count service invocation
        $cancellationStage->responseCallback = function () use (&$cancelShipmentsCount) {
            $cancelShipmentsCount++;
            return null;
        };

        // dispatch
        $this->getRequest()->setMethod($this->httpMethod);
        $this->getRequest()->setPostValue('data', \json_encode($data));
        $this->getRequest()->setParam('order_id', $order->getEntityId());
        $this->dispatch($this->uri);

        /** @var Collection $shipmentCollection */
        $shipmentCollection = $this->_objectManager->create(Collection::class);
        $shipments = $shipmentCollection->setOrderFilter($order)->getItems();
        $shipments = array_values($shipments);

        // assert that the `create` endpoint was invoked for each package
        self::assertCount($createShipmentsCount, $data['packages']);

        // if two packages were sent, assert that the `cancel` endpoint was invoked
        self::assertCount($cancelShipmentsCount + 1, $data['packages']);

        // assert that no shipments were created for the order
        self::assertEmpty($shipments);

        // assert that the order's label status is "Failed"
        /** @var LabelStatusProvider $labelStatusProvider */
        $labelStatusProvider = $this->_objectManager->create(LabelStatusProvider::class);
        $labelStatus = $labelStatusProvider->getLabelStatus([$order->getEntityId()]);
        self::assertSame(LabelStatusManagementInterface::LABEL_STATUS_FAILED, $labelStatus[$order->getEntityId()]);
    }
}
