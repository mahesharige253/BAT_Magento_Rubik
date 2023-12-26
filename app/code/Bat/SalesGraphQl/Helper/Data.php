<?php
namespace Bat\SalesGraphQl\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\ResourceConnection;
use Bat\CustomerBalanceGraphQl\Helper\Data as CustomerBalanceGraphQlHelperData;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var getScopeConfig
     */
    protected $scopeConfig;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var CustomerBalanceGraphQlHelperData
     */
    private $customerBalanceGraphQlHelperData;

    /**
     * Data Construct
     *
     * @param Context $context
     * @param CustomerFactory $customerFactory
     * @param ResourceConnection $resourceConnection
     * @param CustomerBalanceGraphQlHelperData $customerBalanceGraphQlHelperData
     */
    public function __construct(
        Context $context,
        CustomerFactory $customerFactory,
        ResourceConnection $resourceConnection,
        CustomerBalanceGraphQlHelperData $customerBalanceGraphQlHelperData
    ) {
        $this->scopeConfig = $context->getScopeConfig();
        $this->customerFactory = $customerFactory;
        $this->resourceConnection = $resourceConnection;
        $this->customerBalanceGraphQlHelperData = $customerBalanceGraphQlHelperData;
        parent::__construct($context);
    }

    /**
     * Is increment id is parent or not
     *
     * @param string $orderId
     * @return Int
     */
    public function checkIncrementIdIsParent($orderId)
    {
        $is_parent = 0;
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('bat_bulkorder');
        $select = $connection->select()
            ->from(
                ['c' => $tableName],
                ['bulkorder_id']
            )
            ->where(
                "c.increment_id = ?",
                $orderId
            );
        $result = $connection->fetchAll($select);
        if (!empty($result)) {
            $is_parent = 1;
        }
        return $is_parent;
    }

    /**
     * Getting the Shipping Address
     *
     * @param int $currentCustomerId
     * @return array
     */
    public function getShippingAddress($currentCustomerId)
    {
        $customerFactory = $this->customerFactory->create()->load($currentCustomerId);
        if ($customerFactory->getDefaultShippingAddress() != null) {
            $allCustomerAddress = $customerFactory->getDefaultShippingAddress();
        }
        if ($allCustomerAddress != null) {
            $returnArray['firstname'] = $allCustomerAddress['firstname'];
            $returnArray['lastname'] = $allCustomerAddress['lastname'];
            $returnArray['street1'] = $allCustomerAddress->getStreetLine(1);
            $returnArray['street2'] = $allCustomerAddress->getStreetLine(2);
            $returnArray['city'] = $allCustomerAddress['city'];
            $returnArray['region'] = $allCustomerAddress['region'];
            $returnArray['country'] = $allCustomerAddress['country_id'];
            $returnArray['postcode'] = $allCustomerAddress['postcode'];
            $returnArray['telephone'] = $allCustomerAddress['telephone'];
        }
        return $returnArray;
    }

    /**
     * Getting the Shipping Address
     *
     * @param int $currentCustomerId
     * @return array
     */
    public function isPaymentOverdue($customerId, $websiteId, $grandTotal)
    {
        $deadline = '';
        $customerStatus = $this->customerBalanceGraphQlHelperData->getCustomerCartSummary(
            $customerId,
            $websiteId,
            $grandTotal
        );
        if($customerStatus['is_credit'] == 1) {
            if ($customerStatus['grand_total'] > ($customerStatus['remaining_ar'] + $customerStatus['overpayment'])) {
                $deadline = 'overdue';
            }
        } else if($customerStatus['is_credit'] == 0) {
            if($customerStatus['grand_total'] > $customerStatus['overpayment']) {
                $deadline = 'overdue';
            }
        }
        return $deadline;
    }
}
