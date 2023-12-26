<?php
namespace Bat\PasswordHistory\Model;

use Magento\Framework\Model\AbstractModel;
use Bat\PasswordHistory\Api\Data\UsedPasswordInterface;

class UsedPassword extends AbstractModel implements UsedPasswordInterface
{
    /**
     * @var $_eventPrefix
     */
    protected $_eventPrefix = 'used_customer_password';

    /**
     * Construct
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\UsedPassword::class);
    }

    /**
     * @inheritDoc
     */
    public function getHash()
    {
        return (string) $this->getData(self::PASSWORD_HASH);
    }

    /**
     * @inheritDoc
     */
    public function setHash($hash)
    {
        $this->setData(self::PASSWORD_HASH, $hash);
    }

    /**
     * @inheritDoc
     */
    public function setCustomerId($customerId)
    {
        $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt()
    {
        $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt($createdAt)
    {
        $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Get Last Item Date Time
     *
     * @param int $customerId
     * @return array
     */
    public function getLastItemDateTime($customerId)
    {
        if (!$customerId) {
            return [];
        }
        $table = $this->getResource()->getTable('customer_used_password');
        $connection = $this->getResource()->getConnection();
        $query = $connection->select()
            ->from(
                ['c' => $table],
                ['created_at']
            )
            ->where(
                "c.customer_id = ".$customerId
            )->Order("entity_id DESC")->limit(1);
            return $collectionResult = $connection->fetchCol($query);
    }
}
