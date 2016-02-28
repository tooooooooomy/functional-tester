<?php
namespace Tests\Response;

use Tests\lib\TestCase;
use FunctionalTester\Response;

class ResponseTest extends TestCase
{
    function test_getCookies()
    {
        $this->specify('if Set-cookie is unspecified', function () {
            $message = file_get_contents(__DIR__ . '/data/mock_response');
            $response = Response::fromMessage($message);

            $this->assertEmpty($response->getCookies());
        });

        $this->specify('if Set-cookies header is specified', function () {
            $message = file_get_contents(__DIR__ . '/data/mock_response_multiple_cookies');
            $response = Response::fromMessage($message);

            $this->assertEquals($response->getCookies(), [
                [
                    'domain' => 'hogehoge.com',
                    'path' => '/hogehoge',
                    'max_age' => '202054542594',
                    'expires' => 'Fri, 04-Jan-8419 16:11:51 GMT',
                    'version' => null,
                    'secure' => null,
                    'port' => null,
                    'discard' => false,
                    'comment' => null,
                    'comment_url' => null,
                    'http_only' => false,
                    'cookies' => [
                        'hoge' => 'hogehoge'
                    ],
                    'data' => [],
                ],
                [
                    'domain' => 'fugafuga.com',
                    'path' => '/fugafuga',
                    'max_age' => '202054542594',
                    'expires' => 'Fri, 04-Jan-8419 16:11:51 GMT',
                    'version' => null,
                    'secure' => null,
                    'port' => null,
                    'discard' => false,
                    'comment' => null,
                    'comment_url' => null,
                    'http_only' => false,
                    'cookies' => [
                        'fuga' => 'fugafuga'
                    ],
                    'data' => [],
                ],
            ]);
        });
    }
}
