<?php

namespace Bat\Customer\Observer;

use Bat\Customer\Helper\Data;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * @class CustomerSaveBefore
 * Customer save before event observer
 */
class CustomerSaveBefore implements ObserverInterface
{
    /**
     * @var Data
     */
    private Data $helper;

    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $timezoneInterface;

    /**
     * @param Data $helper
     * @param CustomerRepositoryInterface $customerRepository
     * @param TimezoneInterface $timezoneInterface
     */
    public function __construct(
        Data $helper,
        CustomerRepositoryInterface $customerRepository,
        TimezoneInterface $timezoneInterface
    ) {
        $this->helper = $helper;
        $this->customerRepository = $customerRepository;
        $this->timezoneInterface = $timezoneInterface;
    }

    /**
     * Customer before save validation
     *
     * @param Observer $observer
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        /** @var CustomerInterface $customer */
        $customer = $observer->getCustomer();
        $customerOriginalData = $this->customerRepository->getById($customer->getId());

        if ($customer->getCustomAttribute('parent_outlet_id') && $customer->getCustomAttribute('is_parent')) {
            $parentOutletId = $customer->getCustomAttribute(
                'parent_outlet_id'
            )->getValue();

            $isParent = $customer->getCustomAttribute(
                'is_parent'
            )->getValue();
            if($parentOutletId != '' && $isParent == 1) {
                throw new LocalizedException(
                    __('Parent outlet can not assign as a child outlet')
                );
            }
        }
        /** No logic should be implemented before this code - check customer account terminated status*/
        $this->checkAccountTerminatedAlready($customerOriginalData);
        /** No logic should be implemented before this code - check customer account terminated status*/
        $this->helper->checkIfCustomerArTypeCanBeChanged($customer, $customerOriginalData);
        $this->helper->checkIfCustomerCanBeChild($customer);
        $this->checkAccountTerminated($customer, $customerOriginalData);
        $this->checkIfCustomerConfirmedFromSap($customer);
        $this->validateGstBusinessLicenseNumber($customer);
        $this->checkRejectedField($customer);
    }

    /**
     * Do not allow details update to terminated customers
     *
     * @param CustomerInterface $customerOriginalData
     * @throws LocalizedException
     */
    public function checkAccountTerminatedAlready($customerOriginalData)
    {
        if ($customerOriginalData->getCustomAttribute('approval_status')) {
            $deactivationStatusPrevious = $customerOriginalData->getCustomAttribute(
                'approval_status'
            )->getValue();
            if ($deactivationStatusPrevious == 9) {
                throw new LocalizedException(__(
                    'Terminated customer details cannot be updated'
                ));
            }
        }
    }

     /**
     * Do not allow details update to rejected status without select field customers
     *
     * @param object $customer
     * @throws LocalizedException
     */
    public function checkRejectedField($customer)
    { 
        if ($customer->getCustomAttribute('approval_status')->getValue() == 2) {
            if ($customer->getCustomAttribute('rejected_fields')->getValue() =='') {
                throw new LocalizedException(__(
                    'Please select rejected field option'
                ));
            }
        }
    }

    /**
     * Check Customer Account Deactivation Approved and Update Deactivation Date
     *
     * @param CustomerInterface $customer
     * @param CustomerInterface $customerOriginalData
     */
    public function checkAccountTerminated($customer, $customerOriginalData)
    {
        $deactivationStatusPrevious = 0;
        $deactivationStatusCurrent = 0;
        if ($customerOriginalData->getCustomAttribute('approval_status')) {
            $deactivationStatusPrevious = $customerOriginalData->getCustomAttribute(
                'approval_status'
            )->getValue();
        }
        if ($customer->getCustomAttribute('approval_status')) {
            $deactivationStatusCurrent = $customer->getCustomAttribute(
                'approval_status'
            )->getValue();
        }
        if ($deactivationStatusPrevious != 7 && $deactivationStatusCurrent == 7) {
            $currentDate = $this->timezoneInterface->date()->format('Y-m-d');
            $customer->setCustomAttribute('deactivated_at', $currentDate);
        }
    }

    /**
     * Check if customer is confirmed from SAP
     *
     * @param CustomerInterface $customer
     */
    public function checkIfCustomerConfirmedFromSap($customer)
    {
        if ($customer->getCustomAttribute('approval_status')) {
            $approvalStatus = $customer->getCustomAttribute('approval_status')->getValue();
            if ($approvalStatus == 1) {
                if ($customer->getCustomAttribute('approval_status')
                    && $customer->getCustomAttribute('sap_outlet_code')
                ) {
                    $sapConfirmationStatus = $customer->getCustomAttribute(
                        'customer_sap_confirmation_status'
                    )->getValue();
                    $sapOutletCode = $customer->getCustomAttribute('sap_outlet_code')->getValue();
                    if (!$sapConfirmationStatus || $sapOutletCode == '') {
                        throw new LocalizedException(
                            __('Customer Confirmation from SAP is required for approval')
                        );
                    }
                } else {
                    throw new LocalizedException(
                        __('Customer Confirmation from SAP is required for approval')
                    );
                }
            }
        }
    }

    /**
     * Validate customer Business License and Gst Number are same
     *
     * @param CustomerInterface $customer
     */
    public function validateGstBusinessLicenseNumber($customer)
    {
        if ($customer->getCustomAttribute('approval_status')) {
            $approvalStatus = $customer->getCustomAttribute('approval_status')->getValue();
            if ($approvalStatus == 1 || $approvalStatus == 5) {
                if ($customer->getCustomAttribute('approval_status')
                    && $customer->getCustomAttribute('gst_number')
                    && $customer->getCustomAttribute('bat_business_license_number')
                ) {
                    $gstNumber = $customer->getCustomAttribute('gst_number')->getValue();
                    $businessLicenseNumber = $customer->getCustomAttribute('bat_business_license_number')->getValue();
                    if ($gstNumber != $businessLicenseNumber) {
                        throw new LocalizedException(
                            __('Customer GST and Business License Number are required equal value for approval')
                        );
                    }
                } else {
                    throw new LocalizedException(
                        __('Customer Confirmation from GST and Business License Number are required for approval')
                    );
                }
            }
        }
    }
}
