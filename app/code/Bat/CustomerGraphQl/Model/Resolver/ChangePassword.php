<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CustomerGraphQl\Model\Resolver;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\AuthenticationInterface;
use Bat\CustomerGraphQl\Helper\Data as CustomerHelper;
use Magento\Framework\Exception\InvalidEmailOrPasswordException;
use Magento\Framework\Exception\State\UserLockedException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException;
use Bat\PasswordHistory\Api\UsedPasswordManagementInterface;

/**
 * Change customer password resolver
 */
class ChangePassword extends \Magento\CustomerGraphQl\Model\Resolver\ChangePassword
{
    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var AccountManagementInterface
     */
    private $accountManagement;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepositoryInterface;

    /**
     * @var AuthenticationInterface
     */
    private $authentication;

    /**
     * @var CustomerHelper
     */
    protected $customerHelper;

    /**
     * @var UsedPasswordManagementInterface
     */
    private $passwordManagement;

    /**
     * @param GetCustomer $getCustomer
     * @param AccountManagementInterface $accountManagement
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param AuthenticationInterface $authentication
     * @param CustomerHelper $customerHelper
     * @param UsedPasswordManagementInterface $passwordManagement
     */
    public function __construct(
        GetCustomer $getCustomer,
        AccountManagementInterface $accountManagement,
        CustomerRepositoryInterface $customerRepositoryInterface,
        AuthenticationInterface $authentication,
        CustomerHelper $customerHelper,
        UsedPasswordManagementInterface $passwordManagement
    ) {
        $this->getCustomer = $getCustomer;
        $this->accountManagement = $accountManagement;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->authentication = $authentication;
        $this->customerHelper = $customerHelper;
        $this->passwordManagement = $passwordManagement;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        /** @var ContextInterface $context */
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        if (!isset($args['currentPassword']) || '' == trim($args['currentPassword'])) {
            throw new GraphQlInputException(__('Specify the "currentPassword" value.'));
        }

        if (!isset($args['newPassword']) || '' == trim($args['newPassword'])) {
            throw new GraphQlInputException(__('Specify the "newPassword" value.'));
        }

        if (!isset($args['currentPin']) || '' == trim($args['currentPin'])) {
            throw new GraphQlInputException(__('Specify the "current pin" value.'));
        }

        if (!isset($args['newPin']) || '' == trim($args['newPin'])) {
            throw new GraphQlInputException(__('Specify the "new pin" value.'));
        }

        $customerId = $context->getUserId();

        $isPasswordValid = 0;
        $isAccountLocked = 0;
        $isPinValid = 0;
        $isPasswordAndPinValid = 0;
        /** 0 : No error
            1 : Wrong password
            2 : Wrong Pin
            3 : Both Wrong(Password and Pin)
            4 : Account is locked
            5 : Existing password
            6 : Existing Pin
            7 : Both Existing Wrong(Password and Pin)
        */
        try {
            $this->authentication->authenticate($customerId, $args['currentPassword']);
        } catch (InvalidEmailOrPasswordException $e) {
            $isPasswordValid = 1;
        } catch (UserLockedException $e) {
            $isAccountLocked = 1;
        }
        $customer = $this->getCustomer->execute($context);
        $storedCurrentPin = $customer->getCustomAttribute('outlet_pin');
        $storedCurrentPinValue =  $storedCurrentPin->getValue();

        $customerOutletId = $customer->getCustomAttribute('outlet_id');
        $outletId =  $customerOutletId->getValue();

        $customerPasswordData = ['outletId' =>$outletId , 'password' => $args['currentPassword'], 'newPassword' =>$args['newPassword']];
        $passwordExistInHistory = $this->passwordManagement->validatePasswordNew($customerPasswordData);
        $isPasswordExist = 0;
        $isPinExist = 0;
        if($passwordExistInHistory != 1) {
            $isPasswordExist = 1;
        }
        $customerPinData = ['outletId' =>$outletId , 'pin' => $args['currentPin'], 'newPin' =>$args['newPin']];
        $pinExistInHistory = $this->passwordManagement->validatePinNew($customerPinData);
        if($pinExistInHistory != 1) {
            $isPinExist = 1;
        }
        if ($isPasswordExist == 1 && $isPinExist == 1) {
            return ['success' => false, 'message_id' => 7];
        }
        if ($isPasswordExist == 1) {
            return ['success' => false, 'message_id' => 5];
        }
        if ($isPinExist == 1) {
            return ['success' => false, 'message_id' => 6];
        }

        if ($storedCurrentPinValue != base64_encode($args['currentPin'])) {
            $isPinValid = 1;
        }
        if ($isPasswordValid == 0 && $isAccountLocked == 0 && $isPinValid == 0) {

            $customer->setCustomAttribute('outlet_pin', base64_encode($args['newPin']));
            $this->customerRepositoryInterface->save($customer);

            $this->accountManagement->changePasswordById($customerId, $args['currentPassword'], $args['newPassword']);
            return ['success' => true, 'message_id' => 0];
        } else {
            if ($isAccountLocked == 1) {
                return ['success' => false, 'message_id' => 4];
            }
            if ($isPasswordValid == 1 && $isPinValid == 1) {
                return ['success' => false, 'message_id' => 3];
            }
            if ($isPasswordValid == 1) {
                return ['success' => false, 'message_id' => 1];
            }
            if ($isPinValid == 1) {
                return ['success' => false, 'message_id' => 2];
            }
        }
    }
}
