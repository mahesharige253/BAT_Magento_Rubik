<?php
namespace Bat\Customer\Block\Adminhtml;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\AuthorizationInterface;

/**
 * @class PushToSwift
 * Push customer details to swift button class
 */
class PushToSwift implements ButtonProviderInterface
{
    /**
     * @var UrlInterface
     */
    protected $url;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepositoryInterface;

    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * Constructor method

     * @param UrlInterface $url
     * @param RequestInterface $request
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param AuthorizationInterface $authorization
     */
    public function __construct(
        UrlInterface $url,
        RequestInterface $request,
        CustomerRepositoryInterface $customerRepositoryInterface,
        AuthorizationInterface $authorization
    ) {
        $this->url = $url;
        $this->authorization = $authorization;
        $this->request = $request;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
    }

    /**
     * GetButton function
     */
    public function getButtonData()
    {
        $customerId = $this->request->getParam('id');
        $customer = $this->customerRepositoryInterface->getById($customerId);
        $approvalStatus = $customer->getCustomAttribute('approval_status');
        $sapOutletCode = $customer->getCustomAttribute('sap_outlet_code');
        $approvalStatus = ($approvalStatus) ? $approvalStatus->getValue() : '';
        $sapOutletCode = ($sapOutletCode) ? $sapOutletCode->getValue() : '';
        if ($sapOutletCode == '' && $approvalStatus == 5) {
            return [
                'label' => __('Push To Swift+'),
                'class' => 'save',
                'sort_order' => 130,
                'on_click' => 'deleteConfirm(\'' . __(
                    'Are you sure you want to do this ?'
                ) . '\', \'' . $this->getEdaPushUrl() . '\')',
            ];
        }
    }

    /**
     * Return the url for push customer to eda
     */
    public function getEdaPushUrl()
    {
        $customerId = $this->request->getParam('id');
        $queryParams = [
            'customer_id' => $customerId,
            'channel' => 'SWIFTPLUS'
        ];
        return $this->url->getUrl('batcustomer/customer/pushtoeda', ['_query' => $queryParams]);
    }
}
