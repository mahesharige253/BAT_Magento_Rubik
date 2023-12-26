<?php

namespace Bat\BulkOrder\Block\Adminhtml;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;

class BulkOrderForm extends \Magento\Backend\Block\Template
{
    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * Construct method
     *
     * @param Context $context
     * @param Registry $coreRegistry
     * @param DataPersistorInterface $dataPersistor
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        DataPersistorInterface $dataPersistor,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->dataPersistor = $dataPersistor;
        parent::__construct($context, $data);
    }

    /**
     * Returns Outletinfo url
     */
    public function getOutletInfoUrl()
    {
        return $this->getUrl('bulkorder/bulkorder/getoutlets');
    }

    /**
     * Returns Register Data
     */
    public function getRegisterData()
    {
        return $this->dataPersistor->get('outlet_data');
    }

    /**
     * Returns OutletSubmitUrl
     */
    public function getOutletSubmitUrl()
    {
        return $this->getUrl('bulkorder/bulkorder/validoutlet');
    }

    /**
     * Returns outletdata
     */
    public function getSelectedOutlets()
    {
        $data = $this->dataPersistor->get('select_outlets');
        $outletData = [];
        if ($data && count($data) > 0) {
            foreach ($data as $datavalue) {
                $outlet = explode(':', $datavalue);
                $outletData[] = $outlet[0];
            }
        }
        return $outletData;
    }

    /**
     * Returns parentOutlet
     */
    public function getParentOutlet()
    {
        return $this->dataPersistor->get('parent_id');
    }

    /**
     * Returns errorOutlet
     */
    public function getErrorOutlet()
    {
        return $this->dataPersistor->get('select_outlet_error');
    }
}
