<?php
declare(strict_types=1);
namespace Bat\Log\Plugin\Magento\Webapi\Controller;

use Bat\Log\Services\Configuration;
use Bat\Log\Model\Config\Source\LoggingStatus;
use Magento\Framework\Webapi\Rest\Response as RestResponse;
use Magento\Webapi\Controller\Rest;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

class RestPlugin
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var SerializerInterface
     */
    private $jsonSerializer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Configuration $configuration
     * @param SerializerInterface $jsonSerializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        Configuration $configuration,
        SerializerInterface $jsonSerializer,
        LoggerInterface $logger
    ) {
        $this->configuration = $configuration;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
    }

    /**
     * After pluging method
     *
     * @param Rest $subject
     * @param RestResponse $result
     * @param RequestInterface $request
     * @return RestResponse
     */
    public function afterDispatch(
        Rest $subject,
        RestResponse $result,
        RequestInterface $request
    ): RestResponse {
        try {
            if (!$this->canLog($request->getPathInfo())) {
                return $result;
            }
            $requestData = $this->getRequestData($request);
            $responseData = $this->getResponseData($result);
            $data = [
                'REQUEST' => $requestData,
                'RESPONSE' => $responseData
            ];
            $this->logger->info("-=-=-=-=--=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-");
            $this->logger->info("REST API Log Data: " .json_encode($data));
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage() . $e->getTraceAsString());
        }
        return $result;
    }

    /**
     * Method to validate we can log or not
     *
     * @param string $url
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function canLog(string $url): bool
    {
        $status = $this->configuration->getApiLoggingStatus();
        switch ($status) {
            case LoggingStatus::ENABLED_FOR_ALL:
                $returnValue = true;
                break;
            case LoggingStatus::ENABLED_FOR_SPECIFIC:
                $returnValue = $this->isValidUrlForLogging($url);
                break;
            default:
                $returnValue = false;
        }

        return $returnValue;
    }

    /**
     * Method to validate the current url with the configured pattern
     *
     * @param string $currentUrl
     * @return bool
     */
    private function isValidUrlForLogging($currentUrl): bool
    {
        $returnValue = false;
        $patterns = $this->getConfiguredUrls();
        foreach ($patterns as $pattern) {
            $pattern = '#' . ltrim($pattern) . "#";
            preg_match($pattern, $currentUrl, $matches);
            if ($matches) {
                $returnValue = true;
                break;
            }
        }
        return $returnValue;
    }

    /**
     * Method to get all the configured urls
     *
     * @return array
     */
    private function getConfiguredUrls(): array
    {
        $returnValue = [];
        $configuredUrls = trim($this->configuration->getApiLoggingUrls());
        if ($configuredUrls) {
            $returnValue = array_map('trim', explode("\r\n", $configuredUrls));
        }
        return $returnValue;
    }

    /**
     * Method to form the Request Data array for logging
     *
     * @param RequestInterface $request
     * @return array
     */
    private function getRequestData(RequestInterface $request): array
    {
        $bodyParams = [];
        if ($request->getContent()) {
            $bodyParams = $this->jsonSerializer->unserialize($request->getContent(), true);
            if (array_key_exists('password', $bodyParams)) {
                $bodyParams['password'] = '******';
            }
        }
        return [
            'request_uri' => $request->getRequestUri(),
            'path_info' => $request->getPathInfo(),
            'body_params' => $bodyParams,
            'params' => $request->getParams(),
            'http_method' => $request->getMethod(),
            'client_ip' => $request->getClientIp(),
            'headers' => $request->getHeaders()->toArray(),
            'version' => $request->getVersion(),
            'schema' => $request->getScheme()
        ];
    }

    /**
     * Method to form the response data for logging
     *
     * @param RestResponse $response
     * @return array
     */
    private function getResponseData(RestResponse $response): array
    {
        if ($response->getBody()) {
            $body = $this->jsonSerializer->unserialize($response->getBody());
        }
        return [
            'headers' => $response->getHeaders()->toArray(),
            'status_code' => $response->getStatusCode(),
            'version' => $response->getVersion(),
            'exception' => $response->getException(),
            'response_body' => $body ?? ''
        ];
    }
}
