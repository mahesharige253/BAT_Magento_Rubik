<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\BulkOrder\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Bat\CustomerGraphQl\Model\Resolver\DataProvider\GetDiscountMessage;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Bat\BulkOrder\Model\Resolver\CartDetails;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Request\DataPersistorInterface;

/**
 * DecryptData  resolver
 */
class GetBulkOrderItemDiscountMessage extends AbstractModel
{
    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var GetDiscountMessage
     */
    private $getDiscountMessage;

    /**
     * @var QuoteCollectionFactory
     */
    private $quoteCollectionFactory;

    /**
     * @var CartDetails
     */
    protected $cartDetails;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var DataPersistorInterface
     */
    protected $getDataPersistor;

    /**
     *
     * @param GetCustomer $getCustomer
     * @param GetDiscountMessage $getDiscountMessage
     * @param QuoteCollectionFactory $quoteCollectionFactory
     * @param CartDetails $cartDetails
     * @param CustomerRepositoryInterface $customerRepository
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        GetCustomer $getCustomer,
        GetDiscountMessage $getDiscountMessage,
        QuoteCollectionFactory $quoteCollectionFactory,
        CartDetails $cartDetails,
        CustomerRepositoryInterface $customerRepository,
        DataPersistorInterface $dataPersistor
    ) {
        $this->getCustomer = $getCustomer;
        $this->getDiscountMessage = $getDiscountMessage;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->cartDetails = $cartDetails;
        $this->customerRepository = $customerRepository;
        $this->getDataPersistor = $dataPersistor;
    }

    public function getItemDiscountMessage($itemId,$sku) {

        $parentId = $this->getDataPersistor->get('parent_id');
        $cartItemId = $itemId;

        $quote = $this->quoteCollectionFactory->create()->
            addFieldToFilter('parent_outlet_id', $parentId)->addFieldToFilter('is_active', 1);
        $dataDetails = $quote->getData();
        $quoteData = [];
        $inputs = [];
        foreach ($dataDetails as $dataDetail) {
            $quoteData['outlet_id'] = $dataDetail['outlet_id'];
            $quoteId = $dataDetail['entity_id'];
            $entityId = (int) $quoteId;
            $maskedCartId = $this->cartDetails->getQuoteMaskedIdByEntityId($entityId);
            $quoteData['maskedCartId'] = $maskedCartId;
            $inputs[] = $quoteData;
            $userId = $this->cartDetails->getCustomerIdsByCustomAttribute($dataDetail['outlet_id']);
            $currentUserId[] = (int) $userId[0];
        }

        foreach ($currentUserId as $customerId) {
            
            $discountMessage = [];
            $cartItemsId = [];
            $discountMessage[] = $this->getDiscountMessage->getItemMessage($customerId);
            
            if (!empty($discountMessage[0])) {
                $cartItemsId = $this->cartDetails->getCartItemsId($customerId);
                if (in_array($cartItemId, $cartItemsId)) {
                    $productMatched = [];
                    $productMatched = array_keys($discountMessage[0]);
                    if (!empty($productMatched)) {
                        foreach ($productMatched as $productCondition) {
                            if ($productCondition == $sku) {
                                return $discountMessage[0][$sku];
                            }
                        }
                    }
                }
            }
        }

    }

}