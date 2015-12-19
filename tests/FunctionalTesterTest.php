<?php

use tests\lib\TestCase;
use psr\Test\FunctionalTester;

class FunctionalTesterTest extends TestCase {

    private $tester;

    function testSetterAndGetter()
    {
        $this->tester = new FunctionalTester();

        $this->specify('When Set Content-Type', function () {
            $this->tester->setEnv(["Content-Type" => "application/x-www-form-urlencoded"]);

            $this->assertEquals($this->tester->getEnv(), [
                "Content-Type" => "application/x-www-form-urlencoded"
            ]);

            $this->specify('When getter specify option name', function () {
                $this->assertEquals($this->tester->getEnv(['Content-Type']), [
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

            $this->assertEquals($_SESSION,[
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

            $this->assertEquals($tester->makeEnvString(),"REQUEST_METHOD='GET' CONTENT_LENGTH='100'");
        });
    }

    function testGet()
    {
        $tester = new FunctionalTester('/cposthome/tests/_data/psr/Test/');

        $this->specify('When request param is test=hogehoge', function () use($tester) {
            $response = $tester->get('index.php', ['test' => 'hogehoge']);
            $this->assertEquals(json_decode($response->getBody(), true)['get'], ['test' => 'hogehoge']);
        });

        $this->specify('session is test=hogehoge', function () use($tester) {
            $tester->setSession(['test' => 'hogehoge']);
            $response = $tester->get('index.php');
            $this->assertEquals(json_decode($response->getBody(), true)['session'], ['test' => 'hogehoge']);
        });

        $this->specify('session and get parameters are specified', function () use($tester) {
            $tester->setSession(['test' => 'hogehoge']);
            $response = $tester->get('index.php', ['test' => 'hogehoge']);
            $this->assertEquals(json_decode($response->getBody(), true)['session'], ['test' => 'hogehoge']);
            $this->assertEquals(json_decode($response->getBody(), true)['get'], ['test' => 'hogehoge']);
        });
    }

    function testPost()
    {
        $tester = new FunctionalTester('/cposthome/tests/_data/psr/Test/');

        $this->specify('When request param is test=hogehoge', function () use($tester) {
            $response = $tester->post('index.php', ['test' => 'hogehoge']);
            $this->assertEquals(json_decode($response->getBody(), true)['post'], ['test' => 'hogehoge']);
        });

        $this->specify('session is test=hogehoge', function () use($tester) {
            $tester->setSession(['test' => 'hogehoge']);
            $response = $tester->post('index.php');
            $this->assertEquals(json_decode($response->getBody(), true)['session'], ['test' => 'hogehoge']);
        });

        $this->specify('session and post parameters are specified', function () use($tester) {
            $tester->setSession(['test' => 'hogehoge']);
            $response = $tester->post('index.php', ['test' => 'hogehoge']);
            $this->assertEquals(json_decode($response->getBody(), true)['session'], ['test' => 'hogehoge']);
            $this->assertEquals(json_decode($response->getBody(), true)['post'], ['test' => 'hogehoge']);
        });
    }

    function testRequest()
    {
        $tester = new FunctionalTester('/cposthome/tests/_data/psr/Test/');
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
}
