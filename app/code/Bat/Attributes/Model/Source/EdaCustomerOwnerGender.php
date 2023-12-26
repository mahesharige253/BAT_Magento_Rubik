<?php

namespace Bat\Attributes\Model\Source;

class EdaCustomerOwnerGender extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @inheritdoc
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ["value"=>"","label"=>__("Select Gender")],
                ["value"=>"M","label"=>__("Male")],
                ["value"=>"F","label"=>__("Female")],
                ["value"=>"O","label"=>__("Others")],
            ];
        }
        return $this->_options;
    }
}
