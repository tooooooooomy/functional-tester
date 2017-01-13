<?php
namespace FunctionalTester\tests\FunctionalTester;

use FunctionalTester\tests\lib\TestCase;
use FunctionalTester\FunctionalTester;

class FunctionalTesterTest extends TestCase
{
    function testSetterAndGetter()
    {
        $this->specify('When Set Content-Type', function () {
            $tester = new FunctionalTester();
            $tester->setEnv(["Content-Type" => "application/x-www-form-urlencoded"]);

            $this->assertEquals($tester->getEnv(), [
                "Content-Type" => "application/x-www-form-urlencoded"
            ]);

            $this->specify('When getter specify option name', function () use ($tester) {
                $this->assertEquals($tester->getEnv(['Content-Type']), [
                    "Content-Type" => "application/x-www-form-urlencoded"
                ]);
            });
        });
    }

    function testSetSession()
    {
        $this->specify('When set test=hoge session param', function () {
            $tester = new FunctionalTester();
            $tester->setSession(['test' => 'hoge']);

            $this->assertEquals($_SESSION, [
                "test" => "hoge"
            ]);
        });

        $this->specify('When session name specified', function () {
            $tester = new FunctionalTester();
            $tester->setSession(['test' => 'hoge'], 'test');

            $env = $tester->getEnv(['HTTP_COOKIE']);
            $pieces = explode(';', $env['HTTP_COOKIE']);

            $this->assertEquals(1, count($pieces));

        });

        $this->specify('When multiple session name specified', function () {
            $tester = new FunctionalTester();
            $tester->setSession(['test' => 'hoge'], 'test1');
            $tester->setSession(['test' => 'hoge'], 'test2');

            $env = $tester->getEnv(['HTTP_COOKIE']);
            $pieces = explode(';', $env['HTTP_COOKIE']);

            $this->assertEquals(2, count($pieces));
        });
    }

    function testMakeEnvString()
    {
        $tester = new FunctionalTester();

        $this->specify('When set REQUEST_METHOD=GET, CONTENT_LENGTH=100', function () use ($tester) {
            $tester->setEnv([
                'REQUEST_METHOD' => 'GET',
                'CONTENT_LENGTH' => 100,
            ]);

            $this->assertEquals($tester->makeEnvString(), "REQUEST_METHOD='GET' CONTENT_LENGTH='100'");
        });
    }

    function testGet()
    {

        $tester = new FunctionalTester(__DIR__ . '/data/');

        $this->specify('When request param is test=hogehoge', function () use ($tester) {
            $response = $tester->get('index.php', ['test' => 'hogehoge']);
            $this->assertEquals(json_decode($response->getBody(), true)['get'], ['test' => 'hogehoge']);
        });

        $this->specify('session is test=hogehoge', function () use ($tester) {
            $tester->setSession(['test' => 'hogehoge']);
            $response = $tester->get('index.php');
            $this->assertEquals(json_decode($response->getBody(), true)['session'], ['test' => 'hogehoge']);
        });

        $this->specify('session and get parameters are specified', function () use ($tester) {
            $tester->setSession(['test' => 'hogehoge']);
            $response = $tester->get('index.php', ['test' => 'hogehoge']);
            $this->assertEquals(json_decode($response->getBody(), true)['session'], ['test' => 'hogehoge']);
            $this->assertEquals(json_decode($response->getBody(), true)['get'], ['test' => 'hogehoge']);
        });
    }

    function testPost()
    {
        $tester = new FunctionalTester(__DIR__ . '/data/');

        $this->specify('When request param is test=hogehoge', function () use ($tester) {
            $response = $tester->post('index.php', ['test' => 'hogehoge']);
            $this->assertEquals(json_decode($response->getBody(), true)['post'], ['test' => 'hogehoge']);
        });

        $this->specify('session is test=hogehoge', function () use ($tester) {
            $tester->setSession(['test' => 'hogehoge']);
            $response = $tester->post('index.php');
            $this->assertEquals(json_decode($response->getBody(), true)['session'], ['test' => 'hogehoge']);
        });

        $this->specify('session and post parameters are specified', function () use ($tester) {
            $tester->setSession(['test' => 'hogehoge']);
            $response = $tester->post('index.php', ['test' => 'hogehoge']);
            $this->assertEquals(json_decode($response->getBody(), true)['session'], ['test' => 'hogehoge']);
            $this->assertEquals(json_decode($response->getBody(), true)['post'], ['test' => 'hogehoge']);
        });

        $this->specify('when upload files', function () use ($tester) {
            $response = $tester->post('fileupload.php',
                [
                    'test' => 'hogehoge'
                ],
                [],
                [
                    [
                        'name' => 'test',
                        'filename' => 'test.txt',
                        'contents' => 'hogehoge',
                        'type' => 'text/plain',
                    ],
                ]);
            $this->assertEquals(json_decode($response->getBody(), true)['files']['test']['name'], 'test.txt');
            $this->assertEquals(json_decode($response->getBody(), true)['post'], ['test' => 'hogehoge']);
        });
    }

