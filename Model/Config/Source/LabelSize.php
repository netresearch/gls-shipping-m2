<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGermany\Shipping\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class LabelSize implements OptionSourceInterface
{
    public const LABEL_SIZE_A6 = 'A6';
    public const LABEL_SIZE_A5 = 'A5';
    public const LABEL_SIZE_A4 = 'A4';

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
            self::LABEL_SIZE_A6 => 'A6',
            self::LABEL_SIZE_A5 => 'A5',
            self::LABEL_SIZE_A4 => 'A4',
        ];
    }
}
