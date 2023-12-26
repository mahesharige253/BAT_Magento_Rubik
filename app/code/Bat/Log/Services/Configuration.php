<?php
declare(strict_types=1);

namespace Bat\Log\Services;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Configuration
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**#@+
     * XML path  constants
     */
    private const PATH_API_STATUS = 'bat_log/rest/status';
    private const PATH_API_URLS = 'bat_log/rest/urls';
    private const PATH_GRAPHQL_STATUS = 'bat_log/graphql/status';
    private const PATH_GRAPHQL_HEADER = 'bat_log/graphql/header';
    private const PATH_GRAPHQL_HTTP_METHODS = 'bat_log/graphql/http_methods';
    private const PATH_GRAPHQL_QUERY_TYPE = 'bat_log/graphql/query_type';
    /**#@-*/

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param SerializerInterface $serializer
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        SerializerInterface $serializer
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->serializer = $serializer;
    }

    /**
     * To Get the logging status
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getApiLoggingStatus(): mixed
    {
        return $this->scopeConfig->getValue(
            self::PATH_API_STATUS,
            ScopeInterface::SCOPE_WEBSITE,
            $this->storeManager->getStore()->getWebsiteId()
        );
    }

    /**
     * To get the logging urls
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getApiLoggingUrls(): mixed
    {
        return $this->scopeConfig->getValue(
            self::PATH_API_URLS,
            ScopeInterface::SCOPE_WEBSITE,
            $this->storeManager->getStore()->getWebsiteId()
        );
    }

    /**
     * To Get the Graphql logging status
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getGraphqlLoggingStatus(): mixed
    {
        return $this->scopeConfig->getValue(
            self::PATH_GRAPHQL_STATUS,
            ScopeInterface::SCOPE_WEBSITE,
            $this->storeManager->getStore()->getWebsiteId()
        );
    }

    /**
     * To Get the Graphql header
     *
     * @return array
     */
    public function getGraphqlHeader(): array
    {
        $returnValue = [];
        try {
            $header = $this->scopeConfig->getValue(
                self::PATH_GRAPHQL_HEADER,
                ScopeInterface::SCOPE_WEBSITE,
                $this->storeManager->getStore()->getWebsiteId()
            );
            if ($header) {
                $returnValue = $this->serializer->unserialize($header);
            }
        } catch (Exception $e) {
            $this->logger->info($e->getMessage() . $e->getTraceAsString());
        }
        return $returnValue;
    }

    /**
     * To Get the Graphql Http Methods
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getGraphqlHttpMethods(): mixed
    {
        return $this->scopeConfig->getValue(
            self::PATH_GRAPHQL_HTTP_METHODS,
            ScopeInterface::SCOPE_WEBSITE,
            $this->storeManager->getStore()->getWebsiteId()
        );
    }

    /**
     * Get Graphql Query Type
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getGraphqlQueryType(): mixed
    {
        return $this->scopeConfig->getValue(
            self::PATH_GRAPHQL_QUERY_TYPE,
            ScopeInterface::SCOPE_WEBSITE,
            $this->storeManager->getStore()->getWebsiteId()
        );
    }
}
