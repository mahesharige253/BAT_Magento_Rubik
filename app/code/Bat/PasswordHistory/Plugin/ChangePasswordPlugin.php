<?php
namespace Bat\PasswordHistory\Plugin;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Bat\PasswordHistory\Api\UsedPasswordManagementInterface;
use Bat\CustomerGraphQl\Model\Resolver\ChangePassword;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Bat\PasswordHistory\Helper\Config;
use Bat\PasswordHistory\Model\UsedPasswordFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Bat\Integration\Helper\Data as IntegrationHelper;

class ChangePasswordPlugin
{
    /**
     * @var UsedPasswordManagementInterface
     */
    private $passwordManagement;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var UsedPasswordFactory
     */
    protected $usedPasswordFactory;

     /**
      * @var TimezoneInterface
      */
    private $timezoneInterface;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var IntegrationHelper
     */
     private $integrationHelper;

    /**
     * @param UsedPasswordManagementInterface $passwordManagement
     * @param CustomerRepositoryInterface $customerRepository
     * @param UsedPasswordFactory $usedPasswordFactory
     * @param TimezoneInterface $timezoneInterface
     * @param Config $config
     * @param IntegrationHelper $integrationHelper
     */

    public function __construct(
        UsedPasswordManagementInterface $passwordManagement,
        CustomerRepositoryInterface $customerRepository,
        UsedPasswordFactory $usedPasswordFactory,
        TimezoneInterface $timezoneInterface,
        Config $config,
        IntegrationHelper $integrationHelper
    ) {
        $this->passwordManagement = $passwordManagement;
        $this->customerRepository = $customerRepository;
        $this->usedPasswordFactory = $usedPasswordFactory;
        $this->timezoneInterface = $timezoneInterface;
        $this->config = $config;
        $this->integrationHelper = $integrationHelper;
    }

    /**
     * BeforeResolve
     *
     * @param ChangePassword $subject
     * @param object $field
     * @param object $context
     * @param object $info
     * @param array $value
     * @param array $args
     * @return array
     * @throws GraphQlInputException
     */
    public function beforeResolve(
        ChangePassword $subject,
        $field,
        $context,
        $info,
        array $value = null,
        array $args = null
    ) {

        if ($context->getUserId() == 0) {
            throw new GraphQlInputException(__('The current customer isn\'t authorized.'));
        }
        $currentDateTime = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
        $usedPasswordFactory = $this->usedPasswordFactory->create();
        $lastUpdatePasswordTime = $usedPasswordFactory->getLastItemDateTime($context->getUserId());
        if (!empty($lastUpdatePasswordTime)) {
            $hours = $this->config->getAllowedHours();
            $newDate = date('Y-m-d H:i:s', strtotime($lastUpdatePasswordTime[0]. ' + '.$hours.' hours'));
            if ($newDate >= $currentDateTime) {
                throw new GraphQlInputException(__(
                    'You can update new password after %1',
                    $newDate
                ));
            }
        }
        $customerDetatils = $this->customerRepository->getById($context->getUserId());
        if (!empty($customerDetatils->getCustomAttribute('outlet_id'))) {
            $outletId = $customerDetatils->getCustomAttribute('outlet_id')->getValue();
        }
        $customerData = ['outletId' => $outletId,
                         'password'=> $args['currentPassword'],
                         'newPassword' => $args['newPassword'],
                         'newPin' => $args['newPin'],
                         'currentPin' => $args['currentPin']
                        ];
        $this->passwordManagement->validatePassword($customerData);
        $this->passwordManagement->validatePin($customerData);
    }

    /**
     * AfterResolve
     *
     * @param ChangePassword $subject
     * @param object $result
     * @param object $field
     * @param object $context
     * @param object $info
     * @param array $value
     * @param array $args
     * @return array
     * @throws GraphQlInputException
     */
    public function afterResolve(
        ChangePassword $subject,
        $result,
        $field,
        $context,
        $info,
        array $value = null,
        array $args = null
    ) {
        if ($context->getUserId() == 0) {
            throw new GraphQlInputException(__('The current customer isn\'t authorized.'));
        }
        
        if ($result['message_id'] == 0) {
            $customerDetatils = $this->customerRepository->getById($context->getUserId());
            if (!empty($customerDetatils->getCustomAttribute('outlet_id'))) {
                $outletId = $customerDetatils->getCustomAttribute('outlet_id')->getValue();
            }
            $outletId = $this->integrationHelper->encryptData($outletId);
            $customerData = ['outletId' => $outletId,
                                'password'=> $args['currentPassword'],
                                'newPassword' => $args['newPassword'],
                                'newPin' => $args['newPin'],
                                'currentPin' => $args['currentPin']
                            ];
            $this->passwordManagement->saveUsedPinPassword($customerData);
        }
        return $result;
    }
}
