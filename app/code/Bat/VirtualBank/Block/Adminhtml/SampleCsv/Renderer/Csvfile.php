<?php

namespace Bat\VirtualBank\Block\Adminhtml\SampleCsv\Renderer;

use Magento\Framework\DataObject;
use Magento\Framework\View\Asset\Repository;

class Csvfile extends \Magento\Framework\Data\Form\Element\AbstractElement
{
     /**
      * @var Repository
      */
     protected $_assetRepo;

     /**
      * Constructor method
      *
      * @param Repository $assetRepo
      */
    public function __construct(
        Repository $assetRepo
    ) {
         $this->_assetRepo = $assetRepo;
    }

     /**
      * Get Csv Downloadable Link
      *
      * @return string
      */
    public function getElementHtml()
    {
         $csvFile = $this->_assetRepo->getUrl('Bat_VirtualBank::csv/vbaimportsample.csv');
         $csvLink = "<a href=" . $csvFile . " target='_blank'>Download Sample Csv File</a>";
         return $csvLink;
    }
}
