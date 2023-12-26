<?php
declare(strict_types=1);

namespace Bat\Log\Model;

use Bat\Log\Services\Configuration;
use Psr\Log\LoggerInterface;

class GraphQlLogger
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @param Configuration $configuration
     * @param LoggerInterface $logger
     */
    public function __construct(
        Configuration $configuration,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->configuration = $configuration;
    }

    /**
     * Execute
     *
     * @param array $queryDetails
     * @return void
     */
    public function execute(array $queryDetails): void
    {
        foreach ($queryDetails as $data) {
            if (isset($data['GraphQLCustomLogging'])
                && $data['GraphQLCustomLogging']) {
                $this->logger->info("-=-=-=-=--=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-");
                $this->logger->debug("Graphql Log Data: " . json_encode($data['GraphQLCustomLogging']));
                break;
            }
        }
    }
}
