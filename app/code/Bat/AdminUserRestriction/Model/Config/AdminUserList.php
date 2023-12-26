<?php

namespace Bat\AdminUserRestriction\Model\Config;

use Magento\Framework\Option\ArrayInterface;
use Magento\User\Model\ResourceModel\User\CollectionFactory;

class AdminUserList implements ArrayInterface
{

    /**
     * @var CollectionFactory
     */
    protected CollectionFactory $collectionFactory;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * To Option Array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $adminUsers = $this->collectionFactory->create();
        $options = [];
        foreach ($adminUsers as $adminUser) {
            $options[] = [
                'value' => $adminUser->getId(),
                'label' => $adminUser->getUsername(),
            ];
        }

        return $options;
    }
}
