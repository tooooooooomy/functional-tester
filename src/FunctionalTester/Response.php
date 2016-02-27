<?php
namespace FunctionalTester;

use Guzzle\Http\Message\Response as GuzzleResponse;
use Guzzle\Parser\ParserRegistry;

class Response extends GuzzleResponse
{
    protected $cookies = null;

    /**
     * @param string $message
     * @return self|bool
     */
    public static function fromMessage($message)
    {
        return parent::fromMessage($message);
    }

    /**
     * @param $cookieStr
     * @return mixed
     */
    protected function parseCookie($cookieStr)
    {
        return ParserRegistry::getInstance()->getParser('cookie')->parseCookie($cookieStr);
    }

    /**
     * @param $cookieStrings
     * @return array
     */
    protected function parseCookies($cookieStrings)
    {
        $cookies = [];
        foreach ($cookieStrings as $cookieStr) {
            $cookies[] = $this->parseCookie($cookieStr);
        }

        return $cookies;
    }

    /**
     * @return array|null
     */
    public function getCookies()
    {
        if (!$this->cookies && $this->getSetCookie()) {
            $cookieStrings = $this->getHeader('Set-Cookie')->toArray();
            $this->cookies = $this->parseCookies($cookieStrings);

            return $this->cookies;
        }

        return null;
    }
}