<?php
namespace Bat\Rma\Api;

interface RmaCreateInterface
{
    /**
     * Create Rma
     *
     * @param string $batchId
     * @param string $orderType
     * @param string $createdAt
     * @param string $incrementId
     * @param mixed $items
     * @return mixed
     */
    public function createRma($batchId, $orderType, $createdAt, $incrementId, $items);
}
