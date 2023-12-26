<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CatalogGraphQl\Test\Unit\Model\Resolver\Product;

use Magento\TestFramework\TestCase\GraphQlAbstract;
use PHPUnit\Framework\TestResult;

class CategoryCreateTest extends GraphQlAbstract
{
    /**
     * Create Category
     */
    public function testCategoryCreate()
    {
        $query
            = <<<MUTATION
 mutation {
  createCategory (
      input: {
      parentId: "2", 
      name: "KentNew", 
      description: "",
      meta_title: "",
      meta_keywords: "",
      meta_description: ""
    }) {
        message
        status
    }
  }

MUTATION;
        $response = $this->graphQlMutation($query);
        $this->assertIsArray($response['createCategory']);
        $this->assertNotEmpty($response['createCategory']);
        $products = $response['createCategory']['message'];
    }
}
