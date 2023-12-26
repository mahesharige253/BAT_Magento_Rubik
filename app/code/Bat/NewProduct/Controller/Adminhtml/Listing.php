<?php

namespace Bat\NewProduct\Controller\Adminhtml;

use Bat\NewProduct\Model\ResourceModel\NewProductResource\CollectionFactory;
use Bat\NewProduct\Model\NewProductModelFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;
use Magento\Ui\Component\MassAction\Filter;

/**
 * @class Listing
 * Listing Abstract class
 */
abstract class Listing extends Action
{
    /**
     * Session key
     *
     * @var string
     */
    protected $formSessionKey;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * Registry
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
     * @var NewProductModelFactory
     */
    protected NewProductModelFactory $newProductModelFactory;
    /**
     * @var CollectionFactory
     */
    protected CollectionFactory $newProductResourceCollectionFactory;

    /**
     * @param Context $context
     * @param CollectionFactory $newProductResourceCollectionFactory
     * @param PageFactory $resultPageFactory
     * @param Registry $coreRegistry
     * @param DataPersistorInterface $dataPersistor
     * @param Filter $filter
     * @param NewProductModelFactory $newProductModelFactory
     */
    public function __construct(
        Context $context,
        CollectionFactory $newProductResourceCollectionFactory,
        PageFactory $resultPageFactory,
        Registry $coreRegistry,
        DataPersistorInterface $dataPersistor,
        Filter $filter,
        NewProductModelFactory $newProductModelFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->coreRegistry = $coreRegistry;
        $this->dataPersistor = $dataPersistor;
        $this->filter = $filter;
        $this->newProductResourceCollectionFactory = $newProductResourceCollectionFactory;
        $this->newProductModelFactory = $newProductModelFactory;
        parent::__construct($context);
    }
}
