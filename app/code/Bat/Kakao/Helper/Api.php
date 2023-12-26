<?php

namespace Bat\Kakao\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\HTTP\ClientFactory;
use Magento\Framework\Exception\LocalizedException;
use Bat\Kakao\Logger\Logger;

class Api extends AbstractHelper
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var ClientFactory
     */
    protected $clientFactory;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var DataObject
     */
    protected $config;

    /**
     * @var DataObject
     */
    protected $templateConfig;

    /**
     * @param EncryptorInterface $encryptor
     * @param ClientFactory $clientFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     */
    public function __construct(
        EncryptorInterface $encryptor,
        ClientFactory $clientFactory,
        ScopeConfigInterface $scopeConfig,
        Logger $logger
    ) {
        $this->encryptor = $encryptor;
        $this->clientFactory = $clientFactory;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->config = new \Magento\Framework\DataObject();
        $this->templateConfig = new \Magento\Framework\DataObject();
    }

    /**
     * Prepare facade configurations
     *
     * @return DataObject
     */
    public function getConfig()
    {
        if (empty($this->config->getData())) {
            $this->config->addData(
                $this->scopeConfig->getValue('kakao/setting', ScopeInterface::SCOPE_STORE)
            );
        }
        return $this->config;
    }

    /**
     * Prepare facade configurations
     *
     * @return DataObject
     */
    public function getTemplateConfig()
    {
        return $this->scopeConfig->getValue('kakao/template', ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check is cron active or not.
     *
     * @return boolean
     */
    public function isActive()
    {
        $isCronEnable = $this->getConfig()->getIsActive();
        if (!$isCronEnable || $isCronEnable == 0) {
            $this->addLog("Kakao module is disabled.");
            return false;
        }
        return true;
    }

    /**
     * Get usercode from kakao configuration.
     *
     * @return type
     */
    private function getUsercode()
    {
        return $this->getConfig()->getUsercode();
    }

    /**
     * Get dept code from kakao configuration.
     *
     * @return type
     */
    private function getDeptcode()
    {
        return $this->getConfig()->getDeptcode();
    }

    /**
     * Check  configuration.
     *
     * @return type
     */
    public function isCredentialsAvailable()
    {
        if (!$this->getConfig()->getApiUrl() || !$this->getUsercode() || !$this->getDeptcode()) {
            $this->addLog("API URL or Usercode or Deptcode is not added.");
            return false;
        }
        return true;
    }

    /**
     * Check if cron is enabled and credentials are added
     *
     * @return type
     */
    public function isEnabled()
    {
        return ($this->isActive() && $this->isCredentialsAvailable()) ? true : false;
    }

    /**
     * Check if sandbox mode is enabled
     *
     * @return type
     */
    public function isSandboxMode()
    {
        return ($this->getConfig()->getTestMode()) ? true : false;
    }

    /**
     * Get Test Receiver Number
     *
     * @return type
     */
    public function isTestReceiverNumber()
    {
        return ($this->getConfig()->getTestReceiverNumber()) ?? '';
    }

    /**
     * Get Restricted Mobile Numbers
     *
     * @return array
     */
    public function getRestrictedNumbers()
    {
        $numbers = $this->getConfig()->getRestrictNumbers();
        $numbers = ($numbers != '' && trim($numbers) != '') ? explode(",", trim($numbers)) : [];
        $mobileNumbers = [];
        foreach ($numbers as $number) {
            $mobileNumbers[] = trim($number);
        }
        return $mobileNumbers;
    }

    /**
     * Get Test Receiver Number
     *
     * @return string
     */
    public function isMaskNumberEnabled()
    {
        return ($this->getConfig()->getMaskNumberEnabled()) ? true : false;
    }

    /**
     * Get Test Receiver Number
     *
     * @return string
     */
    public function getLeadingDigit()
    {
        return ($this->getConfig()->getLeadingDigit()) ?? '';
    }

    /**
     * Get Test Receiver Number
     *
     * @return string
     */
    public function getUpdateDigitWithNumber()
    {
        return ($this->getConfig()->getUpdateDigitWith()) ?? '';
    }

    /**
     * Get general error message
     *
     * @return type
     */
    public function isGeneralErrorMessage()
    {
        $errorMessage = $this->getConfig()->getErrorMessage();
        return ($errorMessage) ? __($errorMessage) : __("There is some issue while sending sms");
    }

    /**
     * Send request to facade
     *
     * @param type $endPoint
     * @param type $method
     * @param type $requestBody
     * @param type $header
     */
    public function send($endPoint, $method = 'post', $requestBody = '', $header = [])
    {
        $message = (string) $this->isGeneralErrorMessage();
        try {
            $method = strtolower($method);
            $apiUrl = trim($this->getConfig()->getApiUrl()) . $endPoint;

            /** @var \Magento\Framework\HTTP\ClientFactory $client */
            $client = $this->clientFactory->create();
            $client->setHeaders($this->getHeaders($header));

            if ($method == 'post') {
                $client->post($apiUrl, $requestBody);
            } elseif ($method == 'delete') {
                $client->setOption(CURLOPT_CUSTOMREQUEST, $method);
                $client->setOption(CURLOPT_RETURNTRANSFER, true);
                $client->post($apiUrl, $requestBody);
            } else {
                $client->get($apiUrl);
            }

            if ($client->getBody() !== null) {
                $responseBody = ($client->getBody()) ? json_decode($client->getBody(), true) : [];
                $responseMessage = (isset($responseBody['message']))
                        ? $responseBody['message']
                        : '';
                $message = ($responseMessage) ?: $message;
            }

            $this->addLog("Request Body:");
            $this->addLog($requestBody);
            $this->addLog("Response Body:");
            $this->addLog($client->getBody());
            $this->addLog("-------------------------");

            return [
                'code' => $client->getStatus(),
                'message' => $message
            ];
        } catch (LocalizedException $ex) {
            return [
                'code' => 400,
                'message' => $message
            ];
        } catch (\Exception $ex) {
            return [
                'code' => 400,
                'message' => $message
            ];
        }
    }

    /**
     * Get template from kakao server.
     *
     * @param string $endPoint
     * @param string $payload
     */
    public function getTemplate($endPoint, $payload)
    {
        $message = (string) $this->isGeneralErrorMessage();
        try {
            $apiUrl = trim($this->getConfig()->getApiUrl()) . $endPoint;

            /** @var \Magento\Framework\HTTP\ClientFactory $client */
            $client = $this->clientFactory->create();
            $client->setHeaders($this->getHeaders());
            $client->post($apiUrl, $payload);

            $this->addLog("API URL: " . $apiUrl);
            $this->addLog("Request Body:");
            $this->addLog($payload);
            $this->addLog("Response Status: " . $client->getStatus());
            $this->addLog("Response Body:");
            $this->addLog($client->getBody());

            if ($client->getBody() !== null) {
                $responseBody = ($client->getBody()) ? json_decode($client->getBody(), true) : [];
                if (!empty($responseBody) && (isset($responseBody['code'])) && $responseBody['code'] == 200) {
                    $this->addLog("-------------------------");
                    return [
                        'code' => $responseBody['code'],
                        'template' => $responseBody['data']
                    ];
                }
            }

            $this->addLog("-------------------------");
            return [
                'code' => $client->getStatus(),
                'message' => $client->getBody()
            ];
        } catch (LocalizedException $ex) {
            $this->addLog("-------------------------");
            return [
                'code' => 400,
                'message' => $message
            ];
        } catch (\Exception $ex) {
            $this->addLog("-------------------------");
            return [
                'code' => 400,
                'message' => $message
            ];
        }
    }

    /**
     * Prepare headers
     *
     * @param type $headers
     * @return string
     */
    public function getHeaders($headers = [])
    {
        $data = [
            "Content-Type" => "application/json",
            "Expect" => ""
        ];

        if (!empty($headers)) {
            foreach ($headers as $key => $value) {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * Add data to log file.
     *
     * @param type $logdata
     * @return type
     */
    public function addLog($logdata)
    {
        if (!$this->getConfig()->getlogActive()) {
            return;
        }
        if (is_array($logdata)) {
            $this->logger->info('', $logdata);
        } else {
            $this->logger->info($logdata);
        }
    }
}
