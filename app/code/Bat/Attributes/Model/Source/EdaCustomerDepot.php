<?php

namespace Bat\Attributes\Model\Source;

use Bat\Customer\Model\SigunguCodeFactory;

class EdaCustomerDepot extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{

    /**
     * @var SigunguCodeFactory
     */
    private SigunguCodeFactory $sigunguCodeFactory;

    /**
     * @param SigunguCodeFactory $sigunguCodeFactory
     */
    public function __construct(
        SigunguCodeFactory $sigunguCodeFactory
    ) {
        $this->sigunguCodeFactory = $sigunguCodeFactory;
    }

    /**
     * @inheritdoc
     */
    public function getAllOptions()
    {
        $sigunguCode = $this->sigunguCodeFactory->create()->getCollection()->load();
        $this->_options = [["value"=>"","label"=>__("Select Depot")]];
        $depot = [];
        foreach ($sigunguCode as $option) {    
            if (!in_array($option['depot'], $depot)) { 
                   $this->_options[] = ["value" => $option['depot'], "label" => $option['depot']];        
                   $depot[] = $option['depot'];   
                    }
                }
        return $this->_options;
    }
}
