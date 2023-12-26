<?php

namespace Bat\Kakao\Model;

use Magento\Framework\Model\AbstractModel;
use Bat\Kakao\Helper\Api;
use Bat\Kakao\Model\TemplateText;

class Sms extends AbstractModel
{
    private const SMS_API = 'messages/alimtalk';

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var TemplateText
     */
    protected $templateText;

    /**
     * @param Api $api
     * @param TemplateText $templateText
     */
    public function __construct(
        Api $api,
        TemplateText $templateText
    ) {
        $this->api = $api;
        $this->templateText = $templateText;
    }

    /**
     * Send SMS
     *
     * @param string $to
     * @param array $params
     * @param string $templateCode
     * @return array
     */
    public function sendSms($to, $params, $templateCode)
    {
        $message = [];
        if (!$this->api->isEnabled()) {
            $message[] = (string)__("Module is disabled or credentials not configured.");
        }

        if ($this->api->isSandboxMode()) {
            $to = $this->api->isTestReceiverNumber();
            if (!$to) {
                $message[] = (string)__("Sand box mode is enabled but test receiver number is not configured.");
            }
        }

        //Get message text for given template
        $template = $this->templateText->getTemplateText($templateCode, $params);
        if (empty($template)) {
            $message[] = (string)__("Please send valid template name of message.");
        }

        $allowedNumbers = $this->api->getRestrictedNumbers();
        if (!empty($allowedNumbers) && !in_array($to, $allowedNumbers)) {
            $to = $this->api->isTestReceiverNumber();
            if (!$to) {
                $message[] = (string)__("Your mobile number is not in allowed list, Test receiver number is not added.");
            }
        }

        if ($this->api->isMaskNumberEnabled()) {
            $leadingDigit = $this->api->getLeadingDigit();
            $updateWithNumber = $this->api->getUpdateDigitWithNumber();
            $firstCharacter = substr($to, 0, strlen($leadingDigit));
            if ($firstCharacter == $leadingDigit) {
                $to = substr_replace($to, $updateWithNumber, 0, strlen($leadingDigit));
            }
        }

        $to = str_replace(" ", "", $to);
        $payload = $this->getSmsPayload($to, $template, $templateCode);
        if (empty($message)) {
            return $this->api->send(self::SMS_API, 'post', $payload);
        } else {
            $message = implode(", ", $message);
            $this->api->addLog($message);
            $this->api->addLog("Request Body:");
            $this->api->addLog($payload);
            $this->api->addLog("-------------------------");
            return [
                'code' => 400,
                'message' => $message
            ];
        }
    }

    /**
     * SMS Payload
     *
     * @param string $to
     * @param array $template
     * @param string $templateCode
     * @return string
     */
    public function getSmsPayload($to, $template, $templateCode)
    {
        $config = $this->api->getConfig();
        $payload = [
            "message_id" => date('dhi').rand(000,999),
            "usercode" => $config->getUsercode(),
            "deptcode" => $config->getDeptcode(),
            "yellowid_key" => $config->getYellowId(),
            "template_code" => $templateCode,
            "to" => $to,
            "reqphone" => $config->getSmsSenderNumber(),
            "text" => $template['template'],
            "re_send" => "Y"
        ];

        return json_encode([$payload]);
    }
}
