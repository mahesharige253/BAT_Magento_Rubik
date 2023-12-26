<?php

namespace Bat\Customer\Block\Adminhtml;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;

class UpdateAddress implements ButtonProviderInterface
{
    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepositoryInterface;

    /**
     * Constructor method
     *
     * @param AuthorizationInterface $authorization
     * @param RequestInterface $request
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     */
    public function __construct(
        AuthorizationInterface $authorization,
        RequestInterface $request,
        CustomerRepositoryInterface $customerRepositoryInterface,
    ) {
        $this->authorization = $authorization;
        $this->request = $request;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
    }

    /**
     * Display Address Update button if customer status is Address Change Requested
     *
     * @return array
     */
    public function getButtonData()
    {
        $customerId = $this->request->getParam('id');
        $customerData = $this->customerRepositoryInterface->getById($customerId);
        if ($customerData->getCustomAttribute('approval_status') &&
            $customerData->getCustomAttribute('approval_status')->getValue() == 12
        ) {
            $url = "#";
            return [
                'label' => __('Update Address'),
                'on_click' => sprintf("location.href = '%s';", $url),
                'class' => 'add',
                'sort_order' => 30,
                'id' => 'update-address-open-modal'
            ];
        }
    }
}
