<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\VirtualBank\Model\Resolver\DataProvider;

use Psr\Log\LoggerInterface;
use Bat\VirtualBank\Model\ResourceModel\BankResource\BankCollectionFactory;
use Bat\VirtualBank\Model\ResourceModel\AccountResource\CollectionFactory as AccountCollectionFactory;

/**
 * @class VirtualBankListDataProvider
 *
 * Virtual Banks data provider
 */
class VirtualBankListDataProvider
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    /**
     * @var BankCollectionFactory
     */
    private BankCollectionFactory $bankCollectionFactory;
    /**
     * @var AccountCollectionFactory
     */
    private AccountCollectionFactory $accountCollectionFactory;

    /**
     * @param LoggerInterface $logger
     * @param BankCollectionFactory $bankCollectionFactory
     * @param AccountCollectionFactory $accountCollectionFactory
     */
    public function __construct(
        LoggerInterface $logger,
        BankCollectionFactory $bankCollectionFactory,
        AccountCollectionFactory $accountCollectionFactory
    ) {
        $this->logger = $logger;
        $this->bankCollectionFactory = $bankCollectionFactory;
        $this->accountCollectionFactory = $accountCollectionFactory;
    }

    /**
     * Return banks list
     *
     * @return array
     * @throws \Exception
     */
    public function getVirtualBankList()
    {
        $result = [];
        $virtualBankList = $this->getVirtualBankCollection();
        if ($virtualBankList->count()) {
            foreach ($virtualBankList as $bank) {
                $bankCode = $bank->getBankCode();
                $bankDetails = [];
                if ($this->isVirtualAccountNumbersAvailable($bankCode)) {
                    $bankDetails['bank_code'] = $bankCode;
                    $bankDetails['bank_name'] = $bank->getBankName();
                    $result[] = $bankDetails;
                }
            }
        }

        return $result;
    }

    /**
     * Return banks collection
     *
     * @return \Bat\VirtualBank\Model\ResourceModel\BankResource\BankCollection
     */
    public function getVirtualBankCollection()
    {
        return $this->bankCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('bank_status', ['eq'=>1])
            ->setOrder('bank_name', 'asc');
    }

    /**
     * Check virtual banks availability
     *
     * @param String $bankCode
     * @return bool
     */
    public function isVirtualAccountNumbersAvailable($bankCode)
    {
        $collection = $this->accountCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('bank_code', ['eq'=>$bankCode])
            ->addFieldToFilter('vba_assigned_status', ['eq'=>0]);
        if ($collection->count()) {
            return true;
        }
        return false;
    }
}