    function testRequest()
    {
        $tester = new FunctionalTester(__DIR__ . '/data/');
        $this->specify('request when multiple session are specified', function () use ($tester) {
            $tester->setSession(['test' => 'hogehoge'], 'test1');
            $tester->setSession(['test' => 'hogehoge'], 'test2');
            $response = $tester->request('GET', 'session_test.php', ['test' => 'hogehoge']);
            $this->assertEquals(json_decode($response->getBody(), true)['test1'], ['test' => 'hogehoge']);
            $this->assertEquals(json_decode($response->getBody(), true)['test2'], ['test' => 'hogehoge']);
        });
    }

    function testAddIncludePath()
    {
        $tester = new FunctionalTester();
        $tester->addIncludePath(':hogehoge');
        $this->assertEquals('.:/usr/share/pear:/usr/share/php:hogehoge', $tester->getIncludePath());
    }

    function testInitializeSession()
    {
        $tester = new FunctionalTester();
        $tester->setSession(['test' => 'hoge'], 'test');

        $tester->initializeSession('test');

        session_name('test');
        session_start();
        $this->assertEmpty($_SESSION);
    }

    function testMakePhpOptionsString()
    {
        $tester = new FunctionalTester();

        $this->specify('When default', function () use ($tester) {
            $this->assertEquals($tester->makePhpOptionsString(), "");
        });

        $this->specify('When set multiple options', function () use ($tester) {
            $tester->setPhpOptions([
                'display_errors' => 0,
                'memory_limit' => 10000,
            ]);
            $this->assertEquals($tester->makePhpOptionsString(), "-d display_errors='0' -d memory_limit='10000'");
        });
    }

    function testGenerateStringForMultiPart()
    {
        $tester = new FunctionalTester();

        $expected = <<<EOI
--Boundary
Content-Disposition: form-data; name="id"

hoge
--Boundary
Content-Disposition: form-data; name="hogehoge"; filename="test.txt"
Content-Type: text/plain

hogehoge
--Boundary--
EOI;

        $this->assertEquals($expected, $tester->generateStringForMultiPart(
            [
                'id'=> 'hoge'
            ],
            [
                [
                    'name' => 'hogehoge',
                    'filename' => 'test.txt',
                    'contents' => 'hogehoge',
                    'type' => 'text/plain',
                ]
            ])
        );
    }

    function test_bootstrap()
    {
        $this->specify('can call undefined function apache_note', function () {
            $tester = new FunctionalTester(__DIR__ . '/data/');

            $response = $tester->get('bootstrap_test.php');
            $this->assertEquals([false], json_decode($response->getBody(true), true));
        });
    }

    function test_generateExecFile()
    {
        $tester = new FunctionalTester(__DIR__ . '/data/');

        $execFile = $tester->generateExecFile('bootstrap_test.php');

        $this->assertTrue(file_exists($execFile));

        unlink($execFile);
    }

    function test_generateMockFilesStr()
    {
        $tester = new FunctionalTester();

        $files = explode(' ', $tester->generateMockFilesStr());

        foreach ($files as $file) {
            $cmd = 'php -l ' . $file.' >/dev/null 2>&1';
            exec($cmd, $output, $return_var);

            $this->assertEquals($return_var, 0);
        }
    }

    function test_getSessionId()
    {
        session_destroy();
        $tester = new FunctionalTester();
        $tester->setSession([
            'hoge' => 'fuga'
        ]);

        $this->assertEquals(26, strlen($tester->getSessionId()));
    }

    function test_post_has_query_string()
    {
        $tester = new FunctionalTester(__DIR__ . '/data/');
        $response = $tester->post('index.php?hoge=fuga', ['test' => 'hogehoge']);
        $this->assertEquals(json_decode($response->getBody(), true), [
            'session' => [],
            'get' => ['hoge' => 'fuga'],
            'post' => ['test' => 'hogehoge']
        ]);
    }
}
