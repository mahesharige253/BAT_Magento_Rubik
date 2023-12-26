<?php
/**
 * Copyright © 2021 All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\Customer\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\FormKey;

class Modal extends Template
{
    /**
     * @var FormKey
     */
    protected $adminFormKey;

    /**
     * Constructor method
     *
     * @param Context $context
     * @param FormKey $adminFormKey
     * @param array $data
     */
    public function __construct(
        Context $context,
        FormKey $adminFormKey,
        array $data = []
    ) {
        $this->adminFormKey = $adminFormKey;
        parent::__construct($context, $data);
    }

    /**
     * Get Form key
     *
     * @return string
     */
    public function getFormKey()
    {
        return $this->adminFormKey->getFormKey();
    }

    /**
     * Get customer Id
     *
     * @return string
     */
    public function getCustomerId()
    {
        return $this->getRequest()->getParam('id');
    }
}
