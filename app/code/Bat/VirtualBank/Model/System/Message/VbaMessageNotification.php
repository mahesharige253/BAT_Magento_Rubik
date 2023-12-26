<?php
namespace Bat\VirtualBank\Model\System\Message;

use Bat\VirtualBank\Helper\Data;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;

class VbaMessageNotification implements MessageInterface {

    /**
     * Message identity
     */
    const MESSAGE_IDENTITY = 'vba_system_notification';

    /**
     * @var Data
     */
    private Data $data;

    /**
     * @var UrlInterface
     */
    private UrlInterface $urlBuilder;

    /**
     * @param Data $data
     */
    public function __construct(
        Data $data,
        UrlInterface $urlBuilder
    ) {
        $this->data = $data;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Retrieve unique system message identity
     *
     * @return string
     */
    public function getIdentity()
    {
        return self::MESSAGE_IDENTITY;
    }

    /**
     * Check whether the system message should be shown
     *
     * @return bool
     */
    public function isDisplayed()
    {
        $enabledBanks = $this->data->getEnabledBanks();
        if(!empty($enabledBanks)){
            foreach ($enabledBanks as $bank){
                if($bank['available_accounts'] <= 300){
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Retrieve system message text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getText()
    {
        $banksUrl = $this->urlBuilder->getUrl('vba/virtualbank/index');
        return __('Banks are running low on Virtual Accounts. '.sprintf(
            '<a href="%s">%s</a>', $banksUrl, __('View Details'))
        );
    }

    /**
     *
     * @return int
     */
    public function getSeverity()
    {
        return self::SEVERITY_CRITICAL;
    }

}
