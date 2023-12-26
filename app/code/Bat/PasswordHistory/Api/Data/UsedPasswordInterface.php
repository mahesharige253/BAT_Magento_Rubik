<?php
namespace Bat\PasswordHistory\Api\Data;

interface UsedPasswordInterface
{
    public const ID = 'entity_id';
    public const CUSTOMER_ID = 'customer_id';
    public const PASSWORD_HASH = 'password_hash';
    public const CREATED_AT = 'created_at';

    /**
     * GetEntityId
     *
     * @return int|string
     */
    public function getEntityId();

    /**
     * SetEntityId
     *
     * @param int|string $id
     * @return void
     */
    public function setEntityId($id);

    /**
     * GetHash
     *
     * @return string
     */
    public function getHash();

    /**
     * SetHash
     *
     * @param string $hash
     * @return void
     */
    public function setHash($hash);

    /**
     * SetCustomerId
     *
     * @param int|string $customerId
     * @return void
     */
    public function setCustomerId($customerId);

    /**
     * GetCustomerId
     *
     * @return int
     */
    public function getCustomerId();

    /**
     * GetCreatedAt
     *
     * @return string
     */
    public function getCreatedAt();

    /**
     * SetCreatedAt
     *
     * @param string $createdAt
     * @return void
     */
    public function setCreatedAt($createdAt);
}
