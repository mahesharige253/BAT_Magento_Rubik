<?php
namespace Bat\CustomerImport\Block\Adminhtml\CustomerImport;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Bat\CustomerImport\Block\Adminhtml\SampleCsv\Renderer\Csvfile;

class CustomerFrequency extends Template
{
    /**
     * @var Context
     */
    protected Context $context;
    /**
     * @var Csvfile
     */
    protected Csvfile $csvfile;

    /**
     * @param Context $context
     * @param Csvfile $csvfile
     * @param array $data
     */
    public function __construct(
        Context $context,
        Csvfile $csvfile,
        array $data = []
    ) {
        $this->csvfile = $csvfile;
        parent::__construct($context, $data);
    }

    /**
     * Get CsvPath
     */
    public function getCsvPath()
    {
        return $this->csvfile->getCustomerFrequency();
    }
}
