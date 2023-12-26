<?php

namespace Bat\RequisitionList\Block\Adminhtml\Requisitionlist\Button;

use Magento\Backend\Block\Widget\Context;
use Bat\RequisitionList\Model\ResourceModel\RequisitionListAdmin\CollectionFactory;
use Bat\RequisitionList\Helper\Data;

/**
 * @class Generic
 * class to Generate url by route and parameters
 */
class Generic
{

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var CollectionFactory
     */
     protected $collectionFactory;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     * @param Data $helper
     */
    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        Data $helper
    ) {
        $this->context = $context;
        $this->collectionFactory = $collectionFactory;
        $this->helper = $helper;
    }

    /**
     * Generate url by route and parameters
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }

    /**
     * Allowed Rl
     *
     * @return boolean
     */
    protected function notAllowedRl()
    {
        $allowRequisitionlistAdmin = $this->helper->getRequisitionlistAdmin();
        $rlCollection = $this->collectionFactory->create();
        if ($rlCollection->getSize() == $allowRequisitionlistAdmin) {
            return true;
        }
        return false;
    }
}
