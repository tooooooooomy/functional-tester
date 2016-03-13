<?php
namespace FunctionalTester\Message;

use GuzzleHttp\Message\MessageFactory as GuzzleMessageFactory;
use GuzzleHttp\Stream\Stream;

class MessageFactory extends GuzzleMessageFactory
{
    /**
     * @param string $message
     * @return string
     */
    public function setHttpProtocolToMessage($message)
    {
        $lines = preg_split('/(\\r?\\n)/', $message, -1, PREG_SPLIT_DELIM_CAPTURE);
        $parts = explode(':', $lines[0], 2);
        $startLine = $parts[0] == 'Status' ? "HTTP/1.1" . $parts[1] . PHP_EOL : "HTTP/1.1 200 OK" . PHP_EOL;

        return $startLine . $message;
    }

    /**
     * @param string $message
     * @return Response
     */
    public function fromMessage($message)
    {
        return parent::fromMessage($this->setHttpProtocolToMessage($message));
    }

    /**
     * @param string $statusCode
     * @param array $headers
     * @param null $body
     * @param array $options
     * @return Response
     */
    public function createResponse(
        $statusCode,
        array $headers = [],
        $body = null,
        array $options = []
    )
    {
        if (null !== $body) {
            $body = Stream::factory($body);
        }

        return new Response($statusCode, $headers, $body, $options);
    }
}