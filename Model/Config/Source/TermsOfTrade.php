<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGermany\Shipping\Model\Config\Source;

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
            'incoterm1' => 'Incoterm Label 1',
            'incoterm2' => 'Incoterm Label 2',
            'incoterm3' => 'Incoterm Label 3',
        ];
    }
}
