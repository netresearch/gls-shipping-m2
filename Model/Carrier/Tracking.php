<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGermany\Shipping\Model\Carrier;

use Magento\Sales\Api\Data\ShipmentTrackInterface;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as ShipmentCollectionFactory;
use Netresearch\ShippingCore\Model\Config\ShippingConfig;

class Tracking
{
    const TRACKING_TEMPLATE_DE = 'https://www.gls-pakete.de/sendungsverfolgung?trackingNumber=%s';
    const TRACKING_TEMPLATE_AT = 'https://gls-group.eu/AT/de/pakete-empfangen/paket-verfolgen?match=%s';

    /**
     * @var ShipmentCollectionFactory
     */
    private $shipmentCollectionFactory;

    /**
     * @var ShippingConfig
     */
    private $shippingConfig;

    public function __construct(ShipmentCollectionFactory $shipmentCollectionFactory, ShippingConfig $shippingConfig)
    {
        $this->shipmentCollectionFactory = $shipmentCollectionFactory;
        $this->shippingConfig = $shippingConfig;
    }

    /**
     * Obtain tracking URL based on the shipment's origin.
     *
     * @param string $shipmentNumber
     * @return string
     */
    public function getUrl(string $shipmentNumber): string
    {
        $shipmentCollection = $this->shipmentCollectionFactory->create();
        $shipmentCollection
            ->join('sales_shipment_track', 'main_table.entity_id = sales_shipment_track.parent_id', [])
            ->addFieldToFilter(ShipmentTrackInterface::CARRIER_CODE, GlsGermany::CARRIER_CODE)
            ->addFieldToFilter(ShipmentTrackInterface::TRACK_NUMBER, $shipmentNumber)
            ->setPageSize(1)
            ->setCurPage(1);

        $storeId = $shipmentCollection->getFirstItem()->getData('store_id');
        $countryId = $this->shippingConfig->getOriginCountry((int) $storeId);

        switch ($countryId) {
            case 'DE':
                return sprintf(self::TRACKING_TEMPLATE_DE, $shipmentNumber);
            case 'AT':
                return sprintf(self::TRACKING_TEMPLATE_AT, $shipmentNumber);
            default:
                return '';
        }
    }
}
