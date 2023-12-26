<?php

namespace Bat\VirtualBank\Controller\Adminhtml\VirtualBank;

use Bat\VirtualBank\Model\BankModelFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;

/**
 * @class Save
 * Save Bank Details
 */
class Save extends Action
{
    /**
     * @var PageFactory
     */
    private PageFactory $resultPageFactory;
    /**
     * @var BankModelFactory
     */
    private BankModelFactory $bankModelFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param BankModelFactory $bankModelFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        BankModelFactory $bankModelFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->bankModelFactory = $bankModelFactory;
        parent::__construct($context);
    }

    /**
     * Create New Bank Page
     *
     * @return Redirect
     */
    public function execute()
    {
        try {
            $newBank = true;
            $data = $this->getRequest()->getParams();
            $bankModel = $this->bankModelFactory->create();
            if (isset($data['bank_id'])) {
                $bankModel = $bankModel->load($data['bank_id']);
                $newBank = false;
            }
            if ($data['bank_code'] == '' || $data['bank_name'] == '') {
                $this->messageManager->addError(__('Validation failed. Please check the data.'));
                $this->_redirect('*/*/edit');
            }
            $valid = $this->validateData($data);
            if ($valid == 0) {
                $this->messageManager->addError(
                    __('Please enter a valid value with alphanumeric or Korean characters')
                );
                $resultRedirect = $this->resultRedirectFactory->create();
                if ($newBank == 1) {
                    return $resultRedirect->setPath('*/*/addnew', ['_current' => true]);
                }
                return $resultRedirect->setPath('*/*/edit/bank_id/' . $data['bank_id'], ['_current' => true]);
            }
            $bankModel->setData($data);
            $bankModel->save();
            if ($newBank) {
                $this->messageManager->addSuccessMessage(__('The Bank has been successfully created'));
            } else {
                $this->messageManager->addSuccessMessage(__('The Bank details has been updated.'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error while trying to save data' . $e->getMessage()));
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('*/*/index', ['_current' => true]);
    }

    /**
     * Server side validation

     * @param array $data
     * @return boolean
     */
    private function validateData($data)
    {
        $bankCode = $data['bank_code'];
        $bankName = $data['bank_name'];
        if (!ctype_alnum($bankCode) || !preg_match('/^[A-Za-z0-9가-힣\s]*$/', $bankName)) {
            return false; // Validation failed
        }
        return true;
    }
}