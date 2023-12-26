<?php
namespace Bat\ShipmentUpdate\Api;

interface OrderDeliveryUpdateInterface
{
    /**
     * GET for Post api
     *
     * @param mixed $data
     *
     * @return array
     */
    public function deliveryUpdate($data);
}
