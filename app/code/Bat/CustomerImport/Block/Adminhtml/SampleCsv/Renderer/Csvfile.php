<?php

namespace Bat\CustomerImport\Block\Adminhtml\SampleCsv\Renderer;

use Magento\Framework\DataObject;
use Magento\Framework\View\Asset\Repository;

class Csvfile extends \Magento\Framework\Data\Form\Element\AbstractElement
{
     /**
      * @var Repository
      */
     protected $assetRepo;

     /**
      * Constructor method
      *
      * @param Repository $assetRepo
      */
    public function __construct(
        Repository $assetRepo
    ) {
         $this->assetRepo = $assetRepo;
    }

     /**
      * Get Csv Downloadable Link
      *
      * @return string
      */
    public function getElementHtml()
    {
         $csvFile = $this->assetRepo->getUrl('Bat_CustomerImport::csv/customer_import_sample.csv');
         $levelMsg = __("Download Sample Csv File");
         $csvLink = "<a href=" . $csvFile . " target='_blank'>".$levelMsg."</a>";
         return $csvLink;
    }

    /**
     * Get Customer Frequency
     */
    public function getCustomerFrequency()
    {
          return $this->assetRepo->getUrl('Bat_CustomerImport::csv/customer_order_frequency_sample.csv');
    }
}
