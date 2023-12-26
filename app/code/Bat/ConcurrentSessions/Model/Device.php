<?php

namespace Bat\ConcurrentSessions\Model;

use Magento\Framework\Exception\LocalizedException;

class Device
{
    /**
     * @var ConcurrentSessionsFactory
     */
    private $concurrentSessionsFactory;

    /**
     * @param ConcurrentSessionsFactory $concurrentSessionsFactory
     */
    public function __construct(
        ConcurrentSessionsFactory $concurrentSessionsFactory
    ) {
        $this->concurrentSessionsFactory = $concurrentSessionsFactory;
    }

    /**
     * Check if current device is already available in device history or not
     *
     * @param $customerId
     * @param $deviceId
     * @return bool
     * @throws \Exception
     */
    public function isNewDevice($customerId, $deviceId)
    {
        $customerInfo = $this->getCurrentCustomerInfo($customerId);
        if ($customerInfo != null) {
            if ($customerInfo->getDeviceHistory()) {
                $devices = explode(",", $customerInfo->getDeviceHistory());
                if (in_array($deviceId, $devices)) {
                    $this->setCurrentDevice($customerId, $deviceId);
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check if current device is active login or not
     *
     * @param $customerId
     * @param $deviceId
     * @return bool
     * @throws \Exception
     */
    public function isCurrentDevice($customerId, $deviceId)
    {
        $customerInfo = $this->getCurrentCustomerInfo($customerId);
        if ($customerInfo != null) {
            return ($customerInfo->getCurrentDevice() == $deviceId);
        }

        return false;
    }

    /**
     * Set device as a current device
     *
     * @param $customerId
     * @param $deviceId
     * @return bool
     * @throws \Exception
     */
    public function setCurrentDevice($customerId, $deviceId)
    {
        try {
            $sessionsFactory = $this->concurrentSessionsFactory->create();
            $customerInfo = $this->getCurrentCustomerInfo($customerId);
            $sessionsFactory->setId($customerInfo->getId());
            $sessionsFactory->setCustomerId($customerId);
            $sessionsFactory->setCurrentDevice($deviceId);
            $sessionsFactory->save();
        } catch (LocalizedException $e) {
            return false;
        }
        return true;
    }

    /**
     * Add current device to device history
     *
     * @param $customerId
     * @param $deviceId
     * @return array
     * @throws \Exception
     */
    public function addDeviceToHistory($customerId, $deviceId)
    {
        try {
            $devices = [];
            $sessionsFactory = $this->concurrentSessionsFactory->create();
            $customerInfo = $this->getCurrentCustomerInfo($customerId);
            if ($customerInfo != null) {
                $sessionsFactory->setId($customerInfo->getId());
                //Get current device history
                if ($customerInfo->getDeviceHistory()) {
                    $devices = explode(",", $customerInfo->getDeviceHistory());
                    if (!in_array($deviceId, $devices)) {
                        $devices[] = $deviceId;
                    }
                }
            }
            //Add new device to history if no history available for this device.
            if (empty($devices)) {
                $devices[] = $deviceId;
            }
            $allDevices = implode(",", $devices);

            $sessionsFactory->setCustomerId($customerId);
            $sessionsFactory->setCurrentDevice($deviceId);
            $sessionsFactory->setDeviceHistory($allDevices);
            $sessionsFactory->save();
        } catch (LocalizedException $e) {
            return ['success' => false];
        }
        return ['success' => true];
    }

    /**
     * Get current customer device history
     *
     * @param $customerId
     * @return \Magento\Framework\DataObject|null
     */
    private function getCurrentCustomerInfo($customerId)
    {
        $customers = $this->concurrentSessionsFactory->create()->getCollection()
            ->addFieldToFilter('customer_id', $customerId);
        if ($customers->getSize() > 0) {
            return $customers->getFirstItem();
        }
        return null;
    }
}
