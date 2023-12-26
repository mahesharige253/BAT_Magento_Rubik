<?php
namespace Bat\Sales\Api;

interface OrderConfirmationInterface
{
    /**
     * GET for Post api
     *
     * @param mixed $data
     *
     * @return array
     */
    public function confirmOrder($data);
}
