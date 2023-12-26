<?php
namespace Bat\BulkOrder\Cron;

use Bat\BulkOrder\Model\ParentChildOutletCsv;

class BulkOrderCsvCron
{
    /**
     * @var ParentChildOutletCsv
     */
    protected $parentChildOutletCsv;

    /**
     * Parent Child Outllet CSV Generate Cron Construct
     *
     * @param ParentChildOutletCsv $parentChildOutletCsv
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */

    public function __construct(ParentChildOutletCsv $parentChildOutletCsv)
    {
        $this->parentChildOutletCsv = $parentChildOutletCsv;
    }

    /**
     * Generate outlet csv
     *
     * @return void
     */
    public function execute()
    {
        $this->parentChildOutletCsv->addLog('Bulk Order Cron Started.');
        $this->parentChildOutletCsv->generateOutletCsv();
        $this->parentChildOutletCsv->addLog('Bulk Order Cron Finished.');
    }
}
