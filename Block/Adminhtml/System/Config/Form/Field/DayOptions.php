<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGermany\Shipping\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Model\Config\Source\Locale\Weekdays;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;

class DayOptions extends Select
{
    /**
     * @var Weekdays
     */
    private $source;

    /**
     * @var Escaper
     */
    private $escaper;

    public function __construct(
        Context $context,
        Weekdays $source,
        Escaper $escaper,
        array $data = []
    ) {
        $this->source = $source;
        $this->escaper = $escaper;

        parent::__construct($context, $data);
    }

    public function setInputName(string $value): self
    {
        return $this->setData('name', $value);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            foreach ($this->source->toOptionArray() as $weekDayData) {
                $this->addOption($weekDayData['value'], $this->escaper->escapeHtml($weekDayData['label']));
            }
        }

        return parent::_toHtml();
    }
}
