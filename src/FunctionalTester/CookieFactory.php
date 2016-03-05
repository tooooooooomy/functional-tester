<?php
namespace FunctionalTester;

use Guzzle\Plugin\Cookie\Cookie;

class CookieFactory
{
    public function __construct() {}

    /**
     * @param $data
     * @return Cookie
     */
    public function createCookie($data)
    {
        return new Cookie($data);
    }
}