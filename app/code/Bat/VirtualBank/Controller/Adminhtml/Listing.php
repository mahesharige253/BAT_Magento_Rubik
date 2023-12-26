<?php

namespace Bat\VirtualBank\Controller\Adminhtml;

use Bat\VirtualBank\Model\ResourceModel\BankResource\BankCollectionFactory;
use Bat\VirtualBank\Model\BankModelFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;
use Magento\Ui\Component\MassAction\Filter;

/**
 * @class Listing
 * Abstract class
 */
abstract class Listing extends Action
{
    /**
     * session key
     *
     * @var string
     */
    protected $formSessionKey;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * registry
     *
     * @var Registry
     */
    protected $coreRegistry = null;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected CollectionFactory $bankResourceCollectionFactory;

    /**
     * @var BankModelFactory
     */
    protected BankModelFactory $bankModelFactory;
    /**
     * @var BankCollectionFactory
     */
    protected BankCollectionFactory $bankCollectionFactory;

    /**
     * @param Context $context
     * @param BankCollectionFactory $bankCollectionFactory
     * @param PageFactory $resultPageFactory
     * @param Registry $coreRegistry
     * @param DataPersistorInterface $dataPersistor
     * @param Filter $filter
     * @param BankModelFactory $bankModelFactory
     */
    public function __construct(
        Context $context,
        BankCollectionFactory $bankCollectionFactory,
        PageFactory $resultPageFactory,
        Registry $coreRegistry,
        DataPersistorInterface $dataPersistor,
        Filter $filter,
        BankModelFactory $bankModelFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->coreRegistry = $coreRegistry;
        $this->dataPersistor = $dataPersistor;
        $this->filter = $filter;
        $this->bankCollectionFactory = $bankCollectionFactory;
        $this->bankModelFactory = $bankModelFactory;
        parent::__construct($context);
    }
}
