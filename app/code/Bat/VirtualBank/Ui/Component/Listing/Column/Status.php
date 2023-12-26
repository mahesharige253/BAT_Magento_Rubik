<?php
namespace Bat\VirtualBank\Ui\Component\Listing\Column;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * @class Status
 * Map Bank Status to Options
 */
class Status implements OptionSourceInterface
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
     * Return Yes/No options
     *
     * @return array
     */
    public function getOptionArray()
    {
        return [1 => __('Enabled'), 0 => __('Disabled')];
    }
}
