<?php
namespace Bat\Discount\Model\Rule\Condition;

use Magento\Rule\Model\Condition\Context;
use Magento\Config\Model\Config\Source\Yesno;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;

class Customer extends \Magento\Rule\Model\Condition\AbstractCondition
{
    /**
     * @var Yesno
     */
    protected $sourceYesno;

    /**
     * @var CollectionFactory
     */
    protected $customerFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Yesno $sourceYesno
     * @param CollectionFactory $customerFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Yesno $sourceYesno,
        CollectionFactory $customerFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->sourceYesno = $sourceYesno;
        $this->customerFactory = $customerFactory;
    }

    /**
     * Load attribute options
     *
     * @return $this
     */
    public function loadAttributeOptions()
    {
        $this->setAttributeOption([
            'is_migrated' => __('Customer is Migrated')
        ]);
        return $this;
    }

    /**
     * Get input type
     *
     * @return string
     */
    public function getInputType()
    {
        return 'select';
    }

    /**
     * Get value element type
     *
     * @return string
     */
    public function getValueElementType()
    {
        return 'select';
    }

    /**
     * Get value select options
     *
     * @return array|mixed
     */
    public function getValueSelectOptions()
    {
        if (!$this->hasData('value_select_options')) {
            $this->setData(
                'value_select_options',
                $this->sourceYesno->toOptionArray()
            );
        }
        return $this->getData('value_select_options');
    }

    /**
     * Validate Customer First Order Rule Condition
     *
     * @param \Magento\Framework\Model\AbstractModel $model
     * @return bool
     */
    public function validate(\Magento\Framework\Model\AbstractModel $model)
    {
        $customerId = $model->getCustomerId();
        $customer = $this->customerFactory->create()
        ->addAttributeToSelect('is_migrated')
        ->addAttributeToFilter('entity_id', ['eq' => $customerId])
        ->getFirstItem();

        $isMigrated = 0;
        if ($customer->getId() && empty($customer->getIsMigrated())) {
            $isMigrated = 0;
        } elseif ($customer->getId() && !empty($customer->getIsMigrated())) {
            $isMigrated = $customer->getIsMigrated();
        }
        $model->setData('is_migrated', $isMigrated);
        return parent::validate($model);
    }
}
