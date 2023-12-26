<?php
/**
 * Copyright © 2021 All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\VirtualBank\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\FormKey;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Bat\VirtualBank\Model\Resolver\DataProvider\VirtualBankListDataProvider;
use Bat\VirtualBank\Helper\Data as VbaHelper;

class Modal extends Template
{
    /**
     * @var VirtualBankListDataProvider
     */
    protected VirtualBankListDataProvider $virtualBankListDataProvider;

    /**
     * @var FormKey
     */
    protected $adminFormKey;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var VbaHelper
     */
    protected $vbaHelper;

    /**
     * Constructor method
     *
     * @param Context $context
     * @param VirtualBankListDataProvider $virtualBankListDataProvider
     * @param FormKey $adminFormKey
     * @param CustomerRepositoryInterface $customerRepository
     * @param VbaHelper $vbaHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        VirtualBankListDataProvider $virtualBankListDataProvider,
        FormKey $adminFormKey,
        CustomerRepositoryInterface $customerRepository,
        VbaHelper $vbaHelper,
        array $data = []
    ) {
        $this->virtualBankListDataProvider = $virtualBankListDataProvider;
        $this->adminFormKey = $adminFormKey;
        $this->customerRepository = $customerRepository;
        $this->vbaHelper = $vbaHelper;
        parent::__construct($context, $data);
    }

    /**
     * Get Bank List
     *
     * @return array
     */
    public function getBankList()
    {
        return $this->virtualBankListDataProvider->getVirtualBankList();
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

    /**
     * Get Customer current bank
     *
     * @return array
     */
    public function getCurrentBank()
    {
        if ($this->getCustomerId()) {
            $customerId = $this->getCustomerId();
            return $this->vbaHelper->getCurrentBank($customerId);
        }

        return [];
    }
}
