<?php

namespace Bat\Kakao\Api;

interface TemplateInterface
{
    /**
     * GET template
     *
     * @param string $templateCode
     * @return array
     */
    public function getTemplate(string $templateCode);

    /**
     * Send SMS
     *
     * @param string $to
     * @param mixed $params
     * @param string $templateCode
     * @return array
     */
    public function sendSms($to, $params, $templateCode);

    /**
     * GET template
     *
     * @param string $templateCode
     * @return array
     */
    public function sendTestSms(string $templateCode);
}
