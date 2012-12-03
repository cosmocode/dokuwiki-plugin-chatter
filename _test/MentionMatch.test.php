<?php
define('DOKU_INC', '../../../../');
define('DOKU_URL', 'http://127.0.0.1');
require_once '../../../../inc/load.php';
require_once '../helper.php';


class MentionMatchTest extends PHPUnit_Framework_TestCase {

    function testPlainText() {
        $_SERVER['REMOTE_USER'] = 'test';
        $plugin = new helper_plugin_chatter();

        $expected = array(
            'messageSegments' => array(
                'type' => 'text',
                'text' => 'Some text'
            )
        );

        $this->assertEquals($expected, $plugin->processText('Some text', array()));
    }

    function testOneMention() {
        $_SERVER['REMOTE_USER'] = 'test';
        $plugin = new helper_plugin_chatter();

        $expect = array(
            'messageSegments' => array(
                array(
                    'type' => 'text',
                    'text' => 'Hello '
                ),
                array(
                    'type' => 'mention',
                    'id' => '1'
                )
            )
        );

        $this->assertEquals(
                $expect, $plugin->processText('Hello @[Test]', array('Test' => '1')));
    }

    function testStartMention() {
        $_SERVER['REMOTE_USER'] = 'test';
        $plugin = new helper_plugin_chatter();

        $expect = array(
            'messageSegments' => array(
                array(
                    'type' => 'mention',
                    'id' => '1'
                ),
                array(
                    'type' => 'text',
                    'text' => ', Hello'
                )
            )
        );

        $this->assertEquals(
            $expect, $plugin->processText('@[Test], Hello', array('Test' => '1')));
    }

    function testOnlyMention() {
        $_SERVER['REMOTE_USER'] = 'test';
        $plugin = new helper_plugin_chatter();

        $expect = array(
            'messageSegments' => array(
                array(
                    'type' => 'mention',
                    'id' => '1'
                )
            )
        );

        $this->assertEquals(
            $expect, $plugin->processText('@[Test]', array('Test' => '1')));
    }

    function testMiddleMention() {
        $_SERVER['REMOTE_USER'] = 'test';
        $plugin = new helper_plugin_chatter();

        $expect = array(
            'messageSegments' => array(
                array(
                    'type' => 'mention',
                    'id' => '1'
                ),
                array(
                    'type' => 'text',
                    'text' => ' and '
                ),
                array(
                    'type' => 'mention',
                    'id' => '2'
                )
            )
        );

        $this->assertEquals(
            $expect, $plugin->processText('@[Test] and @[Test1]', array('Test' => '1', 'Test1' => '2')));
    }




}