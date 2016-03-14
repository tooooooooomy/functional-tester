<?php
namespace FunctionalTester\tests\FunctionalTester\Message;

use FunctionalTester\tests\lib\TestCase;
use FunctionalTester\Message\MessageFactory;

class MessageFactoryTest extends TestCase
{
    function test_setHttpProtocolToMessage()
    {
        $this->specify('When status exists', function () {
            $target = new MessageFactory();

            $message = <<<EOF
Status: 200 OK
Content-type: text/html;charset=UTF-8

OK
EOF;
            $expected = <<<EOF
HTTP/1.1 200 OK
Status: 200 OK
Content-type: text/html;charset=UTF-8

OK
EOF;

            $this->assertEquals($expected, $target->setHttpProtocolToMessage($message));
        });

        $this->specify('When status does not exists', function () {
            $target = new MessageFactory();

            $message = <<<EOF
Content-type: text/html;charset=UTF-8

OK
EOF;
            $expected = <<<EOF
HTTP/1.1 200 OK
Content-type: text/html;charset=UTF-8

OK
EOF;

            $this->assertEquals($expected, $target->setHttpProtocolToMessage($message));
        });
    }

    function test_fromMessage()
    {
        $target = new MessageFactory();

        $message = <<<EOF
Status: 200 OK
Content-type: text/html;charset=UTF-8

OK
EOF;
        $response = $target->fromMessage($message);

        $this->assertInstanceOf('FunctionalTester\Message\Response', $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    function test_createResponse()
    {
        $target = new MessageFactory();

        $response = $target->createResponse(200, ['Content-type' => 'text/html;charset=UTF-8'], 'OK');

        $this->assertInstanceOf('FunctionalTester\Message\Response', $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
