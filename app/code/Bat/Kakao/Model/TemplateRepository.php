<?php

namespace Bat\Kakao\Model;

use Bat\Kakao\Model\GetTemplate;
use Bat\Kakao\Model\Sms;

class TemplateRepository implements \Bat\Kakao\Api\TemplateInterface
{
    /**
     * @var GetTemplate
     */
    protected $template;

    /**
     * @var Sms
     */
    protected $sms;

    /**
     * @param GetTemplate $template
     * @param Sms $sms
     */
    public function __construct(
        GetTemplate $template,
        Sms $sms
    ) {
        $this->template = $template;
        $this->sms = $sms;
    }

    /**
     * GET template
     *
     * @param string $templateCode
     * @return array
     */
    public function getTemplate($templateCode)
    {
        $response = ['success' => false];
        try {
            $response = $this->template->getTemplate($templateCode);
        } catch (\Exception $e) {
            $response = ['success' => false, 'message' => $e->getMessage()];
        }
        return [$response];
    }

    /**
     * Send SMS
     *
     * @param string $to
     * @param mixed $params
     * @param string $templateCode
     * @return array
     */
    public function sendSms($to, $params, $templateCode)
    {
        $response = $this->sms->sendSms($to, $params, $templateCode);
        return [$response];
    }

    /**
     * Send test SMS
     *
     * @param string $templateCode
     * @return array
     */
    public function sendTestSms($templateCode)
    {
        $to = "123123";
        $params = ['customer_name' => 'Gopal Kacha', 'otp' => 100100];
        $response = $this->sms->sendSms($to, $params, $templateCode);
        return [$response];
    }
}
