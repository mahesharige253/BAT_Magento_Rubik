<?php

namespace Bat\Sales\Plugin\Block\Widget\Button;

use Magento\Backend\Block\Widget\Button\Toolbar as ToolbarContext;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Backend\Block\Widget\Button\ButtonList;
use Magento\Sales\Block\Adminhtml\Order\View;
use Magento\Catalog\Block\Adminhtml\Product\Edit;
use Magento\Rma\Block\Adminhtml\Rma\Edit as RmaEdit;
use Bat\Sales\Helper\Data as SalesHelper;
use Magento\Framework\UrlInterface;

class Toolbar
{
    /**
     * @var SalesHelper
     */
    private SalesHelper $salesHelper;

    /**
     * @var UrlInterface
     */
    private UrlInterface $urlBuilder;

    /**
     * @param SalesHelper $salesHelper
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        SalesHelper $salesHelper,
        UrlInterface $urlBuilder
    ) {
        $this->salesHelper = $salesHelper;
        $this->urlBuilder = $urlBuilder;
    }

    public function beforePushButtons(
        ToolbarContext  $toolbar,
        AbstractBlock   $context,
        ButtonList  $buttonList
    ) {
        if ($context instanceof View) {
            $order = $context->getOrder();
            $buttonList->remove('order_edit');
            $buttonList->remove('order_invoice');
            $buttonList->remove('order_hold');
            $buttonList->remove('order_ship');
            $buttonList->remove('order_cancel');
            $buttonList->remove('order_reorder');
            $buttonList->remove('order_creditmemo');
            $buttonList->remove('send_notification');
            $buttonList->remove('guest_to_customer');
            if ($order) {
                $edaOrderType = $order->getEdaOrderType();
                $orderId = $order->getId();
                $orderIncrementId = $order->getIncrementId();
                $maxFailuresAllowed = $this->salesHelper->getSystemConfigValue(
                    'bat_integrations/bat_order/eda_order_max_failures_allowed'
                );
                $queryParams = [
                    'order_id' => $orderId,
                    'order_type' => $edaOrderType,
                    'order_increment_id' => $orderIncrementId
                ];
                $orderPushedToSwift = $this->salesHelper->isOrderPushedToEda(
                    $orderId,
                    $maxFailuresAllowed,
                    'SWIFTPLUS'
                );
                if ($orderPushedToSwift) {
                    $queryParams['channel'] = 'SWIFTPLUS';
                    $url = $this->urlBuilder->getUrl(
                        'batsales/eda/pushordertoeda',
                        ['_current' => true,'_query' => $queryParams]
                    );
                    $buttonList->add(
                        'push_to_swiftplus',
                        [
                            'label' => __('Push Order To Swift+'),
                            'on_click' => sprintf("location.href = '%s';", $url),
                            'class' => 'action-default',
                            'id' => 'push_to_swiftplus'
                        ]
                    );
                }
                $orderPushedToOms = $this->salesHelper->isOrderPushedToEda(
                    $orderId,
                    $maxFailuresAllowed,
                    'OMS'
                );
                if ($orderPushedToOms) {
                    $queryParams['channel'] = 'OMS';
                    $url = $this->urlBuilder->getUrl(
                        'batsales/eda/pushordertoeda',
                        ['_current' => true,'_query' => $queryParams]
                    );
                    $buttonList->add(
                        'push_to_oms',
                        [
                            'label' => __('Push Order To OMS'),
                            'on_click' => sprintf("location.href = '%s';", $url),
                            'class' => 'action-default',
                            'id' => 'push_to_oms'
                        ]
                    );
                }
            }
        }
        if ($context instanceof RmaEdit) {
            $buttonList->remove('save_and_edit_button');
            $buttonList->remove('close');
            $buttonList->remove('reset');
            $buttonList->remove('print');
        }
        return [$context, $buttonList];
    }
}
