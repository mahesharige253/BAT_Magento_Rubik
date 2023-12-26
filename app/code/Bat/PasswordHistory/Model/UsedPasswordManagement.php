<?php
namespace Bat\PasswordHistory\Model;

use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilderFactory;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Bat\PasswordHistory\Api\Data\UsedPasswordInterface;
use Bat\PasswordHistory\Api\UsedPasswordManagementInterface;
use Bat\PasswordHistory\Api\UsedPasswordRepositoryInterface;
use Bat\PasswordHistory\Helper\Config;
use Bat\Customer\Helper\Data;
use Bat\Integration\Helper\Data as IntegrationHelper;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class UsedPasswordManagement implements UsedPasswordManagementInterface
{
    /**
     * @var UsedPasswordRepositoryInterface
     */
    private $passwordRepository;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $criteriaBuilderFactory;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SortOrderBuilderFactory
     */
    private $sortOrderBuilderFactory;

    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;

    /**
     * @var Data
     */
     protected $helper;

    /**
     * @var TimezoneInterface
     */
    private $timezoneInterface;

    /**
     * @param UsedPasswordRepositoryInterface $passwordRepository
     * @param SearchCriteriaBuilderFactory $criteriaBuilderFactory
     * @param SortOrderBuilderFactory $sortOrderBuilderFactory
     * @param EncryptorInterface $encryptor
     * @param Config $config
     * @param Data $helper
     * @param IntegrationHelper $integrationHelper
     * @param TimezoneInterface $timezoneInterface
     */
    public function __construct(
        UsedPasswordRepositoryInterface $passwordRepository,
        SearchCriteriaBuilderFactory $criteriaBuilderFactory,
        SortOrderBuilderFactory $sortOrderBuilderFactory,
        EncryptorInterface $encryptor,
        Config $config,
        Data $helper,
        IntegrationHelper $integrationHelper,
        TimezoneInterface $timezoneInterface
    ) {
        $this->passwordRepository = $passwordRepository;
        $this->criteriaBuilderFactory = $criteriaBuilderFactory;
        $this->encryptor = $encryptor;
        $this->config = $config;
        $this->sortOrderBuilderFactory = $sortOrderBuilderFactory;
        $this->helper = $helper;
        $this->integrationHelper = $integrationHelper;
        $this->timezoneInterface = $timezoneInterface;
    }

    /**
     * Validate customer old used password
     *
     * @param array $customerData
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function validatePassword($customerData)
    {
        if ($this->config->isEnabled()) {
            $outletId = $customerData['outletId'];
            if (isset($customerData['newPassword'])) {
                $password = $customerData['newPassword'];
            } else {
                $password = $customerData['password'];
            }

            $customer = $this->helper->getCustomer('outlet_id', $outletId);
            $customer = $customer->getFirstItem();
            $customerId = $customer['entity_id'];
            /** @var SearchCriteriaBuilder $criteriaBuilder */
            $criteriaBuilder = $this->criteriaBuilderFactory->create();
            $criteriaBuilder->addFilter(UsedPasswordInterface::CUSTOMER_ID, $customerId);

            /** @var UsedPasswordInterface[] $usedPasswords */
            $usedPasswords = $this->passwordRepository->getList($criteriaBuilder->create())->getItems();
            foreach ($usedPasswords as $usedPassword) {
                if ($this->encryptor->validateHash($password, $usedPassword->getHash())) {
                     throw new GraphQlInputException(__($this->config->getMessage()));
                }
            }
        }
        return true;
    }

    /**
     * Validate customer old used password
     *
     * @param array $customerData
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function validatePasswordNew($customerData)
    {
        $result = 0;
        if ($this->config->isEnabled()) {
            $outletId = $customerData['outletId'];
            $password = (isset($customerData['newPassword'])) ? $customerData['newPassword'] : $customerData['password'];
            $customer = $this->helper->getCustomer('outlet_id', $outletId);
            $customer = $customer->getFirstItem();
            $customerId = $customer['entity_id'];
            /** @var SearchCriteriaBuilder $criteriaBuilder */
            $criteriaBuilder = $this->criteriaBuilderFactory->create();
            $criteriaBuilder->addFilter(UsedPasswordInterface::CUSTOMER_ID, $customerId);

            /** @var UsedPasswordInterface[] $usedPasswords */
            $usedPasswords = $this->passwordRepository->getList($criteriaBuilder->create())->getItems();
            foreach ($usedPasswords as $usedPassword) {
                if ($this->encryptor->validateHash($password, $usedPassword->getHash())) {
                    $result++;
                }
            }
        }
        return ($result == 0) ? true : false;
    }

    /**
     * Validate customer old used pin
     *
     * @param array $customerData
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function validatePin($customerData)
    {
        if ($this->config->isEnabled()) {
            $outletId = $customerData['outletId'];
            if (isset($customerData['newPin'])) {
                $pin = $customerData['newPin'];
            } else {
                $pin = $customerData['pin'];
            }

            $customer = $this->helper->getCustomer('outlet_id', $outletId);
            $customer = $customer->getFirstItem();
            $customerId = $customer['entity_id'];
            /** @var SearchCriteriaBuilder $criteriaBuilder */
            $criteriaBuilder = $this->criteriaBuilderFactory->create();
            $criteriaBuilder->addFilter(UsedPasswordInterface::CUSTOMER_ID, $customerId);

            /** @var UsedPasswordInterface[] $usedPasswords */
            $usedPins = $this->passwordRepository->getList($criteriaBuilder->create())->getItems();
            foreach ($usedPins as $usedPin) {
                if ($usedPin->getOutletPin() == base64_encode($pin)) {
                     throw new GraphQlInputException(__($this->config->getMessage()));
                }
            }
        }
        return true;
    }

    /**
     * Validate customer old used pin
     *
     * @param array $customerData
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function validatePinNew($customerData)
    {
        $result = 0;
        if ($this->config->isEnabled()) {
            $outletId = $customerData['outletId'];
            $pin = (isset($customerData['newPin'])) ? $customerData['newPin'] : $customerData['pin'];
            $customer = $this->helper->getCustomer('outlet_id', $outletId);
            $customer = $customer->getFirstItem();
            $customerId = $customer['entity_id'];
            /** @var SearchCriteriaBuilder $criteriaBuilder */
            $criteriaBuilder = $this->criteriaBuilderFactory->create();
            $criteriaBuilder->addFilter(UsedPasswordInterface::CUSTOMER_ID, $customerId);

            /** @var UsedPasswordInterface[] $usedPasswords */
            $usedPins = $this->passwordRepository->getList($criteriaBuilder->create())->getItems();
            foreach ($usedPins as $usedPin) {
                if ($usedPin->getOutletPin() == base64_encode($pin)) {
                    $result++;
                }
            }
        }
        return ($result == 0) ? true : false;
    }
    /**
     * Save password (as hash) to the list of used pin passwords for customer
     *
     * @param array $customerData
     * @return void
     * @throws LocalizedException
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     */
    public function saveUsedPinPassword($customerData)
    {
        $currentDateTime = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
        $outletId = $this->integrationHelper->decryptData($customerData['outletId']);
        $decryptFields = explode(",", $outletId);
        $outletId = $decryptFields[0];
        if (isset($customerData['newPassword'])) {
            $password = $customerData['newPassword'];
            $pin = $customerData['newPin'];
        } else {
            $password = $customerData['password'];
            $pin = $customerData['pin'];
        }

        $customer = $this->helper->getCustomer('outlet_id', $outletId);
        $customer = $customer->getFirstItem()->getData();
        $customerId = $customer['entity_id'];
        $usedPassword = $this->passwordRepository->getNew();
        $usedPassword->setCustomerId($customerId);
        $usedPassword->setPasswordHash($this->encryptor->getHash($password, true));
        $usedPassword->setOutletPin(base64_encode($pin));
        $usedPassword->setCreatedAt($currentDateTime);
        $this->passwordRepository->save($usedPassword);
        $this->cleanUpOldPinPasswords($customerId);
    }

    /**
     * Remove all used pin passwords for customer except for configured number of last ones
     *
     * @param int $customerId
     * @return void
     * @throws CouldNotDeleteException
     */
    private function cleanUpOldPinPasswords($customerId)
    {
        /** @var SortOrderBuilder $sortOrderBuilder */
        $sortOrderBuilder = $this->sortOrderBuilderFactory->create();

        $sortOrder = $sortOrderBuilder
            ->setField(UsedPasswordInterface::CREATED_AT)
            ->setDescendingDirection()
            ->create();

        /** @var SearchCriteriaBuilder $criteriaBuilderToKeep */
        $criteriaBuilderToKeep = $this->criteriaBuilderFactory->create();
        $criteriaBuilderToKeep->addFilter(UsedPasswordInterface::CUSTOMER_ID, $customerId);

        $criteriaBuilderAll = clone $criteriaBuilderToKeep;

        /** @var UsedPasswordInterface[] $passwordsAll */
        $passwordsAll = $this->passwordRepository->getList($criteriaBuilderAll->create())->getItems();

        $criteriaBuilderToKeep->addSortOrder($sortOrder);
        $criteriaBuilderToKeep->setPageSize($this->config->getHistorySize());
        $passwordsToKeep = $this->passwordRepository->getList($criteriaBuilderToKeep->create())->getItems();

        /** @var UsedPasswordInterface $password */
        foreach (array_diff(array_keys($passwordsAll), array_keys($passwordsToKeep)) as $passwordId) {
            $this->passwordRepository->delete($passwordsAll[$passwordId]);
        }
    }
}
