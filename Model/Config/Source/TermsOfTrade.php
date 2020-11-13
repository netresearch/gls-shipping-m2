<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGroup\Shipping\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class TermsOfTrade implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        $optionArray = [];

        $options = $this->toArray();
        foreach ($options as $value => $label) {
            $optionArray[] = ['value' => $value, 'label' => $label];
        }

        return $optionArray;
    }

    public function toArray(): array
    {
        return [
            '10' => __('DDP (Freight costs, customs costs & taxes paid)'),
            '20' => __('DAP (Freight costs paid, customs costs & taxes unpaid)'),
            '30' => __('DDP, VAT unpaid (Freight costs & customs costs paid, taxes unpaid)'),
            '40' => __('DAP, cleared (Freight costs & customs clearance costs paid, customs duties and taxes unpaid)'),
            '50' => __('DDP (Freight costs and customs clearance costs paid, lowvalue clearance)'),
        ];
    }
}
