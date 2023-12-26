<?php

namespace Bat\Danal\Model;

use Magento\Framework\Model\AbstractModel;
use Bat\Danal\Helper\Api;

class ConfirmDanal extends AbstractModel
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * @param Api $api
     */
    public function __construct(
        Api $api
    ) {
        $this->api = $api;
    }

    /**
     * CPCGI call to Danal
     *
     * @param type $tid
     * @return type
     */
    public function danalConfirmation($tid)
    {
        if (!$this->api->isEnabled()) {
            return [
                'success' => false,
                'message' => (string)__("Module is disabled or credentials not configured.")
            ];
        }

        $payload = $this->getCpcgiPayload($tid);
        $response = $this->api->send($payload);
        if ($response["RETURNCODE"] == "0000") {
            return [
                'success' => true,
                'message' => 'Danal varification is successful.',
                'mobilenumber' => $response['PHONE'],
                'dob' => (isset($response['IDEN'])) ? substr($response['IDEN'], 0, 6) : '',
                'gender' => (isset($response['IDEN'])) ? substr($response['IDEN'], -1) : 0
            ];
        }

        return [
            'success' => false,
            'message' => "DANAL Confirmation failed.",
            'mobilenumber' => '',
            'dob' => '',
            'gender' => '0'
        ];
    }

    /**
     * CPCGI call to danal Payload
     *
     * @param type $tid
     * @return type
     */
    public function getCpcgiPayload($tid)
    {
        return "TXTYPE=CONFIRM&TID=".$tid."&CONFIRMOPTION=0&IDENOPTION=0";
    }
}
