<?php

namespace Bat\ContactUs\Controller\Adminhtml\Create;

use Bat\ContactUs\Model\ContactUsFormFactory;
use Magento\Framework\Registry;

class Edit extends \Magento\Backend\App\Action
{

    const ADMIN_RESOURCE = 'Bat_Information::menu_contactus';

    /**
     * @var Registry|null
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var ContactUsFormFactory
     */
    protected $contactusformFactory;

    /**
     * Edit constructor.
     *
     * @param \Magento\Backend\App\Action\Context        $context
     * @param Registry                                   $registry
     * @param ContactUsFormFactory                     $contactusformFactory
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        Registry $registry,
        ContactUsFormFactory $contactusformFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        $this->contactusformFactory = $contactusformFactory;
    } //end __construct()

    /**
     * Execute function
     */
    public function execute()
    {
        $collection = $this->contactusformFactory->create()->getCollection();
        $id = '';
        $records = $collection->getData();
        if (count($records) > 0) {
            $id = $records[0]['id'];
        }
        $model = $this->contactusformFactory->create();
        $model->load($id);
        $this->_coreRegistry->register('contactusform', $model);
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Bat_ContactUs::contactusform');
        $resultPage->getConfig()->getTitle()->prepend(
            $id ? __('Edit "' . $model->getPageTitle() . '"') : __('Contact Us Form')
        );
        return $resultPage;
    } //end execute()
} //end class
