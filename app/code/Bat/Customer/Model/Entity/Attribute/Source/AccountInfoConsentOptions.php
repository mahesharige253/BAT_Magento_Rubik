<?php
namespace Bat\Customer\Model\Entity\Attribute\Source;

use Bat\CustomerConsentForm\Model\ConsentFormFactory;

class AccountInfoConsentOptions extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @var ConsentFormFactory
     *
     */
    protected $consentformFactory;

    /**
     * Constructor
     *
     * @param ConsentFormFactory $consentformFactory
     */
    public function __construct(
        ConsentFormFactory $consentformFactory
    ) {
        $this->consentformFactory = $consentformFactory;
    }
    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        $options = [];
        $data = $this->consentformFactory->create()->getCollection()
        ->addFieldToFilter('consent_type', 'Registration,Account');
        foreach ($data as $consentdata) {
            $title = $consentdata['title'];
            $identifier = $consentdata['identifier'];
            $options[] = ['label' => $title, 'value' => $identifier];
        }
        return $options;
    }
}
