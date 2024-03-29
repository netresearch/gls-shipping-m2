<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGroup\Shipping\Model\Pipeline\CreateShipments\ShipmentRequest\Validator;

use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\ValidatorException;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Shipping\Model\Shipment\Request;
use Netresearch\ShippingCore\Api\Pipeline\ShipmentRequest\RequestValidatorInterface;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Codes;

/**
 * Class NoPartialValidator
 *
 * Validate that the complete order is shipped together if CoD payment method
 * was chosen. CoD is not compatible with multi-package or multi-shipment.
 */
class NoPartialValidator implements RequestValidatorInterface
{
    /**
     * Collect quantities of all the order's shippable items and compare with items included in the current shipment.
     *
     * @param Request $request
     * @return bool
     */
    private function isPartialShipment(Request $request): bool
    {
        $itemQtyOrdered = array_map(
            static function (OrderItemInterface $item) {
                if ($item->getIsVirtual()) {
                    // virtual items are not shipped, ignore.
                    return 0;
                }

                if ($item->getParentItem() && $item->getParentItem()->getProductType() === Configurable::TYPE_CODE) {
                    // children of a configurable are not shipped, ignore.
                    return 0;
                }

                if ($item->getParentItem() && $item->getParentItem()->getProductType() === Type::TYPE_CODE) {
                    $parentOrderItem = $item->getParentItem();
                    $shipmentType = (int) $parentOrderItem->getProductOptionByCode('shipment_type');
                    if ($shipmentType === AbstractType::SHIPMENT_TOGETHER) {
                        // children of a bundle (shipped together) are not shipped, ignore.
                        return 0;
                    }
                }

                if ($item->getProductType() === Type::TYPE_CODE) {
                    $shipmentType = (int) $item->getProductOptionByCode('shipment_type');
                    if ($shipmentType === AbstractType::SHIPMENT_SEPARATELY) {
                        // a bundle with children (shipped separately) is not shipped, ignore.
                        return 0;
                    }
                }

                return $item->getQtyOrdered();
            },
            $request->getOrderShipment()->getOrder()->getAllItems()
        );

        $qtyOrdered = array_sum($itemQtyOrdered);
        $qtyShipped = (float)$request->getOrderShipment()->getTotalQty();

        return ($qtyOrdered !== $qtyShipped) || (count($request->getData('packages')) > 1);
    }

    /**
     * Check if an order allows partial shipment.
     *
     * Partial shipments are not allowed if
     * - the order was placed with a COD payment method or
     *
     * @param Request $request
     * @return bool
     */
    private function canShipPartially(Request $request): bool
    {
        $packages = $request->getData('packages');

        $hasCodService = false;

        foreach ($packages as $package) {
            $serviceData = $package['params']['services'][Codes::SERVICE_OPTION_CASH_ON_DELIVERY] ?? [];
            $hasCodService = $hasCodService || ($serviceData['enabled'] ?? false);
        }

        return !$hasCodService;
    }

    public function validate(Request $request): void
    {
        if ($this->isPartialShipment($request) && !$this->canShipPartially($request)) {
            throw new ValidatorException(
                __('Partial shipments with Cash on Delivery or Insurance service are not supported. Please ship the entire order in one package or deselect the service.')
            );
        }
    }
}
