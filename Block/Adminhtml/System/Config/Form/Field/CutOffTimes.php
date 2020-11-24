<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGroup\Shipping\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\BlockInterface;

class CutOffTimes extends AbstractFieldArray
{
    /**
     * @var DayOptions
     */
    private $templateRenderer;

    /**
     * Create renderer used for displaying the days select element.
     *
     * @return DayOptions|BlockInterface
     *
     * @throws LocalizedException
     */
    private function getTemplateRenderer()
    {
        if (!$this->templateRenderer) {
            $this->templateRenderer = $this->getLayout()->createBlock(
                DayOptions::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );

            $this->templateRenderer->setClass('weekdays');
        }

        return $this->templateRenderer;
    }

    /**
     * Prepare existing row data object.
     *
     * @param DataObject $row
     *
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row)
    {
        $hash = $this->getTemplateRenderer()->calcOptionHash(
            $row->getData('day')
        );

        $row->setData(
            'option_extra_attrs',
            [
                'option_' . $hash => 'selected="selected"',
            ]
        );
    }

    /**
     * Prepare to render.
     *
     * @throws LocalizedException
     */
    protected function _prepareToRender()
    {
        $this->addColumn('day', [
            'label' => __('Day'),
            'renderer' => $this->getTemplateRenderer()
        ]);

        $this->addColumn('time', [
            'label' => __('Order Time'),
            'style' => 'width: 80px',
            'class' => 'validate-no-empty time'
        ]);

        // Hide "Add after" button
        $this->_addAfter = false;
    }
}
