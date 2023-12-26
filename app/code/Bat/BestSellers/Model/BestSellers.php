<?php

namespace Bat\BestSellers\Model;

use Magento\Framework\Model\AbstractModel;

class BestSellers extends AbstractModel
{

    /**
     * Price Master
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Bat\BestSellers\Model\ResourceModel\BestSellers::class);
    }

    /**
     * Getting Best seller data
     *
     * @param string $sigunguCode
     * return array
     */
    public function getBestSellerData($sigunguCode)
    {
        $tbl = $this->getResource()->getTable('bat_bestseller');
         $select = $this->getResource()->getConnection()->select()->from(
             $tbl,
             ['product_id', 'qty']
         )
        ->where(
            "sigungu_code = ?",
            $sigunguCode
        )
        //->group('product_id')
        ->order('qty', 'desc');
        return $this->getResource()->getConnection()->fetchAll($select);
    }
}
