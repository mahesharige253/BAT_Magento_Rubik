<?php

namespace Bat\Attributes\Model\Source;

use Bat\VirtualBank\Model\ResourceModel\BankResource\BankCollectionFactory;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

/**
 * @class
 * Option class for Banks
 */
class Banks extends AbstractSource
{
    /**
     * @var BankCollectionFactory
     */
    private BankCollectionFactory $bankCollectionFactory;

    /**
     * @param BankCollectionFactory $bankCollectionFactory
     */
    public function __construct(
        BankCollectionFactory $bankCollectionFactory,
    ) {
        $this->bankCollectionFactory = $bankCollectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function getAllOptions()
    {
        $collection = $this->bankCollectionFactory->create()
            ->addFieldToSelect('*')
            ->setOrder('bank_name', 'asc');
        if ($this->_options === null) {
            $this->_options[] = ['label' => 'Select Bank', 'value' => ''];
            foreach ($collection as $bank) {
                $this->_options[] = ['label' => $bank->getBankName(), 'value' => $bank->getBankCode()];
            }
        }
        return $this->_options;
    }
}
