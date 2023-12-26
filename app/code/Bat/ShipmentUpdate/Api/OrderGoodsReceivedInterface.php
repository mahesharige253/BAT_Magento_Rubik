<?php
namespace Bat\ShipmentUpdate\Api;

interface OrderGoodsReceivedInterface
{
    /**
     * GET for Post api
     *
     * @param mixed $data
     *
     * @return array
     */
    public function goodsReceived($data);
}
