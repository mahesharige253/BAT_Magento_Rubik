<?php

namespace Bat\Customer\Cron;

use Bat\Customer\Model\TerminatedCustomerData;
use Bat\Customer\Helper\Data;

/**
 * @class RejectedCustomer
 * Cron to delete rejected customer
 */
class TerminatedCustomerDelete
{
    /**
     * @var TerminatedCustomerData
     */
    private $terminateData;

    /**
     * @var Data
     */
    private Data $dataHelper;

    /**
     * @param TerminatedCustomerData $terminateData
     * @param Data $dataHelper
     */
    public function __construct(
        TerminatedCustomerData $terminateData,
        Data $dataHelper
    ) {
        $this->terminateData = $terminateData;
        $this->dataHelper = $dataHelper;
    }

    /**
     * Delete Terminated customer
     */
    public function execute()
    { 
        $this->terminateData->addLog('=========Delete Terminated Customer Cron Starts===========');
        $enabledCron = $this->dataHelper->getSystemConfigValue(
            'bat_customer_termination/delete_terminate_account/enabled'
        );
        if($enabledCron) {
            $this->terminateData->updateTerminatedCustomerData();
        } else {
            $this->terminateData->addLog('Delete Terminated Customer Cron not enabled');
        }
        $this->terminateData->addLog('=========Delete Terminated Customer Cron Ends===========');
    }
}
