<?php
namespace Bat\Customer\Cron;

use Magento\CustomerSegment\Model\Segment;

class CustomerRefreshSegmentCron
{

    /**
     * @var Segment
     */
    protected $customerSegmentCollection;

    /**
     * Constructor
     * @param Segment $customerSegmentCollection
     */
    public function __construct(
        Segment $customerSegmentCollection
    ) {
        $this->customerSegmentCollection = $customerSegmentCollection;
    }

    /**
     * Customer Refresh Segment cron
     *
     * @return void
     */
    public function refreshSegments()
    {
        try {
            $segments = $this->customerSegmentCollection->getCollection();
            foreach ($segments as $segment) {
                $segment->matchCustomers();
            }
        } catch (\Exception $e) {
            $this->addLog($e->getMessage());
        }
    }

    /**
     * Add Log
     *
     * @param string $message
     * @throws Zend_Log_Exception
     */
    public function addLog($message)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/customer_refresh_segment.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($message);
    }
}
