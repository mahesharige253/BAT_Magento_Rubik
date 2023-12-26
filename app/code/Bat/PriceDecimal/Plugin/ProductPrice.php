<?php

namespace Bat\PriceDecimal\Plugin;

use Bat\PriceDecimal\Helper\Data;

class ProductPrice
{
	/**
	 * @var Data
	 */ 
	protected $helper;

	/**
	 * @param Data $helper
	 */ 
	public function __construct(Data $helper)
	{
		$this->helper = $helper;
	}
	
	/**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function afterPrepareDataSource(\Magento\Catalog\Ui\Component\Listing\Columns\Price $subject, $result)
    {
        if (isset($result['data']['items'])) {
            foreach ($result['data']['items'] as & $item) {
                if (isset($item['price'])) {
                    $item['price'] = $this->helper->getFormatedPrice($item['price']);
                }
            }
        }

        return $result;
    }
}