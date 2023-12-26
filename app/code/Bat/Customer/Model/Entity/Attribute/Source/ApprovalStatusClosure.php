<?php

namespace Bat\Customer\Model\Entity\Attribute\Source;

use Magento\Framework\App\RequestInterface;
use Magento\Customer\Model\CustomerFactory;

class ApprovalStatusClosure extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * Request instance
     *
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @param RequestInterface $request
     * @param CustomerFactory $_customerFactory
     */
    public function __construct(
        RequestInterface $request,
        CustomerFactory $_customerFactory
    ) {
        $this->request = $request;
        $this->_customerFactory = $_customerFactory;
    }

    /**
     * @inheritdoc
     */
    public function getAllOptions()
    {
        $id = $this->request->getParam('id');

        if ($id) {
            $customer = $this->_customerFactory->create()->load($id);
            $status = $customer->getApprovalStatus();
            if ($status == 4) {
                return [
                    ['label' => __('Approved'), 'value' => 1],
                    ['label' => __('VBA Change'), 'value' => 4],
                ];
            } elseif ($status == 1) {
                return [
                    ['label' => __('Approved'), 'value' => 1],
                ];

            } elseif ($status == 0) {
                return [
                    ['label' => __('New'), 'value' => 0],
                    ['label' => __('Rejected'), 'value' => 2],
                    ['label' => __('Under Review'), 'value' => 5],
                ];

            } elseif ($status == 5) {
                return [
                    ['label' => __('Approved'), 'value' => 1],
                    ['label' => __('Rejected'), 'value' => 2],
                    ['label' => __('Under Review'), 'value' => 5],
                ];
            } elseif ($status == 2) {
                return [
                    ['label' => __('Rejected'), 'value' => 2],
                ];
            } elseif ($status == 3) {
                return [
                    ['label' => __('Resubmitted'), 'value' => 3],
                    ['label' => __('Rejected'), 'value' => 2],
                    ['label' => __('Under Review'), 'value' => 5],
                ];
            } elseif ($status == 6) {
                return [
                    ['label' => __('Closure Under Review'), 'value' => 6],
                    ['label' => __('Closure Approved'), 'value' => 7],
                    ['label' => __('Closure Refund In-Progress'), 'value' => 10],
                    ['label' => __('Closure Collection In-Progress'), 'value' => 11],
                ];
            } elseif ($status == 8) {
                return [
                    ['label' => __('Closure Under Review'), 'value' => 6],
                    ['label' => __('Closure Rejected'), 'value' => 8],
                ];
            } elseif ($status == 7) {
                return [
                    ['label' => __('Closure Approved'), 'value' => 7],
                    ['label' => __('Closure Account Terminated'), 'value' => 9],
                    ['label' => __('Closure Refund In-Progress'), 'value' => 10],
                    ['label' => __('Closure Collection In-Progress'), 'value' => 11],
                ];
            } elseif ($status == 12) {
                return [
                    ['label' => __('Approved'), 'value' => 1],
                    ['label' => __('Address Change Requested'), 'value' => 12],
                    ['label' => __('Address Change Rejected'), 'value' => 13],
                ];
            } elseif ($status == 13) {
                return [
                    ['label' => __('Address Change Rejected'), 'value' => 13],
                ];
            } elseif ($status == 9) {
                return [
                    ['label' => __('Closure Account Terminated'), 'value' => 9],
                ];
            } elseif ($status == 10) {
                return [
                    ['label' => __('Closure Approved'), 'value' => 7],
                    ['label' => __('Closure Refund In-Progress'), 'value' => 10],
                    ['label' => __('Closure Collection In-Progress'), 'value' => 11],
                ];
            } elseif ($status == 11) {
                return [
                    ['label' => __('Closure Approved'), 'value' => 7],
                    ['label' => __('Closure Collection In-Progress'), 'value' => 11],
                    ['label' => __('Closure Refund In-Progress'), 'value' => 10],
                ];
            } elseif ($status == 14) {
                return [
                    ['label' => __('Closure Under Review'), 'value' => 6],
                    ['label' => __('Closure Rejected'), 'value' => 8],
                    ['label' => __('Closure New Request'), 'value' => 14],
                ];
            } else {
                return [
                    ['label' => __('New'), 'value' => 0],
                    ['label' => __('Approved'), 'value' => 1],
                    ['label' => __('Rejected'), 'value' => 2],
                    ['label' => __('Resubmitted'), 'value' => 3],
                    ['label' => __('VBA Change'), 'value' => 4],
                    ['label' => __('Under Review'), 'value' => 5],
                    ['label' => __('Closure Under Review'), 'value' => 6],
                    ['label' => __('Closure Approved'), 'value' => 7],
                    ['label' => __('Closure Rejected'), 'value' => 8],
                    ['label' => __('Closure Account Terminated'), 'value' => 9],
                    ['label' => __('Closure Refund In-Progress'), 'value' => 10],
                    ['label' => __('Closure Collection In-Progress'), 'value' => 11],
                    ['label' => __('Address Change Requested'), 'value' => 12],
                    ['label' => __('Address Change Rejected'), 'value' => 13],
                    ['label' => __('Closure New Request'), 'value' => 14],
                ];
            }
        } else {
            return [
                ['label' => __('New'), 'value' => 0],
                ['label' => __('Approved'), 'value' => 1],
                ['label' => __('Rejected'), 'value' => 2],
                ['label' => __('Resubmitted'), 'value' => 3],
                ['label' => __('VBA Change'), 'value' => 4],
                ['label' => __('Under Review'), 'value' => 5],
                ['label' => __('Closure Under Review'), 'value' => 6],
                ['label' => __('Closure Approved'), 'value' => 7],
                ['label' => __('Closure Rejected'), 'value' => 8],
                ['label' => __('Closure Account Terminated'), 'value' => 9],
                ['label' => __('Closure Refund In-Progress'), 'value' => 10],
                ['label' => __('Closure Collection In-Progress'), 'value' => 11],
                ['label' => __('Address Change Requested'), 'value' => 12],
                ['label' => __('Address Change Rejected'), 'value' => 13],
                ['label' => __('Closure New Request'), 'value' => 14],
            ];
        }
    }
}