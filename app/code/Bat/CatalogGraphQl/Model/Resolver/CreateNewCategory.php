<?php

declare(strict_types=1);

namespace Bat\CatalogGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Api\CategoryRepositoryInterface;

class CreateNewCategory implements ResolverInterface
{
    /**
     * @var object \Magento\Catalog\Model\Category $category
     */
    protected $_category;

    /**
     * @var object \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $_categoryRepository;

     /**
      * Construct method
      *
      * @param Category $category
      * @param CategoryRepositoryInterface $categoryRepository
      */
    public function __construct(
        Category $category,
        CategoryRepositoryInterface $categoryRepository
    ) {
        $this->_category = $category;
        $this->_categoryRepository = $categoryRepository;
    }

    /**
     * Resolver method to create product
     *
     * @param Field $field
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|\Magento\Framework\GraphQl\Query\Resolver\Value|mixed
     * @throws GraphQlInputException
     */

    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $message = '';
        $status = true;

        if (!isset($args['input']['name'])) {
            throw new GraphQlInputException(__('Category Name should be specified'));
        } elseif (isset($args['input']['name']) && ($args['input']['name'] == '')) {
            throw new GraphQlInputException(__('Category Name is required'));
        }

        if (!isset($args['input']['parentId'])) {
            throw new GraphQlInputException(__('Parent Id should be specified'));
        } elseif (isset($args['input']['parentId']) && ($args['input']['parentId'] == '')) {
            throw new GraphQlInputException(__('Parent Id is required'));
        }

        if (!isset($args['input']['description'])) {
                throw new GraphQlInputException(__('Description key should be specified'));
        }

        if (!isset($args['input']['meta_title'])) {
                throw new GraphQlInputException(__('Meta Title key should be specified'));
        }

        if (!isset($args['input']['meta_keywords'])) {
                throw new GraphQlInputException(__('Meta Keyword key should be specified'));
        }

        if (!isset($args['input']['meta_description'])) {
                throw new GraphQlInputException(__('Meta Description key should be specified'));
        }

        try {
            $category = $this->_category;
            $cate = $category->getCollection()
                    ->addAttributeToFilter('name', $args['input']['name'])
                    ->addAttributeToFilter('parent_id', ['eq'=> $args['input']['parentId']])
                    ->getFirstItem();
            //Check exist category
            if (!$cate->getId()) {
                $category->setName($args['input']['name']);
                $category->setParentId($args['input']['parentId']);
                $category->setIsActive(true);
                $category->setCustomAttributes([
                  'description' => $args['input']['description'],
                  'meta_title' => $args['input']['meta_title'],
                  'meta_keywords' => $args['input']['meta_keywords'],
                  'meta_description' => $args['input']['meta_description'],
                ]);
                $this->_categoryRepository->save($category);
                $message = __('Category has been created successfully');
                $status = true;
            } else {
                $message = __('Category with this name already exist');
                $status = false;
            }
            return [
                        'status' => $status,
                        'message' => $message
                    ];
        } catch (\Exception $e) {
            throw new GraphQlNoSuchEntityException(
                __('The category was unable to be saved. Please try again.'),
                $e
            );
        }
    }
}
