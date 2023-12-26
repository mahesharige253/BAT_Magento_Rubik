<?php

namespace Bat\VirtualBank\Model\Source;

use Bat\VirtualBank\Model\ResourceModel\BankResource\BankCollectionFactory;
use Magento\Framework\Option\ArrayInterface;

/**
 * Class Banks
 *
 * Return banks option array
 */
class Banks implements ArrayInterface
{
    /**
     * @var BankCollectionFactory
     */
    private BankCollectionFactory $bankCollectionFactory;

    /**
     * @param BankCollectionFactory $bankCollectionFactory
     */
    public function __construct(
        BankCollectionFactory $bankCollectionFactory
    ) {
        $this->bankCollectionFactory = $bankCollectionFactory;
    }
    /**
     * Retrieve banks options array.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $bankOptionArray = [];
        $banksData = $this->getVirtualBankCollection();
        $bankOptionArray[] = ['value' => '','label' => __('Select Bank')];
        foreach ($banksData as $bank) {
            $bankOptionArray[] = ['value' => $bank->getBankCode(),'label' => __($bank->getBankCode())];
        }
        return $bankOptionArray;
    }

    /**
     * Return Bank Collection
     *
     * @return \Bat\VirtualBank\Model\ResourceModel\BankResource\BankCollection
     */
    public function getVirtualBankCollection()
    {
        $collection = $this->bankCollectionFactory->create();
        $collection->addFieldToSelect('*')
            ->addFieldToFilter('bank_status', ['eq'=>1])
            ->setOrder('bank_name', 'asc');
        return $collection;
    }
}
