<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGroup\Shipping\Test\Integration\TestCase\Controller\Adminhtml;

use GlsGroup\Shipping\Model\Webservice\ParcelProcessingServiceFactory;
use GlsGroup\Shipping\Test\Integration\TestDouble\ServiceFactoryStub;
use Magento\Framework\Exception\AuthenticationException;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Class ControllerTest
 *
 * Base controller test for all actions which trigger label api calls for order fixtures:
 * - Create shipment and label for single order
 * - Create shipments and labels for multiple orders (auto-create)
 *
 */
abstract class ControllerTest extends AbstractBackendController
{
    /**
     * @var string
     */
    protected $httpMethod = 'POST';

    /**
     * Set up the shipment service stub to suppress actual api calls.
     *
     * @throws AuthenticationException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->_objectManager->configure(
            [
                'preferences' => [
                    ParcelProcessingServiceFactory::class => ServiceFactoryStub::class,
                ]
            ]
        );
    }
}
