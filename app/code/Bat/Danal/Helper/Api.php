<?php

namespace Bat\Danal\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Escaper;
use Bat\Danal\Logger\Logger;

class Api extends AbstractHelper
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var Logger
     */
    protected $logger;
    
    public const DN_TIMEOUT = "30";
    public const DN_CONNECT_TIMEOUT = "5";
    public const CHARSET = "EUC-KR";

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Escaper $escaper
     * @param Logger $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Escaper $escaper,
        Logger $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->escaper = $escaper;
        $this->logger = $logger;
    }

    /**
     * Prepare danal configurations
     *
     * @return array
     */
    public function getConfig()
    {
        $config = $this->scopeConfig->getValue('danal/setting', ScopeInterface::SCOPE_STORE);
        return (is_array($config)) ? $config : [];
    }

    /**
     * Check Danal is active or not.
     *
     * @return boolean
     */
    public function isActive()
    {
        $config = $this->getConfig();
        if (!array_key_exists('is_active', $config) || $config['is_active'] != 1) {
            $this->addLog("Danal module is disabled.");
            return false;
        }
        return true;
    }

    /**
     * Get API URL from danal configuration.
     *
     * @return string
     */
    private function getApiUrl()
    {
        $config = $this->getConfig();
        return $config['api_url'];
    }

    /**
     * Check credentials configuration.
     *
     * @return boolean
     */
    public function isCredentialsAvailable()
    {
        $config = $this->getConfig();
        if (!array_key_exists('api_url', $config) || $config['api_url'] == ''
                || !array_key_exists('danal_url', $config) || $config['danal_url'] == ''
                || !array_key_exists('cpid', $config) || $config['cpid'] == ''
                || !array_key_exists('cppwd', $config) || $config['cppwd'] == ''
                || !array_key_exists('back_url', $config) || $config['back_url'] == ''
                || !array_key_exists('target_url', $config) || $config['target_url'] == '') {
            $this->addLog("Danal config fields are not added properly.");
            return false;
        }

        return true;
    }

    /**
     * Check if cron is enabled and credentials are added
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return ($this->isActive() && $this->isCredentialsAvailable()) ? true : false;
    }

    /**
     * Send request to danal
     *
     * @param type $data
     */
    public function send($data)
    {
        if (!$this->isEnabled()) {
            return [
                "RETURNCODE" => -1,
                "RETURNMSG" => "Danal functionality is not active."
            ];
        }
        $this->addLog("---------- Request ----------");
        $this->addLog($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSLVERSION, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::DN_CONNECT_TIMEOUT);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::DN_TIMEOUT);
        curl_setopt($ch, CURLOPT_URL, $this->getApiUrl());
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-type:application/x-www-form-urlencoded;charset=".self::CHARSET]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLINFO_HEADER_OUT, 1);

        $RES_STR = curl_exec($ch);

        if (($CURL_VAL=curl_errno($ch)) != 0) {
            return [
                'RETURNCODE' => -1,
                'RETURNMSG' => "NETWORK ERROR(" . $this->escaper->escapeHtml($CURL_VAL) . ":" . $this->escaper->escapeHtml(curl_error($ch)) . ")"
            ];
        }

        curl_close($ch);
        $this->addLog("---------- Response ----------");
        $this->addLog($RES_STR);
        $this->addLog("------------------------------");

        $responseData = [];
        $response = explode("&", $RES_STR);
        if ($response) {
            foreach ($response as $field) {
                $responseFields = explode("=", $field);
                if ((isset($responseFields[0]))) {
                    $value = (isset($responseFields[1])) ? $responseFields[1] : "";
                    $responseData[$responseFields[0]] = $value;
                }
            }
        }

        if (empty($responseData) || !array_key_exists('RETURNCODE', $responseData)) {
            return ["RETURNCODE" => -1, "RETURNMSG" => "There is some issue."];
        }

        return $responseData;
    }

    /**
     * Add data to log file.
     *
     * @param type $logdata
     * @return type
     */
    public function addLog($logdata)
    {
        $config = $this->getConfig();
        if (!array_key_exists('log_active', $config) || $config['log_active'] != 1) {
            return;
        }

        if (is_array($logdata)) {
            $this->logger->info('', $logdata);
        } else {
            $this->logger->info($logdata);
        }
    }
}
