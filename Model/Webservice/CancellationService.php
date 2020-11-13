<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGroup\Shipping\Model\Webservice;

use GlsGroup\Sdk\ParcelProcessing\Api\CancellationServiceInterface;
use GlsGroup\Sdk\ParcelProcessing\Api\ServiceFactoryInterface;
use GlsGroup\Sdk\ParcelProcessing\Exception\ServiceException;
use GlsGroup\Shipping\Model\Config\ModuleConfig;
use Psr\Log\LoggerInterface;

class CancellationService implements CancellationServiceInterface
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
     * @var CancellationServiceInterface|null
     */
    private $cancellationService;

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
     * Create cancellation service.
     *
     * @return CancellationServiceInterface
     * @throws ServiceException
     */
    private function getService(): CancellationServiceInterface
    {
        if ($this->cancellationService === null) {
            $this->cancellationService = $this->serviceFactory->createCancellationService(
                $this->moduleConfig->getUserName(),
                $this->moduleConfig->getPassword(),
                $this->logger,
                $this->moduleConfig->isSandboxMode()
            );
        }

        return $this->cancellationService;
    }

    public function cancelParcels(array $parcelIds): array
    {
        return $this->getService()->cancelParcels($parcelIds);
    }

    public function cancelParcel(string $parcelId): string
    {
        return $this->getService()->cancelParcel($parcelId);
    }
}
