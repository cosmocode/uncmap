<?php
/**
 * General tests for the uncmap plugin
 *
 * @group plugin_uncmap
 * @group plugins
 */
require_once 'uncmap.inc.php';

class mapping_plugin_uncmap_test extends uncmapDokuWikiTest {

    protected $pluginsEnabled = array('uncmap');

    function setUp(){
        parent::setUp();
        TestUtils::rcopy(TMP_DIR, dirname(__FILE__).'/../conf/mapping.php');
        TestUtils::rdelete(dirname(__FILE__).'/../conf/mapping.php');
        touch(dirname(__FILE__).'/../conf/mapping.php');
        TestUtils::fappend(dirname(__FILE__).'/../conf/mapping.php','z           \\\\server1\\documents'.PHP_EOL);
    }

    function tearDown(){
        parent::tearDown();
        if(file_exists(TMP_DIR.'/mapping.php')) {
            TestUtils::rdelete(dirname(__FILE__) . '/../conf/mapping.php');
            TestUtils::rcopy(dirname(__FILE__).'/../conf', TMP_DIR.'/mapping.php');
        }
    }


    function test_parser_mapping() {
        $parser_response = p_get_instructions('Testlink: [[z:/path/to/file]]');
        $parser_response = $this->flatten_array($parser_response);
        $uncmap_pos = array_search("uncmap",$parser_response,true);
        $this->assertTrue($uncmap_pos !== false,'uncmap should be invoked');
        $link_pos = array_search("\\\\server1\\documents\\path\\to\\file",$parser_response,true);
        $this->assertTrue($link_pos !== false,'the link is not mapped correctly');
    }

    function test_parser_mapping_with_title() {
        $parser_response = p_get_instructions('Testlink: [[z:/path/to/file|some title]]');
        $parser_response = $this->flatten_array($parser_response);
        $uncmap_pos = array_search("uncmap",$parser_response,true);
        $this->assertTrue($uncmap_pos !== false,'uncmap should be invoked');
        $link_pos = array_search("\\\\server1\\documents\\path\\to\\file",$parser_response,true);
        $this->assertTrue($link_pos !== false,'the link is not mapped correctly');
        $this->assertTrue($parser_response[$link_pos-1] == 'some title','title not recognized correctly');
    }

    function test_parser_no_mapping() {
        $parser_response = p_get_instructions('Testlink: [[y:/path/to/file]]');
        $parser_response = $this->flatten_array($parser_response);
        $uncmap_pos = array_search("uncmap",$parser_response,true);
        $this->assertTrue($uncmap_pos === false,'uncmap should not be invoked');
    }

    function test_parser_colon() {
        $parser_response = p_get_instructions('Testlink: [[:facts:figures|Foo]]');
        $parser_response = $this->flatten_array($parser_response);
        $uncmap_pos = array_search("uncmap",$parser_response,true);
        $this->assertTrue($uncmap_pos === false,'A link beginning with a colon should not be handled at all by this plugin.');
    }


    function test_output_title() {
        global $ID, $conf;
        $ID = 'wiki:start';
        $request = new TestRequest();
        $input = array(
            'id' => 'wiki:start'
        );
        saveWikiText('wiki:start', 'Testlink: [[z:/path/to/file|some title]]', 'Test initialization');
        $response = $request->post($input);
        $this->assertTrue(
            strpos($response->getContent(), 'Testlink') !== false,
            'This tests the test and should always succeed.'
        );
        $this->assertTrue(
            strpos($response->getContent(), '>some title</a>') !== false
        );

        $this->assertTrue(
            strpos($response->getContent(), 'href="file://///server1/documents/path/to/file"') !== false
        );
    }

}
