<?php
namespace FunctionalTester;

use Guzzle\Http\Message\Response as GuzzleResponse;

class Response
{
    /**
     * @param $message
     * @return bool|GuzzleResponse
     */
    public static function fromMessage($message)
    {
        return GuzzleResponse::fromMessage($message);
    }
}