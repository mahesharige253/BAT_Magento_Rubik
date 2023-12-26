<?php
namespace Bat\PasswordHistory\Plugin;

use Bat\CustomerGraphQl\Model\OutletIdPinPasswordCheck;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Exception\LocalizedException;
use Bat\PasswordHistory\Api\UsedPasswordManagementInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Bat\PasswordHistory\Helper\Config;
use Bat\PasswordHistory\Model\UsedPasswordFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Bat\Integration\Helper\Data as IntegrationHelper;
use Bat\Customer\Helper\Data;

class OutletIdPinPasswordPlugin
{
    /**
     * @var UsedPasswordManagementInterface
     */
    private $passwordManagement;

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
    protected $integrationHelper;

    /**
     * @var Data
     */
     protected $helper;

    /**
     * @param UsedPasswordManagementInterface $passwordManagement
     * @param UsedPasswordFactory $usedPasswordFactory
     * @param TimezoneInterface $timezoneInterface
     * @param Config $config
     * @param IntegrationHelper $integrationHelper
     * @param Data $helper
     */

    public function __construct(
        UsedPasswordManagementInterface $passwordManagement,
        UsedPasswordFactory $usedPasswordFactory,
        TimezoneInterface $timezoneInterface,
        Config $config,
        IntegrationHelper $integrationHelper,
        Data $helper
    ) {
        $this->passwordManagement = $passwordManagement;
        $this->usedPasswordFactory = $usedPasswordFactory;
        $this->timezoneInterface = $timezoneInterface;
        $this->config = $config;
        $this->integrationHelper = $integrationHelper;
        $this->helper = $helper;
    }

    /**
     * BeforeExecute
     *
     * @param OutletIdPinPasswordCheck $subject
     * @param array $customerData
     * @return array
     * @throws GraphQlInputException
     */
    public function beforeExecute(OutletIdPinPasswordCheck $subject, $customerData)
    {
        $currentDateTime = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
        $usedPasswordFactory = $this->usedPasswordFactory->create();
        $customerData['outletId'] = $this->integrationHelper->decryptData($customerData['outletId']);
        $decryptFields = explode(",", $customerData['outletId']);
        $customerData['outletId'] = $decryptFields[0];
        $customer = $this->helper->getCustomer('outlet_id', $customerData['outletId']);
        $customer = $customer->getFirstItem();
        $customerId = $customer['entity_id'];
        $lastUpdatePasswordTime = $usedPasswordFactory->getLastItemDateTime($customerId);
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
        $this->passwordManagement->validatePassword($customerData);
        $this->passwordManagement->validatePin($customerData);
    }

    /**
     * AfterExecute
     *
     * @param OutletIdPinPasswordCheck $subject
     * @param bool $result
     * @param array $customerData
     * @return bool
     */
    public function afterExecute(OutletIdPinPasswordCheck $subject, $result, $customerData)
    {
        if ($result['success'] != false) {
            $this->passwordManagement->saveUsedPinPassword($customerData);
        }
        return $result;
    }
}
