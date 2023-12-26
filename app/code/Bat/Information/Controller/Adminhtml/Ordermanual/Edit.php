<?php

namespace Bat\Information\Controller\Adminhtml\Ordermanual;

use Bat\Information\Model\InformationOrderManualFactory;
use Magento\Framework\Registry;

class Edit extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Bat_Information::menu_ordermanual';

    /**
     * @var Registry|null
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var InformationOrderManualFactory
     */
    protected $informationOrderManualFactory;

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
        InformationOrderManualFactory $informationOrderManualFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        $this->informationOrderManualFactory = $informationOrderManualFactory;
    } //end __construct()

    /**
     * Execute function
     */
    public function execute()
    {
        $collection = $this->informationOrderManualFactory->create()->getCollection();
        $id = '';
        $records = $collection->getData();
        if (count($records) > 0) {
            $id = $records[0]['id'];
        }
        $model = $this->informationOrderManualFactory->create();
        $model->load($id);
        $this->_coreRegistry->register('informationformOrdermanual', $model);
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Bat_Information::informationform');
        $resultPage->getConfig()->getTitle()->prepend(
            $id ? __('Edit') . ' ' . ($model->getInformationTitle()) : __('New Order Manual')
        );
        return $resultPage;
    } //end execute()
} //end class
