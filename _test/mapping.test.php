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
        TestUtils::fappend(dirname(__FILE__).'/../conf/mapping.php','x   \\\\server2\\shareTest   '.TMP_DIR.PHP_EOL);
        TestUtils::fappend(dirname(__FILE__).'/../conf/mapping.php','k           \\\\server3\\documentsTest'.PHP_EOL);
        mkdir(TMP_DIR.'/shareTest');
        touch(TMP_DIR.'/shareTest/testfile.tmp');
        mkdir(TMP_DIR.'/documentsTest');
        touch(TMP_DIR.'/documentsTest/testdefaultfile.tmp');
    }

    function tearDown(){
        parent::tearDown();
        if(file_exists(TMP_DIR.'/mapping.php')) {
            TestUtils::rdelete(dirname(__FILE__) . '/../conf/mapping.php');
            TestUtils::rcopy(dirname(__FILE__).'/../conf', TMP_DIR.'/mapping.php');
        }
        TestUtils::rdelete(TMP_DIR.'/shareTest');
        TestUtils::rdelete(TMP_DIR.'/documentsTest');
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
        global $ID;
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
            strpos($response->getContent(), '>some title</a>') !== false,
            'The title is incorrect.'
        );
        $this->assertTrue(
            strpos($response->getContent(), 'href="file:///server1/documents/path/to/file"') !== false,
            'The url is incorrect.'
        );
        $this->assertTrue(
            strpos($response->getContent(), 'class="windows"') !== false,
            'The class for a link without local fileserver is incorrect.'
        );
    }

    function test_output_file_exists() {
        global $ID;
        $ID = 'wiki:start';
        $request = new TestRequest();
        $input = array(
            'id' => 'wiki:start'
        );
        saveWikiText('wiki:start', 'Testlink: [[x:/testfile.tmp|existing file]]', 'Test initialization');
        $response = $request->post($input);
        $this->assertTrue(
            strpos($response->getContent(), 'Testlink') !== false,
            'This tests the test and should always succeed.'
        );
        $this->assertTrue(
            strpos($response->getContent(), 'class="wikilink1"') !== false,
            'The class for an existing link is incorrect.'
        );
        $this->assertTrue(
            strpos($response->getContent(), 'href="file:///server2/shareTest/testfile.tmp"') !== false,
            'The url is incorrect.'
        );
    }

    function test_output_file_not_exists() {
        global $ID;
        $ID = 'wiki:start';
        $request = new TestRequest();
        $input = array(
            'id' => 'wiki:start'
        );
        saveWikiText('wiki:start', 'Testlink: [[x:/notestfile.tmp|not existing file]]', 'Test initialization');
        $response = $request->post($input);
        $this->assertTrue(
            strpos($response->getContent(), 'Testlink') !== false,
            'This tests the test and should always succeed.'
        );
        $this->assertTrue(
            strpos($response->getContent(), 'class="wikilink2"') !== false,
            'The class for an non-existing link is incorrect.'
        );
        $this->assertTrue(
            strpos($response->getContent(), 'href="file:///server2/shareTest/notestfile.tmp"') !== false,
            'The url is incorrect.'
        );
    }

    function test_output_file_exists_default() {
        global $ID, $conf;

        $conf['plugin']['uncmap']['fileserver'] = TMP_DIR;
        $ID = 'wiki:start';
        $request = new TestRequest();
        $input = array(
            'id' => 'wiki:start'
        );
        saveWikiText('wiki:start', 'Testlink: [[k:/testdefaultfile.tmp|file exists at default fs]]', 'Test init');
        $response = $request->post($input);
        $this->assertTrue(
            strpos($response->getContent(), 'Testlink') !== false,
            'This tests the test and should always succeed.'
        );
        $this->assertTrue(
            strpos($response->getContent(), 'href="file:///server3/documentsTest/testdefaultfile.tmp"') !== false,
            'The url is incorrect.'
        );
        $this->assertTrue(
            strpos($response->getContent(), 'class="wikilink1"') !== false,
            'The class for a link that exists at the default fileserver is incorrect.'
        );
    }

}
