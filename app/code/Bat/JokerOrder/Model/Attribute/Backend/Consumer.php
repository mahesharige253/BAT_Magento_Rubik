<?php
declare(strict_types=1);

namespace Bat\JokerOrder\Model\Attribute\Backend;

use Magento\Framework\MessageQueue\ConsumerConfiguration;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Bat\JokerOrder\Model\ResourceModel\NpiAtrributeSource\SaveNpiAttributeSource;
use Psr\Log\LoggerInterface;

class Consumer extends ConsumerConfiguration
{
    /**
     * @var JsonHelper
     */
    protected $jsonHelper;

    /**
     * @var SaveNpiAttributeSource
     */
    protected $saveNpiAttributeSource;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * CreateConsumer constructor
     *
     * @param JsonHelper $jsonHelper
     * @param SaveNpiAttributeSource $saveNpiAttributeSource
     * @param LoggerInterface $logger
     */
    public function __construct(
        JsonHelper $jsonHelper,
        SaveNpiAttributeSource $saveNpiAttributeSource,
        LoggerInterface $logger
    ) {
        $this->jsonHelper = $jsonHelper;
        $this->saveNpiAttributeSource = $saveNpiAttributeSource;
        $this->logger = $logger;
    }

    /**
     * Update NPI joker order attributes
     *
     * @param json $request
     * @return void
     */
    public function process($request)
    {
        try {
            $params = $this->jsonHelper->jsonDecode($request, true);
            $data = [];
            foreach ($params['attributes'] as $key => $value) {
                $data[] = ['attribute_id' => $key, 'entity_id' => $params['customer_id'], 'value' => $value];
            }
            $this->saveNpiAttributeSource->execute($data);
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }
}
