<?php
namespace Bat\Integration\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;

/**
 * @class Data
 * Helper Class for Integration
 */
class Data extends AbstractHelper
{
    protected const SECRET_KEY_PATH = "bat_integrations/encr_decr/secret_key";
    protected const IV_KEY_PATH = "bat_integrations/encr_decr/iv_key";
    protected const CIPHER_ALGO_PATH = "bat_integrations/encr_decr/cipher";
    protected const OAUTHTOKEN_ENDPOINT_PATH = "bat_integrations/bat_oauth/eda_generate_auth_token_endpoint";
    protected const OAUTHTOKEN_USERNAME_PATH = "bat_integrations/bat_oauth/eda_generate_auth_token_username";
    protected const OAUTHTOKEN_PASSWORD_PATH = "bat_integrations/bat_oauth/eda_generate_auth_token_password";

    /**
     * @var Curl
     */
    private Curl $curl;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Curl $curl
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Curl $curl
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->curl = $curl;
    }

    /**
     * Decrypt Data
     *
     * @param string $data
     * @return false|string
     */
    public function decryptData($data)
    {
        $secretKey = $this->getSystemConfigValue(self::SECRET_KEY_PATH);
        $cipherMethod = $this->getSystemConfigValue(self::CIPHER_ALGO_PATH);
        $ivKey = $this->getSystemConfigValue(self::IV_KEY_PATH);
        $data = mb_convert_encoding($data, 'ISO-8859-1', 'UTF-8');
        //@codingStandardsIgnoreStart
        $data = base64_decode($data);
        //@codingStandardsIgnoreEnd
        return openssl_decrypt($data, $cipherMethod, $secretKey, OPENSSL_RAW_DATA, $ivKey);
    }

    /**
     * Encrypt Data
     *
     * @param string $data
     * @return string
     */
    public function encryptData($data)
    {
        $secretKey = $this->getSystemConfigValue(self::SECRET_KEY_PATH);
        $cipherMethod = $this->getSystemConfigValue(self::CIPHER_ALGO_PATH);
        $ivKey = $this->getSystemConfigValue(self::IV_KEY_PATH);
        $data = mb_convert_encoding($data, 'UTF-8');
        $data = openssl_encrypt($data, $cipherMethod, $secretKey, OPENSSL_RAW_DATA, $ivKey);
        return base64_encode($data);
    }

    /**
     * Return System Configuration value based on path
     *
     * @param string $path
     * @return mixed
     */
    public function getSystemConfigValue($path)
    {
        return $this->scopeConfig->getValue($path);
    }

    /**
     * Check If Encryption/Decryption can be done
     *
     * @return bool
     */
    public function canDoEncryptionDecryption()
    {
        $secretKey = $this->getSystemConfigValue(self::SECRET_KEY_PATH);
        $cipherMethod = $this->getSystemConfigValue(self::CIPHER_ALGO_PATH);
        $ivKey = $this->getSystemConfigValue(self::IV_KEY_PATH);
        if ($secretKey != "" && $cipherMethod != "" && $ivKey != "") {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Post Data to EDA
     *
     * @param string $data
     * @param string $apiEndPoint
     * @return false|string
     */
    public function postDataToEda($data, $apiEndPoint)
    {
        $response = [];
        try {
            $authorization = $this->getAuthToken();
            $this->curl->addHeader("Content-Type", "application/json");
            $this->curl->addHeader("Event-Source", "M2B2B");
            $this->curl->addHeader("Authorization", 'Bearer '.$authorization);
            $this->curl->setOption(CURLOPT_TIMEOUT, 600);
            $this->curl->post($apiEndPoint, $data);
            $response = $this->curl->getBody();
        } catch (\Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
            $response = json_encode($response);
        }
        return $response;
    }

    /**
     * Return oAuth Token
     *
     * @throws LocalizedException
     */
    public function getAuthToken()
    {
        $authTokenEndpoint = $this->getSystemConfigValue(self::OAUTHTOKEN_ENDPOINT_PATH);
        $authorizationUsername = $this->getSystemConfigValue(self::OAUTHTOKEN_USERNAME_PATH);
        $authorizationPassword = $this->getSystemConfigValue(self::OAUTHTOKEN_PASSWORD_PATH);
        if ($authTokenEndpoint == '') {
            throw new LocalizedException(__('oauth API end point not set '));
        }
        if ($authorizationUsername == '') {
            throw new LocalizedException(__('oauth API Client Id not set '));
        }
        if ($authorizationPassword == '') {
            throw new LocalizedException(__('oauth API Client secret key not set '));
        }
        $data = [
            'client_id'=>$authorizationUsername,
            'client_secret'=>$authorizationPassword,
            'grant_type'=>'client_credentials'
        ];
        $response = json_decode($this->generateOauthToken($authTokenEndpoint, $data), true);
        if (isset($response['access_token'])) {
            return $response['access_token'];
        } else {
            throw new LocalizedException(__('oauth token not generated :'.$response['error_description']));
        }
    }

    /**
     * Generate oAuth token
     *
     * @param string $authTokenEndpoint
     * @param array $data
     * @return false|string
     */
    public function generateOauthToken($authTokenEndpoint, $data)
    {
        $response = '';
        try {
            $this->curl->addHeader("Content-Type", "application/x-www-form-urlencoded");
            $this->curl->setOption(CURLOPT_TIMEOUT, 600);
            $this->curl->post($authTokenEndpoint, $data);
            $response = $this->curl->getBody();
        } catch (\Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
            $response = json_encode($response);
        }
        return $response;
    }
}
