<?php

namespace Bat\CustomerConsentForm\Controller\Adminhtml\Create;

use Bat\CustomerConsentForm\Model\ConsentFormFactory;
use Magento\Framework\Registry;

class Edit extends \Magento\Backend\App\Action
{

    /**
     * @var Registry|null
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var ConsentFormFactory
     */
    protected $consentformFactory;

    /**
     * Edit constructor.
     *
     * @param \Magento\Backend\App\Action\Context        $context
     * @param Registry                                   $registry
     * @param ConsentFormFactory                         $consentformFactory
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        Registry $registry,
        ConsentFormFactory $consentformFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        $this->consentformFactory = $consentformFactory;
    } //end __construct()

    /**
     * Execute function
     */
    public function execute()
    {
        $consentform = $this->getRequest()->getParam('id');
        $model = $this->consentformFactory->create();
        $model->load($consentform);
        $this->_coreRegistry->register('customerconsentform', $model);
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Bat_CustomerConsentForm::customerconsentform');
        $resultPage->getConfig()->getTitle()->prepend(
            $consentform ? __('Edit Consent Form',$model->getTitle()) : __('New ConsentForm')
        );
        return $resultPage;
    } //end execute()
} //end class
