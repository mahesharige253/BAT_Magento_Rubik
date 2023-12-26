<?php

namespace Bat\Rma\Block\Adminhtml\CreateReturns;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;

class OutletForm extends \Magento\Backend\Block\Template
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
    public function getOutletSubmitUrl()
    {
        return $this->getUrl('returns/createreturns/validateoutlet');
    }
}
