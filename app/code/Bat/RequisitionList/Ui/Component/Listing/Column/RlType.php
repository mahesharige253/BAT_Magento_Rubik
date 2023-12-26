<?php
namespace Bat\RequisitionList\Ui\Component\Listing\Column;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * @class RlType
 * Map Bank Status to Options
 */
class RlType implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options[] = ['label' => '', 'value' => ''];
        $availableOptions = $this->getOptionArray();
        foreach ($availableOptions as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }
        return $options;
    }

    /**
     * Return RL type options
     *
     * @return array
     */
    public function getOptionArray()
    {
        return ['' => __('-- Select --'),
                'normal' => __('Normal'),
                'seasonal' => __('Seasonal'),
                'bestseller' => __('Bestseller'),
                'other' => __('Other')
               ];
    }
}
