<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGroup\Shipping\Test\Integration\TestDouble;

use GlsGroup\Sdk\ParcelProcessing\Api\CancellationServiceInterface;
use GlsGroup\Sdk\ParcelProcessing\Api\ShipmentServiceInterface;
use GlsGroup\Shipping\Model\Webservice\ParcelProcessingServiceFactory;
use Magento\TestFramework\Helper\Bootstrap;

class ServiceFactoryStub extends ParcelProcessingServiceFactory
{
    public function createShipmentService($store = null): ShipmentServiceInterface
    {
        return Bootstrap::getObjectManager()->create(ShipmentServiceStub::class);
    }

    public function createCancellationService($store = null): CancellationServiceInterface
    {
        return Bootstrap::getObjectManager()->create(CancellationServiceStub::class);
    }
}
