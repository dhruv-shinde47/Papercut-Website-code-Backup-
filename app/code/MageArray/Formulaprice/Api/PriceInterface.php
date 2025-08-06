<?php

namespace MageArray\Formulaprice\Api;

interface PriceInterface
{
    /**
     * POST for getprice api
     * @api
     * @param mixed  $items
     * @return string[]
     */

    public function price($items);
}
