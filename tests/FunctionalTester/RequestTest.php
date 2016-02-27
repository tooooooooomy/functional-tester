<?php
namespace Tests\FunctionalTester;

use Tests\lib\TestCase;
use FunctionalTester\Request;

class RequestTest extends TestCase
{
    function test_parseFilePath_without_query()
    {
        list($f, $q) = Request::parseFilePath('hoge.php');

        $this->assertEquals('hoge.php', $f);
        $this->assertEquals('', $q);
    }

    function test_parseFilePath_with_query()
    {
        list($f, $q) = Request::parseFilePath('hoge.php?hoge=fuga&fuga=hoge');

        $this->assertEquals('hoge.php', $f);
        $this->assertEquals(
            "hoge=fuga&fuga=hoge",
            $q
        );
    }

    function test_parseFilePath_with_query_and_empersand()
    {
        list($f, $q) = Request::parseFilePath('hoge.php?hoge=fuga&fuga=ho+ge#foobar');

        $this->assertEquals('hoge.php', $f);
        $this->assertEquals(
            "hoge=fuga&fuga=ho+ge",
            $q
        );
    }

    function test_makeFakeRequest_succeeds()
    {
        list($ret, $stdout, $stderr) = Request::makeFakeRequest(
            [   'REQUEST_METHOD'  => 'POST',
                'SCRIPT_FILENAME' => 'tests/apps/respond.php',
                'QUERY_STRING'    => 'aaa=aaa&bbb=bbb',
                'CONTENT_TYPE'    => 'application/x-www-form-urlencoded',
            ],
            'hello=world&bye=world'
        );

        $this->assertEquals(0, $ret);

        preg_match('/\r\n\r\n(.+)$/', $stdout, $matches);
        $data = json_decode($matches[1], true);

        $this->assertEquals($data, [
            'GET' => [
                'aaa' => 'aaa',
                'bbb' => 'bbb',
            ],
            'POST' => [
                'hello' => 'world',
                'bye'   => 'world',
            ],
        ]);
        $this->assertEquals('', $stderr);
    }

    function test_makeFakeRequest_fails()
    {
        list($ret, $stdout, $stderr) = Request::makeFakeRequest(
            [   'REQUEST_METHOD'  => 'GET',
                'SCRIPT_FILENAME' => 'tests/apps/die.php',
                'QUERY_STRING'    => '',
            ],
            'hello=world&bye=world'
        );

        $this->assertEquals(255, $ret);
        $this->assertTrue(strlen($stderr) > 0);

        preg_match('/\r\n\r\n(.+)$/', $stdout, $matches);

        $this->assertEquals($matches[1], 'hoge');
    }

    function test_instance()
    {
        $req = new Request('GET', 'hoge.php');

        $this->assertInstanceOf("FunctionalTester\Request", $req);
        $this->assertEquals('GET', $req->getMethod());
        $this->assertEquals('hoge.php', $req->getFilePath());
        $this->assertEquals('', $req->getQueryString());
        $this->assertEquals([], $req->getForm());
        $this->assertEquals([], $req->getHeaders());
        $this->assertEquals([], $req->getFiles());
    }

    function test_query_component_parsed()
    {
        $req = new Request('GET', 'hoge.php?hoge=fuga&foo=bar#hogefuga');

        $this->assertEquals('hoge.php', $req->getFilePath());
        $this->assertEquals(
            'hoge=fuga&foo=bar',
            $req->getQueryString()
        );
        $this->assertEquals([], $req->getForm());
    }

    function test_header_is_normalized()
    {
        $req = new Request('GET', 'hoge.php', [], ['x-hoge' => 'foobar']);

        $this->assertEquals(
            ['X_HOGE' => 'foobar'],
            $req->getHeaders()
        );
    }

    function test_header_is_form()
    {
        $req = new Request('POST', 'hoge.php', ['hoge' => 'fuga']);

        $this->assertEquals('POST', $req->getMethod());
        $this->assertEquals(
            ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'],
            $req->getHeaders()
        );
        $this->assertEquals(
            ['hoge' => 'fuga'],
            $req->getForm()
        );
    }

    function test_query_and_form()
    {
        $req = new Request('POST', 'hoge.php?foo=bar', ['hoge' => 'fuga']);

        $this->assertEquals('POST', $req->getMethod());
        $this->assertEquals(
            ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'],
            $req->getHeaders()
        );
        $this->assertEquals(
            'foo=bar',
            $req->getQueryString()
        );
        $this->assertEquals(
            ['hoge' => 'fuga'],
            $req->getForm()
        );
    }

    function get_send_GET_request()
    {
        $req = new Request('GET', 'tests/apps/get.php?hoge=fuga&fuga=hoge');

        $res = $req->send();
    }
}
