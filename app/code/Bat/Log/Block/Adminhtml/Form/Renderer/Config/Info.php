<?php
declare(strict_types=1);
namespace Bat\Log\Block\Adminhtml\Form\Renderer\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Info extends Field
{
    /**
     *  Get Html Element
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        return '<div> <h3>Log file will be generated in MAGE_ROOT/var/log/bat-logging.log</h3></div>';
    }
}
