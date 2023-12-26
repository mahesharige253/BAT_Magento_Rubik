<?php
namespace Bat\Customer\Block\Adminhtml;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\RequestInterface;

class ForgotOutletId implements ButtonProviderInterface
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
     * Constructor method

     * @param UrlInterface $url
     * @param RequestInterface $request
     */
    public function __construct(
        UrlInterface $url,
        RequestInterface $request
    ) {
        $this->url = $url;
        $this->request = $request;
    }

    /**
     * GetButton function
     */
    public function getButtonData()
    {
        return [
            'label' => __('Forgot OutletId'),
            'class' => 'save',
            'sort_order' => 40,
            'on_click' => 'deleteConfirm(\'' . __(
                    'Are you sure you want to do this ?'
                ) . '\', \'' . $this->getCustomUrl() . '\')',
        ];
    }

    /**
     * Return the url with customer Id
     */
    public function getCustomUrl()
    {
        $customerId = $this->request->getParam('id');
        return $this->url->getUrl('batcustomer/customer/forgotoutletid', ['customer_id' => $customerId]);
    }
}
