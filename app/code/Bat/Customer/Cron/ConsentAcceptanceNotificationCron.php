<?php
namespace Bat\Customer\Cron;

use Bat\Customer\Model\SendConsentNotification;

class ConsentAcceptanceNotificationCron
{

    /**
     * @var SendConsentNotification
     */
    protected $sendConsentNotification;

    /**
     * Constructor
     * @param SendConsentNotification $sendConsentNotification
     */
    public function __construct(
        SendConsentNotification $sendConsentNotification
    ) {
        $this->sendConsentNotification = $sendConsentNotification;
    }

    /**
     * Kakao message send cron
     *
     * @return void
     */
    public function execute()
    {
        $this->sendConsentNotification->addLog('Consent Notification Cron start');
        $this->sendConsentNotification->sendSmsAcceptedConsent();
        $this->sendConsentNotification->addLog('Consent Notification Cron end');
    }
}
