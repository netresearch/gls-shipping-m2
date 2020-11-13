<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGroup\Shipping\Model\Pipeline\CreateShipments\Stage;

use GlsGroup\Shipping\Model\Pipeline\CreateShipments\ArtifactsContainer;
use GlsGroup\Shipping\Model\Pipeline\CreateShipments\RequestDataMapper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Shipping\Model\Shipment\Request;
use Netresearch\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Netresearch\ShippingCore\Api\Pipeline\CreateShipmentsStageInterface;

class MapRequestStage implements CreateShipmentsStageInterface
{
    /**
     * @var RequestDataMapper
     */
    private $requestDataMapper;

    public function __construct(RequestDataMapper $requestDataMapper)
    {
        $this->requestDataMapper = $requestDataMapper;
    }

    /**
     * Transform core shipment requests into request objects suitable for the label API.
     *
     * Requests with mapping errors are removed from requests and instantly added as error responses.
     *
     * @param Request[] $requests
     * @param ArtifactsContainerInterface|ArtifactsContainer $artifactsContainer
     * @return Request[]
     */
    public function execute(array $requests, ArtifactsContainerInterface $artifactsContainer): array
    {
        $callback = function (Request $request, int $requestIndex) use ($artifactsContainer) {
            try {
                $shipmentRequest = $this->requestDataMapper->mapRequest($request);
                $artifactsContainer->addApiRequest((string) $requestIndex, $shipmentRequest);

                return true;
            } catch (LocalizedException $exception) {
                $artifactsContainer->addError(
                    (string) $requestIndex,
                    $request->getOrderShipment(),
                    $exception->getMessage()
                );
                return false;
            }
        };

        // pass on only the shipment requests that could be mapped
        return array_filter($requests, $callback, ARRAY_FILTER_USE_BOTH);
    }
}
