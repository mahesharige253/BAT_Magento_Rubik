<?php
namespace Bat\AccountClosure\Ui\DataProvider\Closure\Listing;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

class Collection extends SearchResult
{

    protected function _initSelect()
    {
        $this->addFilterToMap('entity_id', 'main_table.entity_id');
        $this->addFilterToMap('is_parent', 'ce1.value');
        $this->addFilterToMap('is_credit_customer', 'ce2.value');
        $this->addFilterToMap('total_ar_limit', 'ce3.value');
        $this->addFilterToMap('gst_number', 'ce4.value');
        $this->addFilterToMap('sap_vendor_code', 'ce5.value');
        $this->addFilterToMap('division', 'ce6.value');
        $this->addFilterToMap('customer_account_group', 'ce7.value');
        $this->addFilterToMap('tax_code', 'ce8.value');
        $this->addFilterToMap('sigungu_code', 'ce9.value');
        $this->addFilterToMap('additional_gst_number', 'ce10.value');
        $this->addFilterToMap('sales_office', 'ce11.value');
        $this->addFilterToMap('delivery_plant', 'ce12.value');
        $this->addFilterToMap('bat_country_code', 'ce13.value');
        $this->addFilterToMap('returning_stock', 'ce14.value');
        $this->addFilterToMap('approval_status', 'ce15.value');
        $this->addFilterToMap('bat_customer_group', 'ce16.value');
        $this->addFilterToMap('depot', 'ce17.value');
        parent::_initSelect();
    }
}
