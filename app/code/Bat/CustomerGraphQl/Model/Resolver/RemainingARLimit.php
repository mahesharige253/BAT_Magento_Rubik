<?php
namespace Bat\CustomerGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Store\Model\StoreManagerInterface;
use Bat\CustomerBalanceGraphQl\Helper\Data;
use Magento\CustomerBalance\Model\BalanceFactory;
use Bat\CustomerBalance\Helper\Data as CustomerBalance;

class RemainingARLimit implements ResolverInterface
{

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var StoreManagerInterface
     */
    private $_storeManager;

    /**
     * @var BalanceFactory
     */
    private $balanceFactory;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var CustomerBalance
     */
    private CustomerBalance $customerBalance;

    /**
     * Construct method
     *
     * @param GetCustomer $getCustomer
     * @param StoreManagerInterface $storeManager
     * @param BalanceFactory $BalanceFactory
     * @param Data $helper
     */
    public function __construct(
        GetCustomer $getCustomer,
        StoreManagerInterface $storeManager,
        BalanceFactory $BalanceFactory,
        Data $helper,
        CustomerBalance $customerBalance
    ) {
        $this->getCustomer = $getCustomer;
        $this->_storeManager = $storeManager;
        $this->balanceFactory = $BalanceFactory;
        $this->helper = $helper;
        $this->customerBalance = $customerBalance;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(
                __(
                    'The current customer isn\'t authorized.try agin with authorization token'
                )
            );
        }
        $remainingAR = 0;
        $totalARLimit = 0;

        $customer = $this->getCustomer->execute($context);
        $customerId = $customer->getId();

        if ($customer->getCustomAttribute('total_ar_limit') !='') {
            $totalARLimit = $customer->getCustomAttribute('total_ar_limit')->getValue();
        }
        $remainingAR = $this->helper->getRemainingArLimit($customerId);

        $websiteId = $customer->getWebsiteId();
        $balance = $this->balanceFactory->create()->setCustomerId($customer->getId())
            ->setWebsiteId($websiteId)->loadbyCustomer();
        $availableCredit = $balance->getAmount();
        if ($balance->getCreditExposure() < 0) {
            $creditExposurePositive = 0;
            $creditExposureNegative = $balance->getCreditExposure();
        } else {
            $creditExposurePositive = $balance->getCreditExposure();
            $creditExposureNegative = 0;
        }
        $creditLimit = $balance->getCreditLimit();

        return [
            'customer_id' => $customerId,
            'total_ar_limit' => $creditLimit,
            'remaining_ar' => $remainingAR,
            'available_credit' => $availableCredit,
            'credit_exposure_positive' => $creditExposurePositive,
            'credit_exposure_negative' => $creditExposureNegative,
            'is_overdue' => ($balance->getOverdueFlag() && $balance->getOverdueAmount() > 0)
        ];
    }
}
