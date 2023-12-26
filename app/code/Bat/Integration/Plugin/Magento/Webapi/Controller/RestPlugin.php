<?php
declare(strict_types=1);
namespace Bat\Integration\Plugin\Magento\Webapi\Controller;

use Magento\Framework\Webapi\Rest\Response as RestResponse;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

/**
 * @class RestPlugin
 * Rest API Logging for Product, Price and Inventory
 */
class RestPlugin
{
    /**
     * @var SerializerInterface
     */
    private SerializerInterface $jsonSerializer;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var RequestInterface
     */
    private RequestInterface $requestInterface;

    /**
     * @param SerializerInterface $jsonSerializer
     * @param LoggerInterface $logger
     * @param RequestInterface $requestInterface
     */
    public function __construct(
        SerializerInterface $jsonSerializer,
        LoggerInterface $logger,
        RequestInterface $requestInterface
    ) {
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
        $this->requestInterface = $requestInterface;
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
     * Log for Product Integration
     *
     * @param RestResponse $subject
     * @param $result
     * @return mixed
     */
    public function afterSendResponse(RestResponse $subject, $result)
    {
        try {
            $requestUrl = $this->requestInterface->getRequestUri();
            if (str_contains($requestUrl, '/rest/all/V1/products/') ||
                str_contains($requestUrl, '/rest/all/V1/products') ||
                str_contains($requestUrl, '/rest/V1/products/') ||
                str_contains($requestUrl, '/rest/V1/products')
            ) {
                $logType = '';
                $requestData = $this->getRequestData($this->requestInterface);
                if ($requestUrl == '/rest/V1/products/base-prices' ||
                    $requestUrl == '/rest/all/V1/products/base-prices' ||
                    $requestUrl == '/rest/V1/products/base-prices/' ||
                    $requestUrl == '/rest/all/V1/products/base-prices/'
                ) {
                    $logType = 'EdaPriceMaster.log';
                } else {
                    if (isset($requestData['body_params']['product']['extension_attributes']['stock_item'])) {
                        $logType = 'EdaInventory.log';
                    } else {
                        if ($requestData['http_method'] == 'PUT') {
                            $logType = 'EdaMaterialEnrichment.log';
                        } else {
                            $logType = 'EdaMaterialMaster.log';
                        }
                    }
                }
                if ($logType != '') {
                    $this->addlog("======================================", $logType);
                    $this->addlog("Request:", $logType);
                    $this->addlog(json_encode($requestData), $logType);
                    $this->addlog("Response:", $logType);
                    $this->addlog($subject->getBody(), $logType);
                }
            }
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage() . $e->getMessage());
        }
        return $result;
    }

    /**
     * Material/Enrichment/Inventory Log
     *
     * @param string $message
     * @throws Zend_Log_Exception
     */
    public function addlog($message, $logfile)
    {
        $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/'.$logfile);
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($message);
    }
}
