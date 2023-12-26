<?php
declare(strict_types=1);

namespace Bat\Log\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value as ConfigValue;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

class DynamicField extends ConfigValue
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param SerializerInterface $serializer
     * @param LoggerInterface $logger
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        SerializerInterface $serializer,
        LoggerInterface $logger,
        Context                       $context,
        Registry                      $registry,
        ScopeConfigInterface          $config,
        TypeListInterface             $cacheTypeList,
        AbstractResource              $resource = null,
        AbstractDb                    $resourceCollection = null,
        array                         $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->logger = $logger;
        $this->serializer = $serializer;
    }

    /**
     * Prepare data before save
     *
     * @return void
     */
    public function beforeSave(): void
    {
        /** @var array $value */
        $value = $this->getValue();
        unset($value['__empty']);
        $encodedValue = $this->serializer->serialize($value);
        $this->setValue($encodedValue);
    }

    /**
     * Process data after load
     *
     * @return void
     */
    protected function _afterLoad(): void
    {
        /** @var string $value */
        $value = $this->getValue();
        $decodedValue = [];
        try {
            $decodedValue = $this->serializer->unserialize($value);
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage() . $e->getTraceAsString());
        }
        $this->setValue($decodedValue);
    }
}
