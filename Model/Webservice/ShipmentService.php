<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGroup\Shipping\Model\Webservice;

use GlsGroup\Sdk\ParcelProcessing\Api\Data\ShipmentInterface;
use GlsGroup\Sdk\ParcelProcessing\Api\ServiceFactoryInterface;
use GlsGroup\Sdk\ParcelProcessing\Api\ShipmentServiceInterface;
use GlsGroup\Sdk\ParcelProcessing\Exception\ServiceException;
use GlsGroup\Shipping\Model\Config\ModuleConfig;
use Psr\Log\LoggerInterface;

class ShipmentService implements ShipmentServiceInterface
{
    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var ServiceFactoryInterface
     */
    private $serviceFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $storeId;

    /**
     * @var ShipmentServiceInterface|null
     */
    private $shipmentService;

    public function __construct(
        ModuleConfig $moduleConfig,
        ServiceFactoryInterface $serviceFactory,
        LoggerInterface $logger,
        int $storeId
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->serviceFactory = $serviceFactory;
        $this->logger = $logger;
        $this->storeId = $storeId;
    }

    /**
     * Create shipment service.
     *
     * @return ShipmentServiceInterface
     * @throws ServiceException
     */
    private function getService(): ShipmentServiceInterface
    {
        if ($this->shipmentService === null) {
            $this->shipmentService = $this->serviceFactory->createShipmentService(
                $this->moduleConfig->getUserName(),
                $this->moduleConfig->getPassword(),
                $this->logger,
                $this->moduleConfig->isSandboxMode()
            );
        }

        return $this->shipmentService;
    }

    public function createShipment(\JsonSerializable $shipmentRequest): ShipmentInterface
    {
        return $this->getService()->createShipment($shipmentRequest);
    }
}
