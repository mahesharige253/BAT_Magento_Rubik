<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bat\CustomerBalance\Controller\Adminhtml\Customerbalance;

use Bat\CustomerBalance\Block\Adminhtml\Customer\Edit\Tab\Customerbalance\Order\History\Grid;

class GridHistory extends \Magento\CustomerBalance\Controller\Adminhtml\Customerbalance
{
    /**
     * Customer balance grid
     *
     * @return void
     */
    public function execute()
    {
        $this->initCurrentCustomer();
        $this->_view->loadLayout();
        $this->getResponse()->setBody(
            $this->_view->getLayout()->createBlock(
                Grid::class
            )->toHtml()
        );
    }
}
