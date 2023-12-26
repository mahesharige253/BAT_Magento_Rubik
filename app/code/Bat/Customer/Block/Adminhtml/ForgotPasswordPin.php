<?php
namespace Bat\Customer\Block\Adminhtml;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class ForgotPasswordPin implements ButtonProviderInterface
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
     * Constructor method

     * @param UrlInterface $url
     * @param RequestInterface $request
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     */
    public function __construct(
        UrlInterface $url,
        RequestInterface $request,
        CustomerRepositoryInterface $customerRepositoryInterface
    ) {
        $this->url = $url;
        $this->request = $request;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
    }

    /**
     * GetButton function
     */
    public function getButtonData()
    {
        try {
            $customer = $this->customerRepositoryInterface->getById($this->request->getParam('id'));
            if ($customer->getCustomAttribute('approval_status')) {
                $approvalStatus = $customer->getCustomAttribute(
                    'approval_status'
                )->getValue();
                if (!in_array($approvalStatus, [0, 2, 3, 5, 9])) {
                    return [
                        'label' => __('Forgot Password/Pin'),
                        'class' => 'save',
                        'sort_order' => 50,
                        'on_click' => 'deleteConfirm(\'' . __(
                                'Are you sure you want to do this ?'
                            ) . '\', \'' . $this->getCustomUrl() . '\')',
                    ];
                }
            }
        } catch (NoSuchEntityException $e) {
            throw new NoSuchEntityException(__($e->getMessage()));
        } catch (LocalizedException $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * Return the url with customer Id
     */
    public function getCustomUrl()
    {
        $customerId = $this->request->getParam('id');
        return $this->url->getUrl('batcustomer/customer/forgetpassword', ['customer_id' => $customerId]);
    }
}
