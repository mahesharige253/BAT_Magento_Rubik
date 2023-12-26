<?php

namespace Bat\RequisitionList\Model\Entity\Attribute\Source;

use Bat\RequisitionList\Model\RequisitionListAdminFactory;

class RequisitionList extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @var RequisitionListAdminFactory
     */
    protected $requisitionListAdminFactory;

    /**
     * @param RequisitionListAdminFactory $requisitionListAdminFactory
     */
    public function __construct(
        RequisitionListAdminFactory $requisitionListAdminFactory
    ) {
        $this->requisitionListAdminFactory = $requisitionListAdminFactory;
    }
    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        $options = [];
        $data = $this->requisitionListAdminFactory->create()->getCollection()->addFieldToFilter('rl_type', 'other');
        $options = ['label' => 'Please Select RL', 'value' => ''];
        foreach ($data as $requisitionlist) {
            $name = $requisitionlist['name'];
            $entityId = $requisitionlist['entity_id'];
            $options[] = ['label' => $name, 'value' => $entityId];
        }
        return $options;
    }
}

