<?php

namespace Bat\Information\Controller\Adminhtml\Createbrand;

use Bat\Information\Model\InformationBrandFormFactory;
use Magento\Framework\Registry;

class Edit extends \Magento\Backend\App\Action
{

    const ADMIN_RESOURCE = 'Bat_Information::menu_brand';

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
    protected $informationBrandFormFactory;

    /**
     * Edit constructor.
     *
     * @param \Magento\Backend\App\Action\Context        $context
     * @param Registry                                   $registry
     * @param InformationBrandFormFactory               $informationBrandFormFactory
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        Registry $registry,
        InformationBrandFormFactory $informationBrandFormFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        $this->informationBrandFormFactory = $informationBrandFormFactory;
    } //end __construct()

    /**
     * Execute function
     */
    public function execute()
    {
        $consentform = $this->getRequest()->getParam('id');
        $model = $this->informationBrandFormFactory->create();
        $model->load($consentform);
        $this->_coreRegistry->register('informationformBrand', $model);
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Bat_Information::informationform');
        $resultPage->getConfig()->getTitle()->prepend(
            $consentform ? __('Edit',$model->getInformationTitle()) : __('New Information')
        );
        return $resultPage;
    } //end execute()
} //end class
