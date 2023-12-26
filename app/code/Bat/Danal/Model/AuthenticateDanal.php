<?php

namespace Bat\Danal\Model;

use Magento\Framework\Model\AbstractModel;
use Bat\Danal\Helper\Api;

class AuthenticateDanal extends AbstractModel
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
     * Authenticate Danal
     *
     * @param type $targetQuery
     * @param type $backQuery
     * @return type
     */
    public function authenticate($targetQuery, $backQuery)
    {
        if (!$this->api->isEnabled()) {
            return [
                'success' => false,
                'message' => (string)__("Module is disabled or credentials not configured.")
            ];
        }

        $config = $this->api->getConfig();
        $payload = $this->getAuthenticatePayload();
        $response = $this->api->send($payload);
        if ($response["RETURNCODE"] == "0000") {
            $data = [
                "BgColor" => $this->getRandom(0, 10),
                "BackURL" => $config['back_url'],
                "IsCharSet" => API::CHARSET,
                "TID" => $response["TID"],
                "targetQuery" => $targetQuery,
                "backQuery" => $backQuery
            ];
            return [
                'success' => true,
                'message' => 'Danal authentication is successful.',
                'danal_url' => $config['danal_url'],
                'data' => json_encode($data)
            ];
        } else {
            return [
                'success' => false,
                'message' => (string)$response["RETURNMSG"]
            ];
        }
    }

    /**
     * Authenticate Payload
     *
     * @return type
     */
    public function getAuthenticatePayload()
    {
        $config = $this->api->getConfig();
        return "TXTYPE=ITEMSEND&SERVICE=UAS&AUTHTYPE=36&CPID=".$config['cpid']."&CPPWD=".$config['cppwd']."&TARGETURL=".$config['target_url']."&CPTITLE=www.danal.co.kr";
    }

    /**
     * Get random number
     *
     * @param type $nMin
     * @param type $nMax
     * @return type
     */
    public function getRandom($nMin, $nMax)
    {
        return rand($nMin, $nMax);
    }
}
