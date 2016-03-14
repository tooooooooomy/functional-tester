<?php
namespace FunctionalTester\tests\FunctionalTester\Mock;

require_once __DIR__ . "/../../../src/FunctionalTester/Mock/ApacheNote.php";

use ApacheNote;
use FunctionalTester\tests\lib\TestCase;

class ApacheNoteTest extends TestCase
{
    public function test_getInstance()
    {
        $this->specify('instances are same', function () {
            $instance1 = ApacheNote::getInstance();
            $instance2 = ApacheNote::getInstance();

            $this->assertTrue($instance1 === $instance2);
        });
    }

    public function test_note()
    {
        $this->specify('use get_note when note_name is not specified', function () {
            $instance = ApacheNote::getInstance();

            $this->assertFalse($instance->getNote('hoge'));
        });

        $this->specify('use get_note when note_name is not specified', function () {
            $instance = ApacheNote::getInstance();

            $this->assertFalse($instance->setNote('hoge','hogehoge'));
        });

        $this->specify('use get_note when note_name is specified', function () {
            $instance = ApacheNote::getInstance();

            $this->assertEquals('hogehoge', $instance->getNote('hoge'));
        });

        $this->specify('use set_note when note_name is specified', function () {
            $instance = ApacheNote::getInstance();

            $this->assertEquals('hogehoge', $instance->setNote('hoge', 'hogefuga'));
            $this->assertEquals('hogefuga', $instance->getNote('hoge'));
        });
    }

    /**
     * @expectedException \RuntimeException
     */
    public function test_clone ()
    {
        $instance1 = ApacheNote::getInstance();

        $instance2 = clone $instance1;
    }
}