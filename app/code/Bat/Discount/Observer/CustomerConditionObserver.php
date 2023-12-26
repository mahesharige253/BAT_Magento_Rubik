<?php
namespace Bat\Discount\Observer;

class CustomerConditionObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * Execute observer.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $additional = $observer->getAdditional();
        $conditions = (array) $additional->getConditions();
        $conditions = array_merge_recursive($conditions, [
            $this->getCustomerIsMigratedCondition()
        ]);
        $additional->setConditions($conditions);
        return $this;
    }

    /**
     * Get condition for customer is Migrated.
     *
     * @return array
     */
    private function getCustomerIsMigratedCondition()
    {
        return [
            'label'=> __('Customer is Migrated'),
            'value'=> \Bat\Discount\Model\Rule\Condition\Customer::class
        ];
    }
}
