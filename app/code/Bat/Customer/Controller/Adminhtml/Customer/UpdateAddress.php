<?php

namespace Bat\Customer\Controller\Adminhtml\Customer;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\Controller\ResultFactory;
use Bat\Customer\Model\SigunguCodeFactory;

/**
 * @class UpdateAddress
 * UpdateAddress
 */
class UpdateAddress extends Action
{
    /**     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepositoryInterface;

    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * @var SigunguCodeFactory
     */
    private $sigunguCodeFactory;

    /**
     * @param Context $context
     * @param ManagerInterface $messageManager
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param AddressRepositoryInterface $addressRepository
     * @param SigunguCodeFactory $sigunguCodeFactory
     */
    public function __construct(
        Context $context,
        ManagerInterface $messageManager,
        CustomerRepositoryInterface $customerRepositoryInterface,
        AddressRepositoryInterface $addressRepository,
        SigunguCodeFactory $sigunguCodeFactory
    ) {
        parent::__construct($context);
        $this->messageManager = $messageManager;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->addressRepository = $addressRepository;
        $this->sigunguCodeFactory = $sigunguCodeFactory;
    }

    public function execute()
    {
        try {
            $customerId = $this->getRequest()->getParam('customer_id');
            $postalCode = $this->getRequest()->getParam('postal_code');
            $addressOne = $this->getRequest()->getParam('address_one');
            $addressTwo = $this->getRequest()->getParam('address_two');
            $sigunguCode = $this->getRequest()->getParam('sigungu_code');
            $addressData = [$addressOne, $addressTwo];

            $customerRepository = $this->customerRepositoryInterface->getById($customerId);
            $addressId = '';
            foreach ($customerRepository->getAddresses() as $address)
            {
                $addressId = $address->getId();
            }
            $sigunguData = $this->sigunguCodeFactory->create()->getCollection()
                ->addFieldToFilter('sigungu_code', $sigunguCode)->load()->getFirstItem();
            if (!empty($sigunguData->getData())) {
                $customerRepository->setCustomAttribute('sigungu_code',$sigunguCode);
                $customerRepository->setCustomAttribute('tax_code',$sigunguData['tax_code']);
                $customerRepository->setCustomAttribute('depot',$sigunguData['depot']);
                $customerRepository->setCustomAttribute('sales_office',$sigunguData['depot']);
                $customerRepository->setCustomAttribute('delivery_plant',$sigunguData['depot'].'00');
            }
            $customerRepository->setCustomAttribute('approval_status',1);
            $this->customerRepositoryInterface->save($customerRepository);
            $this->updateAddress($addressId, $addressData, $postalCode);
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $this->messageManager->addSuccess(__("Address has been updated successfully."));
            return $resultRedirect->setPath('customer/index/edit/id/' . $customerId);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addNotice(__("Something wrong, please try again."));
        }
        return $resultRedirect;
    }

    public function updateAddress($addressId, $addressData, $postalCode)
    {
        /** @var \Magento\Customer\Api\Data\AddressInterface $address */
        $address = $this->addressRepository->getById($addressId);
        $address->setPostcode($postalCode);
        $address->setStreet($addressData);
        $this->addressRepository->save($address);
    }
}
