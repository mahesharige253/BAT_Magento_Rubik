<?php
namespace Bat\Dashboard\ViewModel;

use Bat\VirtualBank\Model\ResourceModel\AccountResource\CollectionFactory as AccountCollectionFactory;
use Bat\VirtualBank\Model\ResourceModel\BankResource\BankCollectionFactory;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * @class VbaSummary
 * View Enabled banks and available virtual accounts
 */
class VbaSummary implements ArgumentInterface
{
    /**
     * @var AccountCollectionFactory
     */
    private AccountCollectionFactory $accountCollectionFactory;

    /**
     * @var BankCollectionFactory
     */
    private BankCollectionFactory $bankCollectionFactory;

    /**
     * @param AccountCollectionFactory $accountCollectionFactory
     * @param BankCollectionFactory $bankCollectionFactory
     */
    public function __construct(
        AccountCollectionFactory $accountCollectionFactory,
        BankCollectionFactory $bankCollectionFactory,
    ) {
        $this->accountCollectionFactory = $accountCollectionFactory;
        $this->bankCollectionFactory = $bankCollectionFactory;
    }

    /**
     * Return enabled banks and available accounts count
     *
     * @return array
     */
    public function getEnabledBanks()
    {
        $enabledBanks = [];
        $banks = $this->bankCollectionFactory->create()->addFieldToFilter('bank_status', ['eq' => 1]);
        foreach ($banks as $bank) {
            $bankDetails['bank_name'] = $bank->getBankName();
            $bankDetails['available_accounts'] = $this->getAvailableVirtualAccountsByBankCode($bank->getBankCode());
            $enabledBanks[] = $bankDetails;
        }
        return $enabledBanks;
    }

    /**
     * Return Available virtual accounts count
     *
     * @param string $bankCode
     * @return int
     */
    public function getAvailableVirtualAccountsByBankCode($bankCode)
    {
        $virtualAccounts = $this->accountCollectionFactory->create()
            ->addFieldToFilter('bank_code', ['eq' => $bankCode])
            ->addFieldToFilter('vba_assigned_status', ['eq' => 0]);
        return $virtualAccounts->getSize();
    }
}
