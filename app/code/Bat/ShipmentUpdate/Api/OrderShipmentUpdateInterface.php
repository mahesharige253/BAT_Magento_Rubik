<?php
namespace Bat\ShipmentUpdate\Api;

interface OrderShipmentUpdateInterface
{
    /**
     * GET for Post api
     *
     * @param mixed $entity
     *
     * @return array
     */
    public function shipmentUpdate($entity);
}
