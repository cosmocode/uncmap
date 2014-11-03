<?php
/**
 * General tests for the uncmap plugin
 *
 * @group plugin_uncmap
 * @group plugins
 */

class general_plugin_uncmap_test extends DokuWikiTest {

    protected $pluginsEnabled = array('uncmap');

    /**
     * Simple test to make sure the plugin.info.txt is in correct format
     */
    public function test_plugininfo() {
        $file = __DIR__.'/../plugin.info.txt';
        $this->assertFileExists($file);

        $info = confToHash($file);

        $this->assertArrayHasKey('base', $info);
        $this->assertArrayHasKey('author', $info);
        $this->assertArrayHasKey('email', $info);
        $this->assertArrayHasKey('date', $info);
        $this->assertArrayHasKey('name', $info);
        $this->assertArrayHasKey('desc', $info);
        $this->assertArrayHasKey('url', $info);

        $this->assertEquals('uncmap', $info['base']);
        $this->assertRegExp('/^https?:\/\//', $info['url']);
        $this->assertTrue(mail_isvalid($info['email']));
        $this->assertRegExp('/^\d\d\d\d-\d\d-\d\d$/', $info['date']);
        $this->assertTrue(false !== strtotime($info['date']));
    }

    function test_colon() {
        global $ID, $conf;
        $ID = 'wiki:start';
        $request = new TestRequest();
        $input = array(
            'string' => 'A string',
            'id' => 'wiki:start'
        );
        saveWikiText('wiki:start', 'Testlink: [[:facts:figures|Foo]]', 'Test initialization');
        $response = $request->post($input);
        $this->assertTrue(
            strpos($response->getContent(), 'Testlink') !== false,
            'This tests the test and should always succeed.'
        );
        $this->assertTrue(
            strpos($response->getContent(), 'Foo') !== false,
            'A link beginning with a colon should not be handled at all by this plugin.'
        );
    }

    function test_slash() {
        global $ID, $conf;
        $ID = 'wiki:start';
        $request = new TestRequest();
        $input = array(
            'string' => 'A string',
            'id' => 'wiki:start'
        );
        saveWikiText('wiki:start', 'Testlink: [[/facts:figures|Foo]]', 'Test initialization');
        $response = $request->post($input);
        $this->assertTrue(
            strpos($response->getContent(), 'Testlink') !== false,
            'This tests the test and should always succeed.'
        );
        $this->assertTrue(
            strpos($response->getContent(), 'Foo') !== false,
            'A link beginning with a slash should not be handled at all by this plugin.'
        );
    }

    function test_backslash() {
        global $ID, $conf;
        $ID = 'wiki:start';
        $request = new TestRequest();
        $input = array(
            'string' => 'A string',
            'id' => 'wiki:start'
        );
        saveWikiText('wiki:start', 'Testlink: [[\facts:figures|Foo]]', 'Test initialization');
        $response = $request->post($input);
        $this->assertTrue(
            strpos($response->getContent(), 'Testlink') !== false,
            'This tests the test and should always succeed.'
        );
        $this->assertTrue(
            strpos($response->getContent(), 'Foo') !== false,
            'A link beginning with a backslash should not be handled at all by this plugin.'
        );
    }

}
/*
 * === Facts and Figures ===

  * a [[:facts:figures|Punkt1]]
  * b [[facts:figures|Punkt2]]
  * c Punkt3'
 */
?>