<?php

namespace Bat\Customer\Cron;

use Bat\Customer\Model\RejectedCustomerData;
use Bat\Customer\Helper\Data;

/**
 * @class RejectedCustomer
 * Cron to delete rejected customer
 */
class RejectedCustomerDelete
{
    /**
     * @var RejectedCustomerData
     */
    private $rejectedData;

    /**
     * @var Data
     */
    private Data $dataHelper;

    /**
     * @param RejectedCustomerData $rejectedData
     * @param Data $dataHelper
     */
    public function __construct(
        RejectedCustomerData $rejectedData,
        Data $dataHelper
    ) {
        $this->rejectedData = $rejectedData;
        $this->dataHelper = $dataHelper;
    }

    /**
     * Delete Rejected customer
     */
    public function execute()
    {
        $this->rejectedData->addLog('=========Delete Rejected Customer Cron Starts===========');
        $enabledCron = $this->dataHelper->getSystemConfigValue(
            'bat_customer_rejection/general/enabled'
        );
        if($enabledCron) {
            $this->rejectedData->deleteRejectedCustomerData();
        } else {
            $this->rejectedData->addLog('Delete Rejected Customer Cron not enabled');
        }
        $this->rejectedData->addLog('=========Delete Rejected Customer Cron Ends===========');
    }
}
