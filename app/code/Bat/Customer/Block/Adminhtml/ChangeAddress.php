<?php
namespace Bat\Customer\Block\Adminhtml;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\AuthorizationInterface;


class ChangeAddress implements ButtonProviderInterface
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
        $customerData = $this->customerRepositoryInterface->getById($customerId);
        if (
            ($customerData->getCustomAttribute('approval_status') &&
                $this->authorization->isAllowed('Bat_Customer::address_change_by_admin')) &&
            ($customerData->getCustomAttribute('approval_status')->getValue() == 1 ||
                $customerData->getCustomAttribute('approval_status')->getValue() == 13)
        ) {
            return [
                'label' => __('Change Address'),
                'class' => 'save',
                'sort_order' => 30,
                'on_click' => 'deleteConfirm(\'' . __(
                        'Are you sure you want to do this ?'
                    ) . '\', \'' . $this->getCustomUrl() . '\')',
            ];
        }
    }

    /**
     * Return the url with customer Id
     */
    public function getCustomUrl()
    {
        $customerId = $this->request->getParam('id');
        return $this->url->getUrl('batcustomer/customer/changeaddress', ['customer_id' => $customerId]);
    }
}
