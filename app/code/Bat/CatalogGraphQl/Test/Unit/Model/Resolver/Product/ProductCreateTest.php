<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CatalogGraphQl\Test\Unit\Model\Resolver\Product;

use Magento\TestFramework\TestCase\GraphQlAbstract;
use PHPUnit\Framework\TestResult;

class ProductCreateTest extends GraphQlAbstract
{
    /**
     * Product Create API Unit Case
     */
    public function testProductCreate()
    {
        $query
            = <<<MUTATION
 mutation {
  saveProduct (
      input: {
      sku: "demo27", 
      name: "Demo 27", 
      price: 195, 
      status: "in_stock",
      weight: "1",
      image: "test.jpg"
      categoryIds : "2,3"
      extension_attributes: {
      stock_item: {
        qty: 18,
        is_in_stock: true
      }
    },
    custom_attributes: [
        {
        attribute_code: "bat_default_attribute",
        value: "color"
        },
        {
        attribute_code: "color",
        value: "18"
        }
    ]
     
    }) {    
        message
    }
  }

MUTATION;
        $response = $this->graphQlMutation($query);
        $this->assertIsArray($response['saveProduct']);
        $this->assertNotEmpty($response['saveProduct']);
        $products = $response['saveProduct']['message'];
    }
}
