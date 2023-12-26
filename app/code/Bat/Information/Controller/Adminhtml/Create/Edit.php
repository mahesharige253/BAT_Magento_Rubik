<?php

namespace Bat\Information\Controller\Adminhtml\Create;

use Bat\Information\Model\InformationFormFactory;
use Magento\Framework\Registry;

class Edit extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Bat_Information::menu_productbarcode';

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
    protected $informationformFactory;

    /**
     * Edit constructor.
     *
     * @param \Magento\Backend\App\Action\Context        $context
     * @param Registry                                   $registry
     * @param InformationFormFactory                     $informationformFactory
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        Registry $registry,
        InformationFormFactory $informationformFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        $this->informationformFactory = $informationformFactory;
    } //end __construct()

    /**
     * Execute function
     */
    public function execute()
    {
        $consentform = $this->getRequest()->getParam('id');
        $model = $this->informationformFactory->create();
        $model->load($consentform);
        $this->_coreRegistry->register('informationform', $model);
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Bat_Information::informationform');
        $resultPage->getConfig()->getTitle()->prepend(
            $consentform ? __('Edit',$model->getInformationTitle()) : __('New Information')
        );
        return $resultPage;
    } //end execute()
} //end class
