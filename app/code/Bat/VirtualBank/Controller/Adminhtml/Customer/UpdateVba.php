<?php
namespace Bat\VirtualBank\Controller\Adminhtml\Customer;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Bat\VirtualBank\Helper\Data;

class UpdateVba extends Action
{
    /** @var CustomerRepositoryInterface */
    protected $_customerRepository;

    /** @var ManagerInterface */
    protected $messageManager;

    /** @var Data */
    protected $vbaData;

    /**
     * Contruct method
     *
     * @param Context $context
     * @param CustomerRepositoryInterface $customerRepository
     * @param ManagerInterface $messageManager
     * @param Data $vbaData
     */
    public function __construct(
        Context $context,
        CustomerRepositoryInterface $customerRepository,
        ManagerInterface $messageManager,
        Data $vbaData
    ) {
        $this->_customerRepository = $customerRepository;
        $this->messageManager = $messageManager;
        $this->vbaData = $vbaData;
        parent::__construct($context);
    }

    /**
     * Execute method
     *
     * @return array
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $post = $this->getRequest()->getPostValue();
            $customerId = $post['customer_id'];
            $customer = $this->_customerRepository->getById($customerId);
            if (!empty($post['virtual_bank'])) {
                $vbaAccountResponse = $this->vbaData->isVirtualAccountNumbersAvailable($post['virtual_bank']);
                if ($vbaAccountResponse['status'] == 1) {
                    $customer->setCustomAttribute('virtual_bank_new', $post['virtual_bank']);
                    $customer->setCustomAttribute('virtual_account_new', $vbaAccountResponse['acc_no']);
                    $customer->setCustomAttribute('approval_status', 4);
                    $this->_customerRepository->save($customer);
                    $this->vbaData->deleteAccountNo($vbaAccountResponse['acc_id']);
                    $this->messageManager->addSuccess(__("Bank name is updated successfully."));
                } else {
                    $this->messageManager->addError(__("Bank account number is not available."));
                }
            }
            $resultRedirect->setPath('customer/index/edit/id/'.$customerId);

        } catch (NoSuchEntityException $e) {
            $this->messageManager->addNotice(__("Something wrong, please try again."));
        }
        return $resultRedirect;
    }
}
