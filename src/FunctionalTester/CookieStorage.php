<?php
namespace FunctionalTester;

use Guzzle\Parser\Cookie\CookieParser;

class CookieStorage
{
    /**
     * @var CookieFactory
     */
    protected $cookieFactory;

    /**
     * @var CookieParser
     */
    protected $cookieParser;

    /**
     * @var array
     */
    protected $cookies = [];

    /**
     * @var string
     */
    protected $responseHeader = 'Set-Cookie';

    public function __construct(CookieFactory $cookieFactory=null, CookieParser $cookieParser=null)
    {
        $this->cookieFactory = $cookieFactory ?: new CookieFactory();
        $this->cookieParser = $cookieParser ?: new CookieParser();
    }

    /**
     * @param $name
     * @param $value
     */
    public function setRequestCookie($name, $value)
    {
        if (isset($this->cookies[$name])) {
            $this->cookies[$name]->setValue($value);
        } else {
            $this->cookies[$name] = $this->cookieFactory->createCookie([
                'name' => $name,
                'value' => $value,
            ]);
        }
    }

    public function setResponseCookie($data)
    {
    }
}
