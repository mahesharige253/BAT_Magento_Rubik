<?php

namespace Bat\AccountClosure\Plugin;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Bat\AccountClosure\Ui\DataProvider\Closure\ListingDataProvider as ClosureDataProvider;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\App\ProductMetadataInterface;

class AddAttributesToUiDataProvider
{
    /**
     *
     * @var ProductMetadataInterface
     */
    private $productMetadata;
    
    /**
     *
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;
    
    /**
     *
     * @param AttributeRepositoryInterface $attributeRepository
     * @param ProductMetadataInterface $productMetadata
     */
    public function __construct(
        AttributeRepositoryInterface $attributeRepository,
        ProductMetadataInterface $productMetadata
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->productMetadata = $productMetadata;
    }
    
    /**
     * Display custom attributes
     *
     * @param ClosureDataProvider $subject
     * @param SearchResult $result
     * @return SearchResult
     */
    public function afterGetSearchResult(ClosureDataProvider $subject, SearchResult $result)
    {
        if ($result->isLoaded()) {
            return $result;
        }
        
        $edition = $this->productMetadata->getEdition();

        $column = 'entity_id';
        $addressColumn = 'parent_id';
        $maintableColumn = 'customer_id';
        $companyColumn = 'super_user_id';

        if ($edition == 'Enterprise') {
            $column = 'row_id';
        }
        
        $result->getSelect()->joinLeft(
            ['ce' => 'customer_entity'],
            "ce." . $column . " = main_table." . $maintableColumn,
            ['firstname', 'email', 'lastname', 'group_id', 'gender', 'website_id', 'dob','default_billing','mobilenumber','outlet_id','parent_outlet_id']
        );

        $result->getSelect()->joinLeft(
            ['cec' => 'company'],
            "cec." . $companyColumn . " = ce." . $column,
            ['company_name']
        );
        
        $result->getSelect()->joinLeft(
            ['cae' => 'customer_address_entity'],
            "cae." . $addressColumn . " = main_table." . $maintableColumn." AND cae.entity_id = ce.default_billing",
            ['street', 'city', 'region', 'postcode', 'country_id', 'telephone']
        );
        
        $attArr = ['is_parent', 'is_credit_customer', 'total_ar_limit',
            'gst_number', 'sap_vendor_code', 'division',
            'customer_account_group', 'tax_code', 'sigungu_code',
            'additional_gst_number', 'sales_office', 'delivery_plant',
            'bat_country_code', 'returning_stock','approval_status','bat_customer_group','depot'];
        $i=1;
        foreach ($attArr as $code) {
            $attribute = $this->getAttribute($code);
            $alias = 'ce'.$i;
            $result->getSelect()->joinLeft(
                [$alias => $attribute->getBackendTable()],
                $alias.".".$column." = main_table.".$maintableColumn." AND ".$alias.".attribute_id = ".$attribute->getAttributeId(),
                [$code => $alias.".value"]
            );
            $i++;
        }
        //echo $result->getSelect(); die;

        return $result;
    }
    
    /**
     * Get attribute details from code
     *
     * @param string $attCode
     * @return type
     */
    protected function getAttribute($attCode)
    {
        return $this->attributeRepository->get('customer', $attCode);
    }
}
