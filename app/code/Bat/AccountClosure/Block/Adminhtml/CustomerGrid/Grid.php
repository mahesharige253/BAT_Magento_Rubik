<?php
namespace Bat\AccountClosure\Block\Adminhtml\CustomerGrid;

use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @var \Magento\Sales\Model\Order\ItemFactory
     */
    protected $itemFactory;

    /**
     * @var CollectionFactory $collectionFactory
     */
    protected $collectionFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Sales\Model\Order\ItemFactory $itemFactory
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param CollectionFactory $collectionFactory
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Sales\Model\Order\ItemFactory $itemFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        CollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->itemFactory = $itemFactory;
        $this->moduleManager = $moduleManager;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Construct
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('postGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(false);
    }

    /**
     * Get Collection
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        //You can select your custom data here
        $customerCollection = $this->collectionFactory->create();
        $closure_status = [6,7,8,9,10,11];
        $customerCollection->addAttributeToSelect('outlet_id');
        $customerCollection->addAttributeToSelect('firstname');
        $customerCollection->addAttributeToSelect('mobilenumber');
        $customerCollection->addAttributeToSelect('approval_status');
        $customerCollection->addAttributeToSelect('outlet_name');
        $customerCollection->addAttributeToFilter('approval_status', ['in' => $closure_status])
        ->load();

        $this->setCollection($customerCollection);

        parent::_prepareCollection();

        return $this;
    }

    /**
     * Prepare column
     *
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'entity_id',
            [
                'header' => __('Id'),
                'index' => 'entity_id',
            ]
        );
        $this->addColumn(
            'firstname',
            [
             'header' => __('Name'),
             'index' => 'firstname',
            ]
        );
        $this->addColumn(
            'outlet_id',
            [
             'header' => __('Outlet Id'),
             'index' => 'outlet_id',
             'filter_condition_callback' => [$this, '_filterOutlet'],
            ]
        );

        $this->addColumn(
            'outlet_name',
            [
                'header' => __('Outlet Name'),
                'index' => 'outlet_name',
                'renderer' => OutletName::class
            ]
        );

        $this->addColumn(
            'mobilenumber',
            [
                'header' => __('Mobile Number'),
                'index' => 'mobilenumber',
            ]
        );
           
        $this->addColumn(
            'approval_status',
            [
                'header' => __('Approval Status'),
                'index' => 'approval_status',
                'renderer' => ApprovalStatus::class
            ]
        );

           $this->addColumn(
               'action',
               [
                'header' => __('Action'),
                'align' => 'center',
                'filter' => false,
                'sortable' => false,
                'renderer' => Action::class
               ]
           );

        // $block = $this->getLayout()->getBlock('grid.bottom.links');
        // if ($block) {
        //     $this->setChild('grid.bottom.links', $block);
        // }

        return parent::_prepareColumns();
    }

    /**
     * Get GridUrl
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('accountclosure/accountclosure/indexpage', ['_current' => true]);
    }

    /**
     * Get RowUrl
     *
     * @param string $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return '#';
    }
}
