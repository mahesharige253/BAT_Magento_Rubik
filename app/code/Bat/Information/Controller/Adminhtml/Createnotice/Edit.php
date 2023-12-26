<?php

namespace Bat\Information\Controller\Adminhtml\Createnotice;

use Bat\Information\Model\InformationNoticeFormFactory;
use Magento\Framework\Registry;

class Edit extends \Magento\Backend\App\Action
{
   const ADMIN_RESOURCE = 'Bat_Information::menu_notice';

    /**
     * @var Registry|null
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var InformationFormFactory
     */
    protected $informationNoticeFormFactory;

    /**
     * Edit constructor.
     *
     * @param \Magento\Backend\App\Action\Context        $context
     * @param Registry                                   $registry
     * @param InformationFormNoticeFactory                     $informationNoticeFormFactory;
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        Registry $registry,
        InformationNoticeFormFactory $informationNoticeFormFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        $this->informationNoticeFormFactory = $informationNoticeFormFactory;
    } //end __construct()

    /**
     * Execute function
     */
    public function execute()
    {
        $consentform = $this->getRequest()->getParam('id');
        $model = $this->informationNoticeFormFactory->create();
        $model->load($consentform);
        $this->_coreRegistry->register('informationformNotice', $model);
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Bat_Information::informationform');
        $resultPage->getConfig()->getTitle()->prepend(
            $consentform ? __('Edit') . ' ' . ($model->getInformationTitle()) : __('New Information')
        );
        return $resultPage;
    } //end execute()
} //end class
