<?php

namespace Gateway\Maktapp\Api;

/**
 * Interface MobileInterface
 * @package Gateway\Maktapp\Api
 */
interface MobileInterface
{
    /**
     * Returns greeting message to user
     *
     * @api
     * @param string $name Users name.
     * @return string Greeting message with users name.
     */
    public function mobile();
}