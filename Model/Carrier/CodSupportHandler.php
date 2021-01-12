<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGroup\Shipping\Model\Carrier;

use GlsGroup\Shipping\Model\ShippingSettings\ShippingOption\Codes as CarrierCodes;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilderFactory;
use Magento\Quote\Model\Quote;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\Selection\AssignedSelectionInterface;
use Netresearch\ShippingCore\Api\PaymentMethod\MethodAvailabilityInterface;
use Netresearch\ShippingCore\Api\ShippingSettings\CodSelectorInterface;
use Netresearch\ShippingCore\Api\Util\DeliveryAreaInterface;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Codes;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Selection\QuoteSelectionRepository;

class CodSupportHandler implements MethodAvailabilityInterface, CodSelectorInterface
{
    /**
     * @var QuoteSelectionRepository
     */
    private $quoteSelectionRepository;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var DeliveryAreaInterface
     */
    private $deliveryArea;

    public function __construct(
        QuoteSelectionRepository $quoteSelectionRepository,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        DeliveryAreaInterface $deliveryArea
    ) {
        $this->quoteSelectionRepository = $quoteSelectionRepository;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->deliveryArea = $deliveryArea;
    }

    /**
     * Returns TRUE if a COD incompatible service is used.
     *
     * @param Quote $quote
     *
     * @return bool
     */
    private function hasCodIncompatibleServices(Quote $quote): bool
    {
        $parentIdFilter = $this->filterBuilder
            ->setField(AssignedSelectionInterface::PARENT_ID)
            ->setConditionType('eq')
            ->setValue($quote->getShippingAddress()->getId())
            ->create();

        $optionCodeFilter = $this->filterBuilder
            ->setField(AssignedSelectionInterface::SHIPPING_OPTION_CODE)
            ->setConditionType('in')
            ->setValue(
                [
                    CarrierCodes::SERVICE_OPTION_DEPOSIT,
                    CarrierCodes::SERVICE_OPTION_GUARANTEED24
                ]
            )
            ->create();

        $searchCriteria = $this->searchCriteriaBuilderFactory
            ->create()
            ->addFilter($parentIdFilter)
            ->addFilter($optionCodeFilter)
            ->create();

        return (bool) $this->quoteSelectionRepository
            ->getList($searchCriteria)
            ->getSize();
    }

    public function isAvailable(Quote $quote): bool
    {
        $countryId = $quote->getShippingAddress()->getCountryId();
        if (!\in_array($countryId, ['DE', 'AT'])) {
            return false;
        }

        if ($this->deliveryArea->isIsland($countryId, $quote->getShippingAddress()->getPostcode())) {
            return false;
        }

        if ($quote->getBaseGrandTotal() > 2500) {
            return false;
        }

        if ($this->hasCodIncompatibleServices($quote)) {
            return false;
        }

        return true;
    }

    public function assignCodSelection(AssignedSelectionInterface $selection)
    {
        $selection->setShippingOptionCode(Codes::SERVICE_OPTION_CASH_ON_DELIVERY);
        $selection->setInputCode('enabled');
        $selection->setInputValue((string) true);
    }
}
